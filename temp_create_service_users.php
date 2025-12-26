<?php
putenv('APP_ENV=prod');
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'prod';
putenv('APP_DEBUG=0');
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0';
require 'config/bootstrap.php';
require 'vendor/autoload.php';

use App\Application\DTO\CreateUtilisateurDTO;
use App\Application\UseCase\Utilisateur\CreateUtilisateurUseCase;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\ServiceRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;

$kernel = new App\Kernel('prod', false);
$kernel->boot();
$container = $kernel->getContainer();

/** @var ServiceRepositoryInterface $serviceRepo */
$serviceRepo = $container->get(ServiceRepositoryInterface::class);
/** @var UtilisateurRepositoryInterface $userRepo */
$userRepo = $container->get(UtilisateurRepositoryInterface::class);
/** @var CreateUtilisateurUseCase $createUser */
$createUser = $container->get(CreateUtilisateurUseCase::class);

$password = 'dgi2025';
$services = $serviceRepo->findActifs();
$created = 0;
$skipped = 0;

$slugify = function (string $value): string {
    $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
    $value = strtolower((string) $value);
    $value = preg_replace('/[^a-z0-9]+/', '.', $value) ?? '';
    return trim($value, '.');
};

foreach ($services as $service) {
    $existing = $userRepo->findByService($service);
    if ($existing !== null) {
        $skipped++;
        echo 'SKIP '.$service->getNom().' -> '.$existing->getEmail()."\n";
        continue;
    }

    $email = $service->getMail();
    if ($email === null || trim($email) === '') {
        $base = $service->getSigle() ?? $service->getNom() ?? $service->getId();
        $slug = $slugify((string) $base);
        if ($slug === '') {
            $slug = 'service-'.strtolower($service->getId());
        }
        $email = $slug.'@dgi.gov';
    }

    try {
        $dto = new CreateUtilisateurDTO(
            email: $email,
            role: Utilisateur::ROLE_SERVICE,
            serviceId: $service->getId(),
            password: $password
        );
        $createUser->execute($dto);
        $created++;
        echo 'OK   '.$service->getNom().' -> '.$email."\n";
    } catch (\Throwable $e) {
        echo 'ERR  '.$service->getNom().' : '.$e->getMessage()."\n";
    }
}

echo "Summary: created=$created skipped=$skipped\n";
