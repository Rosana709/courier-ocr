<?php

declare(strict_types=1);

namespace App\Application\UseCase\Utilisateur;

use App\Application\DTO\CreateUtilisateurDTO;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

class CreateUtilisateurUseCase
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

    public function execute(CreateUtilisateurDTO $dto, string $performingUserId): Utilisateur
    {
        // Récupérer l'utilisateur effectuant l'action
        $performingUser = $this->utilisateurRepository->findById($performingUserId);
        if (!$performingUser) {
            throw new \DomainException('Utilisateur effectuant l\'action non trouvé');
        }

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

        // Enregistrer l'action dans l'historique
        try {
            $historiqueAction = new \App\Domain\Entity\HistoriqueAction(
                courrier: null,
                typeAction: \App\Domain\Entity\HistoriqueAction::TYPE_UTILISATEUR_CREATION,
                description: sprintf('Création de l\'utilisateur %s (%s)', $utilisateur->getEmail(), $utilisateur->getRoles()[0]),
                effectuePar: $performingUser,
                nouvelleValeur: $utilisateur->getEmail()
            );
            $this->historiqueActionRepository->save($historiqueAction);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'historisation de la création d\'utilisateur : ' . $e->getMessage());
        }

        // Envoyer l'email de bienvenue
        try {
            $serviceName = $utilisateur->getService()?->getNom();
            $this->mailService->sendWelcomeEmail($dto->email, $dto->password, $dto->role, $serviceName);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de bienvenue : ' . $e->getMessage(), [
                'email' => $dto->email,
                'exception' => $e
            ]);
        }

        return $utilisateur;
    }
}
