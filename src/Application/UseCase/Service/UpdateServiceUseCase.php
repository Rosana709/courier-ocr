<?php

declare(strict_types=1);

namespace App\Application\UseCase\Service;

use App\Application\DTO\UpdateServiceDTO;
use App\Domain\Entity\Service;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\ServiceRepositoryInterface;

class UpdateServiceUseCase
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository
    ) {
    }

    public function execute(UpdateServiceDTO $dto): Service
    {
        $service = $this->serviceRepository->findById($dto->id);

        if ($service === null) {
            throw new EntityNotFoundException(
                'Service non trouvé avec l\'ID: ' . $dto->id
            );
        }

        if ($dto->nom !== null) {
            $service->setNom($dto->nom);
        }

        if ($dto->localite !== null) {
            $service->setLocalite($dto->localite);
        }

        if ($dto->mail !== null) {
            $service->setMail($dto->mail);
        }

        if ($dto->adresse !== null) {
            $service->setAdresse($dto->adresse);
        }

        if ($dto->sigle !== null) {
            $service->setSigle($dto->sigle);
        }

        $this->serviceRepository->save($service);

        return $service;
    }
}
