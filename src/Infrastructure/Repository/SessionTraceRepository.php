<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\SessionTrace;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\SessionTraceRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SessionTraceRepository extends ServiceEntityRepository implements SessionTraceRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SessionTrace::class);
    }

    public function upsert(Utilisateur $utilisateur, string $sessionId): void
    {
        $em = $this->getEntityManager();
        /** @var SessionTrace|null $trace */
        $trace = $this->findOneBy(['sessionId' => $sessionId]);

        if (!$trace) {
            $trace = new SessionTrace($utilisateur, $sessionId);
            $em->persist($trace);
        } else {
            $trace->rafraichir();
        }

        $em->flush();
    }

    public function countActiveSince(\DateTimeImmutable $threshold): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.derniereActivite >= :th')
            ->setParameter('th', $threshold)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findActiveSince(\DateTimeImmutable $threshold): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.utilisateur', 'u')
            ->addSelect('u')
            ->where('s.derniereActivite >= :th')
            ->setParameter('th', $threshold)
            ->orderBy('s.derniereActivite', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function removeOlderThan(\DateTimeImmutable $threshold): int
    {
        return $this->createQueryBuilder('s')
            ->delete()
            ->where('s.derniereActivite < :th')
            ->setParameter('th', $threshold)
            ->getQuery()
            ->execute();
    }
}
