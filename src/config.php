<?php
/*
 * Author: Aldo Medina
 * Created on: 4/12/2026
 * Last updated: 4/18/2026
 * Purpose: Store database settings and Spoonacular config.
 *
 * All sensitive values are read from environment variables so this file
 * is safe to deploy on Railway (or any host) without hardcoding secrets.
 */

// =============================================
// DATABASE SETTINGS
// =============================================
// Railway injects: MYSQLHOST, MYSQL_DATABASE, MYSQLUSER, MYSQL_ROOT_PASSWORD, MYSQLPORT
define('DB_HOST', getenv('MYSQLHOST')            ?: getenv('MYSQL_HOST')     ?: getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('MYSQL_DATABASE')       ?: getenv('MYSQLDATABASE')  ?: getenv('DB_NAME') ?: '');
define('DB_USER', getenv('MYSQLUSER')            ?: getenv('MYSQL_USER')     ?: getenv('DB_USER') ?: '');
define('DB_PASS', getenv('MYSQL_ROOT_PASSWORD')  ?: getenv('MYSQLPASSWORD')  ?: getenv('DB_PASS') ?: '');

// =============================================
// API SETTINGS
// =============================================
define('SPOONACULAR_API_KEY', getenv('SPOONACULAR_API_KEY') ?: 'YOUR_SPOONACULAR_API_KEY_HERE');
define('SPOONACULAR_BASE_URL', 'https://api.spoonacular.com/recipes');

// =============================================
// APP SETTINGS
// =============================================
define('APP_NAME', 'PantryPal');
define('RESULT_LIMIT', 100);
