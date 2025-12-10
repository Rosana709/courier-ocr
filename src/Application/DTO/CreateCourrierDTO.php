<?php

declare(strict_types=1);

namespace App\Application\DTO;

class CreateCourrierDTO
{
    public function __construct(
        public readonly string $type,
        public readonly string $objet,
        public readonly ?string $contenu,
        public readonly string $dateCourrier,
        public readonly string $priorite,
        public readonly string $typeExpediteur,
        public readonly ?string $serviceExpediteurId,
        public readonly ?string $personneExterneExpediteurId,
        public readonly string $typeDestinataire,
        public readonly ?string $serviceDestinataireId,
        public readonly ?string $personneExterneDestinataireId,
        public readonly ?array $destinatairesCopieIds = [],
        public readonly ?string $courrierParentId = null,
        public readonly ?string $notes = null,
        public readonly ?string $numeroReference = null
    ) {
    }
}
