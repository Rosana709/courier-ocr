<?php

declare(strict_types=1);

namespace App\Infrastructure\Command;

use App\Application\DTO\CreateUtilisateurDTO;
use App\Application\UseCase\Utilisateur\CreateUtilisateurUseCase;
use App\Domain\Entity\Utilisateur;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur administrateur'
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly CreateUtilisateurUseCase $createUtilisateurUseCase
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Email de l\'administrateur')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Mot de passe de l\'administrateur');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création d\'un utilisateur administrateur');

        // Récupérer ou demander l'email
        $email = $input->getOption('email');
        if (!$email) {
            $question = new Question('Email de l\'administrateur: ');
            $question->setValidator(function ($answer) {
                if (!filter_var($answer, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('L\'email n\'est pas valide');
                }

                return $answer;
            });
            $email = $io->askQuestion($question);
        }

        // Récupérer ou demander le mot de passe
        $password = $input->getOption('password');
        if (!$password) {
            $question = new Question('Mot de passe: ');
            $question->setHidden(true);
            $question->setValidator(function ($answer) {
                if (strlen($answer) < 6) {
                    throw new \RuntimeException('Le mot de passe doit contenir au moins 6 caractères');
                }

                return $answer;
            });
            $password = $io->askQuestion($question);
        }

        try {
            $dto = new CreateUtilisateurDTO(
                email: $email,
                password: $password,
                role: Utilisateur::ROLE_ADMIN
            );

            $utilisateur = $this->createUtilisateurUseCase->execute($dto);

            $io->success(sprintf(
                'Administrateur créé avec succès! Email: %s',
                $utilisateur->getEmail()
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de la création de l\'administrateur: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
