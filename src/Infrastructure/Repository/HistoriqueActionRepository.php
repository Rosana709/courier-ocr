<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Courrier;
use App\Domain\Entity\HistoriqueAction;
use App\Domain\Repository\HistoriqueActionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HistoriqueActionRepository extends ServiceEntityRepository implements HistoriqueActionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoriqueAction::class);
    }

    public function find($id, $lockMode = null, $lockVersion = null): ?HistoriqueAction
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function findByCourrier(Courrier $courrier): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.courrier = :courrier')
            ->setParameter('courrier', $courrier)
            ->leftJoin('h.effectuePar', 'u')
            ->addSelect('u')
            ->orderBy('h.dateAction', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLast(int $limit = 5): array
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.courrier', 'c')
            ->addSelect('c')
            ->leftJoin('h.effectuePar', 'u')
            ->addSelect('u')
            ->orderBy('h.dateAction', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countSince(\DateTimeImmutable $date): int
    {
        return (int) $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->where('h.dateAction > :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(HistoriqueAction $action): void
    {
        $this->getEntityManager()->persist($action);
        $this->getEntityManager()->flush();
    }
}
