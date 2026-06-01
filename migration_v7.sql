-- Run this in phpMyAdmin on barangay_db
-- migration_v7.sql

-- 1. Holidays / blocked dates table
CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    created_by_admin_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Schedule settings — controls how far ahead residents can book
--    admin sets: booking_window_days (e.g. 7 = residents can only pick
--    dates within the next 7 days from today)
CREATE TABLE IF NOT EXISTS schedule_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Default settings
INSERT INTO schedule_settings (setting_key, setting_value)
VALUES ('booking_window_days', '7')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
