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

echo "--- SERVICES ---\n";
$services = $conn->fetchAllAssociative("SELECT * FROM service");
foreach ($services as $s) {
    echo json_encode($s) . "\n";
}

echo "--- TARGET USER ---\n";
$user = $conn->fetchAssociative("SELECT * FROM utilisateur WHERE email = 'razanajatovolucienne@gmail.com'");
echo json_encode($user) . "\n";

echo "--- USERS WITH SERVICE_ID = PERSO ---\n";
$users = $conn->fetchAllAssociative("SELECT email, service_id FROM utilisateur WHERE service_id = 'PERSO'");
foreach ($users as $u) {
    echo json_encode($u) . "\n";
}
