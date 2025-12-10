<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'piece_jointe')]
class PieceJointe
{
    public const TAILLE_MAX = 10485760; // 10 MB en octets

    public const TYPES_MIME_AUTORISES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // DOCX
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // XLSX
        'application/msword', // DOC
        'application/vnd.ms-excel', // XLS
    ];

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Courrier::class)]
    #[ORM\JoinColumn(name: 'courrier_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Courrier $courrier;

    #[ORM\Column(type: 'string', length: 255, name: 'nomfichier')]
    private string $nomFichier;

    #[ORM\Column(type: 'string', length: 255, name: 'nomfichieroriginal')]
    private string $nomFichierOriginal;

    #[ORM\Column(type: 'string', length: 100, name: 'typemime')]
    private string $typeMime;

    #[ORM\Column(type: 'integer', name: 'taillefichier')]
    private int $tailleFichier;

    #[ORM\Column(type: 'blob', name: 'contenufichier')]
    private $contenuFichier;

    #[ORM\Column(type: 'datetime_immutable', name: 'datetelechargement')]
    private \DateTimeImmutable $dateTelechargement;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'telecharge_par_id', referencedColumnName: 'id', nullable: false)]
    private Utilisateur $telechargeParUtilisateur;

    public function __construct(
        Courrier $courrier,
        string $nomFichierOriginal,
        string $typeMime,
        int $tailleFichier,
        $contenuFichier,
        Utilisateur $telechargeParUtilisateur
    ) {
        $this->id = Uuid::v4()->__toString();
        $this->courrier = $courrier;
        $this->nomFichierOriginal = $this->validateNomFichier($nomFichierOriginal);
        $this->nomFichier = $this->genererNomFichierUnique($nomFichierOriginal);
        $this->typeMime = $this->validateTypeMime($typeMime);
        $this->tailleFichier = $this->validateTailleFichier($tailleFichier);
        $this->contenuFichier = $contenuFichier;
        $this->telechargeParUtilisateur = $telechargeParUtilisateur;
        $this->dateTelechargement = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCourrier(): Courrier
    {
        return $this->courrier;
    }

    public function getNomFichier(): string
    {
        return $this->nomFichier;
    }

    public function getNomFichierOriginal(): string
    {
        return $this->nomFichierOriginal;
    }

    public function getTypeMime(): string
    {
        return $this->typeMime;
    }

    public function getTailleFichier(): int
    {
        return $this->tailleFichier;
    }

    public function getTailleFichierFormate(): string
    {
        $taille = $this->tailleFichier;

        if ($taille < 1024) {
            return $taille . ' o';
        }

        if ($taille < 1048576) {
            return round($taille / 1024, 2) . ' Ko';
        }

        return round($taille / 1048576, 2) . ' Mo';
    }

    public function getContenuFichier()
    {
        return $this->contenuFichier;
    }

    public function getDateTelechargement(): \DateTimeImmutable
    {
        return $this->dateTelechargement;
    }

    public function getTelechargeParUtilisateur(): Utilisateur
    {
        return $this->telechargeParUtilisateur;
    }

    public function getExtension(): string
    {
        $extension = pathinfo($this->nomFichierOriginal, PATHINFO_EXTENSION);
        return strtolower($extension);
    }

    public static function estTypeMimeAutorise(string $typeMime): bool
    {
        return in_array($typeMime, self::TYPES_MIME_AUTORISES, true);
    }

    public static function estTailleValide(int $taille): bool
    {
        return $taille > 0 && $taille <= self::TAILLE_MAX;
    }

    private function validateNomFichier(string $nom): string
    {
        $nom = trim($nom);

        if (empty($nom)) {
            throw new \InvalidArgumentException('Le nom du fichier ne peut pas être vide');
        }

        if (strlen($nom) > 255) {
            throw new \InvalidArgumentException('Le nom du fichier ne peut pas dépasser 255 caractères');
        }

        return $nom;
    }

    private function validateTypeMime(string $typeMime): string
    {
        if (!self::estTypeMimeAutorise($typeMime)) {
            throw new \InvalidArgumentException(
                sprintf('Le type MIME "%s" n\'est pas autorisé. Types autorisés: PDF, JPG, PNG, DOCX, XLSX, DOC, XLS',
                    $typeMime
                )
            );
        }

        return $typeMime;
    }

    private function validateTailleFichier(int $taille): int
    {
        if (!self::estTailleValide($taille)) {
            throw new \InvalidArgumentException(
                sprintf('La taille du fichier doit être entre 1 octet et %s Mo',
                    round(self::TAILLE_MAX / 1048576, 2)
                )
            );
        }

        return $taille;
    }

    private function genererNomFichierUnique(string $nomOriginal): string
    {
        $extension = pathinfo($nomOriginal, PATHINFO_EXTENSION);
        $nomSansExtension = pathinfo($nomOriginal, PATHINFO_FILENAME);

        // Nettoyer le nom de fichier
        $nomSansExtension = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nomSansExtension);

        // Générer un nom unique avec timestamp et UUID
        $timestamp = (new \DateTimeImmutable())->format('Ymd_His');
        $uuid = substr(Uuid::v4()->__toString(), 0, 8);

        return sprintf('%s_%s_%s.%s', $nomSansExtension, $timestamp, $uuid, $extension);
    }
}
