<?php

declare(strict_types=1);

namespace App\Application\UseCase\PieceJointe;

use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\PieceJointeRepositoryInterface;

class DeletePieceJointeUseCase
{
    public function __construct(
        private readonly PieceJointeRepositoryInterface $pieceJointeRepository
    ) {
    }

    public function execute(string $pieceJointeId): void
    {
        $pieceJointe = $this->pieceJointeRepository->findById($pieceJointeId);

        if (!$pieceJointe) {
            throw new EntityNotFoundException("Pièce jointe non trouvée");
        }

        $this->pieceJointeRepository->delete($pieceJointe);
    }
}
