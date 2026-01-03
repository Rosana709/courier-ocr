<?php

declare(strict_types=1);

namespace App\Application\UseCase\Utilisateur;

use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Infrastructure\Service\MailService;
use Psr\Log\LoggerInterface;

class ToggleUserStatusUseCase
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurRepository,
        private readonly \App\Domain\Repository\HistoriqueActionRepositoryInterface $historiqueActionRepository,
        private readonly MailService $mailService,
        private readonly LoggerInterface $logger
    ) {
    }

    public function activate(string $id, ?string $performingUserId = null): Utilisateur
    {
        $utilisateur = $this->utilisateurRepository->findById($id);

        if ($utilisateur === null) {
            throw new \DomainException('Utilisateur non trouvé');
        }

        $utilisateur->activer();
        $this->utilisateurRepository->save($utilisateur);
        
        if ($performingUserId) {
            $this->logAction($utilisateur, 'Activation du compte', $performingUserId);
        }

        $this->sendNotification($utilisateur);

        return $utilisateur;
    }

    public function deactivate(string $id, ?string $performingUserId = null): Utilisateur
    {
        $utilisateur = $this->utilisateurRepository->findById($id);

        if ($utilisateur === null) {
            throw new \DomainException('Utilisateur non trouvé');
        }

        $utilisateur->desactiver();
        $this->utilisateurRepository->save($utilisateur);

        if ($performingUserId) {
            $this->logAction($utilisateur, 'Désactivation du compte', $performingUserId);
        }

        $this->sendNotification($utilisateur);

        return $utilisateur;
    }

    public function toggle(string $id, ?string $performingUserId = null): Utilisateur
    {
        $utilisateur = $this->utilisateurRepository->findById($id);

        if ($utilisateur === null) {
            throw new \DomainException('Utilisateur non trouvé');
        }

        $description = $utilisateur->estActif() ? 'Désactivation du compte' : 'Activation du compte';

        if ($utilisateur->estActif()) {
            $utilisateur->desactiver();
        } else {
            $utilisateur->activer();
        }

        $this->utilisateurRepository->save($utilisateur);

        if ($performingUserId) {
            $this->logAction($utilisateur, $description, $performingUserId);
        }

        $this->sendNotification($utilisateur);

        return $utilisateur;
    }

    private function logAction(Utilisateur $utilisateur, string $description, string $performingUserId): void
    {
        try {
            $performingUser = $this->utilisateurRepository->findById($performingUserId);
            if ($performingUser) {
                $historiqueAction = new \App\Domain\Entity\HistoriqueAction(
                    courrier: null,
                    typeAction: \App\Domain\Entity\HistoriqueAction::TYPE_UTILISATEUR_TOGGLE,
                    description: sprintf('%s pour %s', $description, $utilisateur->getEmail()),
                    effectuePar: $performingUser,
                    nouvelleValeur: $utilisateur->estActif() ? 'ACTIF' : 'INACTIF'
                );
                $this->historiqueActionRepository->save($historiqueAction);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'historisation du changement de statut utilisateur : ' . $e->getMessage());
        }
    }

    private function sendNotification(Utilisateur $utilisateur): void
    {
        try {
            $this->mailService->sendStatusChangeEmail($utilisateur->getEmail(), $utilisateur->estActif());
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de changement de statut : ' . $e->getMessage(), [
                'email' => $utilisateur->getEmail(),
                'status' => $utilisateur->estActif() ? 'actif' : 'inactif',
                'exception' => $e
            ]);
        }
    }
}
