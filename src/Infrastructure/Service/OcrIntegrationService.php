<?php

namespace App\Infrastructure\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class OcrIntegrationService
{
    private string $baseUrl;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly \Psr\Log\LoggerInterface $logger,
        string $ocrBackendUrl = 'http://127.0.0.1:8000'
    ) {
        $this->baseUrl = rtrim($ocrBackendUrl, '/');
    }


    /**
     * Extrait le texte d'un document via l'API OCR.
     */
    public function extractText(UploadedFile $file): array
    {
        if ($this->logger) {
            $this->logger->info('Appel de extractText pour le fichier : ' . $file->getClientOriginalName());
        }

        try {
            $formData = new FormDataPart([
                'file' => DataPart::fromPath($file->getRealPath(), $file->getClientOriginalName(), $file->getClientMimeType()),
            ]);

            $response = $this->httpClient->request('POST', $this->baseUrl . '/api/extract', [
                'headers' => array_merge($formData->getPreparedHeaders()->toArray(), [
                    'accept' => 'application/json',
                ]),
                'body' => $formData->bodyToIterable(),
                'timeout' => 60,
            ]);

            $statusCode = $response->getStatusCode();
            if ($this->logger) {
                $this->logger->info('Statut de la réponse OCR : ' . $statusCode);
            }

            if ($statusCode !== 200) {
                $errorContent = $response->getContent(false);
                if ($this->logger) {
                    $this->logger->error('Erreur OCR : ' . $errorContent);
                }
                throw new \RuntimeException('Échec de l\'extraction du texte : ' . $errorContent);
            }

            $data = $response->toArray()['data'] ?? [];
            if ($this->logger) {
                $this->logger->info('Extraction réussie.');
            }
            return $data;
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->error('Exception lors de l\'extraction : ' . $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Génère du contenu pour un courrier via l'IA.
     */
    public function generateContent(string $prompt, array $context = []): array
    {
        $response = $this->httpClient->request('POST', $this->baseUrl . '/api/generate-content', [
            'json' => array_merge([
                'prompt' => $prompt,
                'senderService' => $context['senderService'] ?? 'Direction Générale des Impôts',
                'receiverService' => $context['receiverService'] ?? '',
                'letterNumber' => $context['letterNumber'] ?? 'N°____/DGI',
                'importance' => $context['importance'] ?? 'Normal',
            ], $context),
            'timeout' => 60,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Échec de la génération de contenu : ' . $response->getContent(false));
        }

        return $response->toArray();
    }

    /**
     * Génère un PDF via l'API.
     */
    public function generatePdf(array $data): string
    {
        $response = $this->httpClient->request('POST', $this->baseUrl . '/api/generate-pdf', [
            'json' => $data,
            'timeout' => 60,
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Échec de la génération du PDF : ' . $response->getContent(false));
        }

        return $response->getContent();
    }
}
