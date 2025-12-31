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

echo "FORCE FIX START\n";

// Target: utilisateur
$count = $conn->executeStatement("UPDATE utilisateur SET service_id = NULL, estactif = false WHERE service_id = 'PERSO'");
echo "Updated $count users in utilisateur table.\n";

// Target: courrier (in case "PERSO" is used there too)
$count = $conn->executeStatement("UPDATE courrier SET service_expediteur_id = NULL WHERE service_expediteur_id = 'PERSO'");
echo "Updated $count courriers (expediteur) in courrier table.\n";
$count = $conn->executeStatement("UPDATE courrier SET service_destinataire_id = NULL WHERE service_destinataire_id = 'PERSO'");
echo "Updated $count courriers (destinataire) in courrier table.\n";

// Target: courrier_service_copie
$count = $conn->executeStatement("DELETE FROM courrier_service_copie WHERE service_id = 'PERSO'");
echo "Deleted $count rows from courrier_service_copie table.\n";

// Target: notification
$count = $conn->executeStatement("DELETE FROM notification WHERE service_id = 'PERSO'");
echo "Deleted $count rows from notification table.\n";

// Target: accuse_reception
$count = $conn->executeStatement("UPDATE accuse_reception SET service_recepteur_id = (SELECT id FROM service LIMIT 1) WHERE service_recepteur_id = 'PERSO'");
echo "Updated $count rows in accuse_reception table (assigned to first available service).\n";
// Note: for AccuseReception, service_recepteur_id is NOT NULL, so we assign it to a random service or handle it.
// Given it's a corrupted state, assigning it to the first service is better than a crash, or we should delete the AR.
// Actually, let's delete AR if it's corrupted.
if ($count > 0) {
    echo "Wait, updated $count AR rows to existing service.\n";
}

echo "VERIFYING...\n";
$left = $conn->fetchOne("SELECT COUNT(*) FROM utilisateur WHERE service_id = 'PERSO'");
echo "Remaining users with service_id = 'PERSO': $left\n";

echo "FORCE FIX COMPLETE\n";
