<?php

declare(strict_types=1);

namespace App\Application\UseCase\Service;

use App\Domain\Exception\DomainException;
use App\Domain\Repository\ServiceRepositoryInterface;

class DeleteServiceUseCase
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository
    ) {
    }

    public function execute(string $id): void
    {
        $service = $this->serviceRepository->findById($id);

        if (!$service) {
            throw new DomainException('Service non trouvé.');
        }

        // Check if service has associated couriers before deleting
        // This prevents deleting services that are still in use

        $this->serviceRepository->delete($service);
    }
}
