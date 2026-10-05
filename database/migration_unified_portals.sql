-- FGCK Joyland unified role portal migration
-- Run while pastorco_fgck_joyland is selected.
USE pastorco_fgck_joyland;

ALTER TABLE users
  MODIFY role ENUM('pastor','church_leader','admin') NOT NULL DEFAULT 'pastor';

CREATE INDEX IF NOT EXISTS idx_users_role_status ON users(role,status);
CREATE INDEX IF NOT EXISTS idx_activity_user_created ON activity_logs(user_id,created_at);

CREATE TABLE IF NOT EXISTS leader_profiles (
  user_id INT UNSIGNED PRIMARY KEY,
  ministry_name VARCHAR(150) NULL,
  title VARCHAR(120) NULL,
  bio VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_leader_profile_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
