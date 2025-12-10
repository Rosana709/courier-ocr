<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Service;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UtilisateurRepository extends ServiceEntityRepository implements UtilisateurRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function findById(string $id): ?Utilisateur
    {
        return $this->find($id);
    }

    public function findByEmail(string $email): ?Utilisateur
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['dateCreation' => 'DESC']);
    }

    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"' . $role . '"%')
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByService(Service $service): ?Utilisateur
    {
        return $this->findOneBy(['service' => $service]);
    }

    public function findActifs(): array
    {
        return $this->findBy(['estActif' => true], ['email' => 'ASC']);
    }

    public function save(Utilisateur $utilisateur): void
    {
        $this->getEntityManager()->persist($utilisateur);
        $this->getEntityManager()->flush();
    }

    public function delete(Utilisateur $utilisateur): void
    {
        $this->getEntityManager()->remove($utilisateur);
        $this->getEntityManager()->flush();
    }

    public function existsByEmail(string $email): bool
    {
        return $this->count(['email' => $email]) > 0;
    }
}
