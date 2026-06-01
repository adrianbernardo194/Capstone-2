-- Run this in phpMyAdmin on barangay_db

-- 1. Add new columns to complaints table
ALTER TABLE complaints
  ADD COLUMN IF NOT EXISTS admin_comment TEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS appointment_date DATE DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS appointment_time VARCHAR(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS lupon_preference VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS resident_notified TINYINT(1) DEFAULT 0;

-- 2. Lupon members table
CREATE TABLE IF NOT EXISTS lupon_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(100) DEFAULT 'Lupon Tagapamayapa',
    is_active TINYINT(1) DEFAULT 1
);

-- 3. Seed default lupon members (edit names as needed)
INSERT INTO lupon_members (name, position) VALUES
  ('Lupon Committee 1', 'Lupon Tagapamayapa'),
  ('Lupon Committee 2', 'Lupon Tagapamayapa'),
  ('Lupon Committee 3', 'Lupon Tagapamayapa'),
  ('Lupon Committee 4', 'Lupon Tagapamayapa'),
  ('Lupon Committee 5', 'Lupon Tagapamayapa');
  ('Lupon Committee 6', 'Lupon Tagapamayapa'),
  ('Lupon Committee 7', 'Lupon Tagapamayapa'),
  ('Lupon Committee 8', 'Lupon Tagapamayapa'),
  ('Lupon Committee 9', 'Lupon Tagapamayapa'),
  ('Lupon Committee 10', 'Lupon Tagapamayapa');

-- 4. Make sure notifications table exists (from previous migration)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    complainant_name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'new_complaint',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. Add type column to notifications if it doesn't exist
ALTER TABLE notifications
  ADD COLUMN IF NOT EXISTS type VARCHAR(50) DEFAULT 'new_complaint';

-- 6. Resident notifications table (for status updates sent to residents)
CREATE TABLE IF NOT EXISTS resident_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
