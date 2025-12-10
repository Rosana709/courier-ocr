<?php

declare(strict_types=1);

namespace App\Application\UseCase\Utilisateur;

use App\Application\DTO\CreateUtilisateurDTO;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUtilisateurUseCase
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurRepository,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function execute(CreateUtilisateurDTO $dto): Utilisateur
    {
        // Vérifier que l'email n'existe pas déjà
        if ($this->utilisateurRepository->existsByEmail($dto->email)) {
            throw new \DomainException('Un utilisateur avec cet email existe déjà');
        }

        // Créer l'utilisateur selon son rôle
        if ($dto->role === Utilisateur::ROLE_ADMIN) {
            $utilisateur = Utilisateur::creerAdmin($dto->email, 'temp');
        } elseif ($dto->role === Utilisateur::ROLE_SERVICE) {
            if ($dto->serviceId === null) {
                throw new \InvalidArgumentException('Un service doit être spécifié pour un utilisateur de service');
            }

            $service = $this->serviceRepository->findById($dto->serviceId);
            if ($service === null) {
                throw new \DomainException('Le service spécifié n\'existe pas');
            }

            // Vérifier qu'il n'y a pas déjà un utilisateur pour ce service
            if ($this->utilisateurRepository->findByService($service) !== null) {
                throw new \DomainException('Ce service a déjà un utilisateur associé');
            }

            $utilisateur = Utilisateur::creerUtilisateurService($dto->email, 'temp', $service);
        } else {
            throw new \InvalidArgumentException('Rôle invalide');
        }

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $dto->password);
        $utilisateur->updatePassword($hashedPassword);

        // Sauvegarder
        $this->utilisateurRepository->save($utilisateur);

        return $utilisateur;
    }
}
