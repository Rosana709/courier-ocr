<?php
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

try {
    $dotenv = new Dotenv();
    $dotenv->loadEnv(__DIR__.'/.env');

    $kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
    $kernel->boot();
    $container = $kernel->getContainer();
    $em = $container->get('doctrine.orm.entity_manager');
    $conn = $em->getConnection();

    echo "Connected to: " . $conn->getDatabase() . "\n";

    // For Postgres, we can use pg_catalog or information_schema
    // Let's use information_schema which is more standard
    $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'";
    $tables = $conn->fetchAllAssociative($sql);

    echo "Found " . count($tables) . " tables.\n";

    foreach ($tables as $table) {
        $tableName = $table['table_name'];
        
        $colSql = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = ? AND table_schema = 'public'";
        $columns = $conn->fetchAllAssociative($colSql, [$tableName]);
        
        foreach ($columns as $column) {
            $columnName = $column['column_name'];
            $dataType = $column['data_type'];
            
            // Only search in text-like columns
            if (strpos($dataType, 'char') !== false || strpos($dataType, 'text') !== false) {
                // Use LIKE to catch variations if any
                $searchSql = "SELECT COUNT(*) FROM \"$tableName\" WHERE \"$columnName\" = 'PERSO'";
                try {
                    $count = $conn->fetchOne($searchSql);
                    if ($count > 0) {
                        echo "MATCH: table=$tableName column=$columnName count=$count\n";
                        $rows = $conn->fetchAllAssociative("SELECT * FROM \"$tableName\" WHERE \"$columnName\" = 'PERSO'");
                        foreach ($rows as $row) {
                            echo "  DATE: " . json_encode($row) . "\n";
                        }
                    }
                } catch (\Exception $e) {
                    // echo "Error searching $tableName.$columnName: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    echo "SEARCH_COMPLETE\n";
} catch (\Exception $e) {
    echo "GLOBAL_ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
