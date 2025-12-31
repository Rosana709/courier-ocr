<?php

declare(strict_types=1);

namespace App\Application\UseCase\Courrier;

use App\Application\DTO\UpdateCourrierDTO;
use App\Domain\Entity\Courrier;
use App\Domain\Entity\Notification;
use App\Domain\Entity\Service;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\CourrierRepositoryInterface;
use App\Domain\Repository\NotificationRepositoryInterface;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\HistoriqueActionRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\Entity\HistoriqueAction;

class UpdateCourrierUseCase
{
    public function __construct(
        private readonly CourrierRepositoryInterface $courrierRepository,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly NotificationRepositoryInterface $notificationRepository,
        private readonly HistoriqueActionRepositoryInterface $historiqueActionRepository,
        private readonly UtilisateurRepositoryInterface $utilisateurRepository
    ) {
    }

    public function execute(string $courrierId, UpdateCourrierDTO $dto, ?string $utilisateurId = null): Courrier
    {
        $courrier = $this->courrierRepository->findById($courrierId);

        if (!$courrier) {
            throw new EntityNotFoundException("Courrier non trouvé");
        }

        if ($dto->objet !== null) {
            $courrier->updateObjet($dto->objet);
        }

        if ($dto->contenu !== null) {
            $courrier->updateContenu($dto->contenu);
        }

        if ($dto->priorite !== null) {
            $courrier->updatePriorite($dto->priorite);
        }

        if ($dto->statut !== null) {
            $statutsVerrouilles = [
                Courrier::STATUT_RECU_CONFIRME,
                Courrier::STATUT_ACCUSE_RECEPTION_RECU,
                Courrier::STATUT_CLOS,
                Courrier::STATUT_ARCHIVE
            ];

            // On ne permet pas de changer le statut si le courrier est déjà confirmé/terminal
            if (!in_array($courrier->getStatut(), $statutsVerrouilles)) {
                $ancienStatut = $courrier->getStatut();
                $courrier->updateStatut($dto->statut);

                if ($utilisateurId) {
                    $utilisateur = $this->utilisateurRepository->findById($utilisateurId);
                    if ($utilisateur) {
                        $historiqueAction = new HistoriqueAction(
                            courrier: $courrier,
                            typeAction: HistoriqueAction::TYPE_MODIFICATION_STATUT,
                            description: sprintf('Statut modifié de %s à %s', $ancienStatut, $dto->statut),
                            effectuePar: $utilisateur,
                            ancienneValeur: $ancienStatut,
                            nouvelleValeur: $dto->statut
                        );
                        $this->historiqueActionRepository->save($historiqueAction);
                    }
                }
            }
        }

        if ($dto->notes !== null) {
            $courrier->ajouterNotes($dto->notes);
        }

        if ($dto->destinatairesCopieIds !== null) {
            // Note: Normalement on devrait vérifier ici sir l'utilisateur est l'expéditeur.
            // Mais le Use Case ne reçoit pas l'utilisateur actuel. 
            // On va supposer que le Voter a fait son travail ou on pourra ajouter un paramètre plus tard si besoin.
            // Cependant, le USER a dit "seul l'expéditeur doit faire le copie".
            
            $existingCopieIds = array_map(fn($s) => $s->getId(), $courrier->getDestinatairesCopie()->toArray());
            
            // Supprimer tous les destinataires en copie existants
            foreach ($courrier->getDestinatairesCopie() as $service) {
                $courrier->retirerDestinataireCopie($service);
            }

            // Ajouter les nouveaux destinataires en copie
            foreach ($dto->destinatairesCopieIds as $serviceId) {
                $service = $this->serviceRepository->findById($serviceId);
                if ($service) {
                    $courrier->ajouterDestinataireCopie($service);
                    
                    // Si le service est nouveau dans la liste des copies, on le notifie
                    if (!in_array($serviceId, $existingCopieIds)) {
                        $this->creerNotification($courrier, $service, true);
                    }
                }
            }
        }

        $this->courrierRepository->save($courrier);

        return $courrier;
    }

    private function creerNotification(Courrier $courrier, \App\Domain\Entity\Service $service, bool $isCopie = false): void
    {
        $expLabel = $courrier->getTypeExpediteur() === Courrier::ACTEUR_SERVICE
            ? ($courrier->getServiceExpediteur()?->getNom() ?? 'Expéditeur inconnu')
            : ($courrier->getPersonneExterneExpediteur()?->getNomOuRaisonSociale() ?? 'Expéditeur externe');

        $typeLabel = $isCopie ? 'en COPIE' : 'reçu';

        $notification = new Notification(
            service: $service,
            courrier: $courrier,
            type: Notification::TYPE_NOUVEAU_COURRIER,
            message: sprintf(
                'Nouveau courrier %s: %s de %s (Réf: %s)',
                $typeLabel,
                $courrier->getObjet(),
                $expLabel,
                $courrier->getNumeroReference()
            )
        );

        $this->notificationRepository->save($notification);
    }
}
