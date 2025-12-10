<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'service')]
class Service
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 20)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $nom;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $localite = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $mail = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $sigle = null;

    #[ORM\Column(type: 'boolean', name: 'estactif')]
    private bool $estActif = true;

    public function __construct(string $id, string $nom, bool $estActif = true)
    {
        $this->id = $id;
        $this->nom = $nom;
        $this->estActif = $estActif;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function getLocalite(): ?string
    {
        return $this->localite;
    }

    public function setLocalite(?string $localite): void
    {
        $this->localite = $localite;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(?string $mail): void
    {
        $this->mail = $mail;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): void
    {
        $this->adresse = $adresse;
    }

    public function getSigle(): ?string
    {
        return $this->sigle;
    }

    public function setSigle(?string $sigle): void
    {
        $this->sigle = $sigle;
    }

    public function isEstActif(): bool
    {
        return $this->estActif;
    }

    public function setEstActif(bool $estActif): void
    {
        $this->estActif = $estActif;
    }

    public function activer(): void
    {
        $this->estActif = true;
    }

    public function desactiver(): void
    {
        $this->estActif = false;
    }
}