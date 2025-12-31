<?php

declare(strict_types=1);

namespace App\Application\UseCase\Utilisateur;

use App\Application\DTO\UpdateUtilisateurDTO;
use App\Domain\Entity\Utilisateur;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UpdateUtilisateurUseCase
{
    public function __construct(
        private readonly \App\Domain\Repository\UtilisateurRepositoryInterface $utilisateurRepository,
        private readonly \App\Domain\Repository\ServiceRepositoryInterface $serviceRepository,
        private readonly \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher,
        private readonly \App\Infrastructure\Service\MailService $mailService
    ) {
    }

    public function execute(UpdateUtilisateurDTO $dto): Utilisateur
    {
        $utilisateur = $this->utilisateurRepository->findById($dto->utilisateurId);

        if ($utilisateur === null) {
            throw new EntityNotFoundException('Utilisateur non trouvé');
        }

        // Mettre à jour l'email si fourni
        if ($dto->email !== null && $dto->email !== $utilisateur->getEmail()) {
            if ($this->utilisateurRepository->existsByEmail($dto->email)) {
                throw new \DomainException('Un utilisateur avec cet email existe déjà');
            }
            $utilisateur->updateEmail($dto->email);
        }

        // Mettre à jour le mot de passe si fourni
        if ($dto->password !== null) {
            $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $dto->password);
            $utilisateur->updatePassword($hashedPassword);
        }

        // Mettre à jour le service si fourni
        if ($dto->serviceId !== null) {
            $service = $this->serviceRepository->findById($dto->serviceId);
            if ($service === null) {
                throw new \DomainException('Le service spécifié n\'existe pas');
            }

            $currentServiceId = $utilisateur->getService()?->getId();
            if ($currentServiceId !== $service->getId()) {
                $utilisateur->updateService($service);
                
                // Envoyer l'email d'affectation
                try {
                    $this->mailService->sendServiceAssignmentEmail($utilisateur->getEmail(), $service->getNom());
                } catch (\Exception $e) {
                    // Log error but don't block
                }
            }
        }

        // Mettre à jour le statut si fourni
        if ($dto->estActif !== null) {
            if ($dto->estActif) {
                $utilisateur->activer();
            } else {
                $utilisateur->desactiver();
            }
        }

        $this->utilisateurRepository->save($utilisateur);

        return $utilisateur;
    }
}
