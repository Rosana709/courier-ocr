<?php
require 'vendor/autoload.php';
use Symfony\Component\Uid\Uuid;

$databaseUrl = getenv('DATABASE_URL') ?: 'postgresql://postgres:postgres@127.0.0.1:5432/gestion_courier';

function parseDatabaseUrl(string $url): array {
    $parts = parse_url($url);
    if ($parts === false) {
        throw new RuntimeException('DATABASE_URL invalide');
    }
    $path = $parts['path'] ?? '';
    return [
        'host' => $parts['host'] ?? '127.0.0.1',
        'port' => $parts['port'] ?? 5432,
        'dbname' => ltrim($path, '/'),
        'user' => $parts['user'] ?? 'postgres',
        'pass' => $parts['pass'] ?? '',
    ];
}

function slugify(string $value): string {
    $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
    $value = strtolower((string) $value);
    $value = preg_replace('/[^a-z0-9]+/', '.', $value) ?? '';
    return trim($value, '.');
}

try {
    $db = parseDatabaseUrl($databaseUrl);
    $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $db['host'], $db['port'], $db['dbname']);
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $services = $pdo->query('SELECT id, nom, mail, sigle FROM service WHERE estactif = true')->fetchAll(PDO::FETCH_ASSOC);
    $checkByService = $pdo->prepare('SELECT id, email FROM utilisateur WHERE service_id = :sid LIMIT 1');
    $checkByEmail = $pdo->prepare('SELECT id FROM utilisateur WHERE email = :email LIMIT 1');
    $insert = $pdo->prepare('INSERT INTO utilisateur (id, service_id, email, password, roles, estactif, datecreation, datemodification) VALUES (:id, :service_id, :email, :password, :roles, true, :datecreation, NULL)');

    $created = 0;
    $skipped = 0;
    foreach ($services as $service) {
        $checkByService->execute(['sid' => $service['id']]);
        $existing = $checkByService->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            $skipped++;
            echo 'SKIP '.$service['nom'].' -> '.$existing['email']."\n";
            continue;
        }

        $email = $service['mail'] ?? '';
        if (trim((string) $email) === '') {
            $base = $service['sigle'] ?: $service['nom'] ?: $service['id'];
            $slug = slugify((string) $base);
            if ($slug === '') {
                $slug = 'service-'.strtolower($service['id']);
            }
            $email = $slug.'@dgi.gov';
        }

        // ensure email unique
        $candidate = $email;
        $suffix = 1;
        while (true) {
            $checkByEmail->execute(['email' => $candidate]);
            if (!$checkByEmail->fetch(PDO::FETCH_ASSOC)) {
                $email = $candidate;
                break;
            }
            $candidate = preg_replace('/@/', '+'.$suffix.'@', $email, 1);
            $suffix++;
        }

        $passwordHash = password_hash('dgi2025', PASSWORD_BCRYPT);
        $id = Uuid::v4()->toRfc4122();
        $insert->execute([
            'id' => $id,
            'service_id' => $service['id'],
            'email' => $email,
            'password' => $passwordHash,
            'roles' => json_encode(['ROLE_SERVICE']),
            'datecreation' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
        $created++;
        echo 'OK   '.$service['nom'].' -> '.$email."\n";
    }

    echo "Summary: created=$created skipped=$skipped\n";
} catch (Throwable $e) {
    echo 'ERR '.$e->getMessage()."\n";
}
