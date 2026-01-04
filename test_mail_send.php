<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

require __DIR__.'/vendor/autoload.php';

$dotenv = new Dotenv();
if (file_exists(__DIR__.'/.env')) $dotenv->load(__DIR__.'/.env');
if (file_exists(__DIR__.'/.env.local')) $dotenv->load(__DIR__.'/.env.local');

$dsn = $_ENV['MAILER_DSN'] ?? 'null://null';
echo "Tentative d'envoi avec DSN: $dsn\n";

try {
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);

    $email = (new Email())
        ->from('ne-pas-repondre@dgi.gov.mg')
        ->to('lucienrazanajatovo84@gmail.com')
        ->subject('Test d\'envoi d\'email - GC DGI')
        ->text('Ceci est un test pour vérifier la configuration de l\'envoi d\'emails.');

    $mailer->send($email);
    echo "Email envoyé avec succès !\n";
} catch (\Exception $e) {
    echo "ERREUR lors de l'envoi : " . $e->getMessage() . "\n";
    echo "Détails : " . get_class($e) . "\n";
}
