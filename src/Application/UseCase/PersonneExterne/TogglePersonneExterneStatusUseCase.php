<?php

declare(strict_types=1);

namespace App\Application\UseCase\PersonneExterne;

use App\Domain\Entity\PersonneExterne;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\PersonneExterneRepositoryInterface;

class TogglePersonneExterneStatusUseCase
{
    public function __construct(
        private readonly PersonneExterneRepositoryInterface $personneExterneRepository,
        private readonly \App\Domain\Repository\HistoriqueActionRepositoryInterface $historiqueActionRepository,
        private readonly \App\Domain\Repository\UtilisateurRepositoryInterface $utilisateurRepository
    ) {
    }

    public function execute(string $id, ?string $performingUserId = null): PersonneExterne
    {
        $personneExterne = $this->personneExterneRepository->findById($id);

        if (!$personneExterne) {
            throw new EntityNotFoundException("Personne externe non trouvée");
        }

        if ($personneExterne->estActif()) {
            $personneExterne->desactiver();
        } else {
            $personneExterne->activer();
        }

        $this->personneExterneRepository->save($personneExterne);

        if ($performingUserId) {
            try {
                $performingUser = $this->utilisateurRepository->findById($performingUserId);
                if ($performingUser) {
                    $historiqueAction = new \App\Domain\Entity\HistoriqueAction(
                        courrier: null,
                        typeAction: \App\Domain\Entity\HistoriqueAction::TYPE_PERSONNE_EXTERNE_TOGGLE,
                        description: sprintf('%s pour %s', $personneExterne->estActif() ? 'Activation' : 'Désactivation', $personneExterne->getNomOuRaisonSociale()),
                        effectuePar: $performingUser,
                        nouvelleValeur: $personneExterne->estActif() ? 'ACTIF' : 'INACTIF'
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
