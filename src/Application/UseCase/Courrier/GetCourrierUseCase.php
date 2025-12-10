<?php

declare(strict_types=1);

namespace App\Application\UseCase\Courrier;

use App\Domain\Entity\Courrier;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\CourrierRepositoryInterface;

class GetCourrierUseCase
{
    public function __construct(
        private readonly CourrierRepositoryInterface $courrierRepository
    ) {
    }

    public function execute(string $courrierId): Courrier
    {
        $courrier = $this->courrierRepository->findByIdWithRelations($courrierId);

        if (!$courrier) {
            throw new EntityNotFoundException("Courrier non trouvé");
        }

        return $courrier;
    }

    public function findByNumeroReference(string $numeroReference): Courrier
    {
        $courrier = $this->courrierRepository->findByNumeroReference($numeroReference);

        if (!$courrier) {
            throw new EntityNotFoundException("Courrier non trouvé");
        }

        return $courrier;
    }
}
