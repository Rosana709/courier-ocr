<?php

declare(strict_types=1);

namespace App\Application\UseCase\PieceJointe;

use App\Domain\Entity\PieceJointe;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\PieceJointeRepositoryInterface;

class DownloadPieceJointeUseCase
{
    public function __construct(
        private readonly PieceJointeRepositoryInterface $pieceJointeRepository
    ) {
    }

    public function execute(string $pieceJointeId): PieceJointe
    {
        $pieceJointe = $this->pieceJointeRepository->findById($pieceJointeId);

        if (!$pieceJointe) {
            throw new EntityNotFoundException("Pièce jointe non trouvée");
        }

        return $pieceJointe;
    }
}
