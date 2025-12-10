<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Courrier;
use App\Domain\Entity\PersonneExterne;
use App\Domain\Entity\Service;

interface CourrierRepositoryInterface
{
    // Méthodes de base
    public function findById(string $id): ?Courrier;

    public function findByNumeroReference(string $numero): ?Courrier;

    public function findAll(): array;

    public function save(Courrier $courrier): void;

    public function delete(Courrier $courrier): void;

    // Recherche par type
    public function findByType(string $type): array;

    public function findEntrants(): array;

    public function findSortants(): array;

    // Recherche par acteurs (expéditeur/destinataire/copie)
    public function findByServiceExpediteur(Service $service): array;

    public function findByServiceDestinataire(Service $service): array;

    public function findByServiceCopie(Service $service): array;

    /**
     * Trouve tous les courriers où le service est impliqué
     * (expéditeur, destinataire ou en copie)
     */
    public function findByServiceImplique(Service $service): array;

    public function findByPersonneExterne(PersonneExterne $personne): array;

    // Recherche par statut
    public function findByStatut(string $statut): array;

    public function findEnAttente(): array;

    public function findEnAttenteAccuseReception(): array;

    // Recherche avancée
    /**
     * Recherche avec filtres multiples
     * @param array $filters [
     *   'type' => string,
     *   'statut' => string,
     *   'priorite' => string,
     *   'serviceId' => string,
     *   'dateDebut' => DateTimeImmutable,
     *   'dateFin' => DateTimeImmutable,
     *   'search' => string (recherche dans objet/contenu)
     * ]
     */
    public function findByFilters(array $filters): array;

    public function findByDateRange(\DateTimeImmutable $debut, \DateTimeImmutable $fin): array;

    // Numérotation
    /**
     * Obtient le prochain numéro séquentiel pour un service et une année
     */
    public function getProchainNumero(Service $service, int $annee): int;

    public function existsByNumeroReference(string $numero): bool;

    // Statistiques
    public function countByService(Service $service): int;

    public function countByServiceAndAnnee(Service $service, int $annee): int;

    public function countByStatut(string $statut): int;

    public function countByType(string $type): int;

    public function countByPriorite(string $priorite): int;

    public function countAll(): int;

    // Récupération avec relations chargées
    public function findByIdWithRelations(string $id): ?Courrier;

    // Courriers récents
    public function findRecent(int $limit = 10): array;

    public function findRecentByService(Service $service, int $limit = 10): array;

    // Courriers urgents
    public function findUrgents(): array;

    public function findUrgentsByService(Service $service): array;
}
