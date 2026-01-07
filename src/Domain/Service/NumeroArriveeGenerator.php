<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Service;
use App\Domain\Repository\CourrierRepositoryInterface;

class NumeroArriveeGenerator
{
    public function __construct(
        private readonly CourrierRepositoryInterface $courrierRepository
    ) {
    }

    /**
     * Génère un numéro d'enregistrement (Arrivée/Chrono)
     * Format : N° XXX-YYYY/DG/SIGLE_SERVICE
     */
    public function generer(Service $service, int $annee): string
    {
        // Compter les enregistrements déjà numérotés pour ce service cette année
        $compteur = $this->courrierRepository->countByServiceAndAnneeWithArrivalNumber(
            $service,
            $annee
        );
        $compteur++;

        // Formater le numéro sur 3 chiffres
        $numeroFormate = str_pad((string)$compteur, 3, '0', STR_PAD_LEFT);

        // Utiliser le sigle du service ou son ID
        $codeService = $service->getSigle() ?? $service->getId();

        // Format : N° XXX-YYYY/DG/SERVICE
        return sprintf('N° %s-%d/DG/%s', $numeroFormate, $annee, $codeService);
    }
}
