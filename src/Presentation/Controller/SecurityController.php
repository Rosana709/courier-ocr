<?php
declare(strict_types=1);

namespace App\Presentation\Controller;

use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MailerInterface $mailer
    ) {
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette methode peut rester vide - elle sera interceptee par la cle logout.');
    }

    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));
            /** @var Utilisateur|null $user */
            $user = $this->utilisateurRepository->findByEmail($email);

            if (!$user) {
                $this->addFlash('error', 'Aucun utilisateur trouve avec cet email.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $tempPassword = substr(bin2hex(random_bytes(8)), 0, 12);
            $hashed = $this->passwordHasher->hashPassword($user, $tempPassword);
            $user->updatePassword($hashed);
            $user->exigerChangementMotDePasse();
            $this->utilisateurRepository->save($user);

            try {
                $mail = (new Email())
                    ->from('razanajatovoestelle@gmail.com')
                    ->to($email)
                    ->subject('Votre mot de passe temporaire - Gestion de Courrier')
                    ->text("Bonjour,\n\nVoici votre mot de passe temporaire : {$tempPassword}\nVeuillez vous connecter puis le modifier immediatement.\n\nCordialement,\nDGI")
                    ->html("<p>Bonjour,</p><p>Voici votre mot de passe temporaire :</p><p><strong>{$tempPassword}</strong></p><p>Veuillez vous connecter puis le modifier immediatement.</p><p>Cordialement,<br>DGI</p>");

                $this->mailer->send($mail);
                $this->addFlash('success', 'Un mot de passe temporaire vous a ete envoye par email.');
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Echec de l\'envoi de l\'email. Verifiez la configuration SMTP. Detail: '.$e->getMessage());
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/changement-mot-de-passe-obligatoire', name: 'app_force_password_change', methods: ['GET', 'POST'])]
    public function forceChangePassword(Request $request): Response
    {
        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $newPassword = (string) $request->request->get('new_password');
            $confirmPassword = (string) $request->request->get('confirm_password');

            if ($newPassword === '' || $confirmPassword === '') {
                $this->addFlash('error', 'Merci de saisir un mot de passe.');
                return $this->redirectToRoute('app_force_password_change');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_force_password_change');
            }

            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caracteres.');
                return $this->redirectToRoute('app_force_password_change');
            }

            $hashed = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->updatePassword($hashed);
            $user->leverExigenceChangementMotDePasse();
            $this->utilisateurRepository->save($user);

            $this->addFlash('success', 'Mot de passe mis a jour.');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('security/force_password_change.html.twig');
    }
}
