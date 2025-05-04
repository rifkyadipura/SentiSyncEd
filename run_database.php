<?php
require_once 'koneksi.php';

try {
    // Drop existing database
    $conn->exec("DROP DATABASE IF EXISTS sentisynced");
    
    // Create and use database
    $conn->exec("CREATE DATABASE IF NOT EXISTS sentisynced");
    $conn->exec("USE sentisynced");
    
    // Read and execute SQL file
    $sql = file_get_contents('database.sql');
    
    // Split SQL into individual statements
    $statements = explode(';', $sql);
    
    foreach ($statements as $statement) {
        $trimmed = trim($statement);
        if (!empty($trimmed)) {
            $conn->exec($trimmed . ';');
        }
    }
    
    echo "Database berhasil diinstal ulang\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    echo "\nQuery yang gagal: " . $conn->lastQuery();
}
?>
