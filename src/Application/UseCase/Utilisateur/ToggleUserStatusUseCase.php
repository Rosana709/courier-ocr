<?php

declare(strict_types=1);

namespace App\Application\UseCase\Utilisateur;

use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\UtilisateurRepositoryInterface;

class ToggleUserStatusUseCase
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurRepository
    ) {
    }

    public function activate(string $id): Utilisateur
    {
        $utilisateur = $this->utilisateurRepository->findById($id);

        if ($utilisateur === null) {
            throw new \DomainException('Utilisateur non trouvé');
        }

        $utilisateur->activer();
        $this->utilisateurRepository->save($utilisateur);

        return $utilisateur;
    }

    public function deactivate(string $id): Utilisateur
    {
        $utilisateur = $this->utilisateurRepository->findById($id);

        if ($utilisateur === null) {
            throw new \DomainException('Utilisateur non trouvé');
        }

        $utilisateur->desactiver();
        $this->utilisateurRepository->save($utilisateur);

        return $utilisateur;
    }

    public function toggle(string $id): Utilisateur
    {
        $utilisateur = $this->utilisateurRepository->findById($id);

        if ($utilisateur === null) {
            throw new \DomainException('Utilisateur non trouvé');
        }

        if ($utilisateur->estActif()) {
            $utilisateur->desactiver();
        } else {
            $utilisateur->activer();
        }

        $this->utilisateurRepository->save($utilisateur);

        return $utilisateur;
    }
}
