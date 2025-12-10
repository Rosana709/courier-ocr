<?php

declare(strict_types=1);

namespace App\Application\UseCase\Service;

use App\Domain\Entity\Service;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\ServiceRepositoryInterface;

class ToggleServiceStatusUseCase
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository
    ) {
    }

    public function activate(string $id): Service
    {
        $service = $this->serviceRepository->findById($id);

        if ($service === null) {
            throw new EntityNotFoundException(
                'Service non trouvé avec l\'ID: ' . $id
            );
        }

        $service->activer();
        $this->serviceRepository->save($service);

        return $service;
    }

    public function deactivate(string $id): Service
    {
        $service = $this->serviceRepository->findById($id);

        if ($service === null) {
            throw new EntityNotFoundException(
                'Service non trouvé avec l\'ID: ' . $id
            );
        }

        $service->desactiver();
        $this->serviceRepository->save($service);

        return $service;
    }

    public function toggle(string $id): Service
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

        return $service;
    }
}
