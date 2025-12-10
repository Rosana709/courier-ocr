<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'personne_externe')]
class PersonneExterne
{
    public const TYPE_CONTRIBUABLE = 'CONTRIBUABLE';
    public const TYPE_ENTREPRISE = 'ENTREPRISE';
    public const TYPE_ADMINISTRATION = 'ADMINISTRATION';
    public const TYPE_ORGANISME = 'ORGANISME';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private string $id;

    #[ORM\Column(type: 'string', length: 255, name: 'nomouraisonsociale')]
    private string $nomOuRaisonSociale;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'text', nullable: true, name: 'adressepostale')]
    private ?string $adressePostale = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'string', length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'boolean', name: 'estactif')]
    private bool $estActif = true;

    #[ORM\Column(type: 'datetime_immutable', name: 'datecreation')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'datemodification')]
    private ?\DateTimeImmutable $dateModification = null;

    public function __construct(
        string $nomOuRaisonSociale,
        string $type,
        ?string $adressePostale = null,
        ?string $telephone = null,
        ?string $email = null
    ) {
        $this->id = Uuid::v4()->__toString();
        $this->nomOuRaisonSociale = $this->validateNom($nomOuRaisonSociale);
        $this->type = $this->validateType($type);
        $this->adressePostale = $adressePostale;
        $this->telephone = $telephone;
        $this->email = $email ? $this->validateEmail($email) : null;
        $this->estActif = true;
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNomOuRaisonSociale(): string
    {
        return $this->nomOuRaisonSociale;
    }

    public function updateNomOuRaisonSociale(string $nom): void
    {
        $this->nomOuRaisonSociale = $this->validateNom($nom);
        $this->dateModification = new \DateTimeImmutable();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function updateType(string $type): void
    {
        $this->type = $this->validateType($type);
        $this->dateModification = new \DateTimeImmutable();
    }

    public function getAdressePostale(): ?string
    {
        return $this->adressePostale;
    }

    public function updateAdressePostale(?string $adresse): void
    {
        $this->adressePostale = $adresse;
        $this->dateModification = new \DateTimeImmutable();
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function updateTelephone(?string $telephone): void
    {
        $this->telephone = $telephone;
        $this->dateModification = new \DateTimeImmutable();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function updateEmail(?string $email): void
    {
        $this->email = $email ? $this->validateEmail($email) : null;
        $this->dateModification = new \DateTimeImmutable();
    }

    public function estActif(): bool
    {
        return $this->estActif;
    }

    public function activer(): void
    {
        $this->estActif = true;
        $this->dateModification = new \DateTimeImmutable();
    }

    public function desactiver(): void
    {
        $this->estActif = false;
        $this->dateModification = new \DateTimeImmutable();
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getDateModification(): ?\DateTimeImmutable
    {
        return $this->dateModification;
    }

    public static function getValidTypes(): array
    {
        return [
            self::TYPE_CONTRIBUABLE,
            self::TYPE_ENTREPRISE,
            self::TYPE_ADMINISTRATION,
            self::TYPE_ORGANISME,
        ];
    }

    private function validateNom(string $nom): string
    {
        $nom = trim($nom);

        if (empty($nom)) {
            throw new \InvalidArgumentException('Le nom ou raison sociale ne peut pas être vide');
        }

        if (strlen($nom) > 255) {
            throw new \InvalidArgumentException('Le nom ou raison sociale ne peut pas dépasser 255 caractères');
        }

        return $nom;
    }

    private function validateType(string $type): string
    {
        if (!in_array($type, self::getValidTypes(), true)) {
            throw new \InvalidArgumentException(
                sprintf('Le type "%s" n\'est pas valide. Types valides: %s',
                    $type,
                    implode(', ', self::getValidTypes())
                )
            );
        }

        return $type;
    }

    private function validateEmail(string $email): string
    {
        $email = trim($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L\'email n\'est pas valide');
        }

        if (strlen($email) > 180) {
            throw new \InvalidArgumentException('L\'email ne peut pas dépasser 180 caractères');
        }

        return $email;
    }
}
