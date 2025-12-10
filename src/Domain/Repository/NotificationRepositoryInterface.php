<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Notification;
use App\Domain\Entity\Service;

interface NotificationRepositoryInterface
{
    public function findById(string $id): ?Notification;

    public function findByService(Service $service): array;

    public function findNonLuesByService(Service $service): array;

    public function countNonLuesByService(Service $service): int;

    public function save(Notification $notification): void;

    public function delete(Notification $notification): void;

    public function marquerCommeLue(Notification $notification): void;

    public function marquerToutesCommeLues(Service $service): void;
}
