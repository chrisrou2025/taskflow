<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class UserProfileController extends AbstractController
{
    #[Route('/', name: 'user_profile', methods: ['GET'])]
    public function show(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/edit', name: 'user_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');
                return $this->redirectToRoute('user_profile');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour de votre profil.');
            }
        }

        return $this->render('user/profile_edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/change-password', name: 'user_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password', '');
            $newPassword = $request->request->get('new_password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            // Validation des données d'entrée
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');
                return $this->redirectToRoute('user_change_password');
            }

            // Vérification du mot de passe actuel
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                return $this->redirectToRoute('user_change_password');
            }

            // Vérification que les nouveaux mots de passe correspondent
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
                return $this->redirectToRoute('user_change_password');
            }

            // Vérification de la longueur du mot de passe
            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins 6 caractères.');
                return $this->redirectToRoute('user_change_password');
            }

            // Vérification que le nouveau mot de passe est différent de l'ancien
            if ($passwordHasher->isPasswordValid($user, $newPassword)) {
                $this->addFlash('error', 'Le nouveau mot de passe doit être différent de l\'ancien.');
                return $this->redirectToRoute('user_change_password');
            }

            try {
                // Hash et sauvegarde
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);

                $entityManager->flush();

                $this->addFlash('success', 'Votre mot de passe a été modifié avec succès !');
                return $this->redirectToRoute('user_profile');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la modification du mot de passe.');
                return $this->redirectToRoute('user_change_password');
            }
        }

        return $this->render('user/change_password.html.twig');
    }

    #[Route('/delete', name: 'user_delete_account', methods: ['GET', 'POST'])]
    public function deleteAccount(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorage
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $confirmationText = $request->request->get('confirmation_text', '');
            $password = $request->request->get('password_confirmation', '');
            $finalConfirmation = $request->request->get('final_confirmation');

            // 1. Vérifier le texte de confirmation
            if ($confirmationText !== 'SUPPRIMER') {
                $this->addFlash('error', 'Le texte de confirmation est incorrect.');
                return $this->redirectToRoute('user_delete_account');
            }

            // 2. Vérifier le mot de passe
            if (!$passwordHasher->isPasswordValid($user, $password)) {
                $this->addFlash('error', 'Le mot de passe est incorrect.');
                return $this->redirectToRoute('user_delete_account');
            }

            // 3. Vérifier la case à cocher finale
            if (!$finalConfirmation) {
                $this->addFlash('error', 'Vous devez cocher la case pour confirmer la suppression.');
                return $this->redirectToRoute('user_delete_account');
            }

            try {
                // Invalider la session (déconnexion) AVANT la suppression
                $tokenStorage->setToken(null);
                
                // Commencer une transaction
                $entityManager->beginTransaction();
                
                // 1. Supprimer toutes les notifications où l'utilisateur est destinataire ou expéditeur
                $notificationRepository = $entityManager->getRepository('App\Entity\Notification');
                $notifications = $notificationRepository->createQueryBuilder('n')
                    ->where('n.recipient = :user OR n.sender = :user')
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->getResult();
                
                foreach ($notifications as $notification) {
                    $entityManager->remove($notification);
                }

                // 2. Supprimer toutes les demandes de collaboration où l'utilisateur est concerné
                $collaborationRepository = $entityManager->getRepository('App\Entity\CollaborationRequest');
                $collaborations = $collaborationRepository->createQueryBuilder('cr')
                    ->where('cr.sender = :user OR cr.invitedUser = :user')
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->getResult();
                
                foreach ($collaborations as $collaboration) {
                    $entityManager->remove($collaboration);
                }

                // 3. Retirer l'utilisateur de tous les projets en tant que collaborateur
                $projects = $user->getCollaborations();
                foreach ($projects as $project) {
                    $project->removeCollaborator($user);
                }

                // 4. Pour les projets possédés, soit les supprimer soit les transférer
                $ownedProjects = $user->getProjects();
                foreach ($ownedProjects as $project) {
                    // Option 1: Supprimer complètement les projets
                    $entityManager->remove($project);
                }
                
                // 6. Enfin, supprimer l'utilisateur
                $entityManager->remove($user);
                
                // Valider la transaction
                $entityManager->flush();
                $entityManager->commit();

                $this->addFlash('success', 'Votre compte a été supprimé avec succès.');

                // Rediriger vers la page d'accueil
                return $this->redirectToRoute('app_home');

            } catch (\Exception $e) {
                // Annuler la transaction en cas d'erreur
                $entityManager->rollback();
                
                // Log l'erreur pour le débogage
                error_log('Erreur suppression compte: ' . $e->getMessage());
                
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression de votre compte.');
                return $this->redirectToRoute('user_delete_account');
            }
        }

        return $this->render('user/delete_account.html.twig', [
            'user' => $user,
        ]);
    }
}