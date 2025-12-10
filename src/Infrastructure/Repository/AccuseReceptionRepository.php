<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\AccuseReception;
use App\Domain\Entity\Courrier;
use App\Domain\Repository\AccuseReceptionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AccuseReceptionRepository extends ServiceEntityRepository implements AccuseReceptionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccuseReception::class);
    }

    public function findByCourrier(Courrier $courrier): ?AccuseReception
    {
        return $this->createQueryBuilder('a')
            ->where('a.courrier = :courrier')
            ->setParameter('courrier', $courrier)
            ->leftJoin('a.serviceRecepteur', 's')
            ->leftJoin('a.confirmeParUtilisateur', 'u')
            ->addSelect('s', 'u')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(AccuseReception $accuse): void
    {
        $this->getEntityManager()->persist($accuse);
        $this->getEntityManager()->flush();
    }

    public function existsForCourrier(Courrier $courrier): bool
    {
        return $this->count(['courrier' => $courrier]) > 0;
    }
}
