<?php
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');
$conn = $em->getConnection();

$tables = $conn->iterateAssociative("SELECT table_name FROM information_schema.tables WHERE table_schema = (SELECT DATABASE())");

echo "GLOBAL_SEARCH_START\n";
foreach ($tables as $table) {
    $tableName = $table['table_name'];
    $columns = $conn->iterateAssociative("SELECT column_name FROM information_schema.columns WHERE table_schema = (SELECT DATABASE()) AND table_name = ?", [$tableName]);
    
    foreach ($columns as $column) {
        $columnName = $column['column_name'];
        $sql = "SELECT COUNT(*) as count FROM `$tableName` WHERE `$columnName` = 'PERSO'";
        try {
            $count = $conn->fetchOne($sql);
            if ($count > 0) {
                echo "Found $count matches in $tableName.$columnName\n";
                // Dump details
                $detailsSql = "SELECT * FROM `$tableName` WHERE `$columnName` = 'PERSO'";
                $rows = $conn->fetchAllAssociative($detailsSql);
                foreach ($rows as $row) {
                    echo "  Row: " . json_encode($row) . "\n";
                }
            }
        } catch (\Exception $e) {
            // Skip columns that don't support the equality check
        }
    }
}
echo "GLOBAL_SEARCH_END\n";
