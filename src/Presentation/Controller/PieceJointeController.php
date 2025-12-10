<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\PieceJointe\DeletePieceJointeUseCase;
use App\Application\UseCase\PieceJointe\DownloadPieceJointeUseCase;
use App\Application\UseCase\PieceJointe\ListPiecesJointesUseCase;
use App\Application\UseCase\PieceJointe\UploadPieceJointeUseCase;
use App\Domain\Exception\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/courriers/{courrierId}/pieces-jointes')]
#[IsGranted('ROLE_USER')]
class PieceJointeController extends AbstractController
{
    public function __construct(
        private readonly UploadPieceJointeUseCase $uploadPieceJointeUseCase,
        private readonly ListPiecesJointesUseCase $listPiecesJointesUseCase,
        private readonly DownloadPieceJointeUseCase $downloadPieceJointeUseCase,
        private readonly DeletePieceJointeUseCase $deletePieceJointeUseCase
    ) {
    }

    #[Route('/upload', name: 'piece_jointe_upload', methods: ['POST'])]
    public function upload(string $courrierId, Request $request): Response
    {
        $file = $request->files->get('fichier');

        if (!$file) {
            $this->addFlash('error', 'Aucun fichier fourni.');
            return $this->redirectToRoute('courrier_show', ['id' => $courrierId]);
        }

        try {
            $this->uploadPieceJointeUseCase->execute(
                $courrierId,
                $file,
                $this->getUser()->getId()
            );

            $this->addFlash('success', 'Fichier téléversé avec succès.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du téléversement : ' . $e->getMessage());
        }

        return $this->redirectToRoute('courrier_show', ['id' => $courrierId]);
    }

    #[Route('/{id}/download', name: 'piece_jointe_download', methods: ['GET'])]
    public function download(string $courrierId, string $id): Response
    {
        try {
            $pieceJointe = $this->downloadPieceJointeUseCase->execute($id);

            $response = new StreamedResponse(function () use ($pieceJointe) {
                if (is_resource($pieceJointe->getContenuFichier())) {
                    rewind($pieceJointe->getContenuFichier());
                    fpassthru($pieceJointe->getContenuFichier());
                } else {
                    echo $pieceJointe->getContenuFichier();
                }
            });

            $response->headers->set('Content-Type', $pieceJointe->getTypeMime());
            $response->headers->set('Content-Disposition',
                'attachment; filename="' . $pieceJointe->getNomFichierOriginal() . '"');
            $response->headers->set('Content-Length', (string) $pieceJointe->getTailleFichier());

            return $response;

        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('courrier_show', ['id' => $courrierId]);
        }
    }

    #[Route('/{id}/delete', name: 'piece_jointe_delete', methods: ['POST'])]
    public function delete(string $courrierId, string $id): Response
    {
        try {
            $this->deletePieceJointeUseCase->execute($id);
            $this->addFlash('success', 'Pièce jointe supprimée avec succès.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('courrier_show', ['id' => $courrierId]);
    }
}
