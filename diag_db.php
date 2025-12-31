<?php
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$conn = $em->getConnection();
$sql = "SELECT id, email, service_id FROM utilisateur";
$stmt = $conn->prepare($sql);
$result = $stmt->executeQuery();
$users = $result->fetchAllAssociative();

echo "USER_DUMP_START\n";
foreach ($users as $user) {
    echo "ID: " . $user['id'] . " Email: " . $user['email'] . " ServiceID: " . ($user['service_id'] ?? 'NULL') . "\n";
}
echo "USER_DUMP_END\n";

$sql = "SELECT id, nom FROM service";
$stmt = $conn->prepare($sql);
$result = $stmt->executeQuery();
$services = $result->fetchAllAssociative();

echo "SERVICE_DUMP_START\n";
foreach ($services as $service) {
    echo "ID: " . $service['id'] . " Nom: " . $service['nom'] . "\n";
}
echo "SERVICE_DUMP_END\n";
