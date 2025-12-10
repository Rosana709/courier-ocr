<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'notification')]
#[ORM\Index(name: 'idx_service_est_lue', columns: ['service_id', 'est_lue'])]
class Notification
{
    public const TYPE_NOUVEAU_COURRIER = 'NOUVEAU_COURRIER';
    public const TYPE_STATUT_CHANGE = 'STATUT_CHANGE';
    public const TYPE_ACCUSE_RECU = 'ACCUSE_RECU';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(name: 'service_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Service $service;

    #[ORM\ManyToOne(targetEntity: Courrier::class)]
    #[ORM\JoinColumn(name: 'courrier_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Courrier $courrier;

    #[ORM\Column(type: 'string', length: 50)]
    private string $type;

    #[ORM\Column(type: 'string', length: 500)]
    private string $message;

    #[ORM\Column(name: 'est_lue', type: 'boolean')]
    private bool $estLue = false;

    #[ORM\Column(name: 'date_creation', type: 'datetime_immutable')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(name: 'date_lecture', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateLecture = null;

    public function __construct(
        Service $service,
        Courrier $courrier,
        string $type,
        string $message
    ) {
        $this->id = Uuid::v4()->__toString();
        $this->service = $service;
        $this->courrier = $courrier;
        $this->type = $this->validateType($type);
        $this->message = $this->validateMessage($message);
        $this->estLue = false;
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getService(): Service
    {
        return $this->service;
    }

    public function getCourrier(): Courrier
    {
        return $this->courrier;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function estLue(): bool
    {
        return $this->estLue;
    }

    public function marquerCommeLue(): void
    {
        if (!$this->estLue) {
            $this->estLue = true;
            $this->dateLecture = new \DateTimeImmutable();
        }
    }

    public function marquerCommeNonLue(): void
    {
        if ($this->estLue) {
            $this->estLue = false;
            $this->dateLecture = null;
        }
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getDateLecture(): ?\DateTimeImmutable
    {
        return $this->dateLecture;
    }

    public function getAge(): \DateInterval
    {
        $maintenant = new \DateTimeImmutable();
        return $this->dateCreation->diff($maintenant);
    }

    public function estRecente(): bool
    {
        $age = $this->getAge();
        return $age->days === 0 && $age->h < 24;
    }

    public static function getValidTypes(): array
    {
        return [
            self::TYPE_NOUVEAU_COURRIER,
            self::TYPE_STATUT_CHANGE,
            self::TYPE_ACCUSE_RECU,
        ];
    }

    private function validateType(string $type): string
    {
        if (!in_array($type, self::getValidTypes(), true)) {
            throw new \InvalidArgumentException(sprintf('Le type de notification "%s" n\'est pas valide', $type));
        }

        return $type;
    }

    private function validateMessage(string $message): string
    {
        $message = trim($message);

        if (empty($message)) {
            throw new \InvalidArgumentException('Le message ne peut pas être vide');
        }

        if (strlen($message) > 500) {
            throw new \InvalidArgumentException('Le message ne peut pas dépasser 500 caractères');
        }

        return $message;
    }
}
