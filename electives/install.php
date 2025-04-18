<?php
session_start();

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('PHP version 7.4 or higher is required.');
}

// Check if MySQL is available
if (!extension_loaded('mysqli')) {
    die('MySQL extension is required.');
}

// Check if PDO is available
if (!extension_loaded('pdo')) {
    die('PDO extension is required.');
}

// Check if PDO MySQL driver is available
if (!in_array('mysql', PDO::getAvailableDrivers())) {
    die('PDO MySQL driver is required.');
}

// Check if mod_rewrite is enabled
if (!isset($_SERVER['MOD_REWRITE'])) {
    die('Apache mod_rewrite module is required.');
}

// Check if config directory is writable
if (!is_writable('../config')) {
    die('config directory must be writable.');
}

// Check if assets directory is writable
if (!is_writable('../assets')) {
    die('assets directory must be writable.');
}

// Database configuration
$db_host = 'localhost';
$db_name = 'electives_db';
$db_user = 'root';
$db_pass = '';

// Try to connect to MySQL
try {
    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Create database if it doesn't exist
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $db_name");
    $pdo->exec("USE $db_name");
} catch (PDOException $e) {
    die('Database creation failed: ' . $e->getMessage());
}

// Import SQL file
$sql = file_get_contents('sql/install.sql');
try {
    $pdo->exec($sql);
    echo "Database setup completed successfully!<br>";
} catch (PDOException $e) {
    die('SQL import failed: ' . $e->getMessage());
}

// Create config file
$config_content = "<?php
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');

try {
    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME, DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException \$e) {
    die('Connection failed: ' . \$e->getMessage());
}
?>";

if (file_put_contents('../config/database.php', $config_content)) {
    echo "Configuration file created successfully!<br>";
} else {
    die('Failed to create configuration file.');
}

echo "<br>Installation completed successfully!<br>";
echo "Default admin credentials:<br>";
echo "Username: admin<br>";
echo "Password: password<br>";
echo "<br>Please delete this installation file for security reasons.";
?> 