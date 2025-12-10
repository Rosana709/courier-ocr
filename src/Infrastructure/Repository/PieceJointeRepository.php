<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Courrier;
use App\Domain\Entity\PieceJointe;
use App\Domain\Repository\PieceJointeRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PieceJointeRepository extends ServiceEntityRepository implements PieceJointeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PieceJointe::class);
    }

    public function findById(string $id): ?PieceJointe
    {
        return $this->find($id);
    }

    public function findByCourrier(Courrier $courrier): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.courrier = :courrier')
            ->setParameter('courrier', $courrier)
            ->leftJoin('p.telechargeParUtilisateur', 'u')
            ->addSelect('u')
            ->orderBy('p.dateTelechargement', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(PieceJointe $piece): void
    {
        $this->getEntityManager()->persist($piece);
        $this->getEntityManager()->flush();
    }

    public function delete(PieceJointe $piece): void
    {
        $this->getEntityManager()->remove($piece);
        $this->getEntityManager()->flush();
    }

    public function countByCourrier(Courrier $courrier): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.courrier = :courrier')
            ->setParameter('courrier', $courrier)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
