<?php

declare(strict_types=1);

namespace App\Application\UseCase\Utilisateur;

use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\UtilisateurRepositoryInterface;

class DeleteUtilisateurUseCase
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurRepository
    ) {
    }

    public function execute(string $utilisateurId): void
    {
        $utilisateur = $this->utilisateurRepository->findById($utilisateurId);

        if ($utilisateur === null) {
            throw new EntityNotFoundException('Utilisateur non trouvé');
        }

        $this->utilisateurRepository->delete($utilisateur);
    }
}
