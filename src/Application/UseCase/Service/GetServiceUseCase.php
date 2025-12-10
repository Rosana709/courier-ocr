<?php

declare(strict_types=1);

namespace App\Application\UseCase\Service;

use App\Domain\Entity\Service;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\ServiceRepositoryInterface;

class GetServiceUseCase
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository
    ) {
    }

    public function execute(string $serviceId): Service
    {
        $service = $this->serviceRepository->findById($serviceId);

        if (!$service) {
            throw new EntityNotFoundException('Service non trouvé: ' . $serviceId);
        }

        return $service;
    }

    public function getAllServices(): array
    {
        return $this->serviceRepository->findAll();
    }

    public function getActiveServices(): array
    {
        return $this->serviceRepository->findActiveServices();
    }
}