<<<<<<< HEAD
USE pastorco_fgck_joyland;
=======
USE fgck_joyland;
>>>>>>> 3095147f1de9cc3fe682800509762cfc6ea2edc1

CREATE TABLE IF NOT EXISTS staff_trusted_logins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_staff_trusted_token (token_hash),
    KEY idx_staff_trusted_user_expiry (user_id, expires_at),
    CONSTRAINT fk_staff_trusted_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
