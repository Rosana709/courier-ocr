<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\AccuseReception;
use App\Domain\Entity\Courrier;

interface AccuseReceptionRepositoryInterface
{
    public function findByCourrier(Courrier $courrier): ?AccuseReception;

    public function save(AccuseReception $accuse): void;

    public function existsForCourrier(Courrier $courrier): bool;
}
