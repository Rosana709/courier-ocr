<?php
$dsn = "pgsql:host=127.0.0.1;port=5432;dbname=gestion_courier";
$user = "postgres";
$pass = "root";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- Services ---\n";
    $stmt = $pdo->query("SELECT id, nom FROM service");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}, Nom: {$row['nom']}\n";
    }

    echo "\n--- Users with Service PERSO ---\n";
    $stmt = $pdo->prepare("SELECT email, service_id FROM utilisateur WHERE service_id = 'PERSO'");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Email: {$row['email']}, Service: {$row['service_id']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
