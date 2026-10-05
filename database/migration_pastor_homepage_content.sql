-- FGCK Joyland: Pastor-managed homepage content
-- Uses the existing settings table; no new database is created.
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('homepage_mission',''),
('homepage_vision',''),
('homepage_motto',''),
('homepage_theme_year',''),
('homepage_daily_quote',''),
('homepage_daily_quote_image',''),
('homepage_daily_quote_date','');


-- Pastor church pictures and notes
CREATE TABLE IF NOT EXISTS homepage_church_updates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    title VARCHAR(180) NULL,
    note TEXT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'published',
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_hcu_status_published(status,published_at),
    KEY idx_hcu_user(user_id),
    CONSTRAINT fk_hcu_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
