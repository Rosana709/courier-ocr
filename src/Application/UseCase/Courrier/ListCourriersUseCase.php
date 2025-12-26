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
        return $this->filterNonArchived($this->courrierRepository->findAll());
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

        return $this->filterNonArchived($this->courrierRepository->findByServiceImplique($service));
    }

    /**
     * Liste avec filtres
     */
    public function executeWithFilters(array $filters): array
    {
        return $this->filterNonArchived($this->courrierRepository->findByFilters($filters));
    }

    /**
     * Liste les courriers récents
     */
    public function executeRecent(int $limit = 10): array
    {
        return $this->filterNonArchived($this->courrierRepository->findRecent($limit));
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

        return $this->filterNonArchived($this->courrierRepository->findRecentByService($service, $limit));
    }

    /**
     * Liste les courriers urgents
     */
    public function executeUrgents(): array
    {
        return $this->filterNonArchived($this->courrierRepository->findUrgents());
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

        return $this->filterNonArchived($this->courrierRepository->findUrgentsByService($service));
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

        // Filtrer : un courrier entrant interne n'apparaît qu'après accusé de réception
        $uniqueCourriers = array_values(array_filter($uniqueCourriers, function($courrier) {
            if ($courrier->getTypeExpediteur() === Courrier::ACTEUR_SERVICE
                && $courrier->getTypeDestinataire() === Courrier::ACTEUR_SERVICE) {
                return in_array($courrier->getStatut(), [
                    Courrier::STATUT_ACCUSE_RECEPTION_RECU,
                    Courrier::STATUT_RECU_CONFIRME
                ]);
            }
            return true;
        }));

        $uniqueCourriers = $this->filterNonArchived($uniqueCourriers);

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

        return $this->filterNonArchived($this->courrierRepository->findByServiceExpediteur($service));
    }

    /**
     * Liste des courriers archivés
     */
    public function executeArchivedAll(): array
    {
        return $this->filterArchived($this->courrierRepository->findAll());
    }

    public function executeArchivedByService(string $serviceId): array
    {
        $service = $this->serviceRepository->findById($serviceId);

        if (!$service) {
            throw new \InvalidArgumentException("Service non trouvé");
        }

        return $this->filterArchived($this->courrierRepository->findByServiceImplique($service));
    }

    private function filterNonArchived(array $courriers): array
    {
        return array_values(array_filter($courriers, fn($c) => $c->getStatut() !== Courrier::STATUT_ARCHIVE));
    }

    private function filterArchived(array $courriers): array
    {
        $filtered = array_values(array_filter($courriers, fn($c) => $c->getStatut() === Courrier::STATUT_ARCHIVE));
        usort($filtered, fn($a, $b) => $b->getDateEnregistrement() <=> $a->getDateEnregistrement());
        return $filtered;
    }
}
