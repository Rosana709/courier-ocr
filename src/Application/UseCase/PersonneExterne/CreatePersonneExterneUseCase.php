<?php

declare(strict_types=1);

namespace App\Application\UseCase\PersonneExterne;

use App\Application\DTO\CreatePersonneExterneDTO;
use App\Domain\Entity\PersonneExterne;
use App\Domain\Repository\PersonneExterneRepositoryInterface;

class CreatePersonneExterneUseCase
{
    public function __construct(
        private readonly PersonneExterneRepositoryInterface $personneExterneRepository
    ) {
    }

    public function execute(CreatePersonneExterneDTO $dto): PersonneExterne
    {
        $personneExterne = new PersonneExterne(
            nomOuRaisonSociale: $dto->nomOuRaisonSociale,
            type: $dto->type,
            adressePostale: $dto->adressePostale,
            telephone: $dto->telephone,
            email: $dto->email
        );

        $this->personneExterneRepository->save($personneExterne);

        return $personneExterne;
    }
}
