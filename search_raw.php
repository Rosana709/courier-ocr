<?php
$dbFile = __DIR__ . '/var/data.db';
if (!file_exists($dbFile)) {
    die("Database not found at $dbFile\n");
}

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $columns = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
        $searchableColumns = [];
        foreach ($columns as $col) {
            if (stripos($col['type'], 'varchar') !== false || stripos($col['type'], 'text') !== false || stripos($col['type'], 'string') !== false) {
                $searchableColumns[] = $col['name'];
            }
        }

        if (empty($searchableColumns)) continue;

        foreach ($searchableColumns as $column) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $column LIKE :search");
            $stmt->execute(['search' => '%PERSO%']);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                echo "Found $count matches in table [$table] column [$column]\n";
                $stmt = $pdo->prepare("SELECT * FROM $table WHERE $column LIKE :search LIMIT 5");
                $stmt->execute(['search' => '%PERSO%']);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                print_r($rows);
            }
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
