<?php

declare(strict_types=1);

namespace App\Application\DTO;

class UpdateServiceDTO
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $nom = null,
        public readonly ?string $localite = null,
        public readonly ?string $mail = null,
        public readonly ?string $adresse = null,
        public readonly ?string $sigle = null
    ) {
    }
}
