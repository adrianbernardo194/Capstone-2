-- Run this in phpMyAdmin on barangay_db
-- Renames all existing "In Remediation" status values to "In Process"

UPDATE complaints SET status = 'In Process' WHERE status = 'In Remediation';
UPDATE resident_notifications SET type = 'in_process' WHERE type = 'followup';
