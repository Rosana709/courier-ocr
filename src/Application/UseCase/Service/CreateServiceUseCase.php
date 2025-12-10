<?php

declare(strict_types=1);

namespace App\Application\UseCase\Service;

use App\Application\DTO\CreateServiceDTO;
use App\Domain\Entity\Service;
use App\Domain\Exception\InvalidServiceDataException;
use App\Domain\Repository\ServiceRepositoryInterface;

class CreateServiceUseCase
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository
    ) {
    }

    public function execute(CreateServiceDTO $dto): Service
    {
        if ($this->serviceRepository->findById($dto->id) !== null) {
            throw new InvalidServiceDataException(
                'Un service avec cet ID existe déjà: ' . $dto->id
            );
        }

        $service = new Service($dto->id, $dto->nom, $dto->estActif);
        $service->setLocalite($dto->localite);
        $service->setMail($dto->mail);
        $service->setAdresse($dto->adresse);
        $service->setSigle($dto->sigle);

        $this->serviceRepository->save($service);

        return $service;
    }
}