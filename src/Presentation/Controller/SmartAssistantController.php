<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Infrastructure\Service\OcrIntegrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/smart')]
#[IsGranted('ROLE_USER')]
class SmartAssistantController extends AbstractController
{
    public function __construct(
        private readonly OcrIntegrationService $ocrService
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
            return new JsonResponse(['data' => $data]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
