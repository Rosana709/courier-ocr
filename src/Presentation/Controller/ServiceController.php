<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\DTO\CreateServiceDTO;
use App\Application\DTO\UpdateServiceDTO;
use App\Application\UseCase\Service\CreateServiceUseCase;
use App\Application\UseCase\Service\DeleteServiceUseCase;
use App\Application\UseCase\Service\GetServiceUseCase;
use App\Application\UseCase\Service\UpdateServiceUseCase;
use App\Application\UseCase\Service\ToggleServiceStatusUseCase;
use App\Domain\Exception\DomainException;
use App\Infrastructure\Service\ExcelExportService;
use App\Infrastructure\Service\PdfExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/services')]
class ServiceController extends AbstractController
{
    public function __construct(
        private readonly CreateServiceUseCase $createServiceUseCase,
        private readonly GetServiceUseCase $getServiceUseCase,
        private readonly UpdateServiceUseCase $updateServiceUseCase,
        private readonly DeleteServiceUseCase $deleteServiceUseCase,
        private readonly ToggleServiceStatusUseCase $toggleServiceStatusUseCase,
        private readonly ExcelExportService $excelExportService,
        private readonly PdfExportService $pdfExportService
    ) {
    }

    #[Route('/', name: 'service_index', methods: ['GET'])]
    public function index(): Response
    {
        $services = $this->getServiceUseCase->getAllServices();

        return $this->render('service/index.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/create', name: 'service_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $dto = new CreateServiceDTO(
                    $request->request->get('id', ''),
                    $request->request->get('nom', ''),
                    $request->request->get('localite'),
                    $request->request->get('mail'),
                    $request->request->get('adresse'),
                    $request->request->get('sigle'),
                    $request->request->get('estActif', '1') === '1'
                );

                $service = $this->createServiceUseCase->execute($dto, $this->getUser()->getId());

                $this->addFlash('success', 'Service créé avec succès.');
                return $this->redirectToRoute('service_show', ['id' => $service->getId()]);

            } catch (DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('service/create.html.twig');
    }

    #[Route('/{id}', name: 'service_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        try {
            $service = $this->getServiceUseCase->execute($id);

            return $this->render('service/show.html.twig', [
                'service' => $service,
            ]);
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('service_index');
        }
    }

    #[Route('/{id}/edit', name: 'service_edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request): Response
    {
        try {
            $service = $this->getServiceUseCase->execute($id);

            if ($request->isMethod('POST')) {
                $dto = new UpdateServiceDTO(
                    $id,
                    $request->request->get('nom'),
                    $request->request->get('localite'),
                    $request->request->get('mail'),
                    $request->request->get('adresse'),
                    $request->request->get('sigle')
                );

                $this->updateServiceUseCase->execute($dto, $this->getUser()->getId());

                $this->addFlash('success', 'Service modifié avec succès.');
                return $this->redirectToRoute('service_show', ['id' => $id]);
            }

            return $this->render('service/edit.html.twig', [
                'service' => $service,
            ]);

        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('service_index');
        }
    }

    #[Route('/{id}/delete', name: 'service_delete', methods: ['POST'])]
    public function delete(string $id): Response
    {
        try {
            $this->deleteServiceUseCase->execute($id);
            $this->addFlash('success', 'Service supprimé avec succès.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Impossible de supprimer ce service car il est lié à des courriers.');
        }

        return $this->redirectToRoute('service_index');
    }

    #[Route('/{id}/toggle-status', name: 'service_toggle_status', methods: ['POST'])]
    public function toggleStatus(string $id): Response
    {
        try {
            $service = $this->toggleServiceStatusUseCase->toggle($id, $this->getUser()->getId());
            $status = $service->isEstActif() ? 'activé' : 'désactivé';
            $this->addFlash('success', "Service $status avec succès.");
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('service_index');
    }

    #[Route('/{id}/activate', name: 'service_activate', methods: ['POST'])]
    public function activate(string $id): Response
    {
        try {
            $this->toggleServiceStatusUseCase->activate($id, $this->getUser()->getId());
            $this->addFlash('success', 'Service activé avec succès.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('service_index');
    }

    #[Route('/{id}/deactivate', name: 'service_deactivate', methods: ['POST'])]
    public function deactivate(string $id): Response
    {
        try {
            $this->toggleServiceStatusUseCase->deactivate($id, $this->getUser()->getId());
            $this->addFlash('success', 'Service désactivé avec succès.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('service_index');
    }

    #[Route('/export/excel', name: 'service_export_excel', methods: ['GET'])]
    public function exportExcel(): Response
    {
        $services = $this->getServiceUseCase->getAllServices();

        $data = [];
        foreach ($services as $service) {
            $data[] = [
                $service->getId(),
                $service->getNom(),
                $service->getSigle() ?? '',
                $service->getLocalite() ?? '',
                $service->getMail() ?? '',
                $service->isEstActif() ? 'Actif' : 'Inactif',
            ];
        }

        $headers = ['ID', 'Nom', 'Sigle', 'Localité', 'Email', 'Statut'];

        return $this->excelExportService->export($data, $headers, 'Liste_Services_' . date('Y-m-d'));
    }

    #[Route('/export/pdf', name: 'service_export_pdf', methods: ['GET'])]
    public function exportPdf(): Response
    {
        $services = $this->getServiceUseCase->getAllServices();

        $data = [];
        foreach ($services as $service) {
            $data[] = [
                $service->getId(),
                $service->getNom(),
                $service->getSigle() ?? '-',
                $service->getLocalite() ?? '-',
                $service->isEstActif() ? 'Actif' : 'Inactif',
            ];
        }

        $headers = ['ID', 'Nom', 'Sigle', 'Localité', 'Statut'];

        return $this->pdfExportService->export($data, $headers, 'Liste_Services_' . date('Y-m-d'), 'Liste des Services');
    }
}