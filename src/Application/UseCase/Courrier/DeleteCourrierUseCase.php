<?php

declare(strict_types=1);

namespace App\Application\UseCase\Courrier;

use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\CourrierRepositoryInterface;
use App\Domain\Repository\HistoriqueActionRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\Entity\HistoriqueAction;

class DeleteCourrierUseCase
{
    public function __construct(
        private readonly CourrierRepositoryInterface $courrierRepository,
        private readonly HistoriqueActionRepositoryInterface $historiqueActionRepository,
        private readonly UtilisateurRepositoryInterface $utilisateurRepository
    ) {
    }

    public function execute(string $courrierId, ?string $utilisateurId = null, ?string $justification = null): void
    {
        $courrier = $this->courrierRepository->findById($courrierId);

        if (!$courrier) {
            throw new EntityNotFoundException("Courrier non trouvé");
        }

        // Si on a un utilisateur, on logue l'action avant suppression
        if ($utilisateurId) {
            $utilisateur = $this->utilisateurRepository->findById($utilisateurId);
            if ($utilisateur) {
                $description = sprintf('Courrier (Réf: %s, Objet: "%s") supprimé définitivement par %s', 
                    $courrier->getNumeroReference(), 
                    $courrier->getObjet(), 
                    $utilisateur->getEmail()
                );
                
                if ($justification) {
                    $description .= ' | Justification obligatoire : ' . $justification;
                }

                $historiqueAction = new HistoriqueAction(
                    courrier: $courrier,
                    typeAction: HistoriqueAction::TYPE_SUPPRESSION,
                    description: $description,
                    effectuePar: $utilisateur
                );
                $this->historiqueActionRepository->save($historiqueAction);
            }
        }

        $this->courrierRepository->delete($courrier);
    }
}
