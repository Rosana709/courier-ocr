<?php

declare(strict_types=1);

namespace App\Application\UseCase\Utilisateur;

use App\Application\DTO\UpdateProfilDTO;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UpdateProfilUseCase
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function execute(Utilisateur $utilisateur, UpdateProfilDTO $dto): Utilisateur
    {
        // Mise à jour de l'email si fourni
        if ($dto->email !== null && $dto->email !== '' && $dto->email !== $utilisateur->getEmail()) {
            // Vérifier que l'email n'est pas déjà utilisé
            $existingUser = $this->utilisateurRepository->findByEmail($dto->email);
            if ($existingUser !== null && $existingUser->getId() !== $utilisateur->getId()) {
                throw new \DomainException('Cet email est déjà utilisé par un autre utilisateur');
            }
            $utilisateur->updateEmail($dto->email);
        }

        // Mise à jour du mot de passe si fourni
        if ($dto->newPassword !== null && $dto->newPassword !== '') {
            // Vérifier que l'ancien mot de passe est correct
            if ($dto->currentPassword === null || $dto->currentPassword === '') {
                throw new \DomainException('Vous devez fournir votre mot de passe actuel');
            }

            if (!$this->passwordHasher->isPasswordValid($utilisateur, $dto->currentPassword)) {
                throw new \DomainException('Le mot de passe actuel est incorrect');
            }

            // Vérifier que les mots de passe correspondent
            if ($dto->newPassword !== $dto->confirmPassword) {
                throw new \DomainException('Les mots de passe ne correspondent pas');
            }

            // Vérifier la longueur minimale
            if (strlen($dto->newPassword) < 6) {
                throw new \DomainException('Le mot de passe doit contenir au moins 6 caractères');
            }

            $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $dto->newPassword);
            $utilisateur->updatePassword($hashedPassword);
        }

        $this->utilisateurRepository->save($utilisateur);

        return $utilisateur;
    }
}
