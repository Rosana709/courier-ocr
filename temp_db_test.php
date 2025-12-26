<?php
try {
    $pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=gestion_courier','postgres','postgres');
    $count = $pdo->query('select count(*) from service')->fetchColumn();
    echo "services=$count\n";
} catch (Throwable $e) {
    echo 'ERR '.$e->getMessage();
}
