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
        private readonly PersonneExterneRepositoryInterface $personneExterneRepository,
        private readonly \App\Domain\Repository\HistoriqueActionRepositoryInterface $historiqueActionRepository,
        private readonly \App\Domain\Repository\UtilisateurRepositoryInterface $utilisateurRepository
    ) {
    }

    public function execute(string $id, UpdatePersonneExterneDTO $dto, ?string $performingUserId = null): PersonneExterne
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

        if ($performingUserId) {
            try {
                $performingUser = $this->utilisateurRepository->findById($performingUserId);
                if ($performingUser) {
                    $historiqueAction = new \App\Domain\Entity\HistoriqueAction(
                        courrier: null,
                        typeAction: \App\Domain\Entity\HistoriqueAction::TYPE_PERSONNE_EXTERNE_MODIFICATION,
                        description: sprintf('Mise à jour de la personne externe %s', $personneExterne->getNomOuRaisonSociale()),
                        effectuePar: $performingUser,
                        nouvelleValeur: $personneExterne->getId()
                    );
                    $this->historiqueActionRepository->save($historiqueAction);
                }
            } catch (\Exception $e) {
                // Log error safely
            }
        }

        return $personneExterne;
    }
}
