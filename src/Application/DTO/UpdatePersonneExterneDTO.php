<?php

declare(strict_types=1);

namespace App\Application\DTO;

class UpdatePersonneExterneDTO
{
    public function __construct(
        public readonly ?string $nomOuRaisonSociale = null,
        public readonly ?string $type = null,
        public readonly ?string $adressePostale = null,
        public readonly ?string $telephone = null,
        public readonly ?string $email = null
    ) {
    }
}
