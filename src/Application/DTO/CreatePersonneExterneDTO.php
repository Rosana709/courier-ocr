<?php

declare(strict_types=1);

namespace App\Application\DTO;

class CreatePersonneExterneDTO
{
    public function __construct(
        public readonly string $nomOuRaisonSociale,
        public readonly string $type,
        public readonly ?string $adressePostale = null,
        public readonly ?string $telephone = null,
        public readonly ?string $email = null
    ) {
    }
}
