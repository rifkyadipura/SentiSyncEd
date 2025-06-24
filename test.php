<?php
// Test PHP file to verify setup

echo "<h1>PHP Test Page</h1>";
echo "<h2>PHP Version: " . phpversion() . "</h2>";

echo "<h3>Server Information:</h3>";
echo "<pre>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Server Name: " . $_SERVER['SERVER_NAME'] . "\n";
echo "</pre>";

echo "<h3>Database Connection Test:</h3>";
try {
    $conn = new mysqli(
        getenv('DB_HOST') ?: 'db', 
        getenv('DB_USER') ?: 'sentisyncuser', 
        getenv('DB_PASSWORD') ?: 'sentisyncpassword', 
        getenv('DB_NAME') ?: 'sentisyncdb'
    );
    
    if ($conn->connect_error) {
        echo "<div style='color: red;'>Database connection failed: " . $conn->connect_error . "</div>";
    } else {
        echo "<div style='color: green;'>Database connection successful!</div>";
        $conn->close();
    }
} catch (Exception $e) {
    echo "<div style='color: red;'>Database error: " . $e->getMessage() . "</div>";
}

echo "<h3>PHP Extensions:</h3>";
echo "<pre>";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $extension) {
    echo $extension . "\n";
}
echo "</pre>";

echo "<h3>PHP Configuration:</h3>";
echo "<pre>";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "error_reporting: " . ini_get('error_reporting') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "</pre>";
?>
