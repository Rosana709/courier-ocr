<?php

declare(strict_types=1);

namespace App\Application\UseCase\Utilisateur;

use App\Application\DTO\UpdateUtilisateurDTO;
use App\Domain\Entity\Utilisateur;
use App\Domain\Entity\HistoriqueAction;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

class UpdateUtilisateurUseCase
{
    public function __construct(
        private readonly \App\Domain\Repository\UtilisateurRepositoryInterface $utilisateurRepository,
        private readonly \App\Domain\Repository\ServiceRepositoryInterface $serviceRepository,
        private readonly \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher,
        private readonly \App\Infrastructure\Service\MailService $mailService,
        private readonly \App\Domain\Repository\HistoriqueActionRepositoryInterface $historiqueActionRepository,
        private readonly \Psr\Log\LoggerInterface $logger
    ) {
    }

    public function execute(UpdateUtilisateurDTO $dto, ?string $performingUserId = null): Utilisateur
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
                
                if ($performingUserId) {
                    $this->logAction($utilisateur, HistoriqueAction::TYPE_UTILISATEUR_MODIFICATION, sprintf('Changement de service : %s', $service->getNom()), $performingUserId);
                }

                // Envoyer l'email d'affectation
                try {
                    $this->mailService->sendServiceAssignmentEmail($utilisateur->getEmail(), $service->getNom());
                } catch (\Exception $e) {
                    // Log error but don't block
                }
            }
        }

        // Mettre à jour le statut si fourni
        if ($dto->estActif !== null && $dto->estActif !== $utilisateur->estActif()) {
            if ($dto->estActif) {
                $utilisateur->activer();
            } else {
                $utilisateur->desactiver();
            }

            if ($performingUserId) {
                $this->logAction(
                    $utilisateur, 
                    HistoriqueAction::TYPE_UTILISATEUR_TOGGLE, 
                    sprintf('%s par mise à jour du profil', $dto->estActif ? 'Activation' : 'Désactivation'), 
                    performingUserId: $performingUserId
                );
            }

            // Envoyer l'email de changement de statut
            try {
                $this->mailService->sendStatusChangeEmail($utilisateur->getEmail(), $utilisateur->estActif());
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de l\'envoi de l\'email de changement de statut (update) : ' . $e->getMessage(), [
                    'email' => $utilisateur->getEmail(),
                    'status' => $utilisateur->estActif() ? 'actif' : 'inactif',
                    'exception' => $e
                ]);
            }
        }

        $this->utilisateurRepository->save($utilisateur);

        return $utilisateur;
    }

    private function logAction(Utilisateur $utilisateur, string $type, string $description, string $performingUserId): void
    {
        try {
            $performingUser = $this->utilisateurRepository->findById($performingUserId);
            if ($performingUser) {
                $historiqueAction = new \App\Domain\Entity\HistoriqueAction(
                    courrier: null,
                    typeAction: $type,
                    description: sprintf('%s pour %s', $description, $utilisateur->getEmail()),
                    effectuePar: $performingUser,
                    nouvelleValeur: $utilisateur->estActif() ? 'ACTIF' : 'INACTIF'
                );
                $this->historiqueActionRepository->save($historiqueAction);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'historisation de la mise à jour utilisateur : ' . $e->getMessage());
        }
    }
}
