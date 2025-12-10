<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\PersonneExterne;

interface PersonneExterneRepositoryInterface
{
    public function findById(string $id): ?PersonneExterne;

    public function findByNom(string $nom): array;

    public function findByType(string $type): array;

    public function findActives(): array;

    public function findAll(): array;

    public function save(PersonneExterne $personne): void;

    public function delete(PersonneExterne $personne): void;

    public function countAll(): int;
}
