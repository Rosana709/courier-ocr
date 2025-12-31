<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'courrier')]
class Courrier
{
    // Types de courrier
    public const TYPE_ENTRANT = 'ENTRANT';
    public const TYPE_SORTANT = 'SORTANT';

    // Types d'acteurs (pour polymorphisme)
    public const ACTEUR_SERVICE = 'SERVICE';
    public const ACTEUR_PERSONNE_EXTERNE = 'PERSONNE_EXTERNE';

    // Priorités
    public const PRIORITE_BASSE = 'BASSE';
    public const PRIORITE_NORMALE = 'NORMALE';
    public const PRIORITE_HAUTE = 'HAUTE';
    public const PRIORITE_URGENTE = 'URGENTE';

    // Statuts génériques
    public const STATUT_ENREGISTRE = 'ENREGISTRE';
    public const STATUT_EN_COURS_TRAITEMENT = 'EN_COURS_TRAITEMENT';
    public const STATUT_EN_ATTENTE = 'EN_ATTENTE';
    public const STATUT_CLOS = 'CLOS';
    public const STATUT_ARCHIVE = 'ARCHIVE';

    // Statuts spécifiques courriers internes
    public const STATUT_EN_ATTENTE_ACCUSE_RECEPTION = 'EN_ATTENTE_ACCUSE_RECEPTION';
    public const STATUT_ACCUSE_RECEPTION_RECU = 'ACCUSE_RECEPTION_RECU';
    public const STATUT_RECU_NON_CONFIRME = 'RECU_NON_CONFIRME';
    public const STATUT_RECU_CONFIRME = 'RECU_CONFIRME';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100, unique: true, name: 'numeroreference')]
    private string $numeroReference;

    #[ORM\Column(type: 'string', length: 100, unique: true, name: 'numero_arrivee', nullable: true)]
    private ?string $numeroArrivee = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(type: 'string', length: 500)]
    private string $objet;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $contenu = null;

    #[ORM\Column(type: 'date_immutable', name: 'datecourrier')]
    private \DateTimeImmutable $dateCourrier;

    #[ORM\Column(type: 'datetime_immutable', name: 'dateenregistrement')]
    private \DateTimeImmutable $dateEnregistrement;

    #[ORM\Column(type: 'string', length: 20)]
    private string $priorite;

    #[ORM\Column(type: 'string', length: 50)]
    private string $statut;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    // Expéditeur polymorphe
    #[ORM\Column(type: 'string', length: 30, name: 'typeexpediteur')]
    private string $typeExpediteur;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(name: 'service_expediteur_id', referencedColumnName: 'id', nullable: true)]
    private ?Service $serviceExpediteur = null;

    #[ORM\ManyToOne(targetEntity: PersonneExterne::class)]
    #[ORM\JoinColumn(name: 'personne_externe_expediteur_id', referencedColumnName: 'id', nullable: true)]
    private ?PersonneExterne $personneExterneExpediteur = null;

    // Destinataire polymorphe
    #[ORM\Column(type: 'string', length: 30, name: 'typedestinataire')]
    private string $typeDestinataire;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(name: 'service_destinataire_id', referencedColumnName: 'id', nullable: true)]
    private ?Service $serviceDestinataire = null;

    #[ORM\ManyToOne(targetEntity: PersonneExterne::class)]
    #[ORM\JoinColumn(name: 'personne_externe_destinataire_id', referencedColumnName: 'id', nullable: true)]
    private ?PersonneExterne $personneExterneDestinataire = null;

    // Destinataires en copie (uniquement services)
    #[ORM\ManyToMany(targetEntity: Service::class)]
    #[ORM\JoinTable(name: 'courrier_service_copie')]
    #[ORM\JoinColumn(name: 'courrier_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'service_id', referencedColumnName: 'id')]
    private Collection $destinatairesCopie;

    // Relation parent/réponse
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'reponses')]
    #[ORM\JoinColumn(name: 'courrier_parent_id', referencedColumnName: 'id', nullable: true)]
    private ?self $courrierParent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'courrierParent')]
    private Collection $reponses;

    // Métadonnées
    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'cree_par_utilisateur_id', referencedColumnName: 'id', nullable: false)]
    private Utilisateur $creeParUtilisateur;

    #[ORM\Column(type: 'datetime_immutable', name: 'datecreation')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'datemodification')]
    private ?\DateTimeImmutable $dateModification = null;

    public function __construct(
        string $numeroReference,
        string $type,
        string $objet,
        \DateTimeImmutable $dateCourrier,
        string $priorite,
        string $typeExpediteur,
        Service|PersonneExterne $expediteur,
        string $typeDestinataire,
        Service|PersonneExterne $destinataire,
        Utilisateur $creeParUtilisateur,
        ?string $contenu = null,
        ?self $courrierParent = null
    ) {
        $this->id = Uuid::v4()->__toString();
        $this->numeroReference = $this->validateNumeroReference($numeroReference);
        $this->type = $this->validateType($type);
        $this->objet = $this->validateObjet($objet);
        $this->contenu = $contenu;
        $this->dateCourrier = $dateCourrier;
        $this->dateEnregistrement = new \DateTimeImmutable();
        $this->priorite = $this->validatePriorite($priorite);
        $this->statut = $this->determinerStatutInitial($type, $typeExpediteur, $typeDestinataire);
        $this->creeParUtilisateur = $creeParUtilisateur;
        $this->courrierParent = $courrierParent;
        $this->dateCreation = new \DateTimeImmutable();

        // Configuration expéditeur polymorphe
        $this->typeExpediteur = $this->validateTypeActeur($typeExpediteur);
        if ($typeExpediteur === self::ACTEUR_SERVICE) {
            if (!$expediteur instanceof Service) {
                throw new \InvalidArgumentException('L\'expéditeur doit être un Service');
            }
            $this->serviceExpediteur = $expediteur;
        } else {
            if (!$expediteur instanceof PersonneExterne) {
                throw new \InvalidArgumentException('L\'expéditeur doit être une PersonneExterne');
            }
            $this->personneExterneExpediteur = $expediteur;
        }

        // Configuration destinataire polymorphe
        $this->typeDestinataire = $this->validateTypeActeur($typeDestinataire);
        if ($typeDestinataire === self::ACTEUR_SERVICE) {
            if (!$destinataire instanceof Service) {
                throw new \InvalidArgumentException('Le destinataire doit être un Service');
            }
            $this->serviceDestinataire = $destinataire;
        } else {
            if (!$destinataire instanceof PersonneExterne) {
                throw new \InvalidArgumentException('Le destinataire doit être une PersonneExterne');
            }
            $this->personneExterneDestinataire = $destinataire;
        }

        $this->destinatairesCopie = new ArrayCollection();
        $this->reponses = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNumeroReference(): string
    {
        return $this->numeroReference;
    }

    public function getNumeroArrivee(): ?string
    {
        return $this->numeroArrivee;
    }

    public function setNumeroArrivee(string $numeroArrivee): void
    {
        $this->numeroArrivee = $numeroArrivee;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function estEntrant(): bool
    {
        return $this->type === self::TYPE_ENTRANT;
    }

    public function getTypeRelatif(Service $service): string
    {
        // Si le service est l'expéditeur, c'est un courrier SORTANT pour lui
        if ($this->typeExpediteur === self::ACTEUR_SERVICE && $this->serviceExpediteur?->getId() === $service->getId()) {
            return self::TYPE_SORTANT;
        }

        // Si le service est le destinataire ou en copie, c'est un courrier ENTRANT pour lui
        if (($this->typeDestinataire === self::ACTEUR_SERVICE && $this->serviceDestinataire?->getId() === $service->getId())) {
            return self::TYPE_ENTRANT;
        }

        // Vérification dans les destinataires en copie
        foreach ($this->destinatairesCopie as $serviceCopie) {
            if ($serviceCopie->getId() === $service->getId()) {
                return self::TYPE_ENTRANT;
            }
        }

        // Par défaut, retourner le type original (pour les admins ou acteurs externes)
        return $this->type;
    }

    public function getStatutRelatif(Service $service): string
    {
        // Liste des statuts considérés comme "confirmés" ou terminaux
        $statutsTerminaux = [
            self::STATUT_RECU_CONFIRME,
            self::STATUT_ACCUSE_RECEPTION_RECU,
            self::STATUT_CLOS,
            self::STATUT_ARCHIVE
        ];

        if (in_array($this->statut, $statutsTerminaux)) {
            return 'ACCUSÉ RÉCEPTION REÇU';
        }

        return 'EN ATTENTE ACCUSÉ RÉCEPTION';
    }

    public function estInterne(): bool
    {
        return $this->typeExpediteur === self::ACTEUR_SERVICE
            && $this->typeDestinataire === self::ACTEUR_SERVICE;
    }

    public function getObjet(): string
    {
        return $this->objet;
    }

    public function updateObjet(string $objet): void
    {
        $this->objet = $this->validateObjet($objet);
        $this->dateModification = new \DateTimeImmutable();
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function updateContenu(?string $contenu): void
    {
        $this->contenu = $contenu;
        $this->dateModification = new \DateTimeImmutable();
    }

    public function getDateCourrier(): \DateTimeImmutable
    {
        return $this->dateCourrier;
    }

    public function getDateEnregistrement(): \DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function getPriorite(): string
    {
        return $this->priorite;
    }

    public function updatePriorite(string $priorite): void
    {
        $this->priorite = $this->validatePriorite($priorite);
        $this->dateModification = new \DateTimeImmutable();
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function updateStatut(string $statut): void
    {
        $this->statut = $this->validateStatut($statut);
        $this->dateModification = new \DateTimeImmutable();
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function ajouterNotes(string $notes): void
    {
        if ($this->notes === null) {
            $this->notes = $notes;
        } else {
            $this->notes .= "\n\n" . $notes;
        }
        $this->dateModification = new \DateTimeImmutable();
    }

    // Méthodes expéditeur
    public function getTypeExpediteur(): string
    {
        return $this->typeExpediteur;
    }

    public function getServiceExpediteur(): ?Service
    {
        return $this->serviceExpediteur;
    }

    public function getPersonneExterneExpediteur(): ?PersonneExterne
    {
        return $this->personneExterneExpediteur;
    }

    public function getExpediteur(): Service|PersonneExterne
    {
        return $this->typeExpediteur === self::ACTEUR_SERVICE
            ? $this->serviceExpediteur
            : $this->personneExterneExpediteur;
    }

    // Méthodes destinataire
    public function getTypeDestinataire(): string
    {
        return $this->typeDestinataire;
    }

    public function getServiceDestinataire(): ?Service
    {
        return $this->serviceDestinataire;
    }

    public function getPersonneExterneDestinataire(): ?PersonneExterne
    {
        return $this->personneExterneDestinataire;
    }

    public function getDestinataire(): Service|PersonneExterne
    {
        return $this->typeDestinataire === self::ACTEUR_SERVICE
            ? $this->serviceDestinataire
            : $this->personneExterneDestinataire;
    }

    // Méthodes destinataires en copie
    public function getDestinatairesCopie(): Collection
    {
        return $this->destinatairesCopie;
    }

    public function ajouterDestinataireCopie(Service $service): void
    {
        if (!$this->destinatairesCopie->contains($service)) {
            $this->destinatairesCopie->add($service);
            $this->dateModification = new \DateTimeImmutable();
        }
    }

    public function retirerDestinataireCopie(Service $service): void
    {
        if ($this->destinatairesCopie->contains($service)) {
            $this->destinatairesCopie->removeElement($service);
            $this->dateModification = new \DateTimeImmutable();
        }
    }

    // Méthodes parent/réponses
    public function getCourrierParent(): ?self
    {
        return $this->courrierParent;
    }

    public function estUneReponse(): bool
    {
        return $this->courrierParent !== null;
    }

    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    // Méthodes métadonnées
    public function getCreeParUtilisateur(): Utilisateur
    {
        return $this->creeParUtilisateur;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getDateModification(): ?\DateTimeImmutable
    {
        return $this->dateModification;
    }

    // Méthodes statiques
    public static function getValidTypes(): array
    {
        return [self::TYPE_ENTRANT, self::TYPE_SORTANT];
    }

    public static function getValidPriorites(): array
    {
        return [
            self::PRIORITE_BASSE,
            self::PRIORITE_NORMALE,
            self::PRIORITE_HAUTE,
            self::PRIORITE_URGENTE,
        ];
    }

    public static function getValidStatuts(): array
    {
        return [
            self::STATUT_ENREGISTRE,
            self::STATUT_EN_COURS_TRAITEMENT,
            self::STATUT_EN_ATTENTE,
            self::STATUT_CLOS,
            self::STATUT_ARCHIVE,
            self::STATUT_EN_ATTENTE_ACCUSE_RECEPTION,
            self::STATUT_ACCUSE_RECEPTION_RECU,
            self::STATUT_RECU_NON_CONFIRME,
            self::STATUT_RECU_CONFIRME,
        ];
    }

    // Méthodes de validation privées
    private function validateNumeroReference(string $numero): string
    {
        $numero = trim($numero);

        if (empty($numero)) {
            throw new \InvalidArgumentException('Le numéro de référence ne peut pas être vide');
        }

        if (strlen($numero) > 100) {
            throw new \InvalidArgumentException('Le numéro de référence ne peut pas dépasser 100 caractères');
        }

        return $numero;
    }

    private function validateType(string $type): string
    {
        if (!in_array($type, self::getValidTypes(), true)) {
            throw new \InvalidArgumentException(sprintf('Le type "%s" n\'est pas valide', $type));
        }

        return $type;
    }

    private function validateTypeActeur(string $type): string
    {
        $validTypes = [self::ACTEUR_SERVICE, self::ACTEUR_PERSONNE_EXTERNE];

        if (!in_array($type, $validTypes, true)) {
            throw new \InvalidArgumentException(sprintf('Le type d\'acteur "%s" n\'est pas valide', $type));
        }

        return $type;
    }

    private function validateObjet(string $objet): string
    {
        $objet = trim($objet);

        if (empty($objet)) {
            throw new \InvalidArgumentException('L\'objet ne peut pas être vide');
        }

        if (strlen($objet) > 500) {
            throw new \InvalidArgumentException('L\'objet ne peut pas dépasser 500 caractères');
        }

        return $objet;
    }

    private function validatePriorite(string $priorite): string
    {
        if (!in_array($priorite, self::getValidPriorites(), true)) {
            throw new \InvalidArgumentException(sprintf('La priorité "%s" n\'est pas valide', $priorite));
        }

        return $priorite;
    }

    private function validateStatut(string $statut): string
    {
        if (!in_array($statut, self::getValidStatuts(), true)) {
            throw new \InvalidArgumentException(sprintf('Le statut "%s" n\'est pas valide', $statut));
        }

        return $statut;
    }

    private function determinerStatutInitial(
        string $type,
        string $typeExpediteur,
        string $typeDestinataire
    ): string {
        // Si destinataire est un SERVICE (nécessite confirmation de réception)
        if ($typeDestinataire === self::ACTEUR_SERVICE) {
            // Courrier entrant vers service : RECU_NON_CONFIRME
            if ($type === self::TYPE_ENTRANT) {
                return self::STATUT_RECU_NON_CONFIRME;
            }

            // Courrier sortant vers service : EN_ATTENTE_ACCUSE_RECEPTION
            if ($type === self::TYPE_SORTANT) {
                return self::STATUT_EN_ATTENTE_ACCUSE_RECEPTION;
            }
        }

        // Courrier vers personne externe : pas de confirmation de réception
        return self::STATUT_ENREGISTRE;
    }
}