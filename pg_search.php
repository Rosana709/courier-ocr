<?php
$dsn = "pgsql:host=127.0.0.1;port=5432;dbname=gestion_courier";
$user = "postgres";
$pass = "root";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tablesSql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
    $tables = $pdo->query($tablesSql)->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $colsSql = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = :table AND table_schema = 'public'";
        $stmt = $pdo->prepare($colsSql);
        $stmt->execute(['table' => $table]);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $searchableColumns = [];
        foreach ($columns as $col) {
            $type = strtolower($col['data_type']);
            if (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
                $searchableColumns[] = $col['column_name'];
            }
        }

        if (empty($searchableColumns)) continue;

        foreach ($searchableColumns as $column) {
            $sql = "SELECT COUNT(*) FROM \"$table\" WHERE CAST(\"$column\" AS TEXT) LIKE :search";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['search' => '%PERSO%']);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                echo "Found $count matches in table [$table] column [$column]\n";
                $sql = "SELECT * FROM \"$table\" WHERE CAST(\"$column\" AS TEXT) LIKE :search LIMIT 5";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['search' => '%PERSO%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                print_r($rows);
            }
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
