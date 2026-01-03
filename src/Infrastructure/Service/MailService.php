<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailService
{
    public function __construct(
        private readonly MailerInterface $mailer
    ) {
    }

    public function sendWelcomeEmail(string $recipientEmail, string $password, string $role, ?string $serviceName = null): void
    {
        $roleLabel = ($role === 'ROLE_ADMIN') ? 'Administrateur' : 'Utilisateur de service';
        $roleInfo = sprintf('<li><strong>Rôle :</strong> %s</li>', $roleLabel);
        $serviceInfo = $serviceName ? sprintf('<li><strong>Service affecté :</strong> %s</li>', $serviceName) : '';

        $email = (new Email())
            ->from('ne-pas-repondre@dgi.gov.mg')
            ->to($recipientEmail)
            ->subject('Bienvenue sur la plateforme de Gestion de Courrier')
            ->html(sprintf(
                '<h1>Bienvenue !</h1>
                <p>Votre compte a été créé avec succès par un administrateur.</p>
                <p>Voici vos identifiants de connexion :</p>
                <ul>
                    <li><strong>Email :</strong> %s</li>
                    <li><strong>Mot de passe par défaut :</strong> %s</li>
                    %s
                    %s
                </ul>
                <p>Nous vous recommandons de changer votre mot de passe dès votre première connexion.</p>
                <p><a href="http://localhost:8000/login">Accéder à la plateforme</a></p>',
                $recipientEmail,
                $password,
                $roleInfo,
                $serviceInfo
            ));

        $this->mailer->send($email);
    }

    public function sendStatusChangeEmail(string $recipientEmail, bool $isActive): void
    {
        $message = $isActive 
            ? 'Nous vous informons que votre compte a été activé par un administrateur. Vous pouvez désormais accéder à la plateforme.' 
            : 'Nous vous informons que votre compte a été désactivé par un administrateur. Par conséquent, vous ne pouvez plus accéder à la plateforme.';

        $link = $isActive ? '<p><a href="http://localhost:8000/login">Accéder à la plateforme</a></p>' : '';

        $email = (new Email())
            ->from('ne-pas-repondre@dgi.gov.mg')
            ->to($recipientEmail)
            ->subject('Mise à jour de votre compte - Gestion de Courrier')
            ->html(sprintf(
                '<h1>Mise à jour de votre compte</h1>
                <p>%s</p>
                %s',
                $message,
                $link
            ));

        $this->mailer->send($email);
    }

    public function sendServiceAssignmentEmail(string $recipientEmail, string $serviceName): void
    {
        $email = (new Email())
            ->from('ne-pas-repondre@dgi.gov.mg')
            ->to($recipientEmail)
            ->subject('Affectation à un nouveau service - Gestion de Courrier')
            ->html(sprintf(
                '<h1>Nouvelle affectation</h1>
                <p>Vous avez été affecté au service : <strong>%s</strong>.</p>
                <p>Vous pouvez désormais gérer les courriers de ce service.</p>
                <p><a href="http://localhost:8000/login">Accéder à la plateforme</a></p>',
                $serviceName
            ));

        $this->mailer->send($email);
    }
}
