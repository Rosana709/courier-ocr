<?php

// Direct PostgreSQL connection to add missing column
$host = '127.0.0.1';
$port = '5432';
$dbname = 'gestion_courier';
$user = 'postgres';
$password = 'root';

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully\n";
    
    // Check if column exists
    $stmt = $pdo->query("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_name = 'utilisateur' 
        AND column_name = 'last_activity_checked_at'
    ");
    
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "Column 'last_activity_checked_at' does NOT exist. Adding it now...\n";
        
        $pdo->exec("
            ALTER TABLE utilisateur 
            ADD COLUMN last_activity_checked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL
        ");
        
        echo "✓ Column 'last_activity_checked_at' added successfully!\n";
    } else {
        echo "✓ Column 'last_activity_checked_at' already exists.\n";
    }
    
    // Verify the column was added
    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable 
        FROM information_schema.columns 
        WHERE table_name = 'utilisateur' 
        AND column_name = 'last_activity_checked_at'
    ");
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        echo "\nColumn details:\n";
        echo "  Name: " . $result['column_name'] . "\n";
        echo "  Type: " . $result['data_type'] . "\n";
        echo "  Nullable: " . $result['is_nullable'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
