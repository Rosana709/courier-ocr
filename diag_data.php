<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use App\Domain\Entity\Service;
use App\Domain\Entity\Utilisateur;
use App\Domain\Entity\Courrier;

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');

echo "--- SERVICES ---\n";
$services = $em->getRepository(Service::class)->findAll();
foreach ($services as $s) {
    echo sprintf("ID: %s | Nom: %s\n", $s->getId(), $s->getNom());
}

echo "\n--- USERS ---\n";
$users = $em->getRepository(Utilisateur::class)->findAll();
foreach ($users as $u) {
    echo sprintf("Email: %s | ServiceID: %s | Roles: %s\n", 
        $u->getEmail(), 
        $u->getService() ? $u->getService()->getId() : 'NULL',
        implode(',', $u->getRoles())
    );
}

echo "\n--- ERRANT ENTRANTS? (Type ENTRANT but maybe wrong service link) ---\n";
$courriers = $em->getRepository(Courrier::class)->findBy(['type' => 'ENTRANT']);
echo "Total ENTRANT: " . count($courriers) . "\n";
foreach ($courriers as $c) {
    $dest = $c->getTypeDestinataire() === 'SERVICE' ? $c->getServiceDestinataire()?->getId() : 'EXT';
    echo sprintf("Ref: %s | DestID: %s | Statut: %s\n", $c->getNumeroReference(), $dest, $c->getStatut());
}
