<?php
/**
 * Session Monitor - Runs via cron every minute
 * Checks expired sessions and disconnects users
 * 
 * Setup cron:
 * * * * * * /usr/bin/php /var/www/bottle-wifi/monitor_sessions.php >> /var/log/wifi-monitor.log 2>&1
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Prevent running from web browser
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

echo "[" . date('Y-m-d H:i:s') . "] Starting session monitor...\n";

try {
    $db = getDB();
    
    // Check database connection
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    // Find expired sessions
    $stmt = $db->query("
        SELECT id, mac_address, end_time
        FROM wifi_sessions 
        WHERE is_active = 1 
        AND end_time < NOW()
    ");
    
    $expired = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($expired)) {
        echo "No expired sessions found.\n";
        exit(0);
    }
    
    echo "Found " . count($expired) . " expired session(s)\n";
    
    $disconnected_count = 0;
    
    foreach ($expired as $session) {
        $mac = $session['mac_address'];
        $id = $session['id'];
        
        echo "Processing session #{$id} - MAC: {$mac}\n";
        
        // Deactivate session in database
        $updateStmt = $db->prepare("
            UPDATE wifi_sessions 
            SET is_active = 0
            WHERE id = ?
        ");
        
        if ($updateStmt->execute([$id])) {
            echo "  ✓ Database updated\n";
            
            // Remove iptables rule (for firewall control)
            $result = removeFirewallRule($mac);
            if ($result['success']) {
                echo "  ✓ Firewall rule removed\n";
                $disconnected_count++;
            } else {
                echo "  ⚠ Warning: " . $result['message'] . "\n";
            }
            
            // Log the disconnection
            function logDisconnection($db, $session_id, $mac) {
    try {
        $stmt = $db->prepare("
            INSERT INTO admin_logs (action, details, ip_address, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            'auto_disconnect',
            "Disconnected session #{$session_id}, MAC: {$mac}",
            'SYSTEM'
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to log disconnection: " . $e->getMessage());
        return false;
    }
}
            
        } else {
            echo "  ✗ Failed to update database\n";
        }
    }
    
    echo "\n✓ Monitor completed: {$disconnected_count} session(s) disconnected\n";
    
    // Clean up old sessions (older than 30 days)
    echo "Cleaning up old sessions...\n";
    $cleanupStmt = $db->query("
        DELETE FROM wifi_sessions 
        WHERE is_active = 0 
        AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    
    $deleted = $cleanupStmt->rowCount();
    if ($deleted > 0) {
        echo "✓ Cleaned up {$deleted} old session record(s)\n";
    } else {
        echo "No old sessions to clean up\n";
    }
    
    // Update statistics
    echo "Updating statistics...\n";
    updateDailyStats();
    echo "✓ Statistics updated\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n[" . date('Y-m-d H:i:s') . "] Session monitor finished\n";
echo str_repeat("=", 50) . "\n\n";

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Remove iptables firewall rule for MAC address
 */
function removeFirewallRule($mac) {
    // Validate MAC address
    if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/', $mac)) {
        return [
            'success' => false,
            'message' => 'Invalid MAC address format'
        ];
    }
    
    $mac_clean = escapeshellarg($mac);
    $output = [];
    $return_code = 0;
    
    // Try to remove the rule
    exec("sudo /sbin/iptables -D FORWARD -m mac --mac-source {$mac_clean} -j ACCEPT 2>&1", $output, $return_code);
    
    if ($return_code === 0) {
        return [
            'success' => true,
            'message' => 'Firewall rule removed successfully'
        ];
    } else {
        // Rule might not exist, which is okay
        return [
            'success' => true,
            'message' => 'No firewall rule found (may have been removed already)'
        ];
    }
}

/**
 * Update daily statistics
 */
function updateDailyStats() {
    $db = getDB();
    
    // Get today's stats from transactions table
    $stmt = $db->query("
        SELECT 
            COUNT(DISTINCT bt.mac_address) as unique_users,
            COUNT(DISTINCT bt.session_id) as total_sessions,
            IFNULL(SUM(bt.bottles_inserted), 0) as total_bottles,
            IFNULL(SUM(bt.minutes_earned), 0) as total_minutes
        FROM bottle_transactions bt
        WHERE DATE(bt.transaction_time) = CURDATE()
    ");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats) {
        // Insert or update today's stats
        $insertStmt = $db->prepare("
            INSERT INTO daily_stats 
                (stat_date, unique_users, total_sessions, total_bottles, total_minutes_given) 
            VALUES 
                (CURDATE(), ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                unique_users = VALUES(unique_users),
                total_sessions = VALUES(total_sessions),
                total_bottles = VALUES(total_bottles),
                total_minutes_given = VALUES(total_minutes_given)
        ");
        
        $insertStmt->execute([
            $stats['unique_users'] ?? 0,
            $stats['total_sessions'] ?? 0,
            $stats['total_bottles'] ?? 0,
            $stats['total_minutes'] ?? 0
        ]);
    }
}

/**
 * Send notification (Optional - for future use)
 */
function sendDisconnectNotification($mac, $reason = 'Time expired') {
    // Future: Send SMS, Email, or Push notification
    // For now, just log it
    error_log("User disconnected - MAC: {$mac}, Reason: {$reason}");
}
?>