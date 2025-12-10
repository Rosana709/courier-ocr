<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\DTO\CreatePersonneExterneDTO;
use App\Application\DTO\UpdatePersonneExterneDTO;
use App\Application\UseCase\PersonneExterne\CreatePersonneExterneUseCase;
use App\Application\UseCase\PersonneExterne\DeletePersonneExterneUseCase;
use App\Application\UseCase\PersonneExterne\GetPersonneExterneUseCase;
use App\Application\UseCase\PersonneExterne\TogglePersonneExterneStatusUseCase;
use App\Application\UseCase\PersonneExterne\UpdatePersonneExterneUseCase;
use App\Domain\Entity\PersonneExterne;
use App\Domain\Exception\DomainException;
use App\Infrastructure\Service\ExcelExportService;
use App\Infrastructure\Service\PdfExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/personnes-externes')]
#[IsGranted('ROLE_USER')]
class PersonneExterneController extends AbstractController
{
    public function __construct(
        private readonly CreatePersonneExterneUseCase $createPersonneExterneUseCase,
        private readonly GetPersonneExterneUseCase $getPersonneExterneUseCase,
        private readonly UpdatePersonneExterneUseCase $updatePersonneExterneUseCase,
        private readonly DeletePersonneExterneUseCase $deletePersonneExterneUseCase,
        private readonly TogglePersonneExterneStatusUseCase $togglePersonneExterneStatusUseCase,
        private readonly ExcelExportService $excelExportService,
        private readonly PdfExportService $pdfExportService
    ) {
    }

    #[Route('/', name: 'personne_externe_index', methods: ['GET'])]
    public function index(): Response
    {
        $personnes = $this->getPersonneExterneUseCase->getAll();

        return $this->render('personne_externe/index.html.twig', [
            'personnes' => $personnes,
        ]);
    }

    #[Route('/create', name: 'personne_externe_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $dto = new CreatePersonneExterneDTO(
                    nomOuRaisonSociale: $request->request->get('nomOuRaisonSociale', ''),
                    type: $request->request->get('type', ''),
                    adressePostale: $request->request->get('adressePostale'),
                    telephone: $request->request->get('telephone'),
                    email: $request->request->get('email')
                );

                $personne = $this->createPersonneExterneUseCase->execute($dto);

                $this->addFlash('success', 'Personne externe créée avec succès.');
                return $this->redirectToRoute('personne_externe_show', ['id' => $personne->getId()]);

            } catch (DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('personne_externe/create.html.twig', [
            'types' => PersonneExterne::getValidTypes(),
        ]);
    }

    #[Route('/{id}', name: 'personne_externe_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        try {
            $personne = $this->getPersonneExterneUseCase->execute($id);

            return $this->render('personne_externe/show.html.twig', [
                'personne' => $personne,
            ]);
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('personne_externe_index');
        }
    }

    #[Route('/{id}/edit', name: 'personne_externe_edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request): Response
    {
        try {
            $personne = $this->getPersonneExterneUseCase->execute($id);

            if ($request->isMethod('POST')) {
                $dto = new UpdatePersonneExterneDTO(
                    nomOuRaisonSociale: $request->request->get('nomOuRaisonSociale'),
                    type: $request->request->get('type'),
                    adressePostale: $request->request->get('adressePostale'),
                    telephone: $request->request->get('telephone'),
                    email: $request->request->get('email')
                );

                $this->updatePersonneExterneUseCase->execute($id, $dto);

                $this->addFlash('success', 'Personne externe modifiée avec succès.');
                return $this->redirectToRoute('personne_externe_show', ['id' => $id]);
            }

            return $this->render('personne_externe/edit.html.twig', [
                'personne' => $personne,
                'types' => PersonneExterne::getValidTypes(),
            ]);

        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('personne_externe_index');
        }
    }

    #[Route('/{id}/toggle-status', name: 'personne_externe_toggle_status', methods: ['POST'])]
    public function toggleStatus(string $id): Response
    {
        try {
            $this->togglePersonneExterneStatusUseCase->execute($id);
            $this->addFlash('success', 'Statut modifié avec succès.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('personne_externe_show', ['id' => $id]);
    }

    #[Route('/{id}/delete', name: 'personne_externe_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id): Response
    {
        try {
            $this->deletePersonneExterneUseCase->execute($id);
            $this->addFlash('success', 'Personne externe supprimée avec succès.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('personne_externe_index');
    }

    #[Route('/export/excel', name: 'personne_externe_export_excel', methods: ['GET'])]
    public function exportExcel(): Response
    {
        $personnes = $this->getPersonneExterneUseCase->getAll();

        $data = [];
        foreach ($personnes as $personne) {
            $data[] = [
                $personne->getNomOuRaisonSociale(),
                $personne->getType(),
                $personne->getAdressePostale() ?? '',
                $personne->getTelephone() ?? '',
                $personne->getEmail() ?? '',
                $personne->estActif() ? 'Actif' : 'Inactif',
                $personne->getDateCreation()->format('d/m/Y'),
            ];
        }

        $headers = ['Nom/Raison Sociale', 'Type', 'Adresse', 'Téléphone', 'Email', 'Statut', 'Date Création'];

        return $this->excelExportService->export($data, $headers, 'Liste_Personnes_Externes_' . date('Y-m-d'));
    }

    #[Route('/export/pdf', name: 'personne_externe_export_pdf', methods: ['GET'])]
    public function exportPdf(): Response
    {
        $personnes = $this->getPersonneExterneUseCase->getAll();

        $data = [];
        foreach ($personnes as $personne) {
            $data[] = [
                $personne->getNomOuRaisonSociale(),
                $personne->getType(),
                $personne->getTelephone() ?? '-',
                $personne->getEmail() ?? '-',
                $personne->estActif() ? 'Actif' : 'Inactif',
            ];
        }

        $headers = ['Nom/Raison Sociale', 'Type', 'Téléphone', 'Email', 'Statut'];

        return $this->pdfExportService->export($data, $headers, 'Liste_Personnes_Externes_' . date('Y-m-d'), 'Liste des Personnes Externes');
    }
}
