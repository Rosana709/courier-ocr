<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Service;
use App\Domain\Repository\ServiceRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServiceRepository extends ServiceEntityRepository implements ServiceRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function save(Service $service): void
    {
        $this->getEntityManager()->persist($service);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?Service
    {
        return $this->find($id);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['nom' => 'ASC']);
    }

    public function findActiveServices(): array
    {
        return $this->findBy(['estActif' => true], ['nom' => 'ASC']);
    }

    public function findActifs(): array
    {
        return $this->findActiveServices();
    }

    public function delete(Service $service): void
    {
        $this->getEntityManager()->remove($service);
        $this->getEntityManager()->flush();
    }
}