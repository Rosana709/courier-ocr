<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\PersonneExterne;
use App\Domain\Repository\PersonneExterneRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PersonneExterneRepository extends ServiceEntityRepository implements PersonneExterneRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonneExterne::class);
    }

    public function findById(string $id): ?PersonneExterne
    {
        return $this->find($id);
    }

    public function findByNom(string $nom): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.nomOuRaisonSociale LIKE :nom')
            ->setParameter('nom', '%' . $nom . '%')
            ->orderBy('p.nomOuRaisonSociale', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type], ['nomOuRaisonSociale' => 'ASC']);
    }

    public function findActives(): array
    {
        return $this->findBy(['estActif' => true], ['nomOuRaisonSociale' => 'ASC']);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['nomOuRaisonSociale' => 'ASC']);
    }

    public function save(PersonneExterne $personne): void
    {
        $this->getEntityManager()->persist($personne);
        $this->getEntityManager()->flush();
    }

    public function delete(PersonneExterne $personne): void
    {
        $this->getEntityManager()->remove($personne);
        $this->getEntityManager()->flush();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
