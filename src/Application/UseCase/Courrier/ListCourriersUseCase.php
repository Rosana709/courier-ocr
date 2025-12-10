<?php

declare(strict_types=1);

namespace App\Application\UseCase\Courrier;

use App\Domain\Entity\Courrier;
use App\Domain\Entity\Service;
use App\Domain\Repository\CourrierRepositoryInterface;
use App\Domain\Repository\ServiceRepositoryInterface;

class ListCourriersUseCase
{
    public function __construct(
        private readonly CourrierRepositoryInterface $courrierRepository,
        private readonly ServiceRepositoryInterface $serviceRepository
    ) {
    }

    /**
     * Liste tous les courriers (admin uniquement)
     */
    public function executeAll(): array
    {
        return $this->courrierRepository->findAll();
    }

    /**
     * Liste les courriers d'un service spécifique
     */
    public function executeByService(string $serviceId): array
    {
        $service = $this->serviceRepository->findById($serviceId);

        if (!$service) {
            throw new \InvalidArgumentException("Service non trouvé");
        }

        return $this->courrierRepository->findByServiceImplique($service);
    }

    /**
     * Liste avec filtres
     */
    public function executeWithFilters(array $filters): array
    {
        return $this->courrierRepository->findByFilters($filters);
    }

    /**
     * Liste les courriers récents
     */
    public function executeRecent(int $limit = 10): array
    {
        return $this->courrierRepository->findRecent($limit);
    }

    /**
     * Liste les courriers récents d'un service
     */
    public function executeRecentByService(string $serviceId, int $limit = 10): array
    {
        $service = $this->serviceRepository->findById($serviceId);

        if (!$service) {
            throw new \InvalidArgumentException("Service non trouvé");
        }

        return $this->courrierRepository->findRecentByService($service, $limit);
    }

    /**
     * Liste les courriers urgents
     */
    public function executeUrgents(): array
    {
        return $this->courrierRepository->findUrgents();
    }

    /**
     * Liste les courriers urgents d'un service
     */
    public function executeUrgentsByService(string $serviceId): array
    {
        $service = $this->serviceRepository->findById($serviceId);

        if (!$service) {
            throw new \InvalidArgumentException("Service non trouvé");
        }

        return $this->courrierRepository->findUrgentsByService($service);
    }

    /**
     * Liste les courriers entrants d'un service (destinataire + copie)
     */
    public function executeEntrantsByService(string $serviceId): array
    {
        $service = $this->serviceRepository->findById($serviceId);

        if (!$service) {
            throw new \InvalidArgumentException("Service non trouvé");
        }

        // Destinataire principal
        $courriersDestinataire = $this->courrierRepository->findByServiceDestinataire($service);

        // En copie
        $courriersCopie = $this->courrierRepository->findByServiceCopie($service);

        // Fusionner et dédupliquer
        $allCourriers = array_merge($courriersDestinataire, $courriersCopie);
        $uniqueCourriers = [];
        $seenIds = [];

        foreach ($allCourriers as $courrier) {
            if (!in_array($courrier->getId(), $seenIds)) {
                $uniqueCourriers[] = $courrier;
                $seenIds[] = $courrier->getId();
            }
        }

        // Trier par date (plus récent en premier)
        usort($uniqueCourriers, function($a, $b) {
            return $b->getDateEnregistrement() <=> $a->getDateEnregistrement();
        });

        return $uniqueCourriers;
    }

    /**
     * Liste les courriers sortants d'un service (expéditeur)
     */
    public function executeSortantsByService(string $serviceId): array
    {
        $service = $this->serviceRepository->findById($serviceId);

        if (!$service) {
            throw new \InvalidArgumentException("Service non trouvé");
        }

        return $this->courrierRepository->findByServiceExpediteur($service);
    }
}
