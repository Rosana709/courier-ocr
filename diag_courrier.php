<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use App\Domain\Entity\Courrier;
use App\Domain\Repository\CourrierRepositoryInterface;

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
$repo = $container->get(CourrierRepositoryInterface::class);

$all = $repo->findAll();
echo "Total courriers: " . count($all) . "\n";

foreach ($all as $c) {
    echo sprintf("- [%s] Type: %s, Exped: %s, Dest: %s, Statut: %s, Ref: %s\n",
        $c->getId(),
        $c->getType(),
        $c->getTypeExpediteur() === 'SERVICE' ? $c->getServiceExpediteur()?->getNom() : $c->getPersonneExterneExpediteur()?->getNomOuRaisonSociale(),
        $c->getTypeDestinataire() === 'SERVICE' ? $c->getServiceDestinataire()?->getNom() : $c->getPersonneExterneDestinataire()?->getNomOuRaisonSociale(),
        $c->getStatut(),
        $c->getNumeroReference()
    );
}
