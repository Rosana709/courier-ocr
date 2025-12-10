<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Notification;
use App\Domain\Entity\Service;
use App\Domain\Repository\NotificationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository implements NotificationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findById(string $id): ?Notification
    {
        return $this->find($id);
    }

    public function findByService(Service $service): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.service = :service')
            ->setParameter('service', $service)
            ->leftJoin('n.courrier', 'c')
            ->addSelect('c')
            ->orderBy('n.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findNonLuesByService(Service $service): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.service = :service')
            ->andWhere('n.estLue = :estLue')
            ->setParameter('service', $service)
            ->setParameter('estLue', false)
            ->leftJoin('n.courrier', 'c')
            ->addSelect('c')
            ->orderBy('n.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countNonLuesByService(Service $service): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.service = :service')
            ->andWhere('n.estLue = :estLue')
            ->setParameter('service', $service)
            ->setParameter('estLue', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Notification $notification): void
    {
        $this->getEntityManager()->persist($notification);
        $this->getEntityManager()->flush();
    }

    public function delete(Notification $notification): void
    {
        $this->getEntityManager()->remove($notification);
        $this->getEntityManager()->flush();
    }

    public function marquerCommeLue(Notification $notification): void
    {
        $notification->marquerCommeLue();
        $this->getEntityManager()->flush();
    }

    public function marquerToutesCommeLues(Service $service): void
    {
        $notifications = $this->findNonLuesByService($service);

        foreach ($notifications as $notification) {
            $notification->marquerCommeLue();
        }

        $this->getEntityManager()->flush();
    }
}
