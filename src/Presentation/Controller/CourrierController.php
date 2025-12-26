<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\DTO\CreateCourrierDTO;
use App\Application\DTO\UpdateCourrierDTO;
use App\Application\UseCase\Courrier\CreateCourrierUseCase;
use App\Application\UseCase\Courrier\DeleteCourrierUseCase;
use App\Application\UseCase\Courrier\GetCourrierUseCase;
use App\Application\UseCase\Courrier\ListCourriersUseCase;
use App\Application\UseCase\Courrier\UpdateCourrierUseCase;
use App\Application\UseCase\Courrier\AccuseReceptionUseCase;
use App\Application\UseCase\PieceJointe\ListPiecesJointesUseCase;
use App\Domain\Entity\Courrier;
use App\Domain\Exception\DomainException;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\PersonneExterneRepositoryInterface;
use App\Infrastructure\Service\ExcelExportService;
use App\Infrastructure\Service\PdfExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/courriers')]
#[IsGranted('ROLE_USER')]
class CourrierController extends AbstractController
{
    public function __construct(
        private readonly CreateCourrierUseCase $createCourrierUseCase,
        private readonly GetCourrierUseCase $getCourrierUseCase,
        private readonly UpdateCourrierUseCase $updateCourrierUseCase,
        private readonly DeleteCourrierUseCase $deleteCourrierUseCase,
        private readonly ListCourriersUseCase $listCourriersUseCase,
        private readonly AccuseReceptionUseCase $accuseReceptionUseCase,
        private readonly ListPiecesJointesUseCase $listPiecesJointesUseCase,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly PersonneExterneRepositoryInterface $personneExterneRepository,
        private readonly ExcelExportService $excelExportService,
        private readonly PdfExportService $pdfExportService
    ) {
    }

    #[Route('/', name: 'courrier_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $courriers = $this->listCourriersUseCase->executeAll();
        } else {
            $serviceId = $user->getService()->getId();
            $courriers = $this->listCourriersUseCase->executeByService($serviceId);
        }

        return $this->render('courrier/index.html.twig', [
            'courriers' => $courriers,
            'viewType' => 'tous',
            'pageTitle' => 'Tous les Courriers',
        ]);
    }

    #[Route('/entrants', name: 'courrier_entrants', methods: ['GET'])]
    public function entrants(Request $request): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $courriers = $this->listCourriersUseCase->executeAll();
            $courriers = array_filter($courriers, fn($c) => $c->getType() === Courrier::TYPE_ENTRANT);
        } else {
            $serviceId = $user->getService()->getId();
            $courriers = $this->listCourriersUseCase->executeEntrantsByService($serviceId);
        }

        return $this->render('courrier/index.html.twig', [
            'courriers' => $courriers,
            'viewType' => 'entrants',
            'pageTitle' => 'Courriers Entrants',
        ]);
    }

    #[Route('/sortants', name: 'courrier_sortants', methods: ['GET'])]
    public function sortants(Request $request): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $courriers = $this->listCourriersUseCase->executeAll();
            $courriers = array_filter($courriers, fn($c) => $c->getType() === Courrier::TYPE_SORTANT);
        } else {
            $serviceId = $user->getService()->getId();
            $courriers = $this->listCourriersUseCase->executeSortantsByService($serviceId);
        }

        return $this->render('courrier/index.html.twig', [
            'courriers' => $courriers,
            'viewType' => 'sortants',
            'pageTitle' => 'Courriers Sortants',
        ]);
    }

    #[Route('/archives', name: 'courrier_archives', methods: ['GET'])]
    public function archives(): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $courriers = $this->listCourriersUseCase->executeArchivedAll();
        } else {
            $serviceId = $user->getService()->getId();
            $courriers = $this->listCourriersUseCase->executeArchivedByService($serviceId);
        }

        return $this->render('courrier/index.html.twig', [
            'courriers' => $courriers,
            'viewType' => 'archives',
            'pageTitle' => 'Courriers Archivés',
        ]);
    }

    #[Route('/create', name: 'courrier_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $user = $this->getUser();
        $userService = $user?->getService();

        if (!$userService) {
            $this->addFlash('error', "Votre compte n'est associ?? ?? aucun service.");
            return $this->redirectToRoute('courrier_index');
        }

        if ($request->isMethod('POST')) {
            try {
                $type = $request->request->get('type', '');

                // Exp??diteur : verrouill?? sur service connect?? pour SORTANT, s??lectionnable pour ENTRANT
                $typeExpediteur = $request->request->get('typeExpediteur', Courrier::ACTEUR_SERVICE);
                $serviceExpediteurId = $request->request->get('serviceExpediteurId');
                $personneExterneExpediteurId = $request->request->get('personneExterneExpediteurId');

                if ($type === Courrier::TYPE_SORTANT) {
                    $typeExpediteur = Courrier::ACTEUR_SERVICE;
                    $serviceExpediteurId = $userService->getId();
                    $personneExterneExpediteurId = null;
                }

                $dto = new CreateCourrierDTO(
                    type: $type,
                    objet: $request->request->get('objet', ''),
                    contenu: $request->request->get('contenu'),
                    dateCourrier: $request->request->get('dateCourrier', date('Y-m-d')),
                    priorite: $request->request->get('priorite', Courrier::PRIORITE_NORMALE),
                    typeExpediteur: $typeExpediteur,
                    serviceExpediteurId: $serviceExpediteurId,
                    personneExterneExpediteurId: $personneExterneExpediteurId,
                    typeDestinataire: $request->request->get('typeDestinataire', ''),
                    serviceDestinataireId: $request->request->get('serviceDestinataireId'),
                    personneExterneDestinataireId: $request->request->get('personneExterneDestinataireId'),
                    destinatairesCopieIds: $request->request->all('destinatairesCopieIds'),
                    courrierParentId: $request->request->get('courrierParentId'),
                    notes: $request->request->get('notes'),
                    numeroReference: $request->request->get('numeroReference')
                );

                $courrier = $this->createCourrierUseCase->execute($dto, $user->getId());

                $this->addFlash('success', 'Courrier cr???? avec succ??s.');
                return $this->redirectToRoute('courrier_show', ['id' => $courrier->getId()]);

            } catch (DomainException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        $services = $this->serviceRepository->findAll();
        $personnesExternes = $this->personneExterneRepository->findAll();

        return $this->render('courrier/create.html.twig', [
            'services' => $services,
            'personnesExternes' => $personnesExternes,
            'priorites' => Courrier::getValidPriorites(),
            'types' => Courrier::getValidTypes(),
            'userService' => $userService,
        ]);
    }

    #[Route('/{id}', name: 'courrier_show', methods: ['GET'])]
    public function show(string $id): Response
    {
        try {
            $courrier = $this->getCourrierUseCase->execute($id);

            $this->denyAccessUnlessGranted('courrier_view', $courrier);

            $piecesJointes = $this->listPiecesJointesUseCase->execute($id);

            return $this->render('courrier/show.html.twig', [
                'courrier' => $courrier,
                'piecesJointes' => $piecesJointes,
            ]);
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('courrier_index');
        }
    }

    #[Route('/{id}/edit', name: 'courrier_edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request): Response
    {
        try {
            $courrier = $this->getCourrierUseCase->execute($id);

            $this->denyAccessUnlessGranted('courrier_edit', $courrier);

            if ($request->isMethod('POST')) {
                $dto = new UpdateCourrierDTO(
                    objet: $request->request->get('objet'),
                    contenu: $request->request->get('contenu'),
                    priorite: $request->request->get('priorite'),
                    statut: $request->request->get('statut'),
                    notes: $request->request->get('notes'),
                    destinatairesCopieIds: $request->request->all('destinatairesCopieIds')
                );

                $this->updateCourrierUseCase->execute($id, $dto);

                $this->addFlash('success', 'Courrier modifi?? avec succ??s.');
                return $this->redirectToRoute('courrier_show', ['id' => $id]);
            }

            $services = $this->serviceRepository->findAll();

            return $this->render('courrier/edit.html.twig', [
                'courrier' => $courrier,
                'services' => $services,
                'priorites' => Courrier::getValidPriorites(),
                'statuts' => Courrier::getValidStatuts(),
            ]);

        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('courrier_index');
        }
    }

    #[Route('/{id}/accuse-reception', name: 'courrier_accuse_reception', methods: ['POST'])]
    public function accuseReception(string $id): Response
    {
        try {
            $user = $this->getUser();
            $serviceId = $user->getService()->getId();

            $this->accuseReceptionUseCase->execute($id, $serviceId, $user->getId());

            $this->addFlash('success', 'Accus?? de r??ception confirm?? avec succ??s.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('courrier_show', ['id' => $id]);
    }

    #[Route('/export/excel', name: 'courrier_export_excel', methods: ['GET'])]
    public function exportExcel(): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $courriers = $this->listCourriersUseCase->executeAll();
        } else {
            $courriers = $this->listCourriersUseCase->executeByService($user->getService()->getId());
        }

        $data = [];
        foreach ($courriers as $courrier) {
            $data[] = [
                $courrier->getNumeroArrivee() ?? '-',
                $courrier->getNumeroReference(),
                $courrier->getType(),
                $courrier->getObjet(),
                $courrier->getPriorite(),
                $courrier->getStatut(),
                $courrier->getDateCourrier()->format('d/m/Y'),
                $courrier->getDateEnregistrement()->format('d/m/Y H:i'),
            ];
        }

        $headers = ['N?? Arriv??e', 'N?? R??f??rence', 'Type', 'Objet', 'Priorit??', 'Statut', 'Date Courrier', 'Date Enregistrement'];

        return $this->excelExportService->export($data, $headers, 'Liste_Courriers_' . date('Y-m-d'));
    }

    #[Route('/export/pdf', name: 'courrier_export_pdf', methods: ['GET'])]
    public function exportPdf(): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $courriers = $this->listCourriersUseCase->executeAll();
        } else {
            $courriers = $this->listCourriersUseCase->executeByService($user->getService()->getId());
        }

        $data = [];
        foreach ($courriers as $courrier) {
            $data[] = [
                $courrier->getNumeroArrivee() ?? '-',
                $courrier->getNumeroReference(),
                $courrier->getType(),
                mb_substr($courrier->getObjet(), 0, 40) . (strlen($courrier->getObjet()) > 40 ? '...' : ''),
                $courrier->getPriorite(),
                $courrier->getStatut(),
                $courrier->getDateCourrier()->format('d/m/Y'),
            ];
        }

        $headers = ['N?? Arriv??e', 'N?? R??f??rence', 'Type', 'Objet', 'Priorit??', 'Statut', 'Date'];

        return $this->pdfExportService->export($data, $headers, 'Liste_Courriers_' . date('Y-m-d'), 'Liste des Courriers');
    }

    #[Route('/{id}/archiver', name: 'courrier_archiver', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function archiver(string $id): Response
    {
        try {
            $this->getCourrierUseCase->execute($id); // assure existence
            $dto = new UpdateCourrierDTO(
                statut: Courrier::STATUT_ARCHIVE
            );
            $this->updateCourrierUseCase->execute($id, $dto);
            $this->addFlash('success', 'Courrier archiv??.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('courrier_show', ['id' => $id]);
    }

    #[Route('/{id}/delete', name: 'courrier_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id): Response
    {
        $this->addFlash('error', 'La suppression de courrier est interdite. Utilisez l\'archivage.');
        return $this->redirectToRoute('courrier_show', ['id' => $id]);
    }
}


