-- FGCK Joyland: member-only password reset storage.
-- Run this while the pastorco_fgck_joyland database is selected in phpMyAdmin.
-- This does not alter the users table or any existing member records.
CREATE TABLE IF NOT EXISTS member_password_resets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    member_id INT UNSIGNED NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    verified_at DATETIME NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_member_password_reset_member (member_id),
    KEY idx_member_password_reset_expiry (expires_at),
    CONSTRAINT fk_member_password_reset_member
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
