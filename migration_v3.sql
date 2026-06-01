-- Run this in phpMyAdmin on barangay_db
-- migration_v3.sql

-- 1. Resident accounts table
CREATE TABLE IF NOT EXISTS residents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    birthday DATE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Admin accounts table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Seed the predefined admin account (username: admin, password: 123)
INSERT INTO admins (username, password)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username=username;
-- Note: that hash above is bcrypt for "password". We'll use a PHP script to seed
-- the real hash. See seed_admin.php below.

-- 4. Add resident_id to complaints so we know who filed it
ALTER TABLE complaints
    ADD COLUMN IF NOT EXISTS resident_id INT DEFAULT NULL;

-- 5. Update resident_notifications: add resident_id so only
--    the complainant sees their own notifications
ALTER TABLE resident_notifications
    ADD COLUMN IF NOT EXISTS resident_id INT DEFAULT NULL;
