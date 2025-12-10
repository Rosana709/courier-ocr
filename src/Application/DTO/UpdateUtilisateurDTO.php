<?php

declare(strict_types=1);

namespace App\Application\DTO;

class UpdateUtilisateurDTO
{
    public function __construct(
        public readonly string $utilisateurId,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
        public readonly ?string $serviceId = null,
        public readonly ?bool $estActif = null
    ) {
    }
}
