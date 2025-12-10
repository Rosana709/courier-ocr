<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Courrier;
use App\Domain\Entity\PieceJointe;

interface PieceJointeRepositoryInterface
{
    public function findById(string $id): ?PieceJointe;

    public function findByCourrier(Courrier $courrier): array;

    public function save(PieceJointe $piece): void;

    public function delete(PieceJointe $piece): void;

    public function countByCourrier(Courrier $courrier): int;
}
