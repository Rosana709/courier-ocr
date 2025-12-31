<?php
$dsn = "pgsql:host=127.0.0.1;port=5432;dbname=gestion_courier";
$user = "postgres";
$pass = "root";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Checking for orphaned service_id in utilisateur...\n";
    $sql = "SELECT u.id, u.email, u.service_id FROM utilisateur u 
            LEFT JOIN service s ON u.service_id = s.id 
            WHERE u.service_id IS NOT NULL AND s.id IS NULL";
    $orphans = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    print_r($orphans);

    echo "Checking for orphaned services in courrier...\n";
    $sql = "SELECT id, service_expediteur_id, service_destinataire_id FROM courrier 
            WHERE (service_expediteur_id IS NOT NULL AND service_expediteur_id NOT IN (SELECT id FROM service))
            OR (service_destinataire_id IS NOT NULL AND service_destinataire_id NOT IN (SELECT id FROM service))";
    $orphans = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    print_r($orphans);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
