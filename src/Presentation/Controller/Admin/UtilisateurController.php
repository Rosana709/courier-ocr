<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Application\DTO\CreateUtilisateurDTO;
use App\Application\DTO\UpdateUtilisateurDTO;
use App\Application\UseCase\Utilisateur\CreateUtilisateurUseCase;
use App\Application\UseCase\Utilisateur\DeleteUtilisateurUseCase;
use App\Application\UseCase\Utilisateur\GetUtilisateurUseCase;
use App\Application\UseCase\Utilisateur\ToggleUserStatusUseCase;
use App\Application\UseCase\Utilisateur\UpdateUtilisateurUseCase;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Infrastructure\Service\ExcelExportService;
use App\Infrastructure\Service\PdfExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs', name: 'admin_utilisateur_')]
#[IsGranted('ROLE_ADMIN')]
class UtilisateurController extends AbstractController
{
    public function __construct(
        private readonly GetUtilisateurUseCase $getUtilisateurUseCase,
        private readonly CreateUtilisateurUseCase $createUtilisateurUseCase,
        private readonly UpdateUtilisateurUseCase $updateUtilisateurUseCase,
        private readonly DeleteUtilisateurUseCase $deleteUtilisateurUseCase,
        private readonly ToggleUserStatusUseCase $toggleUserStatusUseCase,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly ExcelExportService $excelExportService,
        private readonly PdfExportService $pdfExportService
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $utilisateurs = $this->getUtilisateurUseCase->getAll();

        return $this->render('admin/utilisateur/index.html.twig', [
            'utilisateurs' => $utilisateurs,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $dto = new CreateUtilisateurDTO(
                    email: $request->request->get('email'),
                    role: $request->request->get('role'),
                    serviceId: $request->request->get('service_id') ?: null
                );

                $this->createUtilisateurUseCase->execute($dto);

                $this->addFlash('success', 'Utilisateur créé avec succès avec le mot de passe par défaut');

                return $this->redirectToRoute('admin_utilisateur_index');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        $services = $this->serviceRepository->findActifs();

        return $this->render('admin/utilisateur/create.html.twig', [
            'services' => $services,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(string $id): Response
    {
        try {
            $utilisateur = $this->getUtilisateurUseCase->execute($id);

            return $this->render('admin/utilisateur/show.html.twig', [
                'utilisateur' => $utilisateur,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('admin_utilisateur_index');
        }
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request): Response
    {
        try {
            $utilisateur = $this->getUtilisateurUseCase->execute($id);

            if ($request->isMethod('POST')) {
                $dto = new UpdateUtilisateurDTO(
                    utilisateurId: $id,
                    email: $request->request->get('email') ?: null,
                    password: $request->request->get('password') ?: null,
                    serviceId: $request->request->get('service_id') ?: null,
                    estActif: $request->request->get('est_actif') === '1'
                );

                $this->updateUtilisateurUseCase->execute($dto);

                $this->addFlash('success', 'Utilisateur modifié avec succès');

                return $this->redirectToRoute('admin_utilisateur_show', ['id' => $id]);
            }

            $services = $this->serviceRepository->findActifs();

            return $this->render('admin/utilisateur/edit.html.twig', [
                'utilisateur' => $utilisateur,
                'services' => $services,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('admin_utilisateur_index');
        }
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(string $id): Response
    {
        try {
            $this->deleteUtilisateurUseCase->execute($id);

            $this->addFlash('success', 'Utilisateur désactivé avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_utilisateur_index');
    }

    #[Route('/{id}/activate', name: 'activate', methods: ['POST'])]
    public function activate(string $id): Response
    {
        try {
            $this->toggleUserStatusUseCase->activate($id);
            $this->addFlash('success', 'Utilisateur activé avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_utilisateur_index');
    }

    #[Route('/{id}/deactivate', name: 'deactivate', methods: ['POST'])]
    public function deactivate(string $id): Response
    {
        try {
            $this->toggleUserStatusUseCase->deactivate($id);
            $this->addFlash('success', 'Utilisateur désactivé avec succès');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_utilisateur_index');
    }

    #[Route('/{id}/toggle-status', name: 'toggle_status', methods: ['POST'])]
    public function toggleStatus(string $id): Response
    {
        try {
            $utilisateur = $this->toggleUserStatusUseCase->toggle($id);
            $message = $utilisateur->estActif()
                ? 'Utilisateur activé avec succès'
                : 'Utilisateur désactivé avec succès';
            $this->addFlash('success', $message);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_utilisateur_index');
    }

    #[Route('/export/excel', name: 'export_excel', methods: ['GET'])]
    public function exportExcel(): Response
    {
        $utilisateurs = $this->getUtilisateurUseCase->getAll();

        $data = [];
        foreach ($utilisateurs as $utilisateur) {
            $data[] = [
                $utilisateur->getEmail(),
                $utilisateur->isAdmin() ? 'Administrateur' : 'Utilisateur de service',
                $utilisateur->getService() ? $utilisateur->getService()->getNom() : '-',
                $utilisateur->estActif() ? 'Actif' : 'Inactif',
                $utilisateur->getDateCreation()->format('d/m/Y H:i'),
            ];
        }

        $headers = ['Email', 'Rôle', 'Service', 'Statut', 'Date Création'];

        return $this->excelExportService->export($data, $headers, 'Liste_Utilisateurs_' . date('Y-m-d'));
    }

    #[Route('/export/pdf', name: 'export_pdf', methods: ['GET'])]
    public function exportPdf(): Response
    {
        $utilisateurs = $this->getUtilisateurUseCase->getAll();

        $data = [];
        foreach ($utilisateurs as $utilisateur) {
            $data[] = [
                $utilisateur->getEmail(),
                $utilisateur->isAdmin() ? 'Admin' : 'Service',
                $utilisateur->getService() ? $utilisateur->getService()->getNom() : '-',
                $utilisateur->estActif() ? 'Actif' : 'Inactif',
            ];
        }

        $headers = ['Email', 'Rôle', 'Service', 'Statut'];

        return $this->pdfExportService->export($data, $headers, 'Liste_Utilisateurs_' . date('Y-m-d'), 'Liste des Utilisateurs');
    }
}
