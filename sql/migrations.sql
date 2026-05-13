/* =========================================
 * PantryPal - Migration: users, tiers, user_id links
 * Run this against the Railway MySQL database after deploying
 * the new auth + tier features.
 * ========================================= */

/* =========================================
 * users table
 * stores accounts, tier, and daily search tracking
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
 * NULL = saved before auth was added (legacy rows)
 * ========================================= */
ALTER TABLE saved_recipes
    ADD COLUMN IF NOT EXISTS user_id INT NULL DEFAULT NULL,
    ADD CONSTRAINT fk_saved_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

/* =========================================
 * add user_id to search_history
 * ========================================= */
ALTER TABLE search_history
    ADD COLUMN IF NOT EXISTS user_id INT NULL DEFAULT NULL,
    ADD CONSTRAINT fk_history_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
