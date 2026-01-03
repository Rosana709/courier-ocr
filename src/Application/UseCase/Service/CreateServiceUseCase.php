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
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly \App\Domain\Repository\HistoriqueActionRepositoryInterface $historiqueActionRepository,
        private readonly \App\Domain\Repository\UtilisateurRepositoryInterface $utilisateurRepository
    ) {
    }

    public function execute(CreateServiceDTO $dto, ?string $performingUserId = null): Service
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

        if ($performingUserId) {
            try {
                $performingUser = $this->utilisateurRepository->findById($performingUserId);
                if ($performingUser) {
                    $historiqueAction = new \App\Domain\Entity\HistoriqueAction(
                        courrier: null,
                        typeAction: \App\Domain\Entity\HistoriqueAction::TYPE_SERVICE_CREATION,
                        description: sprintf('Création du service %s (%s)', $service->getNom(), $service->getId()),
                        effectuePar: $performingUser,
                        nouvelleValeur: $service->getId()
                    );
                    $this->historiqueActionRepository->save($historiqueAction);
                }
            } catch (\Exception $e) {
                // Log error safely
            }
        }

        return $service;
    }
}