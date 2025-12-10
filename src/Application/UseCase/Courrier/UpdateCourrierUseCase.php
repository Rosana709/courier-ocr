<?php

declare(strict_types=1);

namespace App\Application\UseCase\Courrier;

use App\Application\DTO\UpdateCourrierDTO;
use App\Domain\Entity\Courrier;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\CourrierRepositoryInterface;
use App\Domain\Repository\ServiceRepositoryInterface;

class UpdateCourrierUseCase
{
    public function __construct(
        private readonly CourrierRepositoryInterface $courrierRepository,
        private readonly ServiceRepositoryInterface $serviceRepository
    ) {
    }

    public function execute(string $courrierId, UpdateCourrierDTO $dto): Courrier
    {
        $courrier = $this->courrierRepository->findById($courrierId);

        if (!$courrier) {
            throw new EntityNotFoundException("Courrier non trouvé");
        }

        if ($dto->objet !== null) {
            $courrier->updateObjet($dto->objet);
        }

        if ($dto->contenu !== null) {
            $courrier->updateContenu($dto->contenu);
        }

        if ($dto->priorite !== null) {
            $courrier->updatePriorite($dto->priorite);
        }

        if ($dto->statut !== null) {
            $courrier->updateStatut($dto->statut);
        }

        if ($dto->notes !== null) {
            $courrier->ajouterNotes($dto->notes);
        }

        if ($dto->destinatairesCopieIds !== null) {
            // Supprimer tous les destinataires en copie existants
            foreach ($courrier->getDestinatairesCopie() as $service) {
                $courrier->retirerDestinataireCopie($service);
            }

            // Ajouter les nouveaux destinataires en copie
            foreach ($dto->destinatairesCopieIds as $serviceId) {
                $service = $this->serviceRepository->findById($serviceId);
                if ($service) {
                    $courrier->ajouterDestinataireCopie($service);
                }
            }
        }

        $this->courrierRepository->save($courrier);

        return $courrier;
    }
}
