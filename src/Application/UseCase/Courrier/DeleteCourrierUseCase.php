<?php

declare(strict_types=1);

namespace App\Application\UseCase\Courrier;

use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\CourrierRepositoryInterface;

class DeleteCourrierUseCase
{
    public function __construct(
        private readonly CourrierRepositoryInterface $courrierRepository
    ) {
    }

    public function execute(string $courrierId): void
    {
        $courrier = $this->courrierRepository->findById($courrierId);

        if (!$courrier) {
            throw new EntityNotFoundException("Courrier non trouvé");
        }

        $this->courrierRepository->delete($courrier);
    }
}
