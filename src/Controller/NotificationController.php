<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    /**
     * Affiche la liste des notifications
     */
    #[Route('/', name: 'notification_index', methods: ['GET'])]
    public function index(
        Request $request,
        NotificationRepository $notificationRepository
    ): Response {
        $currentUser = $this->getUser();
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;

        $notifications = $this->notificationService->getNotificationsForUser($currentUser, $page, $limit);
        
        // Compter le total pour la pagination
        $total = $notificationRepository->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.recipient = :user')
            ->setParameter('user', $currentUser)
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = ceil($total / $limit);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages,
            'previous_page' => $page - 1,
            'next_page' => $page + 1,
        ]);
    }

    /**
     * Marque une notification comme lue et redirige vers l'action
     */
    #[Route('/{id}/read', name: 'notification_read', methods: ['GET'])]
    public function read(Notification $notification): Response
    {
        $currentUser = $this->getUser();

        // Vérifier que l'utilisateur est le destinataire
        if ($notification->getRecipient() !== $currentUser) {
            $this->addFlash('error', 'Accès non autorisé à cette notification.');
            return $this->redirectToRoute('notification_index');
        }

        // Marquer comme lue
        $this->notificationService->markAsRead($notification);

        // Rediriger vers l'action si une URL est définie
        if ($notification->getActionUrl()) {
            return $this->redirect($notification->getActionUrl());
        }

        // Sinon, rediriger vers la liste des notifications
        return $this->redirectToRoute('notification_index');
    }

    /**
     * Marque une notification comme lue via AJAX
     */
    #[Route('/{id}/mark-read', name: 'notification_mark_read', methods: ['POST'])]
    public function markRead(Notification $notification): JsonResponse
    {
        $currentUser = $this->getUser();

        if ($notification->getRecipient() !== $currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $this->notificationService->markAsRead($notification);

        return new JsonResponse([
            'success' => true,
            'message' => 'Notification marquée comme lue'
        ]);
    }

    /**
     * Marque toutes les notifications comme lues
     */
    #[Route('/mark-all-read', name: 'notification_mark_all_read', methods: ['POST'])]
    public function markAllRead(Request $request): Response
    {
        $currentUser = $this->getUser();

        if ($this->isCsrfTokenValid('mark-all-read', $request->request->get('_token'))) {
            $count = $this->notificationService->markAllAsReadForUser($currentUser);
            
            if ($count > 0) {
                $this->addFlash('success', sprintf(
                    '%d notification%s marquée%s comme lue%s.',
                    $count,
                    $count > 1 ? 's' : '',
                    $count > 1 ? 's' : '',
                    $count > 1 ? 's' : ''
                ));
            } else {
                $this->addFlash('info', 'Aucune notification non lue à marquer.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('notification_index');
    }

    /**
     * Supprime une notification
     */
    #[Route('/{id}/delete', name: 'notification_delete', methods: ['POST'])]
    public function delete(Request $request, Notification $notification): Response
    {
        $currentUser = $this->getUser();

        if ($notification->getRecipient() !== $currentUser) {
            $this->addFlash('error', 'Accès non autorisé à cette notification.');
            return $this->redirectToRoute('notification_index');
        }

        if ($this->isCsrfTokenValid('delete-notification-' . $notification->getId(), 
            $request->request->get('_token'))) {
            
            $this->notificationService->deleteNotification($notification);
            $this->addFlash('success', 'Notification supprimée.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('notification_index');
    }

    /**
     * Supprime toutes les notifications lues
     */
    #[Route('/delete-read', name: 'notification_delete_read', methods: ['POST'])]
    public function deleteRead(Request $request): Response
    {
        $currentUser = $this->getUser();

        if ($this->isCsrfTokenValid('delete-read-notifications', $request->request->get('_token'))) {
            $count = $this->notificationService->deleteReadNotificationsForUser($currentUser);
            
            if ($count > 0) {
                $this->addFlash('success', sprintf(
                    '%d notification%s supprimée%s.',
                    $count,
                    $count > 1 ? 's' : '',
                    $count > 1 ? 's' : ''
                ));
            } else {
                $this->addFlash('info', 'Aucune notification lue à supprimer.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('notification_index');
    }

    /**
     * API - Récupère le nombre de notifications non lues
     */
    #[Route('/api/unread-count', name: 'api_notification_unread_count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        $currentUser = $this->getUser();
        $count = $this->notificationService->countUnreadForUser($currentUser);

        return new JsonResponse(['count' => $count]);
    }

    /**
     * API - Récupère les notifications récentes
     */
    #[Route('/api/recent', name: 'api_notification_recent', methods: ['GET'])]
    public function recent(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $limit = $request->query->getInt('limit', 5);
        
        $notifications = $this->notificationService->getRecentNotificationsForUser($currentUser, $limit);

        $data = array_map(function (Notification $notification) {
            return [
                'id' => $notification->getId(),
                'type' => $notification->getType(),
                'title' => $notification->getTitle(),
                'message' => $notification->getMessage(),
                'icon' => $notification->getIcon(),
                'color_class' => $notification->getColorClass(),
                'time_ago' => $notification->getTimeAgo(),
                'is_read' => $notification->isRead(),
                'action_url' => $notification->getActionUrl(),
                'read_url' => $this->generateUrl('notification_read', ['id' => $notification->getId()]),
            ];
        }, $notifications);

        return new JsonResponse($data);
    }

    /**
     * Widget des notifications pour la sidebar
     */
    #[Route('/widget', name: 'notification_widget', methods: ['GET'])]
    public function widget(): Response
    {
        $currentUser = $this->getUser();
        $recentNotifications = $this->notificationService->getRecentNotificationsForUser($currentUser, 5);
        $unreadCount = $this->notificationService->countUnreadForUser($currentUser);

        return $this->render('notification/widget.html.twig', [
            'notifications' => $recentNotifications,
            'unread_count' => $unreadCount,
        ]);
    }
}