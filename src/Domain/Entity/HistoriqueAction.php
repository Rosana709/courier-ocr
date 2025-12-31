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

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Courrier::class)]
    #[ORM\JoinColumn(name: 'courrier_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Courrier $courrier;

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
        Courrier $courrier,
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

    public function getCourrier(): Courrier
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
        ];
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
