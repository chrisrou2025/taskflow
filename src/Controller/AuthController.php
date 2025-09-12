<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class AuthController extends AbstractController
{
    // Injection du service EmailVerifier
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est déjà connecté, redirection vers le dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        // Récupération des erreurs de connexion éventuelles
        $error = $authenticationUtils->getLastAuthenticationError();

        // Créer une réponse avec des en-têtes pour empêcher la mise en cache
        $response = $this->render('auth/login.html.twig', [
            'last_username' => '', // Toujours vide
            'error' => $error,
        ]);

        // Ajouter des en-têtes pour empêcher la mise en cache
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        // Si l'utilisateur est déjà connecté, redirection vers le dashboard
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hashage du mot de passe
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Attribution du rôle utilisateur par défaut
            $user->setRoles(['ROLE_USER']);

            // IMPORTANT : L'utilisateur n'est PAS vérifié à la création
            $user->setIsVerified(false);

            // Sauvegarde en base de données
            $entityManager->persist($user);
            $entityManager->flush();

            // Génération et envoi de l'email de vérification
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('noreply@taskflow.app', 'TaskFlow'))
                    ->to($user->getEmail())
                    ->subject('Veuillez confirmer votre adresse email')
                    ->htmlTemplate('auth/confirmation_email.html.twig')
            );

            // Message de succès modifié
            $this->addFlash('success', 'Votre compte a été créé ! Veuillez vérifier votre boîte email pour confirmer votre adresse.');

            return $this->redirectToRoute('app_login');
        }

        // Créer une réponse avec des en-têtes pour empêcher la mise en cache
        $response = $this->render('auth/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);

        // Ajouter des en-têtes pour empêcher la mise en cache
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $id = $request->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // Validation de l'email avec le token
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());
            return $this->redirectToRoute('app_register');
        }

        $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès ! Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            
            if (empty($email)) {
                $this->addFlash('error', 'Veuillez saisir votre adresse email.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $user = $userRepository->findOneBy(['email' => $email]);
            
            // Pour des raisons de sécurité, on affiche toujours le même message
            $this->addFlash('success', 'Si cette adresse email existe dans notre système, vous recevrez un lien de réinitialisation.');
            
            if ($user) {
                // Générer un token unique
                $resetToken = Uuid::v4()->toString();
                $user->setResetToken($resetToken);
                $user->setResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'));
                
                $entityManager->flush();
                
                // Envoyer l'email de réinitialisation
                $resetUrl = $this->generateUrl('app_reset_password', ['token' => $resetToken], UrlGeneratorInterface::ABSOLUTE_URL);
                
                $emailMessage = (new Email())
                    ->from('noreply@taskflow.app')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe TaskFlow')
                    ->html($this->renderView('auth/reset_password_email.html.twig', [
                        'user' => $user,
                        'resetUrl' => $resetUrl
                    ]));
                    
                try {
                    $mailer->send($emailMessage);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer plus tard.');
                }
            }
            
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/forgot_password.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $userRepository->findOneBy([
            'resetToken' => $token
        ]);

        // Vérifier si le token existe et n'a pas expiré
        if (!$user || !$user->getResetTokenExpiresAt() || $user->getResetTokenExpiresAt() < new \DateTimeImmutable()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            if (empty($newPassword) || strlen($newPassword) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);
            }

            // Mettre à jour le mot de passe
            $hashedPassword = $userPasswordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            
            // Supprimer le token de réinitialisation
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/reset_password.html.twig', [
            'token' => $token,
            'user' => $user
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut rester vide - elle sera interceptée par la clé logout dans security.yaml');
    }
}