<!-- ============= FILE 1: config.php ============= -->
<?php
// Basic DB config
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_strong_password_here'); // ← set your real password
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

<!-- ============= FILE 2: functions.php ============= -->
<?php
// functions.php
require_once 'config.php';

function getCurrentSession($mac_address) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT *, 
        TIMESTAMPDIFF(SECOND, NOW(), end_time) as seconds_left 
        FROM wifi_sessions 
        WHERE mac_address = ? AND is_active = 1
    ");
    $stmt->execute([$mac_address]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function insertBottle($mac_address) {
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        // Call stored procedure
        $stmt = $db->prepare("CALL insert_bottle(?, @session_id, @minutes_added)");
        $stmt->execute([$mac_address]);
        
        // Get output values
        $result = $db->query("SELECT @session_id as session_id, @minutes_added as minutes_added")->fetch(PDO::FETCH_ASSOC);
        
        $db->commit();
        
        return [
            'success' => true,
            'session_id' => $result['session_id'],
            'minutes_added' => $result['minutes_added']
        ];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getTodayStats() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM today_stats_view");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getActiveSessionsCount() {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as count FROM wifi_sessions WHERE is_active = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

function getDailyStats($days = 7) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT stat_date, total_bottles, total_minutes_given, unique_users, total_sessions
        FROM daily_stats
        WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ORDER BY stat_date DESC
    ");
    $stmt->execute([$days]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllActiveSessions() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM active_sessions_view WHERE status = 'Active' ORDER BY start_time DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatTime($seconds) {
    if ($seconds < 0) return "0:00";
    $mins = floor($seconds / 60);
    $secs = $seconds % 60;
    return sprintf("%d:%02d", $mins, $secs);
}

function disconnectSession($session_id) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE wifi_sessions SET is_active = 0 WHERE id = ?");
    return $stmt->execute([$session_id]);
}
?>
