<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Service;
use App\Domain\Entity\Utilisateur;

interface UtilisateurRepositoryInterface
{
    public function findById(string $id): ?Utilisateur;

    public function findByEmail(string $email): ?Utilisateur;

    public function findAll(): array;

    public function findByRole(string $role): array;

    public function findByService(Service $service): ?Utilisateur;

    public function findActifs(): array;

    public function save(Utilisateur $utilisateur): void;

    public function delete(Utilisateur $utilisateur): void;

    public function existsByEmail(string $email): bool;
}
