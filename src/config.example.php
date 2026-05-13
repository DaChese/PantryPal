<?php
/*
 * Copy this file to src/config.php and fill in your values,
 * OR set the environment variables listed below on your host (Railway, etc.)
 * and the real config.php will pick them up automatically.
 *
 * Required environment variables:
 *   DB_HOST            - MySQL host      (Railway auto-injects as MYSQLHOST)
 *   DB_NAME            - Database name   (Railway auto-injects as MYSQLDATABASE)
 *   DB_USER            - Database user   (Railway auto-injects as MYSQLUSER)
 *   DB_PASS            - Database password (Railway auto-injects as MYSQLPASSWORD)
 *   SPOONACULAR_API_KEY - Your key from https://spoonacular.com/food-api
 */

define('DB_HOST', getenv('MYSQLHOST')     ?: getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'pantrypal');
define('DB_USER', getenv('MYSQLUSER')     ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');

define('SPOONACULAR_API_KEY', getenv('SPOONACULAR_API_KEY') ?: 'YOUR_SPOONACULAR_API_KEY_HERE');
define('SPOONACULAR_BASE_URL', 'https://api.spoonacular.com/recipes');

define('APP_NAME', 'PantryPal');
define('RESULT_LIMIT', 100);
