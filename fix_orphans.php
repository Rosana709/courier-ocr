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
// Trouver les utilisateurs dont le service_id ne correspond à aucun service existant
$sql = "SELECT id, email, service_id FROM utilisateur WHERE service_id IS NOT NULL AND service_id NOT IN (SELECT id FROM service)";
$stmt = $conn->prepare($sql);
$result = $stmt->executeQuery();
$orphans = $result->fetchAllAssociative();

echo "Found " . count($orphans) . " orphans.\n";

foreach ($orphans as $orphan) {
    echo "Fixing user: " . $orphan['email'] . "\n";
    // On utilise SQL direct pour éviter les contraintes de l'entité
    $conn->executeStatement("UPDATE utilisateur SET service_id = NULL, estactif = 0 WHERE id = ?", [$orphan['id']]);
}

echo "Done.\n";
