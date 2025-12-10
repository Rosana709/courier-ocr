<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Domain\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        // Rediriger selon le rôle de l'utilisateur
        if ($user->isAdmin()) {
            return $this->redirectToRoute('admin_dashboard');
        }

        if ($user->isServiceUser()) {
            return $this->redirectToRoute('service_dashboard');
        }

        // Par défaut, rediriger vers la page de connexion
        return $this->redirectToRoute('app_login');
    }
}
