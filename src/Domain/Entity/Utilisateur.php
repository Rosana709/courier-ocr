<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'utilisateur')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_SERVICE = 'ROLE_SERVICE';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private string $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(name: 'service_id', referencedColumnName: 'id', nullable: true)]
    private ?Service $service = null;

    #[ORM\Column(type: 'boolean', name: 'estactif')]
    private bool $estActif = true;

    #[ORM\Column(type: 'datetime_immutable', name: 'datecreation')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(type: 'datetime_immutable', nullable: true, name: 'datemodification')]
    private ?\DateTimeImmutable $dateModification = null;

    private function __construct(
        string $email,
        string $password,
        array $roles,
        ?Service $service = null
    ) {
        $this->id = Uuid::v4()->__toString();
        $this->email = $this->validateEmail($email);
        $this->password = $password;
        $this->roles = $this->validateRoles($roles);
        $this->service = $service;
        $this->estActif = true;
        $this->dateCreation = new \DateTimeImmutable();
    }

    public static function creerAdmin(string $email, string $password): self
    {
        return new self($email, $password, [self::ROLE_ADMIN], null);
    }

    public static function creerUtilisateurService(
        string $email,
        string $password,
        Service $service
    ): self {
        if (!$service->isEstActif()) {
            throw new \DomainException('Le service doit être actif pour créer un utilisateur');
        }

        return new self($email, $password, [self::ROLE_SERVICE], $service);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function updateEmail(string $email): void
    {
        $this->email = $this->validateEmail($email);
        $this->dateModification = new \DateTimeImmutable();
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function updatePassword(string $password): void
    {
        if (empty($password)) {
            throw new \InvalidArgumentException('Le mot de passe ne peut pas être vide');
        }

        $this->password = $password;
        $this->dateModification = new \DateTimeImmutable();
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Garantir que chaque utilisateur a au moins ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $this->validateRoles($roles);
        $this->dateModification = new \DateTimeImmutable();
    }

    public function isAdmin(): bool
    {
        return in_array(self::ROLE_ADMIN, $this->roles, true);
    }

    public function isServiceUser(): bool
    {
        return in_array(self::ROLE_SERVICE, $this->roles, true);
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function updateService(?Service $service): void
    {
        if ($this->isAdmin() && $service !== null) {
            throw new \DomainException('Un administrateur ne peut pas être lié à un service');
        }

        if ($this->isServiceUser() && $service === null) {
            throw new \DomainException('Un utilisateur de service doit être lié à un service');
        }

        if ($service !== null && !$service->isEstActif()) {
            throw new \DomainException('Le service doit être actif');
        }

        $this->service = $service;
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

    // Méthodes UserInterface
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function eraseCredentials(): void
    {
        // Si vous stockez des données sensibles temporaires, effacez-les ici
    }

    // Méthodes de validation
    private function validateEmail(string $email): string
    {
        $email = trim($email);

        if (empty($email)) {
            throw new \InvalidArgumentException('L\'email ne peut pas être vide');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L\'email n\'est pas valide');
        }

        if (strlen($email) > 180) {
            throw new \InvalidArgumentException('L\'email ne peut pas dépasser 180 caractères');
        }

        return $email;
    }

    private function validateRoles(array $roles): array
    {
        if (empty($roles)) {
            throw new \InvalidArgumentException('Au moins un rôle doit être spécifié');
        }

        $validRoles = [self::ROLE_ADMIN, self::ROLE_SERVICE];

        foreach ($roles as $role) {
            if (!in_array($role, $validRoles, true)) {
                throw new \InvalidArgumentException(sprintf('Le rôle "%s" n\'est pas valide', $role));
            }
        }

        // Vérifier qu'il n'y a pas à la fois ROLE_ADMIN et ROLE_SERVICE
        if (in_array(self::ROLE_ADMIN, $roles, true) && in_array(self::ROLE_SERVICE, $roles, true)) {
            throw new \InvalidArgumentException('Un utilisateur ne peut pas avoir à la fois ROLE_ADMIN et ROLE_SERVICE');
        }

        return array_unique($roles);
    }
}
