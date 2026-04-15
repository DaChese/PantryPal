/* =========================================
 * Author: Aldo Medina
 * Created on: 4/12/2026
 * Last updated: 4/15/2026
 * Purpose: Create the PantryPal database tables
 * ========================================= */
/* =========================================
 * saved recipes table
 * keeps recipes users want to come back to
 * ========================================= */
CREATE TABLE IF NOT EXISTS saved_recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipe_api_id INT NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    image_url TEXT,
    used_ingredients TEXT,
    missed_ingredients TEXT,
    notes TEXT,
    source_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

/* =========================================
 * search history table
 * stores ingredient searches from the home page
 * ========================================= */
CREATE TABLE IF NOT EXISTS search_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ingredients_query VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
