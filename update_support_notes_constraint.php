<?php
require_once 'koneksi.php';

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // Drop existing foreign key constraint
    $conn->exec("
        ALTER TABLE support_notes 
        DROP FOREIGN KEY support_notes_ibfk_2
    ");
    
    // Add new foreign key constraint with ON DELETE SET NULL
    $conn->exec("
        ALTER TABLE support_notes 
        ADD CONSTRAINT support_notes_ibfk_2 
        FOREIGN KEY (session_id) 
        REFERENCES sessions(id) 
        ON DELETE SET NULL
    ");
    
    // Commit transaction
    $conn->commit();
    
    echo "Database updated successfully. The support_notes table now has proper constraints.";
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "Error updating database: " . $e->getMessage();
}
?>
