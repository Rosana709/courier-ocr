<?php

declare(strict_types=1);

namespace App\Application\UseCase\PersonneExterne;

use App\Domain\Entity\PersonneExterne;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\PersonneExterneRepositoryInterface;

class GetPersonneExterneUseCase
{
    public function __construct(
        private readonly PersonneExterneRepositoryInterface $personneExterneRepository
    ) {
    }

    public function execute(string $id): PersonneExterne
    {
        $personneExterne = $this->personneExterneRepository->findById($id);

        if (!$personneExterne) {
            throw new EntityNotFoundException("Personne externe non trouvée");
        }

        return $personneExterne;
    }

    public function getAll(): array
    {
        return $this->personneExterneRepository->findAll();
    }

    public function getActifs(): array
    {
        return $this->personneExterneRepository->findActifs();
    }
}
