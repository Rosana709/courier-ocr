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
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly \App\Domain\Repository\HistoriqueActionRepositoryInterface $historiqueActionRepository,
        private readonly \App\Domain\Repository\UtilisateurRepositoryInterface $utilisateurRepository
    ) {
    }

    public function execute(UpdateServiceDTO $dto, ?string $performingUserId = null): Service
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

        if ($performingUserId) {
            try {
                $performingUser = $this->utilisateurRepository->findById($performingUserId);
                if ($performingUser) {
                    $historiqueAction = new \App\Domain\Entity\HistoriqueAction(
                        courrier: null,
                        typeAction: \App\Domain\Entity\HistoriqueAction::TYPE_SERVICE_MODIFICATION,
                        description: sprintf('Mise à jour du service %s (%s)', $service->getNom(), $service->getId()),
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
