<?php

declare(strict_types=1);

namespace App\Infrastructure\Command;

use App\Application\DTO\CreateUtilisateurDTO;
use App\Application\UseCase\Utilisateur\CreateUtilisateurUseCase;
use App\Domain\Entity\Service;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-service-users',
    description: 'Cree un utilisateur de service pour chaque service present dans la base'
)]
class CreateServiceUsersCommand extends Command
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly UtilisateurRepositoryInterface $utilisateurRepository,
        private readonly CreateUtilisateurUseCase $createUtilisateurUseCase
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Mot de passe par defaut', 'dgi2025')
            ->addOption('only-active', null, InputOption::VALUE_NONE, 'Ne cree que pour les services actifs');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Creation des utilisateurs pour chaque service');

        $password = (string) $input->getOption('password');
        $onlyActive = (bool) $input->getOption('only-active');

        $services = $onlyActive
            ? $this->serviceRepository->findActifs()
            : $this->serviceRepository->findAll();

        if (count($services) === 0) {
            $io->warning('Aucun service trouve.');
            return Command::SUCCESS;
        }

        $created = 0;
        $skipped = 0;
        foreach ($services as $service) {
            $existingUser = $this->utilisateurRepository->findByService($service);
            if ($existingUser !== null) {
                $skipped++;
                $io->text(sprintf('SKIP %s : deja lie a %s', $service->getNom(), $existingUser->getEmail()));
                continue;
            }

            $email = $service->getMail();
            if ($email === null || trim($email) === '') {
                $email = $this->buildEmailForService($service);
            }

            try {
                $dto = new CreateUtilisateurDTO(
                    email: $email,
                    role: Utilisateur::ROLE_SERVICE,
                    serviceId: $service->getId(),
                    password: $password
                );

                $this->createUtilisateurUseCase->execute($dto);
                $created++;
                $io->text(sprintf('OK %s -> %s', $service->getNom(), $email));
            } catch (\Throwable $e) {
                $io->error(sprintf('Erreur pour %s : %s', $service->getNom(), $e->getMessage()));
            }
        }

        $io->success(sprintf('Termine : %d crees, %d ignores.', $created, $skipped));

        return Command::SUCCESS;
    }

    private function buildEmailForService(Service $service): string
    {
        $base = $service->getSigle() ?? $service->getNom() ?? $service->getId();
        $slug = $this->slugify((string) $base);

        if ($slug === '') {
            $slug = 'service-' . strtolower($service->getId());
        }

        return sprintf('%s@dgi.gov', $slug);
    }

    private function slugify(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        $value = strtolower((string) $value);
        $value = preg_replace('/[^a-z0-9]+/', '.', $value) ?? '';
        return trim($value, '.');
    }
}
