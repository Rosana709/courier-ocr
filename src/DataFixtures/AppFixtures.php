<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Entity\Service;
use App\Domain\Entity\Utilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Créer l'utilisateur administrateur principal
        $admin = Utilisateur::creerAdmin('samuelson.andri@gmail.com', 'temp');
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'roottoor');
        $admin->updatePassword($hashedPassword);
        $manager->persist($admin);

        // Créer des services de base
        $services = [
            ['DGE', 'Direction des Grandes Entreprises'],
            ['DME', 'Direction des Moyennes Entreprises'],
            ['DPE', 'Direction des Petites Entreprises'],
            ['DVNI', 'Direction de Vérification Nationale et Internationale'],
            ['DRH', 'Direction des Ressources Humaines'],
        ];

        foreach ($services as [$code, $nom]) {
            $service = new Service($nom, $code);
            $manager->persist($service);
        }

        $manager->flush();
    }
}
