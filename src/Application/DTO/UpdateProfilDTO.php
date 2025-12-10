<?php

declare(strict_types=1);

namespace App\Application\DTO;

class UpdateProfilDTO
{
    public function __construct(
        public readonly ?string $email = null,
        public readonly ?string $currentPassword = null,
        public readonly ?string $newPassword = null,
        public readonly ?string $confirmPassword = null
    ) {
    }
}
