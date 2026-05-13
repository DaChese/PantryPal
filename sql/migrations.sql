/* =========================================
 * PantryPal - Migration: users, tiers, user_id links
 * Run this against the Railway MySQL database after deploying
 * the new auth + tier features.
 * ========================================= */

/* =========================================
 * users table
 * ========================================= */
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    tier          ENUM('free','pro','chef') NOT NULL DEFAULT 'free',
    searches_today INT NOT NULL DEFAULT 0,
    searches_date  DATE         NOT NULL DEFAULT (CURRENT_DATE),
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

/* =========================================
 * add user_id to saved_recipes
 * Skip if the column already exists
 * ========================================= */
SET @col_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'saved_recipes'
      AND COLUMN_NAME  = 'user_id'
);

SET @sql = IF(@col_exists = 0,
    'ALTER TABLE saved_recipes ADD COLUMN user_id INT NULL DEFAULT NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

/* =========================================
 * add user_id to search_history
 * ========================================= */
SET @col_exists2 = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'search_history'
      AND COLUMN_NAME  = 'user_id'
);

SET @sql2 = IF(@col_exists2 = 0,
    'ALTER TABLE search_history ADD COLUMN user_id INT NULL DEFAULT NULL',
    'SELECT 1'
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
