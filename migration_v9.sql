-- migration_v9.sql
-- Creates the audit_trail table for tracking all admin actions
-- Run this in phpMyAdmin on barangay_db

CREATE TABLE IF NOT EXISTS audit_trail (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    admin_id        INT DEFAULT NULL,
    admin_username  VARCHAR(100) DEFAULT 'System',
    action          VARCHAR(100) NOT NULL,
    module          VARCHAR(60)  NOT NULL,
    target_id       INT DEFAULT NULL,
    target_label    VARCHAR(255) DEFAULT NULL,
    old_value       TEXT DEFAULT NULL,
    new_value       TEXT DEFAULT NULL,
    description     TEXT NOT NULL,
    ip_address      VARCHAR(45) DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index for fast filtering by module and date
CREATE INDEX idx_audit_module    ON audit_trail(module);
CREATE INDEX idx_audit_admin     ON audit_trail(admin_id);
CREATE INDEX idx_audit_created   ON audit_trail(created_at);
CREATE INDEX idx_audit_target    ON audit_trail(target_id);
