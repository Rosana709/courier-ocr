<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Application\UseCase\Notification\ListNotificationsUseCase;
use App\Application\UseCase\Notification\MarquerNotificationCommeLueUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly ListNotificationsUseCase $listNotificationsUseCase,
        private readonly MarquerNotificationCommeLueUseCase $marquerNotificationCommeLueUseCase,
        private readonly \App\Domain\Repository\HistoriqueActionRepositoryInterface $historiqueActionRepository
    ) {
    }

    #[Route('/', name: 'notification_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        
        if ($this->isGranted('ROLE_ADMIN')) {
            // Pour l'admin, les "notifications" sont le rapport d'activité sur le dashboard
            return $this->redirectToRoute('admin_dashboard');
        }

        $serviceId = $user->getService()?->getId();

        if (!$serviceId) {
            $this->addFlash('error', 'Aucun service associé à votre compte.');
            return $this->redirectToRoute('app_dashboard');
        }

        $notifications = $this->listNotificationsUseCase->executeByService($serviceId);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/count', name: 'notification_count', methods: ['GET'])]
    public function count(): JsonResponse
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $lastCheck = $user->getLastActivityCheckedAt();
            // Si jamais consulté, on propose un count de 0 ou on prend une date par défaut (ex: 24h)
            if (!$lastCheck) {
                return new JsonResponse(['count' => 0]);
            }
            $count = $this->historiqueActionRepository->countSince($lastCheck);
            return new JsonResponse(['count' => $count]);
        }

        $serviceId = $user->getService()?->getId();

        if (!$serviceId) {
            return new JsonResponse(['count' => 0]);
        }

        $count = $this->listNotificationsUseCase->countNonLuesByService($serviceId);

        return new JsonResponse(['count' => $count]);
    }

    #[Route('/{id}/lire', name: 'notification_marquer_lue', methods: ['POST'])]
    public function marquerCommeLue(string $id): Response
    {
        try {
            $this->marquerNotificationCommeLueUseCase->execute($id);
            $this->addFlash('success', 'Notification marquée comme lue.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('notification_index');
    }

    #[Route('/marquer-toutes-lues', name: 'notification_marquer_toutes_lues', methods: ['POST'])]
    public function marquerToutesCommeLues(): Response
    {
        $user = $this->getUser();
        $service = $user->getService();

        if (!$service) {
            $this->addFlash('error', 'Aucun service associé à votre compte.');
            return $this->redirectToRoute('app_dashboard');
        }

        try {
            $this->marquerNotificationCommeLueUseCase->executeAllByService($service);
            $this->addFlash('success', 'Toutes les notifications ont été marquées comme lues.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('notification_index');
    }
}
