<?php

declare(strict_types=1);

namespace App\Application\UseCase\Service;

use App\Domain\Exception\DomainException;
use App\Domain\Repository\ServiceRepositoryInterface;

class DeleteServiceUseCase
{
    public function __construct(
        private readonly \App\Domain\Repository\ServiceRepositoryInterface $serviceRepository,
        private readonly \App\Domain\Repository\UtilisateurRepositoryInterface $utilisateurRepository
    ) {
    }

    public function execute(string $id): void
    {
        $service = $this->serviceRepository->findById($id);

        if (!$service) {
            throw new \App\Domain\Exception\EntityNotFoundException('Service non trouvé: ' . $id);
        }

        // Dissocier les utilisateurs liés à ce service avant la suppression
        $utilisateurs = $this->utilisateurRepository->findAll(); // C'est un peu lourd, mais on va filtrer
        foreach ($utilisateurs as $utilisateur) {
            if ($utilisateur->getService() && $utilisateur->getService()->getId() === $id) {
                $utilisateur->updateService(null);
                $this->utilisateurRepository->save($utilisateur);
            }
        }

        $this->serviceRepository->delete($service);
    }
}
