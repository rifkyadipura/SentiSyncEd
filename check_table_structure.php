<?php
require_once 'koneksi.php';

try {
    // Check the structure of support_notes table
    $stmt = $conn->prepare("SHOW CREATE TABLE support_notes");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Table structure for support_notes:\n";
    echo $result['Create Table'] ?? 'Not found';
    
    echo "\n\n";
    
    // Check the structure of sessions table
    $stmt = $conn->prepare("SHOW CREATE TABLE sessions");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Table structure for sessions:\n";
    echo $result['Create Table'] ?? 'Not found';
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
