<?php
require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();
$connection = $entityManager->getConnection();

$out = "";

$tables = ['utilisateur', 'service', 'historique_action', 'notification', 'courrier'];

foreach ($tables as $table) {
    echo "Processing $table...\n";
    $out .= "--- $table ---\n";
    try {
        $rows = $connection->fetchAllAssociative("SELECT * FROM $table");
        foreach ($rows as $row) {
            foreach ($row as $col => $val) {
                if ($val === 'razanajatovoestelle@gmail.com') {
                    $out .= "Found EMAIL in $table.$col row ID: {$row['id']}\n";
                }
            }
        }
        
        // Check for non-UUIDs in likely columns
        if ($table === 'utilisateur') {
            foreach ($rows as $row) {
                if (!preg_match('/^[0-9a-f-]{36}$/i', $row['id'])) {
                    $out .= "Non-UUID ID in utilisateur: [{$row['id']}] for email [{$row['email']}]\n";
                }
            }
        }
        
        if ($table === 'historique_action') {
             foreach ($rows as $row) {
                if (!preg_match('/^[0-9a-f-]{36}$/i', $row['effectue_par_id'])) {
                    $out .= "Non-UUID effectue_par_id in historique_action: [{$row['effectue_par_id']}] row ID: {$row['id']}\n";
                }
            }
        }

    } catch (\Exception $e) {
        $out .= "Error reading $table: " . $e->getMessage() . "\n";
    }
}

file_put_contents('debug_ids.txt', $out);
echo "Done. Check debug_ids.txt\n";
