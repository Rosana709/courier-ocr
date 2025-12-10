<?php

declare(strict_types=1);

namespace App\Application\UseCase\PieceJointe;

use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\CourrierRepositoryInterface;
use App\Domain\Repository\PieceJointeRepositoryInterface;

class ListPiecesJointesUseCase
{
    public function __construct(
        private readonly PieceJointeRepositoryInterface $pieceJointeRepository,
        private readonly CourrierRepositoryInterface $courrierRepository
    ) {
    }

    public function execute(string $courrierId): array
    {
        $courrier = $this->courrierRepository->findById($courrierId);

        if (!$courrier) {
            throw new EntityNotFoundException("Courrier non trouvé");
        }

        return $this->pieceJointeRepository->findByCourrier($courrier);
    }
}
