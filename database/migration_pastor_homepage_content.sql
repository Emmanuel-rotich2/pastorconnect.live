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
