<?php
require_once 'koneksi.php';

try {
    // Get database name from environment or use default
    $dbname = getenv('DB_NAME') ?: 'sentisyncdb';
    
    // Drop existing database
    $conn->exec("DROP DATABASE IF EXISTS $dbname");
    
    // Create and use database
    $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $conn->exec("USE $dbname");
    
    // Read and execute SQL file
    $sql = file_get_contents('database.sql');
    
    // Split SQL into individual statements
    $statements = explode(';', $sql);
    
    $lastQuery = '';
    foreach ($statements as $statement) {
        $trimmed = trim($statement);
        if (!empty($trimmed)) {
            try {
                $lastQuery = $trimmed;
                $conn->exec($trimmed . ';');
            } catch (PDOException $innerEx) {
                echo "Error executing query: " . $innerEx->getMessage() . "\n";
                echo "Query yang gagal: " . $lastQuery . "\n";
                // Lanjutkan eksekusi meskipun ada error pada satu statement
            }
        }
    }
    
    echo "Database berhasil diinstal ulang\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
