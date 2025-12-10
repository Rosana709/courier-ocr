<?php

declare(strict_types=1);

namespace App\Application\UseCase\PersonneExterne;

use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\PersonneExterneRepositoryInterface;

class DeletePersonneExterneUseCase
{
    public function __construct(
        private readonly PersonneExterneRepositoryInterface $personneExterneRepository
    ) {
    }

    public function execute(string $id): void
    {
        $personneExterne = $this->personneExterneRepository->findById($id);

        if (!$personneExterne) {
            throw new EntityNotFoundException("Personne externe non trouvée");
        }

        $this->personneExterneRepository->delete($personneExterne);
    }
}
