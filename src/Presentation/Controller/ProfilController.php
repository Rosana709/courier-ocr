<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\DTO\UpdateProfilDTO;
use App\Application\UseCase\Utilisateur\UpdateProfilUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
class ProfilController extends AbstractController
{
    public function __construct(
        private readonly UpdateProfilUseCase $updateProfilUseCase
    ) {
    }

    #[Route('', name: 'profil_show', methods: ['GET'])]
    public function show(): Response
    {
        $user = $this->getUser();

        return $this->render('profil/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit', name: 'profil_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            try {
                $dto = new UpdateProfilDTO(
                    email: $request->request->get('email'),
                    currentPassword: $request->request->get('current_password'),
                    newPassword: $request->request->get('new_password'),
                    confirmPassword: $request->request->get('confirm_password')
                );

                $this->updateProfilUseCase->execute($user, $dto);

                $this->addFlash('success', 'Profil mis à jour avec succès');

                return $this->redirectToRoute('profil_show');
            } catch (\Exception $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('profil/edit.html.twig', [
            'user' => $user,
        ]);
    }
}
