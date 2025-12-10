<?php

declare(strict_types=1);

namespace App\Application\UseCase\Notification;

use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\NotificationRepositoryInterface;

class MarquerNotificationCommeLueUseCase
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository
    ) {
    }

    public function execute(string $notificationId): void
    {
        $notification = $this->notificationRepository->findById($notificationId);

        if (!$notification) {
            throw new EntityNotFoundException("Notification non trouvée");
        }

        $notification->marquerCommeLue();
        $this->notificationRepository->save($notification);
    }

    /**
     * Marque toutes les notifications d'un service comme lues
     */
    public function executeAllByService(\App\Domain\Entity\Service $service): void
    {
        $this->notificationRepository->marquerToutesCommeLues($service);
    }
}
