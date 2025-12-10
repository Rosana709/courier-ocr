<?php

declare(strict_types=1);

namespace App\Application\UseCase\Utilisateur;

use App\Domain\Entity\Utilisateur;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\UtilisateurRepositoryInterface;

class GetUtilisateurUseCase
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurRepository
    ) {
    }

    public function execute(string $utilisateurId): Utilisateur
    {
        $utilisateur = $this->utilisateurRepository->findById($utilisateurId);

        if ($utilisateur === null) {
            throw new EntityNotFoundException('Utilisateur non trouvé');
        }

        return $utilisateur;
    }

    public function getAll(): array
    {
        return $this->utilisateurRepository->findAll();
    }

    public function getActifs(): array
    {
        return $this->utilisateurRepository->findActifs();
    }
}
