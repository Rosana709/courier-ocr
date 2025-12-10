<?php

declare(strict_types=1);

namespace App\Infrastructure\Command;

use App\Domain\Entity\Service;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:generate-test-data',
    description: 'Génère 10 services et 10 utilisateurs pour les tests'
)]
class GenerateTestDataCommand extends Command
{
    private array $servicesData = [
        ['id' => 'DGE', 'nom' => 'Direction des Grandes Entreprises', 'sigle' => 'DGE', 'localite' => 'Antananarivo', 'mail' => 'dge@impots.mg'],
        ['id' => 'DME', 'nom' => 'Direction des Moyennes Entreprises', 'sigle' => 'DME', 'localite' => 'Antananarivo', 'mail' => 'dme@impots.mg'],
        ['id' => 'DPE', 'nom' => 'Direction des Petites Entreprises', 'sigle' => 'DPE', 'localite' => 'Antananarivo', 'mail' => 'dpe@impots.mg'],
        ['id' => 'DPFI', 'nom' => 'Direction de la Programmation, du Fichier et de l\'Informatique', 'sigle' => 'DPFI', 'localite' => 'Antananarivo', 'mail' => 'dpfi@impots.mg'],
        ['id' => 'DREC', 'nom' => 'Direction des Recherches et des Contrôles Fiscaux', 'sigle' => 'DREC', 'localite' => 'Antananarivo', 'mail' => 'drec@impots.mg'],
        ['id' => 'DLC', 'nom' => 'Direction de la Législation et du Contentieux', 'sigle' => 'DLC', 'localite' => 'Antananarivo', 'mail' => 'dlc@impots.mg'],
        ['id' => 'DRH', 'nom' => 'Direction des Ressources Humaines', 'sigle' => 'DRH', 'localite' => 'Antananarivo', 'mail' => 'drh@impots.mg'],
        ['id' => 'DAF', 'nom' => 'Direction Administrative et Financière', 'sigle' => 'DAF', 'localite' => 'Antananarivo', 'mail' => 'daf@impots.mg'],
        ['id' => 'SPI_IHO', 'nom' => 'Service Provincial des Impôts - Ihosy', 'sigle' => 'SPI-IHO', 'localite' => 'Ihosy', 'mail' => 'spi.ihosy@impots.mg'],
        ['id' => 'SPI_FIA', 'nom' => 'Service Provincial des Impôts - Fianarantsoa', 'sigle' => 'SPI-FIA', 'localite' => 'Fianarantsoa', 'mail' => 'spi.fianarantsoa@impots.mg'],
    ];

    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly UtilisateurRepositoryInterface $utilisateurRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Génération des données de test');

        // Création des services
        $io->section('Création des services');
        $servicesCreated = 0;

        foreach ($this->servicesData as $data) {
            // Vérifier si le service existe déjà
            if ($this->serviceRepository->findById($data['id']) !== null) {
                $io->warning("Le service {$data['id']} existe déjà, ignoré");
                continue;
            }

            $service = new Service($data['id'], $data['nom']);
            $service->setSigle($data['sigle']);
            $service->setLocalite($data['localite']);
            $service->setMail($data['mail']);
            $service->setEstActif(true);

            $this->serviceRepository->save($service);
            $servicesCreated++;
            $io->success("Service créé: {$data['nom']} ({$data['id']})");
        }

        $io->info("Total services créés: $servicesCreated");

        // Création des utilisateurs
        $io->section('Création des utilisateurs');
        $utilisateursCreated = 0;

        foreach ($this->servicesData as $data) {
            $service = $this->serviceRepository->findById($data['id']);
            if ($service === null) {
                continue;
            }

            $email = $data['mail'];

            // Vérifier si l'utilisateur existe déjà
            if ($this->utilisateurRepository->findByEmail($email) !== null) {
                $io->warning("L'utilisateur $email existe déjà, ignoré");
                continue;
            }

            // Créer l'utilisateur avec un mot de passe temporaire
            $utilisateur = Utilisateur::creerUtilisateurService(
                $email,
                'temp', // Temporaire
                $service
            );

            // Hasher le mot de passe réel
            $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, 'dgi2025');
            $utilisateur->updatePassword($hashedPassword);

            $this->utilisateurRepository->save($utilisateur);
            $utilisateursCreated++;
            $io->success("Utilisateur créé: $email (Service: {$service->getNom()})");
        }

        $io->info("Total utilisateurs créés: $utilisateursCreated");

        $io->success([
            'Génération terminée!',
            "Services créés: $servicesCreated",
            "Utilisateurs créés: $utilisateursCreated",
            'Mot de passe par défaut: dgi2025'
        ]);

        return Command::SUCCESS;
    }
}
