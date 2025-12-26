<?php
declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Entity\Utilisateur;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * After login, force users with a temporary password to the change-password page.
 */
class LoginSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof Utilisateur) {
            return;
        }

        if ($user->mustChangePassword()) {
            $event->setResponse(
                new RedirectResponse($this->urlGenerator->generate('app_force_password_change'))
            );
        }
    }
}
