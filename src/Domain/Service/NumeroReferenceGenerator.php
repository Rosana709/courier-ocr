<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Service;
use App\Domain\Repository\CourrierRepositoryInterface;

class NumeroReferenceGenerator
{
    public function __construct(
        private readonly CourrierRepositoryInterface $courrierRepository
    ) {
    }

    /**
     * Génère un numéro de référence au format : XXX/MEF/SG/DGI/SERVICE
     * XXX : compteur global par service (001, 002...)
     */
    public function generer(Service $service): string
    {
        // Récupérer le nombre total de courriers envoyés par le service
        $compteur = $this->courrierRepository->countByServiceExpediteur($service);

        // Incrémenter pour le prochain courrier
        $compteur++;

        // Formater le compteur sur 3 chiffres (ou plus si nécessaire)
        $numeroFormate = str_pad((string)$compteur, 3, '0', STR_PAD_LEFT);

        // Utiliser le sigle du service s'il existe, sinon l'ID
        $codeService = $service->getSigle() ?? $service->getId();

        // Format : XXX/MEF/SG/DGI/SERVICE
        return sprintf('%s/MEF/SG/DGI/%s', $numeroFormate, $codeService);
    }
}
