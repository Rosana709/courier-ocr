<?php

declare(strict_types=1);

namespace App\Application\UseCase\Courrier;

use App\Domain\Entity\AccuseReception;
use App\Domain\Entity\Courrier;
use App\Domain\Entity\Notification;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\InvalidCourierDataException;
use App\Domain\Repository\AccuseReceptionRepositoryInterface;
use App\Domain\Repository\CourrierRepositoryInterface;
use App\Domain\Repository\NotificationRepositoryInterface;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\Repository\HistoriqueActionRepositoryInterface;

class AccuseReceptionUseCase
{
    public function __construct(
        private readonly CourrierRepositoryInterface $courrierRepository,
        private readonly AccuseReceptionRepositoryInterface $accuseReceptionRepository,
        private readonly NotificationRepositoryInterface $notificationRepository,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly UtilisateurRepositoryInterface $utilisateurRepository,
        private readonly HistoriqueActionRepositoryInterface $historiqueActionRepository
    ) {
    }

    /**
     * Crée un accusé de réception pour un courrier interne
     */
    public function execute(string $courrierId, string $serviceId, string $utilisateurId): AccuseReception
    {
        $courrier = $this->courrierRepository->findById($courrierId);

        if (!$courrier) {
            throw new EntityNotFoundException("Courrier non trouvé");
        }

        $service = $this->serviceRepository->findById($serviceId);

        if (!$service) {
            throw new EntityNotFoundException("Service non trouvé");
        }

        $utilisateur = $this->utilisateurRepository->findById($utilisateurId);

        if (!$utilisateur) {
            throw new EntityNotFoundException("Utilisateur non trouvé");
        }

        $estDestinatairePrincipal = ($courrier->getServiceDestinataire() && $courrier->getServiceDestinataire()->getId() === $service->getId());

        if (!$estDestinatairePrincipal) {
            throw new InvalidCourierDataException("Seul le destinataire principal peut confirmer la réception");
        }

        // Vérifier le statut du courrier
        if ($courrier->getStatut() !== Courrier::STATUT_RECU_NON_CONFIRME
            && $courrier->getStatut() !== Courrier::STATUT_EN_ATTENTE_ACCUSE_RECEPTION) {
            throw new InvalidCourierDataException("Le courrier n'est pas en attente de confirmation");
        }

        // Créer l'accusé de réception
        $accuseReception = new AccuseReception(
            courrier: $courrier,
            serviceRecepteur: $service,
            dateReceptionPhysique: new \DateTimeImmutable(),
            confirmeParUtilisateur: $utilisateur
        );

        $this->accuseReceptionRepository->save($accuseReception);

        // Mettre à jour le statut du courrier : l'expéditeur doit voir que c'est fini immédiatement
        $ancienStatut = $courrier->getStatut();
        $courrier->updateStatut(Courrier::STATUT_CLOS);

        $this->courrierRepository->save($courrier);

        // Enregistrer l'action
        $historiqueAction = new \App\Domain\Entity\HistoriqueAction(
            courrier: $courrier,
            typeAction: \App\Domain\Entity\HistoriqueAction::TYPE_ENVOI_ACCUSE_RECEPTION,
            description: sprintf('Réception confirmée par le service %s (Utilisateur: %s)', $service->getNom(), $utilisateur->getEmail()),
            effectuePar: $utilisateur,
            ancienneValeur: $ancienStatut,
            nouvelleValeur: Courrier::STATUT_CLOS
        );
        $this->historiqueActionRepository->save($historiqueAction);

        // Créer une notification pour le service expéditeur (si expéditeur = SERVICE)
        if ($courrier->getTypeExpediteur() === Courrier::ACTEUR_SERVICE) {
            $serviceExpediteur = $courrier->getServiceExpediteur();

            if ($serviceExpediteur) {
                $this->creerNotificationAccuse($courrier, $serviceExpediteur);
            }
        }
        // Si l'expéditeur est une PERSONNE_EXTERNE, pas de notification (ils n'ont pas de compte)

        return $accuseReception;
    }

    private function creerNotificationAccuse(Courrier $courrier, $serviceExpediteur): void
    {
        $notification = new Notification(
            service: $serviceExpediteur,
            courrier: $courrier,
            type: Notification::TYPE_ACCUSE_RECU,
            message: sprintf(
                'Accusé de réception confirmé pour: %s',
                $courrier->getObjet()
            )
        );

        $this->notificationRepository->save($notification);
    }
}
