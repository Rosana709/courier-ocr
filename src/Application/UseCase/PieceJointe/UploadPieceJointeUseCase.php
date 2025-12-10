<?php

declare(strict_types=1);

namespace App\Application\UseCase\PieceJointe;

use App\Domain\Entity\PieceJointe;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\CourrierRepositoryInterface;
use App\Domain\Repository\PieceJointeRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadPieceJointeUseCase
{
    public function __construct(
        private readonly PieceJointeRepositoryInterface $pieceJointeRepository,
        private readonly CourrierRepositoryInterface $courrierRepository,
        private readonly UtilisateurRepositoryInterface $utilisateurRepository
    ) {
    }

    public function execute(string $courrierId, UploadedFile $file, string $utilisateurId): PieceJointe
    {
        $courrier = $this->courrierRepository->findById($courrierId);

        if (!$courrier) {
            throw new EntityNotFoundException("Courrier non trouvé");
        }

        $utilisateur = $this->utilisateurRepository->findById($utilisateurId);

        if (!$utilisateur) {
            throw new EntityNotFoundException("Utilisateur non trouvé");
        }

        // Validation du fichier
        if (!$file->isValid()) {
            throw new \InvalidArgumentException("Le fichier uploadé est invalide");
        }

        // Récupérer les informations du fichier
        $nomFichierOriginal = $file->getClientOriginalName();
        $typeMime = $file->getMimeType();
        $tailleFichier = $file->getSize();
        $contenuFichier = file_get_contents($file->getPathname());

        // Créer la pièce jointe
        $pieceJointe = new PieceJointe(
            courrier: $courrier,
            nomFichierOriginal: $nomFichierOriginal,
            typeMime: $typeMime,
            tailleFichier: $tailleFichier,
            contenuFichier: $contenuFichier,
            telechargeParUtilisateur: $utilisateur
        );

        $this->pieceJointeRepository->save($pieceJointe);

        return $pieceJointe;
    }
}
