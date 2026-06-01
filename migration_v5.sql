-- Run this in phpMyAdmin on barangay_db
-- migration_v5.sql
-- Seeds 12 Lupon Tagapamayapa committee members
-- If you already have some members, this won't duplicate them (uses INSERT IGNORE)
-- Edit the names to match the real lupon of Barangay San Roque

TRUNCATE TABLE lupon_members;

INSERT INTO lupon_members (name, position, is_active) VALUES
('Juan dela Cruz',     'Lupon Tagapamayapa', 1),
('Maria Santos',       'Lupon Tagapamayapa', 1),
('Pedro Reyes',        'Lupon Tagapamayapa', 1),
('Rosa Garcia',        'Lupon Tagapamayapa', 1),
('Carlos Bautista',    'Lupon Tagapamayapa', 1),
('Elena Villanueva',   'Lupon Tagapamayapa', 1),
('Ramon Castillo',     'Lupon Tagapamayapa', 1),
('Lourdes Torres',     'Lupon Tagapamayapa', 1),
('Antonio Flores',     'Lupon Tagapamayapa', 1),
('Carmela Mendoza',    'Lupon Tagapamayapa', 1),
('Roberto Aquino',     'Lupon Tagapamayapa', 1),
('Marites Pascual',    'Lupon Tagapamayapa', 1);
