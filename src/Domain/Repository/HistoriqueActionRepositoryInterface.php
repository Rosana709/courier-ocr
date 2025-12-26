<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Courrier;
use App\Domain\Entity\HistoriqueAction;

interface HistoriqueActionRepositoryInterface
{
    public function findByCourrier(Courrier $courrier): array;

    public function findLast(int $limit = 5): array;

    public function save(HistoriqueAction $action): void;
}
