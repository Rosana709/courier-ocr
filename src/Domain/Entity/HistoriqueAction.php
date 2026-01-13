<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'historique_action')]
#[ORM\Index(name: 'idx_courrier_date', columns: ['courrier_id', 'date_action'])]
class HistoriqueAction
{
    public const TYPE_CREATION = 'CREATION';
    public const TYPE_MODIFICATION_STATUT = 'MODIFICATION_STATUT';
    public const TYPE_MODIFICATION_CONTENU = 'MODIFICATION_CONTENU';
    public const TYPE_MODIFICATION_PRIORITE = 'MODIFICATION_PRIORITE';
    public const TYPE_AJOUT_PIECE_JOINTE = 'AJOUT_PIECE_JOINTE';
    public const TYPE_SUPPRESSION_PIECE_JOINTE = 'SUPPRESSION_PIECE_JOINTE';
    public const TYPE_ENVOI_ACCUSE_RECEPTION = 'ENVOI_ACCUSE_RECEPTION';
    public const TYPE_AJOUT_DESTINATAIRE_COPIE = 'AJOUT_DESTINATAIRE_COPIE';
    public const TYPE_SUPPRESSION_DESTINATAIRE_COPIE = 'SUPPRESSION_DESTINATAIRE_COPIE';
    public const TYPE_AJOUT_NOTES = 'AJOUT_NOTES';
    public const TYPE_ARCHIVAGE = 'ARCHIVAGE';
    public const TYPE_SUPPRESSION = 'SUPPRESSION';

    // Actions administratives
    public const TYPE_UTILISATEUR_CREATION = 'UTILISATEUR_CREATION';
    public const TYPE_UTILISATEUR_MODIFICATION = 'UTILISATEUR_MODIFICATION';
    public const TYPE_UTILISATEUR_TOGGLE = 'UTILISATEUR_TOGGLE';
    public const TYPE_SERVICE_CREATION = 'SERVICE_CREATION';
    public const TYPE_SERVICE_MODIFICATION = 'SERVICE_MODIFICATION';
    public const TYPE_SERVICE_TOGGLE = 'SERVICE_TOGGLE';
    public const TYPE_PERSONNE_EXTERNE_CREATION = 'PERSONNE_EXTERNE_CREATION';
    public const TYPE_PERSONNE_EXTERNE_MODIFICATION = 'PERSONNE_EXTERNE_MODIFICATION';
    public const TYPE_PERSONNE_EXTERNE_TOGGLE = 'PERSONNE_EXTERNE_TOGGLE';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Courrier::class)]
    #[ORM\JoinColumn(name: 'courrier_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Courrier $courrier = null;

    #[ORM\Column(name: 'typeaction', type: 'string', length: 50)]
    private string $typeAction;

    #[ORM\Column(name: 'anciennevaleur', type: 'text', nullable: true)]
    private ?string $ancienneValeur = null;

    #[ORM\Column(name: 'nouvellevaleur', type: 'text', nullable: true)]
    private ?string $nouvelleValeur = null;

    #[ORM\Column(type: 'string', length: 500)]
    private string $description;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'effectue_par_id', referencedColumnName: 'id', nullable: false)]
    private Utilisateur $effectuePar;

    #[ORM\Column(name: 'date_action', type: 'datetime_immutable')]
    private \DateTimeImmutable $dateAction;

    public function __construct(
        ?Courrier $courrier,
        string $typeAction,
        string $description,
        Utilisateur $effectuePar,
        ?string $ancienneValeur = null,
        ?string $nouvelleValeur = null
    ) {
        $this->id = Uuid::v4()->__toString();
        $this->courrier = $courrier;
        $this->typeAction = $this->validateTypeAction($typeAction);
        $this->description = $this->validateDescription($description);
        $this->effectuePar = $effectuePar;
        $this->ancienneValeur = $ancienneValeur;
        $this->nouvelleValeur = $nouvelleValeur;
        $this->dateAction = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCourrier(): ?Courrier
    {
        return $this->courrier;
    }

    public function getTypeAction(): string
    {
        return $this->typeAction;
    }

    public function getAncienneValeur(): ?string
    {
        return $this->ancienneValeur;
    }

    public function getNouvelleValeur(): ?string
    {
        return $this->nouvelleValeur;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getEffectuePar(): Utilisateur
    {
        return $this->effectuePar;
    }

    public function getDateAction(): \DateTimeImmutable
    {
        return $this->dateAction;
    }

    public static function getValidTypesAction(): array
    {
        return [
            self::TYPE_CREATION,
            self::TYPE_MODIFICATION_STATUT,
            self::TYPE_MODIFICATION_CONTENU,
            self::TYPE_MODIFICATION_PRIORITE,
            self::TYPE_AJOUT_PIECE_JOINTE,
            self::TYPE_SUPPRESSION_PIECE_JOINTE,
            self::TYPE_ENVOI_ACCUSE_RECEPTION,
            self::TYPE_AJOUT_DESTINATAIRE_COPIE,
            self::TYPE_SUPPRESSION_DESTINATAIRE_COPIE,
            self::TYPE_AJOUT_NOTES,
            self::TYPE_ARCHIVAGE,
            self::TYPE_SUPPRESSION,
            self::TYPE_UTILISATEUR_CREATION,
            self::TYPE_UTILISATEUR_MODIFICATION,
            self::TYPE_UTILISATEUR_TOGGLE,
            self::TYPE_SERVICE_CREATION,
            self::TYPE_SERVICE_MODIFICATION,
            self::TYPE_SERVICE_TOGGLE,
            self::TYPE_PERSONNE_EXTERNE_CREATION,
            self::TYPE_PERSONNE_EXTERNE_MODIFICATION,
            self::TYPE_PERSONNE_EXTERNE_TOGGLE,
        ];
    }

    public function getPhraseAction(): string
    {
        $acteur = $this->getEffectuePar()->getService() 
            ? $this->getEffectuePar()->getService()->getNom() 
            : $this->getEffectuePar()->getEmail();
        
        $actionVerb = match($this->typeAction) {
            self::TYPE_CREATION => "a créé le courrier",
            self::TYPE_MODIFICATION_STATUT => $this->ancienneValeur === Courrier::STATUT_ARCHIVE ? "a désarchivé le courrier" : "a modifié le statut du courrier",
            self::TYPE_MODIFICATION_CONTENU => "a modifié le contenu du courrier",
            self::TYPE_MODIFICATION_PRIORITE => "a modifié la priorité du courrier",
            self::TYPE_AJOUT_PIECE_JOINTE => "a ajouté une pièce jointe au courrier",
            self::TYPE_SUPPRESSION_PIECE_JOINTE => "a supprimé une pièce jointe du courrier",
            self::TYPE_ENVOI_ACCUSE_RECEPTION => "a envoyé l'accusé de réception pour le courrier",
            self::TYPE_AJOUT_DESTINATAIRE_COPIE => "a ajouté un destinataire en copie au courrier",
            self::TYPE_SUPPRESSION_DESTINATAIRE_COPIE => "a supprimé un destinataire en copie du courrier",
            self::TYPE_AJOUT_NOTES => "a ajouté des notes au courrier",
            self::TYPE_ARCHIVAGE => "a archivé le courrier",
            self::TYPE_SUPPRESSION => "a supprimé le courrier",
            self::TYPE_UTILISATEUR_CREATION => "a créé l'utilisateur",
            self::TYPE_UTILISATEUR_MODIFICATION => "a modifié l'utilisateur",
            self::TYPE_UTILISATEUR_TOGGLE => $this->nouvelleValeur === 'ACTIF' ? "a activé l'utilisateur" : "a désactivé l'utilisateur",
            self::TYPE_SERVICE_CREATION => "a créé le service",
            self::TYPE_SERVICE_MODIFICATION => "a modifié le service",
            self::TYPE_SERVICE_TOGGLE => $this->nouvelleValeur === 'ACTIF' ? "a activé le service" : "a désactivé le service",
            self::TYPE_PERSONNE_EXTERNE_CREATION => "a créé la personne externe",
            self::TYPE_PERSONNE_EXTERNE_MODIFICATION => "a modifié la personne externe",
            self::TYPE_PERSONNE_EXTERNE_TOGGLE => $this->nouvelleValeur === 'ACTIF' ? "a activé la personne externe" : "a désactivé la personne externe",
            default => "a effectué l'action " . str_replace('_', ' ', strtolower($this->typeAction))
        };

        $complement = "";
        if ($this->courrier) {
            $complement = " N°" . $this->courrier->getNumeroReference();
        } elseif ($this->description && (
            str_contains($this->typeAction, 'UTILISATEUR') || 
            str_contains($this->typeAction, 'SERVICE') || 
            str_contains($this->typeAction, 'PERSONNE')
        )) {
             // For creation/modification of user/service/person, the description usually contains the label or details
             $complement = " (" . $this->description . ")";
        }

        return $acteur . " " . $actionVerb . $complement . ".";
    }

    private function validateTypeAction(string $type): string
    {
        if (!in_array($type, self::getValidTypesAction(), true)) {
            throw new \InvalidArgumentException(sprintf('Le type d\'action "%s" n\'est pas valide', $type));
        }

        return $type;
    }

    private function validateDescription(string $description): string
    {
        $description = trim($description);

        if (empty($description)) {
            throw new \InvalidArgumentException('La description ne peut pas être vide');
        }

        if (strlen($description) > 500) {
            throw new \InvalidArgumentException('La description ne peut pas dépasser 500 caractères');
        }

        return $description;
    }
}
