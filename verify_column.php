<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

try {
    // Try to fetch all users to verify the column works
    $utilisateurs = $em->getRepository(App\Domain\Entity\Utilisateur::class)->findAll();
    
    echo "✓ Successfully queried Utilisateur entity!\n";
    echo "Found " . count($utilisateurs) . " user(s)\n\n";
    
    foreach ($utilisateurs as $user) {
        echo "User: " . $user->getEmail() . "\n";
        echo "  Last Activity Checked: " . 
            ($user->getLastActivityCheckedAt() ? $user->getLastActivityCheckedAt()->format('Y-m-d H:i:s') : 'Never') . 
            "\n\n";
    }
    
    echo "✓ The 'last_activity_checked_at' column is working correctly!\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
