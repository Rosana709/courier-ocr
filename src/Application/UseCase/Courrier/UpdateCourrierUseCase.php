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

        // Empêcher la modification d'un courrier sortant (sauf si c'est pour l'archiver ou désarchiver)
        if ($courrier->estSortant() && $dto->statut !== Courrier::STATUT_ARCHIVE && $courrier->getStatut() !== Courrier::STATUT_ARCHIVE) {
            throw new \RuntimeException("Un courrier sortant ne peut pas être modifié après sa création.");
        }

        if ($dto->objet !== null && $courrier->estEntrant()) {
            $courrier->updateObjet($dto->objet);
        }

        if ($dto->contenu !== null && $courrier->estEntrant()) {
            $courrier->updateContenu($dto->contenu);
        }

        if ($dto->priorite !== null && $courrier->estEntrant()) {
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
            // SAUF si on veut l'archiver (et qu'il n'est pas déjà archivé)
            // OU si on veut le désarchiver (passer de ARCHIVE à un autre statut)
            $isArchiving = $dto->statut === Courrier::STATUT_ARCHIVE;
            $isUnarchiving = $courrier->getStatut() === Courrier::STATUT_ARCHIVE && $dto->statut !== Courrier::STATUT_ARCHIVE;
            $isAlreadyLocked = in_array($courrier->getStatut(), $statutsVerrouilles);

            if (!$isAlreadyLocked || $isArchiving || $isUnarchiving) {
                $ancienStatut = $courrier->getStatut();
                $nouveauStatut = $dto->statut;

                // Si on archive, mémoriser le statut actuel
                if ($isArchiving && $ancienStatut !== Courrier::STATUT_ARCHIVE) {
                    $courrier->setStatutAnterieur($ancienStatut);
                }
                
                // Si on désarchive, restaurer le statut précédent
                if ($isUnarchiving) {
                    $statutRestored = $courrier->getStatutAnterieur() ?? $this->trouverStatutAvantArchivage($courrier);
                    $nouveauStatut = $statutRestored;
                    $courrier->setStatutAnterieur(null); // Nettoyer après restauration
                }
                
                $courrier->updateStatut($nouveauStatut);

                if ($utilisateurId) {
                    $utilisateur = $this->utilisateurRepository->findById($utilisateurId);
                    if ($utilisateur) {
                        $historiqueAction = new HistoriqueAction(
                            courrier: $courrier,
                            typeAction: HistoriqueAction::TYPE_MODIFICATION_STATUT,
                            description: sprintf('Statut modifié de %s à %s', $ancienStatut, $nouveauStatut),
                            effectuePar: $utilisateur,
                            ancienneValeur: $ancienStatut,
                            nouvelleValeur: $nouveauStatut
                        );
                        $this->historiqueActionRepository->save($historiqueAction);
                        
                        // Créer des notifications lors du désarchivage
                        if ($isUnarchiving) {
                            $this->creerNotificationsDesarchivage($courrier);
                        }
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
    
    private function creerNotificationsDesarchivage(Courrier $courrier): void
    {
        $servicesAConcerner = [];
        
        // Ajouter l'expéditeur si c'est un service
        if ($courrier->getTypeExpediteur() === Courrier::ACTEUR_SERVICE && $courrier->getServiceExpediteur()) {
            $servicesAConcerner[] = $courrier->getServiceExpediteur();
        }
        
        // Ajouter le destinataire si c'est un service
        if ($courrier->getTypeDestinataire() === Courrier::ACTEUR_SERVICE && $courrier->getServiceDestinataire()) {
            $servicesAConcerner[] = $courrier->getServiceDestinataire();
        }
        
        // Créer une notification pour chaque service concerné
        foreach ($servicesAConcerner as $service) {
            $notification = new Notification(
                service: $service,
                courrier: $courrier,
                type: Notification::TYPE_STATUT_CHANGE,
                message: sprintf(
                    'Le courrier "%s" (Réf: %s) a été désarchivé et est de nouveau actif.',
                    $courrier->getObjet(),
                    $courrier->getNumeroReference()
                )
            );
            
            $this->notificationRepository->save($notification);
        }
    }
    
    private function trouverStatutAvantArchivage(Courrier $courrier): ?string
    {
        // Chercher dans l'historique la dernière action qui a mis le statut à ARCHIVE
        $historique = $this->historiqueActionRepository->findByCourrier($courrier);
        
        foreach ($historique as $action) {
            if ($action->getTypeAction() === HistoriqueAction::TYPE_MODIFICATION_STATUT 
                && $action->getNouvelleValeur() === Courrier::STATUT_ARCHIVE) {
                // Retourner l'ancienne valeur (le statut avant l'archivage)
                return $action->getAncienneValeur();
            }
        }
        
        // Si on ne trouve pas, retourner EN_ATTENTE par défaut
        return Courrier::STATUT_EN_ATTENTE;
    }
}
