<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Domain\Repository\CourrierRepositoryInterface;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\SessionTraceRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\Entity\Courrier;
use App\Domain\Repository\HistoriqueActionRepositoryInterface;
use App\Infrastructure\Service\ExcelExportService;
use App\Infrastructure\Service\PdfExportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly CourrierRepositoryInterface $courrierRepository,
        private readonly UtilisateurRepositoryInterface $utilisateurRepository,
        private readonly SessionTraceRepositoryInterface $sessionTraceRepository,
        private readonly HistoriqueActionRepositoryInterface $historiqueActionRepository,
        private readonly ExcelExportService $excelExportService,
        private readonly PdfExportService $pdfExportService
    ) {
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        $stats = [
            'total_services' => count($this->serviceRepository->findAll()),
            'services_actifs' => count($this->serviceRepository->findActifs()),
            'total_couriers' => count($this->courrierRepository->findAll()),
            'total_utilisateurs' => count($this->utilisateurRepository->findAll()),
            'utilisateurs_actifs' => count($this->utilisateurRepository->findActifs()),
            'utilisateurs_connectes' => $this->sessionTraceRepository->countActiveSince(new \DateTimeImmutable('-15 minutes')),
            'courriers_attente_accuse' => count($this->courrierRepository->findByStatut(Courrier::STATUT_EN_ATTENTE_ACCUSE_RECEPTION)),
        ];

        // Mettre à jour la date de dernière consultation des activités pour l'admin
        $user = $this->getUser();
        if ($user instanceof \App\Domain\Entity\Utilisateur) {
            $user->updateLastActivityCheckedAt();
            $this->utilisateurRepository->save($user);
        }

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'courriers_recents' => $this->courrierRepository->findRecent(10),
            'actions_recentres' => $this->historiqueActionRepository->findLast(10),
            'sessions_connectees' => $this->sessionTraceRepository->findActiveSince(new \DateTimeImmutable('-15 minutes')),
        ]);
    }

    #[Route('/action/{id}/detail', name: 'action_detail')]
    public function actionDetail(string $id): Response
    {
        $action = $this->historiqueActionRepository->find($id);
        
        if (!$action) {
            throw $this->createNotFoundException('Action non trouvée');
        }

        return $this->render('admin/dashboard/_action_detail.html.twig', [
            'action' => $action,
        ]);
    }

    #[Route('/audit', name: 'audit_index')]
    public function listAudit(): Response
    {
        return $this->render('admin/audit/index.html.twig', [
            'actions' => $this->historiqueActionRepository->findAllSortedByDate(),
        ]);
    }

    #[Route('/audit/export/excel', name: 'audit_export_excel', methods: ['GET'])]
    public function exportAuditExcel(): Response
    {
        $actions = $this->historiqueActionRepository->findAllSortedByDate();
        
        $data = [];
        foreach ($actions as $action) {
            $actor = 'Système';
            if ($action->getEffectuePar()) {
                $actor = $action->getEffectuePar()->getService() 
                    ? $action->getEffectuePar()->getService()->getNom() 
                    : $action->getEffectuePar()->getEmail();
            }

            $description = $action->getDescription();
            if ($action->getCourrier()) {
                $description .= ' [N°' . $action->getCourrier()->getNumeroReference() . ']';
            }

            $data[] = [
                $action->getDateAction()->format('d/m/Y H:i:s'),
                $actor,
                $action->getTypeAction(),
                $description
            ];
        }

        $headers = ['Date & Heure', 'Acteur', 'Type d\'action', 'Description'];

        return $this->excelExportService->export($data, $headers, 'Journal_Audit_' . date('Y-m-d'));
    }

    #[Route('/audit/export/pdf', name: 'audit_export_pdf', methods: ['GET'])]
    public function exportAuditPdf(): Response
    {
        $actions = $this->historiqueActionRepository->findAllSortedByDate();
        
        $data = [];
        foreach ($actions as $action) {
            $actor = 'Système';
            if ($action->getEffectuePar()) {
                $actor = $action->getEffectuePar()->getService() 
                    ? $action->getEffectuePar()->getService()->getNom() 
                    : $action->getEffectuePar()->getEmail();
            }

            $description = $action->getDescription();
            if ($action->getCourrier()) {
                $description .= ' [N°' . $action->getCourrier()->getNumeroReference() . ']';
            }

            $data[] = [
                $action->getDateAction()->format('d/m/Y H:i:s'),
                $actor,
                $action->getTypeAction(),
                $description
            ];
        }

        $headers = ['Date & Heure', 'Acteur', 'Type', 'Description'];

        return $this->pdfExportService->export($data, $headers, 'Journal_Audit_' . date('Y-m-d'), 'Journal d\'Audit Système');
    }
}
