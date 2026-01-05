<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Courrier;
use App\Domain\Entity\PersonneExterne;
use App\Domain\Entity\Service;
use App\Domain\Repository\CourrierRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CourrierRepository extends ServiceEntityRepository implements CourrierRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Courrier::class);
    }

    public function findById(string $id): ?Courrier
    {
        return $this->find($id);
    }

    public function findByNumeroReference(string $numero): ?Courrier
    {
        return $this->findOneBy(['numeroReference' => $numero]);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['dateEnregistrement' => 'DESC']);
    }

    public function save(Courrier $courrier): void
    {
        $this->getEntityManager()->persist($courrier);
        $this->getEntityManager()->flush();
    }

    public function delete(Courrier $courrier): void
    {
        $this->getEntityManager()->remove($courrier);
        $this->getEntityManager()->flush();
    }

    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type], ['dateEnregistrement' => 'DESC']);
    }

    public function findEntrants(): array
    {
        return $this->findByType(Courrier::TYPE_ENTRANT);
    }

    public function findSortants(): array
    {
        return $this->findByType(Courrier::TYPE_SORTANT);
    }

    public function findByServiceExpediteur(Service $service): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.typeExpediteur = :typeService')
            ->andWhere('c.serviceExpediteur = :service')
            ->setParameter('typeService', Courrier::ACTEUR_SERVICE)
            ->setParameter('service', $service)
            ->orderBy('c.dateEnregistrement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByServiceDestinataire(Service $service): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.typeDestinataire = :typeService')
            ->andWhere('c.serviceDestinataire = :service')
            ->setParameter('typeService', Courrier::ACTEUR_SERVICE)
            ->setParameter('service', $service)
            ->orderBy('c.dateEnregistrement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByServiceCopie(Service $service): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.destinatairesCopie', 'cc')
            ->where('cc = :service')
            ->setParameter('service', $service)
            ->orderBy('c.dateEnregistrement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByServiceImplique(Service $service): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.destinatairesCopie', 'cc')
            ->where('(c.typeExpediteur = :typeService AND c.serviceExpediteur = :service)')
            ->orWhere('(c.typeDestinataire = :typeService AND c.serviceDestinataire = :service)')
            ->orWhere('cc = :service')
            ->setParameter('typeService', Courrier::ACTEUR_SERVICE)
            ->setParameter('service', $service)
            ->orderBy('c.dateEnregistrement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByPersonneExterne(PersonneExterne $personne): array
    {
        return $this->createQueryBuilder('c')
            ->where('(c.typeExpediteur = :typePersonne AND c.personneExterneExpediteur = :personne)')
            ->orWhere('(c.typeDestinataire = :typePersonne AND c.personneExterneDestinataire = :personne)')
            ->setParameter('typePersonne', Courrier::ACTEUR_PERSONNE_EXTERNE)
            ->setParameter('personne', $personne)
            ->orderBy('c.dateEnregistrement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatut(string $statut): array
    {
        return $this->findBy(['statut' => $statut], ['dateEnregistrement' => 'DESC']);
    }

    public function findEnAttente(): array
    {
        return $this->findByStatut(Courrier::STATUT_EN_ATTENTE);
    }

    public function findEnAttenteAccuseReception(): array
    {
        return $this->findByStatut(Courrier::STATUT_EN_ATTENTE_ACCUSE_RECEPTION);
    }

    public function findByFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('c');

        if (isset($filters['type'])) {
            $qb->andWhere('c.type = :type')
                ->setParameter('type', $filters['type']);
        }

        if (isset($filters['statut'])) {
            $qb->andWhere('c.statut = :statut')
                ->setParameter('statut', $filters['statut']);
        }

        if (isset($filters['priorite'])) {
            $qb->andWhere('c.priorite = :priorite')
                ->setParameter('priorite', $filters['priorite']);
        }

        if (isset($filters['serviceId'])) {
            $qb->leftJoin('c.destinatairesCopie', 'cc')
                ->andWhere(
                    $qb->expr()->orX(
                        'c.serviceExpediteur = :serviceId',
                        'c.serviceDestinataire = :serviceId',
                        'cc.id = :serviceId'
                    )
                )
                ->setParameter('serviceId', $filters['serviceId']);
        }

        if (isset($filters['dateDebut'])) {
            $qb->andWhere('c.dateCourrier >= :dateDebut')
                ->setParameter('dateDebut', $filters['dateDebut']);
        }

        if (isset($filters['dateFin'])) {
            $qb->andWhere('c.dateCourrier <= :dateFin')
                ->setParameter('dateFin', $filters['dateFin']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'c.objet LIKE :search',
                    'c.contenu LIKE :search',
                    'c.numeroReference LIKE :search'
                )
            )
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb->orderBy('c.dateEnregistrement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.dateCourrier >= :debut')
            ->andWhere('c.dateCourrier <= :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->orderBy('c.dateCourrier', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getProchainNumero(Service $service, int $annee): int
    {
        $dateDebut = new \DateTimeImmutable($annee . '-01-01 00:00:00');
        $dateFin = new \DateTimeImmutable($annee . '-12-31 23:59:59');

        $result = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.serviceExpediteur = :service')
            ->andWhere('c.dateEnregistrement >= :dateDebut')
            ->andWhere('c.dateEnregistrement <= :dateFin')
            ->setParameter('service', $service)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $result) + 1;
    }

    public function existsByNumeroReference(string $numero): bool
    {
        return $this->count(['numeroReference' => $numero]) > 0;
    }

    public function countByService(Service $service): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->leftJoin('c.destinatairesCopie', 'cc')
            ->where('c.serviceExpediteur = :service')
            ->orWhere('c.serviceDestinataire = :service')
            ->orWhere('cc = :service')
            ->setParameter('service', $service)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByServiceAndAnnee(Service $service, int $annee): int
    {
        $dateDebut = new \DateTimeImmutable($annee . '-01-01 00:00:00');
        $dateFin = new \DateTimeImmutable($annee . '-12-31 23:59:59');

        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.serviceExpediteur = :service')
            ->andWhere('c.dateEnregistrement >= :dateDebut')
            ->andWhere('c.dateEnregistrement <= :dateFin')
            ->setParameter('service', $service)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByServiceExpediteur(Service $service): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.serviceExpediteur = :service')
            ->setParameter('service', $service)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les courriers entrants reçus par un service dans une année donnée
     */
    public function countArriveesByServiceAndAnnee(Service $service, int $annee): int
    {
        $dateDebut = new \DateTimeImmutable($annee . '-01-01 00:00:00');
        $dateFin = new \DateTimeImmutable($annee . '-12-31 23:59:59');

        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.type = :type')
            ->andWhere('c.serviceDestinataire = :service')
            ->andWhere('c.dateEnregistrement >= :dateDebut')
            ->andWhere('c.dateEnregistrement <= :dateFin')
            ->setParameter('type', Courrier::TYPE_ENTRANT)
            ->setParameter('service', $service)
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countByStatut(string $statut): int
    {
        return $this->count(['statut' => $statut]);
    }

    public function countByType(string $type): int
    {
        return $this->count(['type' => $type]);
    }

    public function countByPriorite(string $priorite): int
    {
        return $this->count(['priorite' => $priorite]);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByIdWithRelations(string $id): ?Courrier
    {
        return $this->createQueryBuilder('c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->leftJoin('c.serviceExpediteur', 'se')
            ->leftJoin('c.personneExterneExpediteur', 'pee')
            ->leftJoin('c.serviceDestinataire', 'sd')
            ->leftJoin('c.personneExterneDestinataire', 'ped')
            ->leftJoin('c.destinatairesCopie', 'cc')
            ->leftJoin('c.creeParUtilisateur', 'u')
            ->addSelect('se', 'pee', 'sd', 'ped', 'cc', 'u')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.dateEnregistrement', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRecentByService(Service $service, int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.destinatairesCopie', 'cc')
            ->where('c.serviceExpediteur = :service')
            ->orWhere('c.serviceDestinataire = :service')
            ->orWhere('cc = :service')
            ->setParameter('service', $service)
            ->orderBy('c.dateEnregistrement', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findUrgents(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.priorite = :priorite')
            ->andWhere('c.statut NOT IN (:statutsFinis)')
            ->setParameter('priorite', Courrier::PRIORITE_URGENTE)
            ->setParameter('statutsFinis', [Courrier::STATUT_CLOS, Courrier::STATUT_ARCHIVE])
            ->orderBy('c.dateEnregistrement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUrgentsByService(Service $service): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.destinatairesCopie', 'cc')
            ->where('c.priorite = :priorite')
            ->andWhere('c.statut NOT IN (:statutsFinis)')
            ->andWhere(
                '(c.serviceExpediteur = :service OR c.serviceDestinataire = :service OR cc = :service)'
            )
            ->setParameter('priorite', Courrier::PRIORITE_URGENTE)
            ->setParameter('statutsFinis', [Courrier::STATUT_CLOS, Courrier::STATUT_ARCHIVE])
            ->setParameter('service', $service)
            ->orderBy('c.dateEnregistrement', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
