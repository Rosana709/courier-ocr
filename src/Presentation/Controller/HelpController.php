<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class HelpController extends AbstractController
{
    #[Route('/aide', name: 'app_help', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('help/index.html.twig', [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }
}
