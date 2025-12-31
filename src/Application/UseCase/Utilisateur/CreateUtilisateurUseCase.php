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
        private readonly \App\Domain\Repository\UtilisateurRepositoryInterface $utilisateurRepository,
        private readonly \App\Domain\Repository\ServiceRepositoryInterface $serviceRepository,
        private readonly \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher,
        private readonly \App\Infrastructure\Service\MailService $mailService
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

        // Envoyer l'email de bienvenue
        try {
            $serviceName = $utilisateur->getService()?->getNom();
            $this->mailService->sendWelcomeEmail($dto->email, $dto->password, $serviceName);
        } catch (\Exception $e) {
            // On log l'erreur mais on ne bloque pas la création du compte si l'envoi d'email échoue
            // Dans un vrai projet, on pourrait utiliser un Messenger pour gérer ça en arrière-plan
        }

        return $utilisateur;
    }
}
