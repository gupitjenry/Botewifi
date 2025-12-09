<!-- ============= FILE 1: config.php ============= -->
<?php
// Basic DB config
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'YOUR_MYSQL_PASSWORD'); // ← set your real password
define('DB_NAME', 'bottle_wifi_vendo');
define('DB_LOG_FILE', '/var/log/bottle-wifi.log');

// Sensor settings
define('SENSOR_PIN', 17);
define('MINUTES_PER_BOTTLE', 5);
define('MAX_SESSION_TIME', 240);
define('SESSION_CHECK_INTERVAL', 60);

// System settings
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', '1234');

// DB connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $pdo;
}

// Client info helpers
function getClientMAC() {
    $mac = '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $output = shell_exec("arp -a " . escapeshellarg($ip));
    if (preg_match('/([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})/', $output, $m)) {
        $mac = $m[0];
    }
    return strtoupper($mac ?: 'IP_' . str_replace('.', '_', $ip));
}

function getClientIP() {
    $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    return $ip;
}
?>
