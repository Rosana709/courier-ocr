<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'session_trace')]
class SessionTrace
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'utilisateur_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Utilisateur $utilisateur;

    #[ORM\Column(type: 'string', length: 120, unique: true)]
    private string $sessionId;

    #[ORM\Column(type: 'datetime_immutable', name: 'derniere_activite')]
    private \DateTimeImmutable $derniereActivite;

    public function __construct(Utilisateur $utilisateur, string $sessionId)
    {
        $this->id = Uuid::v4()->__toString();
        $this->utilisateur = $utilisateur;
        $this->sessionId = $sessionId;
        $this->derniereActivite = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getDerniereActivite(): \DateTimeImmutable
    {
        return $this->derniereActivite;
    }

    public function rafraichir(): void
    {
        $this->derniereActivite = new \DateTimeImmutable();
    }
}
