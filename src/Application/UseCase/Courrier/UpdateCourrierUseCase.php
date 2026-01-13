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

    public function execute(string $courrierId, UpdateCourrierDTO $dto, ?string $utilisateurId = null, ?string $justification = null): Courrier
    {
        $courrier = $this->courrierRepository->findById($courrierId);

        if (!$courrier) {
            throw new EntityNotFoundException("Courrier non trouvé");
        }

        // Les modifications sont maintenant gérées au cas par cas dans la suite de la méthode


        $modifications = [];

        if ($dto->objet !== null && $courrier->estEntrant() && $dto->objet !== $courrier->getObjet()) {
            $modifications[] = sprintf('Objet modifié : "%s" -> "%s"', $courrier->getObjet(), $dto->objet);
            $courrier->updateObjet($dto->objet);
        }

        if ($dto->contenu !== null && $courrier->estEntrant() && $dto->contenu !== $courrier->getContenu()) {
            $modifications[] = 'Contenu modifié';
            $courrier->updateContenu($dto->contenu);
        }

        if ($dto->priorite !== null && $courrier->estEntrant() && $dto->priorite !== $courrier->getPriorite()) {
            $modifications[] = sprintf('Priorité modifiée : %s -> %s', $courrier->getPriorite(), $dto->priorite);
            $courrier->updatePriorite($dto->priorite);
        }

        $statusChanged = false;
        if ($dto->statut !== null && $dto->statut !== $courrier->getStatut()) {
            $statutsVerrouilles = [
                Courrier::STATUT_RECU_CONFIRME,
                Courrier::STATUT_ACCUSE_RECEPTION_RECU,
                Courrier::STATUT_CLOS,
                Courrier::STATUT_ARCHIVE
            ];

            $isArchiving = $dto->statut === Courrier::STATUT_ARCHIVE;
            $isUnarchiving = $courrier->getStatut() === Courrier::STATUT_ARCHIVE && $dto->statut !== Courrier::STATUT_ARCHIVE;
            $isAlreadyLocked = in_array($courrier->getStatut(), $statutsVerrouilles);

            if (!$isAlreadyLocked || $isArchiving || $isUnarchiving) {
                $ancienStatut = $courrier->getStatut();
                $nouveauStatut = $dto->statut;

                if ($isArchiving && $ancienStatut !== Courrier::STATUT_ARCHIVE) {
                    $courrier->setStatutAnterieur($ancienStatut);
                }
                
                if ($isUnarchiving) {
                    $statutRestored = $courrier->getStatutAnterieur() ?? $this->trouverStatutAvantArchivage($courrier);
                    $nouveauStatut = $statutRestored;
                    $courrier->setStatutAnterieur(null);
                }
                
                $courrier->updateStatut($nouveauStatut);
                $statusChanged = true;

                if ($utilisateurId) {
                    $utilisateur = $this->utilisateurRepository->findById($utilisateurId);
                    if ($utilisateur) {
                        $typeAction = HistoriqueAction::TYPE_MODIFICATION_STATUT;
                        
                        if ($nouveauStatut === Courrier::STATUT_ARCHIVE) {
                            if ($utilisateur->isAdmin()) {
                                $typeAction = HistoriqueAction::TYPE_ARCHIVAGE;
                                $description = "Courrier archivé par l'administrateur";
                            } else {
                                $typeAction = HistoriqueAction::TYPE_SUPPRESSION;
                                $description = "Courrier supprimé (archivé) par le service " . ($utilisateur->getService() ? $utilisateur->getService()->getNom() : '');
                            }
                        } elseif ($isUnarchiving) {
                            $description = "Courrier désarchivé et remis en circulation";
                        } else {
                            $description = sprintf('Statut modifié de %s à %s', $ancienStatut, $nouveauStatut);
                        }

                        if ($justification) {
                            $description .= ' | Justification : ' . $justification;
                        }

                        $historiqueAction = new HistoriqueAction(
                            courrier: $courrier,
                            typeAction: $typeAction,
                            description: $description,
                            effectuePar: $utilisateur,
                            ancienneValeur: $ancienStatut,
                            nouvelleValeur: $nouveauStatut
                        );
                        $this->historiqueActionRepository->save($historiqueAction);
                        
                        if ($isUnarchiving) {
                            $this->creerNotificationsDesarchivage($courrier);
                        }
                    }
                }
            }
        }

        if ($dto->notes !== null) {
            $courrier->ajouterNotes($dto->notes);
            $modifications[] = 'Note ajoutée';
        }

        if ($dto->destinatairesCopieIds !== null) {
            $courrier->getDestinatairesCopie()->clear();
            foreach ($dto->destinatairesCopieIds as $serviceId) {
                $service = $this->serviceRepository->findById($serviceId);
                if ($service) {
                    $courrier->ajouterDestinataireCopie($service);
                }
            }
            $modifications[] = 'Liste des copies mise à jour';
        }

        // Si on a des modifications autres que le statut, on les logue globalement
        if (!empty($modifications) && $utilisateurId) {
            $utilisateur = $this->utilisateurRepository->findById($utilisateurId);
            if ($utilisateur) {
                $historiqueAction = new HistoriqueAction(
                    courrier: $courrier,
                    typeAction: HistoriqueAction::TYPE_MODIFICATION_CONTENU,
                    description: implode(' | ', $modifications),
                    effectuePar: $utilisateur
                );
                $this->historiqueActionRepository->save($historiqueAction);
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
