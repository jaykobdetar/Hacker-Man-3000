-- Upgrades a database created from the original (2019) game.sql to the schema used by the
-- modernised code. Fresh installs don't need this: game.sql already includes these changes.
--
-- BACK UP FIRST, then run (MariaDB 10.6+):
--     mysql -u root -p game < scripts/migrations/2026-09-modernize.sql
--
-- Players keep their passwords: old hashes are still accepted and upgraded on their next login.

-- password_hash() output can be longer than the old 60-character bcrypt hashes.
ALTER TABLE users MODIFY `password` varchar(255) NOT NULL;

-- Password reset codes are now random and stored as SHA-256 hashes; old codes stop working.
ALTER TABLE email_reset MODIFY `code` char(64) NOT NULL;
DELETE FROM email_reset;

-- "Keep me logged in" tokens are now stored hashed: sign everybody out once.
DELETE FROM users_online;

-- Brute-force protection for the login form.
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `login` varchar(50) NOT NULL,
  `attemptTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`, `attemptTime`),
  KEY `login` (`login`, `attemptTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Removed features: paid (premium) accounts and Facebook/Twitter login.
DROP TABLE IF EXISTS `payments`, `payments_history`, `premium_history`, `users_premium`,
                     `users_facebook`, `users_twitter`;
ALTER TABLE users DROP COLUMN IF EXISTS `premium`;
ALTER TABLE profile DROP COLUMN IF EXISTS `premium`;

-- Optional: move the remaining MyISAM/latin1 tables to InnoDB/utf8mb4 like a fresh install.
-- The game works either way. This query prints the statements to run:
--
--   SELECT CONCAT('ALTER TABLE `', table_name, '` ENGINE=InnoDB, CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;')
--   FROM information_schema.tables WHERE table_schema = DATABASE();
--
-- Caution: the original code talked to MySQL over a latin1 connection, so non-ASCII text may be
-- stored as double-encoded UTF-8. Check a few rows with accents before and after converting.
