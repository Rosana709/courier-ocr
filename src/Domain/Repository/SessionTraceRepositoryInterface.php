<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\SessionTrace;
use App\Domain\Entity\Utilisateur;

interface SessionTraceRepositoryInterface
{
    public function upsert(Utilisateur $utilisateur, string $sessionId): void;

    public function countActiveSince(\DateTimeImmutable $threshold): int;

    public function findActiveSince(\DateTimeImmutable $threshold): array;

    public function removeOlderThan(\DateTimeImmutable $threshold): int;
}
