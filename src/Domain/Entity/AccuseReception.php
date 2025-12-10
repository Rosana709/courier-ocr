<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'accuse_reception')]
class AccuseReception
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private string $id;

    #[ORM\OneToOne(targetEntity: Courrier::class)]
    #[ORM\JoinColumn(name: 'courrier_id', referencedColumnName: 'id', nullable: false, unique: true, onDelete: 'CASCADE')]
    private Courrier $courrier;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(name: 'servicerecepteurid', referencedColumnName: 'id', nullable: false)]
    private Service $serviceRecepteur;

    #[ORM\Column(type: 'datetime_immutable', name: 'datereceptionphysique')]
    private \DateTimeImmutable $dateReceptionPhysique;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'confirmeparid', referencedColumnName: 'id', nullable: false)]
    private Utilisateur $confirmeParUtilisateur;

    #[ORM\Column(type: 'datetime_immutable', name: 'dateconfirmation')]
    private \DateTimeImmutable $dateConfirmation;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $commentaire = null;

    public function __construct(
        Courrier $courrier,
        Service $serviceRecepteur,
        \DateTimeImmutable $dateReceptionPhysique,
        Utilisateur $confirmeParUtilisateur,
        ?string $commentaire = null
    ) {
        if (!$courrier->estInterne()) {
            throw new \DomainException('L\'accusé de réception ne peut être créé que pour un courrier interne');
        }

        $this->id = Uuid::v4()->__toString();
        $this->courrier = $courrier;
        $this->serviceRecepteur = $serviceRecepteur;
        $this->dateReceptionPhysique = $dateReceptionPhysique;
        $this->confirmeParUtilisateur = $confirmeParUtilisateur;
        $this->dateConfirmation = new \DateTimeImmutable();
        $this->commentaire = $commentaire;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCourrier(): Courrier
    {
        return $this->courrier;
    }

    public function getServiceRecepteur(): Service
    {
        return $this->serviceRecepteur;
    }

    public function getDateReceptionPhysique(): \DateTimeImmutable
    {
        return $this->dateReceptionPhysique;
    }

    public function updateDateReceptionPhysique(\DateTimeImmutable $date): void
    {
        $this->dateReceptionPhysique = $date;
    }

    public function getConfirmeParUtilisateur(): Utilisateur
    {
        return $this->confirmeParUtilisateur;
    }

    public function getDateConfirmation(): \DateTimeImmutable
    {
        return $this->dateConfirmation;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function updateCommentaire(?string $commentaire): void
    {
        $this->commentaire = $commentaire;
    }

    public function getDelaiReception(): \DateInterval
    {
        return $this->courrier->getDateEnregistrement()->diff($this->dateReceptionPhysique);
    }

    public function getDelaiConfirmation(): \DateInterval
    {
        return $this->dateReceptionPhysique->diff($this->dateConfirmation);
    }
}
