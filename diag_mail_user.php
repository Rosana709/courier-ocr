<?php
require __DIR__.'/vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');
if (file_exists(__DIR__.'/.env.local')) {
    $dotenv->load(__DIR__.'/.env.local');
}

echo "MAILER_DSN: " . $_ENV['MAILER_DSN'] . "\n";
echo "APP_ENV: " . $_ENV['APP_ENV'] . "\n";

// Test DB connection and search user
try {
    $dsn = $_ENV['DATABASE_URL'];
    // Parse DATABASE_URL manually for simple PDO
    preg_match('/postgresql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/', $dsn, $matches);
    $user = $matches[1];
    $pass = $matches[2];
    $host = $matches[3];
    $port = $matches[4];
    $dbname = explode('?', $matches[5])[0];

    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass);
    $stmt = $pdo->prepare("SELECT email FROM utilisateur WHERE email LIKE :email");
    $emailSearch = '%lucien%';
    $stmt->execute(['email' => $emailSearch]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Users found with 'lucien':\n";
    foreach ($users as $u) {
        echo "- " . $u['email'] . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
