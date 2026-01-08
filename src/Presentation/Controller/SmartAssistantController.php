<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Infrastructure\Service\OcrIntegrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/smart')]
#[IsGranted('ROLE_USER')]
class SmartAssistantController extends AbstractController
{
    public function __construct(
        private readonly OcrIntegrationService $ocrService,
        private readonly \App\Domain\Repository\CourrierRepositoryInterface $courrierRepository
    ) {
    }

    #[Route('/extract', name: 'smart_extract', methods: ['POST'])]
    public function extract(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file) {
            return new JsonResponse(['error' => 'Aucun fichier fourni'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $data = $this->ocrService->extractText($file);
            
            // Vérification des doublons
            $isDuplicate = false;
            if (!empty($data['letterNumber'])) {
                $isDuplicate = $this->courrierRepository->existsByNumeroReference((string)$data['letterNumber']);
            }

            return new JsonResponse([
                'data' => $data,
                'isDuplicate' => $isDuplicate
            ]);
        } catch (\Throwable $e) {
            // Log full error for developer
            error_log('OCR Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            return new JsonResponse([
                'error' => 'Erreur lors de l\'analyse du document',
                'message' => $e->getMessage(),
                'trace' => $this->getParameter('kernel.debug') ? $e->getTraceAsString() : null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/generate-content', name: 'smart_generate_content', methods: ['POST'])]
    public function generateContent(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        $prompt = $content['prompt'] ?? '';

        if (empty($prompt)) {
            return new JsonResponse(['error' => 'Le prompt est vide'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->ocrService->generateContent($prompt, $content);
            return new JsonResponse($result);
        } catch (\Throwable $e) {
            error_log('Generate Content Error: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Erreur lors de la génération de contenu',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/generate-pdf', name: 'smart_generate_pdf', methods: ['POST'])]
    public function generatePdf(Request $request): Response
    {
        $content = json_decode($request->getContent(), true);

        try {
            $pdfContent = $this->ocrService->generatePdf($content);
            
            return new Response($pdfContent, Response::HTTP_OK, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="courrier_genere.pdf"',
            ]);
        } catch (\Throwable $e) {
            error_log('Generate PDF Error: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Erreur lors de la génération du PDF',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    #[Route('/chat', name: 'smart_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        $message = $content['message'] ?? '';
        $history = $content['history'] ?? [];
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (empty($message)) {
            return new JsonResponse(['error' => 'Le message est vide'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->ocrService->chat($message, $history, $isAdmin);
            return new JsonResponse($result);
        } catch (\Throwable $e) {
            error_log('Chat Error: ' . $e->getMessage());
            return new JsonResponse([
                'error' => 'Erreur lors de la discussion',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
