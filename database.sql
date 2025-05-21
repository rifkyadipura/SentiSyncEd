-- Create database
CREATE DATABASE IF NOT EXISTS sentisynced;
USE sentisynced;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('SuperAdmin', 'Mahasiswa', 'Dosen') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create sessions table
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_time DATETIME NOT NULL,
    end_time DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create classes table
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    description TEXT,
    dosen_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dosen_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Create emotions table
CREATE TABLE IF NOT EXISTS emotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id INT NOT NULL,
    emotion ENUM('Senang', 'Stres', 'Lelah', 'Netral') NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
);

-- Create support_notes table
CREATE TABLE IF NOT EXISTS support_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id INT NULL,
    class_id INT NULL,
    message TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- Create logs table
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Classes table already created above
-- This is a duplicate and has been removed

CREATE TABLE class_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE class_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME,
    status ENUM('active', 'ended') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Add class_session_id to emotions table
ALTER TABLE emotions 
ADD COLUMN class_session_id INT,
ADD FOREIGN KEY (class_session_id) REFERENCES class_sessions(id) ON DELETE SET NULL;

-- Create emotion_alert_views table to track viewed alerts
CREATE TABLE IF NOT EXISTS emotion_alert_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT NOT NULL,
    class_session_id INT NOT NULL,
    alert_timestamp VARCHAR(50) NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dosen_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (class_session_id) REFERENCES class_sessions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_alert (dosen_id, class_session_id, alert_timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insert initial users
INSERT INTO users (name, email, password, role) VALUES
('Super Admin', 'SuperAdmin@sentisynced.com', '$2y$10$eJqkqmLEK/KqLwaY3FRVfurgemJ/3wZtyYiIqQPlfzyDsJJIfFmzC', 'SuperAdmin'),
('Mahasiswa Contoh', 'mahasiswa@example.com', '$2y$10$VkiPl.F6oJdG6LpWd/bhtekdMd3z1HgFc0nmWuYkdRToD9JnaN3E2', 'Mahasiswa'),
('Dosen Contoh', 'dosen@example.com', '$2y$10$UCmJMYK6yKJcRXpHayNZQeiBjowtN7eIBjowtIjf7G5jELFDOWesG', 'Dosen');

-- Insert dummy classes (dosennya pakai id dosen contoh = 3)
INSERT INTO classes (class_name, description, dosen_id) VALUES
('Rekayasa Perangkat Lunak II (21)', 'Kelas Rekayasa Perangkat Lunak tingkat lanjut', 3),
('Literasi Manusia (21)', 'Kelas Literasi dan pengembangan sumber daya manusia', 3);

-- Misal id kelas:
-- id 1 = Rekayasa Perangkat Lunak II (21)
-- id 2 = Literasi Manusia (21)

-- Insert one active class_session for "Rekayasa Perangkat Lunak II (21)"
INSERT INTO class_sessions (class_id, start_time, end_time, status, created_by) VALUES
(1, NOW(), NULL, 'active', 3);

-- Insert dummy class members (hanya mahasiswa contoh id=2)
INSERT INTO class_members (class_id, user_id) VALUES
(1, 2),  -- Mahasiswa di kelas 1
(2, 2);  -- Mahasiswa di kelas 2

-- Insert dummy sessions (misal untuk sesi umum)
INSERT INTO sessions (start_time, end_time) VALUES
(NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 1 HOUR),
(NOW() - INTERVAL 1 HOUR, NOW());

-- Insert dummy emotions for mahasiswa di sesi aktif kelas 1
SET @session_id = (SELECT id FROM sessions ORDER BY id DESC LIMIT 1);
SET @class_session_id = (SELECT id FROM class_sessions WHERE class_id = 1 AND status = 'active' LIMIT 1);

INSERT INTO emotions (user_id, session_id, class_session_id, emotion, timestamp) VALUES
(2, @session_id, @class_session_id, 'Senang', NOW() - INTERVAL 50 MINUTE),
(2, @session_id, @class_session_id, 'Stres', NOW() - INTERVAL 40 MINUTE),
(2, @session_id, @class_session_id, 'Lelah', NOW() - INTERVAL 30 MINUTE),
(2, @session_id, @class_session_id, 'Netral', NOW() - INTERVAL 20 MINUTE);
