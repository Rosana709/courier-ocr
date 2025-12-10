<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Service;

interface ServiceRepositoryInterface
{
    public function save(Service $service): void;

    public function findById(string $id): ?Service;

    public function findAll(): array;

    public function findActiveServices(): array;

    public function findActifs(): array;

    public function delete(Service $service): void;
}