<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Domain\Repository\CourrierRepositoryInterface;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\SessionTraceRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\Repository\HistoriqueActionRepositoryInterface;
use App\Domain\Entity\Courrier;
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
        private readonly HistoriqueActionRepositoryInterface $historiqueActionRepository
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
}
