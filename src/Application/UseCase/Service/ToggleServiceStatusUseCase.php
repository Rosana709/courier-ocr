<?php

declare(strict_types=1);

namespace App\Application\UseCase\Service;

use App\Domain\Entity\Service;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\ServiceRepositoryInterface;

class ToggleServiceStatusUseCase
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly \App\Domain\Repository\HistoriqueActionRepositoryInterface $historiqueActionRepository,
        private readonly \App\Domain\Repository\UtilisateurRepositoryInterface $utilisateurRepository
    ) {
    }

    public function activate(string $id, ?string $performingUserId = null): Service
    {
        $service = $this->serviceRepository->findById($id);

        if ($service === null) {
            throw new EntityNotFoundException(
                'Service non trouvé avec l\'ID: ' . $id
            );
        }

        $service->activer();
        $this->serviceRepository->save($service);

        if ($performingUserId) {
            $this->logAction($service, 'Activation du service', $performingUserId);
        }

        return $service;
    }

    public function deactivate(string $id, ?string $performingUserId = null): Service
    {
        $service = $this->serviceRepository->findById($id);

        if ($service === null) {
            throw new EntityNotFoundException(
                'Service non trouvé avec l\'ID: ' . $id
            );
        }

        $service->desactiver();
        $this->serviceRepository->save($service);

        if ($performingUserId) {
            $this->logAction($service, 'Désactivation du service', $performingUserId);
        }

        return $service;
    }

    public function toggle(string $id, ?string $performingUserId = null): Service
    {
        $service = $this->serviceRepository->findById($id);

        if ($service === null) {
            throw new EntityNotFoundException(
                'Service non trouvé avec l\'ID: ' . $id
            );
        }

        if ($service->isEstActif()) {
            $service->desactiver();
        } else {
            $service->activer();
        }

        $this->serviceRepository->save($service);

        if ($performingUserId) {
            $this->logAction($service, $service->isEstActif() ? 'Activation du service' : 'Désactivation du service', $performingUserId);
        }

        return $service;
    }

    private function logAction(Service $service, string $description, string $performingUserId): void
    {
        try {
            $performingUser = $this->utilisateurRepository->findById($performingUserId);
            if ($performingUser) {
                $historiqueAction = new \App\Domain\Entity\HistoriqueAction(
                    courrier: null,
                    typeAction: \App\Domain\Entity\HistoriqueAction::TYPE_SERVICE_TOGGLE,
                    description: sprintf('%s %s (%s)', $description, $service->getNom(), $service->getId()),
                    effectuePar: $performingUser,
                    nouvelleValeur: $service->isEstActif() ? 'ACTIF' : 'INACTIF'
                );
                $this->historiqueActionRepository->save($historiqueAction);
            }
        } catch (\Exception $e) {
            // Log error safely
        }
    }
}
