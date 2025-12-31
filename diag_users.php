<?php
require_once 'vendor/autoload.php';
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv('.env');
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$users = $em->getRepository(\App\Domain\Entity\Utilisateur::class)->findAll();
echo "Total Users: " . count($users) . "\n";
foreach ($users as $user) {
    echo "User: " . $user->getEmail() . " Service: ";
    try {
        $service = $user->getService();
        if ($service === null) {
            echo "NULL\n";
        } else {
            echo $service->getId() . " (" . $service->getNom() . ")\n";
        }
    } catch (\Exception $e) {
        echo "ERROR [" . get_class($e) . "]: " . $e->getMessage() . "\n";
    }
}
