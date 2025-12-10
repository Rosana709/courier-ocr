<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurRepository
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->utilisateurRepository->findByEmail($identifier);

        if ($user === null) {
            throw new UserNotFoundException(sprintf('Utilisateur avec l\'email "%s" non trouvé.', $identifier));
        }

        if (!$user->estActif()) {
            throw new UserNotFoundException('Ce compte utilisateur est désactivé.');
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances de "%s" ne sont pas supportées.', get_class($user)));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return Utilisateur::class === $class || is_subclass_of($class, Utilisateur::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances de "%s" ne sont pas supportées.', get_class($user)));
        }

        $user->updatePassword($newHashedPassword);
        $this->utilisateurRepository->save($user);
    }
}
