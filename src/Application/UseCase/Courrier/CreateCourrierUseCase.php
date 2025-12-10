<?php

declare(strict_types=1);

namespace App\Application\UseCase\Courrier;

use App\Application\DTO\CreateCourrierDTO;
use App\Domain\Entity\Courrier;
use App\Domain\Entity\Notification;
use App\Domain\Exception\InvalidCourierDataException;
use App\Domain\Repository\CourrierRepositoryInterface;
use App\Domain\Repository\PersonneExterneRepositoryInterface;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\Repository\NotificationRepositoryInterface;
use App\Domain\Service\NumeroReferenceGenerator;
use App\Domain\Service\NumeroArriveeGenerator;

class CreateCourrierUseCase
{
    public function __construct(
        private readonly CourrierRepositoryInterface $courrierRepository,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly PersonneExterneRepositoryInterface $personneExterneRepository,
        private readonly UtilisateurRepositoryInterface $utilisateurRepository,
        private readonly NotificationRepositoryInterface $notificationRepository,
        private readonly NumeroReferenceGenerator $numeroReferenceGenerator,
        private readonly NumeroArriveeGenerator $numeroArriveeGenerator
    ) {
    }

    public function execute(CreateCourrierDTO $dto, string $utilisateurId): Courrier
    {
        // Récupérer l'utilisateur créateur
        $utilisateur = $this->utilisateurRepository->findById($utilisateurId);
        if (!$utilisateur) {
            throw new InvalidCourierDataException('Utilisateur non trouvé');
        }

        // Récupérer l'expéditeur
        $expediteur = $this->getActeur(
            $dto->typeExpediteur,
            $dto->serviceExpediteurId,
            $dto->personneExterneExpediteurId,
            'expéditeur'
        );

        // Récupérer le destinataire
        $destinataire = $this->getActeur(
            $dto->typeDestinataire,
            $dto->serviceDestinataireId,
            $dto->personneExterneDestinataireId,
            'destinataire'
        );

        // Générer le numéro de référence selon le type de courrier
        if ($dto->type === Courrier::TYPE_SORTANT) {
            // Courrier sortant : générer numéro de référence automatiquement
            if ($dto->typeExpediteur === Courrier::ACTEUR_SERVICE) {
                $annee = (int) date('Y');
                $numeroReference = $this->numeroReferenceGenerator->generer($expediteur, $annee);
            } else {
                // Courrier externe sortant (rare)
                $numeroReference = sprintf('EXT-%s-%s', date('Y'), uniqid());
            }
        } else {
            // Courrier entrant : utiliser le numéro fourni ou générer un numéro temporaire
            if (!empty($dto->numeroReference)) {
                $numeroReference = $dto->numeroReference;
            } else {
                // Si pas de numéro fourni, générer un numéro temporaire
                $numeroReference = sprintf('REF-%s-%s', date('Ymd'), uniqid());
            }
        }

        // Gérer le courrier parent si c'est une réponse
        $courrierParent = null;
        if ($dto->courrierParentId) {
            $courrierParent = $this->courrierRepository->findById($dto->courrierParentId);
            if (!$courrierParent) {
                throw new InvalidCourierDataException('Courrier parent non trouvé');
            }
        }

        // Créer le courrier
        $courrier = new Courrier(
            numeroReference: $numeroReference,
            type: $dto->type,
            objet: $dto->objet,
            dateCourrier: new \DateTimeImmutable($dto->dateCourrier),
            priorite: $dto->priorite,
            typeExpediteur: $dto->typeExpediteur,
            expediteur: $expediteur,
            typeDestinataire: $dto->typeDestinataire,
            destinataire: $destinataire,
            creeParUtilisateur: $utilisateur,
            contenu: $dto->contenu,
            courrierParent: $courrierParent
        );

        // Ajouter les notes si présentes
        if ($dto->notes) {
            $courrier->ajouterNotes($dto->notes);
        }

        // Ajouter les destinataires en copie
        if ($dto->destinatairesCopieIds) {
            foreach ($dto->destinatairesCopieIds as $serviceId) {
                $service = $this->serviceRepository->findById($serviceId);
                if ($service) {
                    $courrier->ajouterDestinataireCopie($service);
                }
            }
        }

        // Sauvegarder le courrier
        $this->courrierRepository->save($courrier);

        // Générer le numéro d'arrivée pour les courriers entrants vers un SERVICE
        if ($courrier->getType() === Courrier::TYPE_ENTRANT
            && $courrier->getTypeDestinataire() === Courrier::ACTEUR_SERVICE) {

            $serviceDestinataire = $courrier->getServiceDestinataire();
            $annee = (int) $courrier->getDateEnregistrement()->format('Y');

            $numeroArrivee = $this->numeroArriveeGenerator->generer($serviceDestinataire, $annee);
            $courrier->setNumeroArrivee($numeroArrivee);

            // Sauvegarder à nouveau avec le numéro d'arrivée
            $this->courrierRepository->save($courrier);
        }

        // Créer une notification si le destinataire est un SERVICE (peu importe l'expéditeur)
        if ($dto->typeDestinataire === Courrier::ACTEUR_SERVICE) {
            $serviceDestinataire = $courrier->getServiceDestinataire();

            if ($serviceDestinataire) {
                $this->creerNotification($courrier);
            }
        }

        return $courrier;
    }

    private function getActeur(
        string $typeActeur,
        ?string $serviceId,
        ?string $personneExterneId,
        string $role
    ): mixed {
        if ($typeActeur === Courrier::ACTEUR_SERVICE) {
            if (!$serviceId) {
                throw new InvalidCourierDataException("L'ID du service {$role} est requis");
            }
            $service = $this->serviceRepository->findById($serviceId);
            if (!$service) {
                throw new InvalidCourierDataException("Service {$role} non trouvé");
            }
            if (!$service->isEstActif()) {
                throw new InvalidCourierDataException("Le service {$role} est désactivé");
            }
            return $service;
        }

        if ($typeActeur === Courrier::ACTEUR_PERSONNE_EXTERNE) {
            if (!$personneExterneId) {
                throw new InvalidCourierDataException("L'ID de la personne externe {$role} est requis");
            }
            $personne = $this->personneExterneRepository->findById($personneExterneId);
            if (!$personne) {
                throw new InvalidCourierDataException("Personne externe {$role} non trouvée");
            }
            return $personne;
        }

        throw new InvalidCourierDataException("Type d'acteur invalide pour {$role}");
    }

    private function creerNotification(Courrier $courrier): void
    {
        $notification = new Notification(
            service: $courrier->getServiceDestinataire(),
            courrier: $courrier,
            type: Notification::TYPE_NOUVEAU_COURRIER,
            message: sprintf(
                'Nouveau courrier reçu: %s de %s',
                $courrier->getObjet(),
                $courrier->getServiceExpediteur()->getNom()
            )
        );

        $this->notificationRepository->save($notification);
    }
}
