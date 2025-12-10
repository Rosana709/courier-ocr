<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Service;

use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\CourrierRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/service', name: 'service_')]
#[IsGranted('ROLE_SERVICE')]
class ServiceDashboardController extends AbstractController
{
    public function __construct(
        private readonly CourrierRepositoryInterface $courrierRepository
    ) {
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $service = $user->getService();

        if ($service === null) {
            throw $this->createAccessDeniedException('Aucun service associé à cet utilisateur.');
        }

        // Récupérer les courriers du service
        $courriersRecus = $this->courrierRepository->findByServiceDestinataire($service);
        $courriersEnvoyes = $this->courrierRepository->findByServiceExpediteur($service);

        $stats = [
            'service' => $service,
            'courriers_recus' => count($courriersRecus),
            'courriers_envoyes' => count($courriersEnvoyes),
        ];

        return $this->render('service/dashboard.html.twig', [
            'stats' => $stats,
            'courriers_recus' => array_slice($courriersRecus, 0, 5), // 5 derniers
            'courriers_envoyes' => array_slice($courriersEnvoyes, 0, 5), // 5 derniers
        ]);
    }
}
