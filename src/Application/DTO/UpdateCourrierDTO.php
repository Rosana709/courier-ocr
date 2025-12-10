<?php

declare(strict_types=1);

namespace App\Application\DTO;

class UpdateCourrierDTO
{
    public function __construct(
        public readonly ?string $objet = null,
        public readonly ?string $contenu = null,
        public readonly ?string $priorite = null,
        public readonly ?string $statut = null,
        public readonly ?string $notes = null,
        public readonly ?array $destinatairesCopieIds = null
    ) {
    }
}
