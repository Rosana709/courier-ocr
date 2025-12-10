<?php

declare(strict_types=1);

namespace App\Application\UseCase\Notification;

use App\Domain\Entity\Service;
use App\Domain\Repository\NotificationRepositoryInterface;
use App\Domain\Repository\ServiceRepositoryInterface;

class ListNotificationsUseCase
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
        private readonly ServiceRepositoryInterface $serviceRepository
    ) {
    }

    /**
     * Liste toutes les notifications d'un service
     */
    public function executeByService(string $serviceId): array
    {
        $service = $this->serviceRepository->findById($serviceId);

        if (!$service) {
            throw new \InvalidArgumentException("Service non trouvé");
        }

        return $this->notificationRepository->findByService($service);
    }

    /**
     * Liste les notifications non lues d'un service
     */
    public function executeNonLuesByService(string $serviceId): array
    {
        $service = $this->serviceRepository->findById($serviceId);

        if (!$service) {
            throw new \InvalidArgumentException("Service non trouvé");
        }

        return $this->notificationRepository->findNonLuesByService($service);
    }

    /**
     * Compte les notifications non lues d'un service
     */
    public function countNonLuesByService(string $serviceId): int
    {
        $service = $this->serviceRepository->findById($serviceId);

        if (!$service) {
            throw new \InvalidArgumentException("Service non trouvé");
        }

        return $this->notificationRepository->countNonLuesByService($service);
    }
}
