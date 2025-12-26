<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSubscriber;

use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\SessionTraceRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;

class SessionActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly SessionTraceRepositoryInterface $sessionTraceRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        /** @var Utilisateur|null $user */
        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        $sessionId = $session->getId();
        if (!$sessionId) {
            return;
        }

        // Rafraîchir ou créer une trace pour cette session utilisateur
        $this->sessionTraceRepository->upsert($user, $sessionId);

        // Nettoyage léger des sessions trop anciennes (2 heures)
        $this->sessionTraceRepository->removeOlderThan(new \DateTimeImmutable('-2 hours'));
    }
}
