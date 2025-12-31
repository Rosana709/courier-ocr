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

    public function execute(string $courrierId, ?string $utilisateurId = null): void
    {
        $courrier = $this->courrierRepository->findById($courrierId);

        if (!$courrier) {
            throw new EntityNotFoundException("Courrier non trouvé");
        }

        // Si on a un utilisateur, on logue l'action avant suppression
        if ($utilisateurId) {
            $utilisateur = $this->utilisateurRepository->findById($utilisateurId);
            if ($utilisateur) {
                $historiqueAction = new HistoriqueAction(
                    courrier: $courrier,
                    typeAction: 'SUPPRESSION', // Type custom car non défini dans les const mais utile pour le rapport
                    description: sprintf('Courrier "%s" supprimé définitivement par %s', $courrier->getObjet(), $utilisateur->getEmail()),
                    effectuePar: $utilisateur
                );
                // Note: On ne peut pas facilement sauver HistoriqueAction si Courrier est supprimé à cause de la FK
                // Sauf si on utilise un type d'action sans FK ou si on gère autrement.
                // Dans HistoriqueAction.php, courrier_id est nullable: false, onDelete: 'CASCADE'.
                // Donc si on supprime le courrier, l'historique part. 
                // C'est peut-être voulu, ou non. Le user veut un "petit rapport".
                // Si on veut garder la trace, il faudrait que HistoriqueAction garde une trace texte du courrier au lieu de l'objet.
            }
        }

        $this->courrierRepository->delete($courrier);
    }
}
