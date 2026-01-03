<?php

declare(strict_types=1);

namespace App\Application\UseCase\PersonneExterne;

use App\Application\DTO\CreatePersonneExterneDTO;
use App\Domain\Entity\PersonneExterne;
use App\Domain\Repository\PersonneExterneRepositoryInterface;

class CreatePersonneExterneUseCase
{
    public function __construct(
        private readonly PersonneExterneRepositoryInterface $personneExterneRepository,
        private readonly \App\Domain\Repository\HistoriqueActionRepositoryInterface $historiqueActionRepository,
        private readonly \App\Domain\Repository\UtilisateurRepositoryInterface $utilisateurRepository
    ) {
    }

    public function execute(CreatePersonneExterneDTO $dto, ?string $performingUserId = null): PersonneExterne
    {
        $personneExterne = new PersonneExterne(
            nomOuRaisonSociale: $dto->nomOuRaisonSociale,
            type: $dto->type,
            adressePostale: $dto->adressePostale,
            telephone: $dto->telephone,
            email: $dto->email
        );

        $this->personneExterneRepository->save($personneExterne);

        if ($performingUserId) {
            try {
                $performingUser = $this->utilisateurRepository->findById($performingUserId);
                if ($performingUser) {
                    $historiqueAction = new \App\Domain\Entity\HistoriqueAction(
                        courrier: null,
                        typeAction: \App\Domain\Entity\HistoriqueAction::TYPE_PERSONNE_EXTERNE_CREATION,
                        description: sprintf('Création de la personne externe %s', $personneExterne->getNomOuRaisonSociale()),
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
