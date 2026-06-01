-- Run this in phpMyAdmin on barangay_db
-- migration_v4.sql

-- Proper appointments table to track lupon bookings
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    lupon_member_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (lupon_member_id) REFERENCES lupon_members(id) ON DELETE CASCADE
);

-- Index for fast availability lookups
CREATE INDEX IF NOT EXISTS idx_appt_date_time ON appointments(appointment_date, appointment_time);
CREATE INDEX IF NOT EXISTS idx_appt_lupon ON appointments(lupon_member_id);
