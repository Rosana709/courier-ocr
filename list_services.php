<?php
use App\Kernel;
use App\Domain\Entity\Service;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

require 'vendor/autoload.php';

$kernel = new Kernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

$services = $em->getRepository(Service::class)->findAll();

foreach ($services as $service) {
    echo sprintf("ID: %s, Nom: %s\n", $service->getId(), $service->getNom());
}
echo "Count: " . count($services) . "\n";
