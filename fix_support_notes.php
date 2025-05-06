<?php
require_once 'koneksi.php';

try {
    // Begin transaction
    $conn->beginTransaction();
    
    echo "Starting database fix...\n";
    
    // Step 1: Modify session_id to allow NULL
    $conn->exec("ALTER TABLE support_notes MODIFY session_id INT NULL");
    echo "Modified session_id to allow NULL values.\n";
    
    // Step 2: Check if class_id column exists, if not add it
    $stmt = $conn->prepare("SHOW COLUMNS FROM support_notes LIKE 'class_id'");
    $stmt->execute();
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE support_notes ADD COLUMN class_id INT NULL");
        echo "Added class_id column.\n";
    } else {
        echo "class_id column already exists.\n";
    }
    
    // Step 3: Check if foreign key for session_id exists
    $stmt = $conn->prepare("
        SELECT * FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'sentisynced'
        AND TABLE_NAME = 'support_notes'
        AND COLUMN_NAME = 'session_id'
        AND REFERENCED_TABLE_NAME = 'sessions'
    ");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // No foreign key exists, add it
        try {
            $conn->exec("
                ALTER TABLE support_notes
                ADD CONSTRAINT fk_support_notes_session
                FOREIGN KEY (session_id) REFERENCES sessions(id)
                ON DELETE SET NULL
            ");
            echo "Added foreign key constraint for session_id.\n";
        } catch (PDOException $e) {
            echo "Could not add foreign key constraint: " . $e->getMessage() . "\n";
            
            // Check if there are invalid session_id values
            $stmt = $conn->prepare("
                SELECT COUNT(*) as invalid_count
                FROM support_notes sn
                LEFT JOIN sessions s ON sn.session_id = s.id
                WHERE sn.session_id IS NOT NULL AND s.id IS NULL
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['invalid_count'] > 0) {
                echo "Found {$result['invalid_count']} support_notes with invalid session_id references.\n";
                
                // Get a valid session ID or create one
                $stmt = $conn->prepare("SELECT id FROM sessions LIMIT 1");
                $stmt->execute();
                $validSession = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$validSession) {
                    // Create a new session
                    $conn->exec("INSERT INTO sessions (start_time) VALUES (NOW())");
                    $validSessionId = $conn->lastInsertId();
                    echo "Created new session with ID: $validSessionId\n";
                } else {
                    $validSessionId = $validSession['id'];
                    echo "Found existing session with ID: $validSessionId\n";
                }
                
                // Update invalid session_id references
                $conn->exec("
                    UPDATE support_notes 
                    SET session_id = $validSessionId
                    WHERE session_id IS NOT NULL AND 
                    session_id NOT IN (SELECT id FROM sessions)
                ");
                echo "Updated invalid session_id references to valid session ID.\n";
                
                // Try adding the foreign key again
                try {
                    $conn->exec("
                        ALTER TABLE support_notes
                        ADD CONSTRAINT fk_support_notes_session
                        FOREIGN KEY (session_id) REFERENCES sessions(id)
                        ON DELETE SET NULL
                    ");
                    echo "Successfully added foreign key constraint after fixing data.\n";
                } catch (PDOException $e2) {
                    echo "Still could not add foreign key constraint: " . $e2->getMessage() . "\n";
                    echo "Setting all invalid session_id values to NULL instead.\n";
                    
                    // Set all invalid session_id values to NULL
                    $conn->exec("
                        UPDATE support_notes 
                        SET session_id = NULL
                        WHERE session_id IS NOT NULL AND 
                        session_id NOT IN (SELECT id FROM sessions)
                    ");
                    
                    // Try one more time
                    try {
                        $conn->exec("
                            ALTER TABLE support_notes
                            ADD CONSTRAINT fk_support_notes_session
                            FOREIGN KEY (session_id) REFERENCES sessions(id)
                            ON DELETE SET NULL
                        ");
                        echo "Successfully added foreign key constraint after setting invalid values to NULL.\n";
                    } catch (PDOException $e3) {
                        echo "Final attempt failed. Manual database intervention may be required.\n";
                    }
                }
            }
        }
    } else {
        echo "Foreign key for session_id already exists.\n";
    }
    
    // Step 4: Check if foreign key for class_id exists
    $stmt = $conn->prepare("
        SELECT * FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'sentisynced'
        AND TABLE_NAME = 'support_notes'
        AND COLUMN_NAME = 'class_id'
        AND REFERENCED_TABLE_NAME = 'classes'
    ");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0 && $stmt->rowCount() > 0) { // Only if class_id column exists
        // No foreign key exists, add it
        try {
            $conn->exec("
                ALTER TABLE support_notes
                ADD CONSTRAINT fk_support_notes_class
                FOREIGN KEY (class_id) REFERENCES classes(id)
                ON DELETE CASCADE
            ");
            echo "Added foreign key constraint for class_id.\n";
        } catch (PDOException $e) {
            echo "Could not add foreign key constraint for class_id: " . $e->getMessage() . "\n";
        }
    }
    
    // Commit transaction
    $conn->commit();
    echo "Database fix completed successfully!\n";
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "Error fixing database: " . $e->getMessage() . "\n";
}
?>
