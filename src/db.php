<?php
/*
 * Author: Aldo Medina
 * Created on: 4/12/2026
 * Last updated: 4/18/2026
 * Purpose: Create the shared PDO connection for the app.
 */

require_once __DIR__ . '/config.php';

// =============================================
// PDO CONNECTION
// =============================================
function get_pdo(): PDO
{
    static $pdo = null;

    // Reuse the same PDO connection during the request //
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $port = getenv('MYSQLPORT') ?: '3306';
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . $port . ';dbname=' . DB_NAME . ';charset=utf8mb4';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    // this is for storing recipe titles and notes cleanly ///
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $exception) {
        throw new RuntimeException('Database connection failed. Please check your MySQL settings in src/config.php.');
    }

    return $pdo;
}
