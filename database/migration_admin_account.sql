-- FGCK Joyland administrator account setup
-- Select pastorco_fgck_joyland before running this file.
USE pastorco_fgck_joyland;

-- Ensure the unified role set is available.
ALTER TABLE users
  MODIFY role ENUM('pastor','church_leader','admin') NOT NULL DEFAULT 'pastor';

-- Create or repair the primary administrator account.
-- Change this password immediately after the first successful login.
INSERT INTO users
    (username, full_name, email, password_hash, role, status)
VALUES
    ('administrator', 'FGCK Joyland Administrator', 'administrator@fgckjoyland.org',
     '$2y$12$1GxHhiX1Eh95qY154DOB4e3Hagmubu9NSTwU0Ri6dyLoHvx9/lypm',
     'admin', 'active')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    password_hash = VALUES(password_hash),
    role = 'admin',
    status = 'active';
