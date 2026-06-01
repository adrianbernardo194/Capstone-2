-- migration_v8.sql
-- Adds paper-based record support to the complaints table
-- Run this in phpMyAdmin on barangay_db

ALTER TABLE complaints
ADD COLUMN is_paper_based TINYINT(1) DEFAULT 0 AFTER resident_notified,
ADD COLUMN encoded_by_admin_id INT DEFAULT NULL AFTER is_paper_based,
ADD COLUMN encoded_at TIMESTAMP NULL DEFAULT NULL AFTER encoded_by_admin_id,
ADD COLUMN original_filed_date DATE DEFAULT NULL AFTER encoded_at;

-- original_filed_date = the date written on the physical sumbong form
-- encoded_at = the date/time the admin digitized it into the system
-- encoded_by_admin_id = which admin encoded the record
-- is_paper_based = 1 for paper records, 0 for online submissions
