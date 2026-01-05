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

        // Check if session is already started to avoid circular dependency
        $session = $request->getSession();
        if (!$session->isStarted()) {
            // Don't try to start the session here, it will be started later
            return;
        }

        $sessionId = $session->getId();
        if (!$sessionId) {
            return;
        }

        // Wrap database operations in try-catch to prevent blocking the request
        try {
            // Rafraîchir ou créer une trace pour cette session utilisateur
            $this->sessionTraceRepository->upsert($user, $sessionId);

            // Nettoyage léger des sessions trop anciennes (2 heures)
            // Only do cleanup occasionally (1 in 100 requests) to reduce DB load
            if (random_int(1, 100) === 1) {
                $this->sessionTraceRepository->removeOlderThan(new \DateTimeImmutable('-2 hours'));
            }
        } catch (\Exception $e) {
            // Log error but don't block the request
            // You can add logging here if needed
        }
    }
}
