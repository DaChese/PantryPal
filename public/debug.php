<?php
// Temporary debug page - DELETE before final submission
// Visit /debug.php to see what env vars Railway is injecting

$vars = [
    'MYSQLHOST', 'MYSQLDATABASE', 'MYSQLUSER', 'MYSQLPASSWORD', 'MYSQLPORT',
    'MYSQL_HOST', 'MYSQL_DATABASE', 'MYSQL_USER', 'MYSQL_PASSWORD', 'MYSQL_PORT',
    'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
    'DATABASE_URL', 'SPOONACULAR_API_KEY', 'PORT',
];

echo '<pre>';
foreach ($vars as $var) {
    $val = getenv($var);
    if ($val !== false) {
        // Mask passwords
        if (stripos($var, 'pass') !== false || stripos($var, 'key') !== false) {
            $val = str_repeat('*', strlen($val));
        }
        echo $var . ' = ' . $val . "\n";
    } else {
        echo $var . ' = (not set)' . "\n";
    }
}
echo '</pre>';
