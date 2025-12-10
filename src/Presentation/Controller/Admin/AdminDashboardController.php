<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use App\Domain\Repository\CourrierRepositoryInterface;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
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
        private readonly UtilisateurRepositoryInterface $utilisateurRepository
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
        ];

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
        ]);
    }
}
