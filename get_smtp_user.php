<?php
require __DIR__.'/vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
if (file_exists(__DIR__.'/.env')) $dotenv->load(__DIR__.'/.env');
if (file_exists(__DIR__.'/.env.local')) $dotenv->load(__DIR__.'/.env.local');

$dsn = $_ENV['MAILER_DSN'] ?? '';
$user = parse_url($dsn, PHP_URL_USER);
echo "SMTP_USER: " . urldecode($user) . "\n";
