<?php

declare(strict_types=1);

namespace App\Application\UseCase\PersonneExterne;

use App\Application\DTO\UpdatePersonneExterneDTO;
use App\Domain\Entity\PersonneExterne;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\PersonneExterneRepositoryInterface;

class UpdatePersonneExterneUseCase
{
    public function __construct(
        private readonly PersonneExterneRepositoryInterface $personneExterneRepository
    ) {
    }

    public function execute(string $id, UpdatePersonneExterneDTO $dto): PersonneExterne
    {
        $personneExterne = $this->personneExterneRepository->findById($id);

        if (!$personneExterne) {
            throw new EntityNotFoundException("Personne externe non trouvée");
        }

        if ($dto->nomOuRaisonSociale !== null) {
            $personneExterne->updateNomOuRaisonSociale($dto->nomOuRaisonSociale);
        }

        if ($dto->type !== null) {
            $personneExterne->updateType($dto->type);
        }

        if ($dto->adressePostale !== null) {
            $personneExterne->updateAdressePostale($dto->adressePostale);
        }

        if ($dto->telephone !== null) {
            $personneExterne->updateTelephone($dto->telephone);
        }

        if ($dto->email !== null) {
            $personneExterne->updateEmail($dto->email);
        }

        $this->personneExterneRepository->save($personneExterne);

        return $personneExterne;
    }
}
