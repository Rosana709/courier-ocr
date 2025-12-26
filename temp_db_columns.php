<?php
$pdo = new PDO('pgsql:host=127.0.0.1;port=5432;dbname=gestion_courier','postgres','postgres');
foreach (['service','utilisateur'] as $table) {
    echo "-- $table --\n";
    $stmt = $pdo->query("select column_name, data_type from information_schema.columns where table_name='{$table}' order by ordinal_position");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['column_name'].' => '.$row['data_type']."\n";
    }
}
