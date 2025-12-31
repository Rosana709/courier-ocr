<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$connection = $em->getConnection();

try {
    $sql = "SELECT column_name, data_type, is_nullable 
            FROM information_schema.columns 
            WHERE table_name = 'utilisateur' 
            AND column_name = 'last_activity_checked_at'";
    
    $result = $connection->executeQuery($sql)->fetchAllAssociative();
    
    if (empty($result)) {
        echo "Column 'last_activity_checked_at' NOT FOUND in utilisateur table\n";
        echo "Adding column...\n";
        
        $connection->executeStatement(
            "ALTER TABLE utilisateur ADD COLUMN last_activity_checked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL"
        );
        
        echo "Column added successfully!\n";
    } else {
        echo "Column 'last_activity_checked_at' EXISTS:\n";
        print_r($result);
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
