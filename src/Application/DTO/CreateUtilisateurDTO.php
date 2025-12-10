<?php

declare(strict_types=1);

namespace App\Application\DTO;

class CreateUtilisateurDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $role,
        public readonly ?string $serviceId = null,
        public readonly string $password = 'dgi2025'
    ) {
    }
}
