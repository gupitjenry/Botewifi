<?php
// sensor.php - API endpoint for bottle sensor
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $mac = getClientMAC();
    $ip  = getClientIP();

    // Validate MAC format
    if (!preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/i', $mac)) {
        throw new Exception("Invalid MAC address: $mac");
    }

    // Insert bottle record
    $result = insertBottle($mac);

    if ($result['success']) {

        // ✅ BETTER: Use escapeshellarg (quotes safely)
        $mac_arg = escapeshellarg($mac);

        $cmd = "sudo /usr/sbin/iptables -I FORWARD -m mac --mac-source $mac_arg -j ACCEPT";
        exec($cmd . " 2>&1", $output, $return_code);

        if ($return_code === 0) {
            error_log("✓ Internet access granted to $mac");
        } else {
            error_log("⚠ Failed iptables for $mac: " . implode(" | ", $output));
        }

        // Log to database
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO session_logs (session_id, action, notes, created_at)
            VALUES (?, 'internet_grant', ?, NOW())
        ");
        $notes = ($return_code === 0 ? "iptables OK" : "iptables FAILED: " . implode(" | ", $output));
        $stmt->execute([$result['session_id'], $notes]);

        echo json_encode([
            'success' => true,
            'message' => 'Bottle detected! Internet access granted.',
            'session_id' => $result['session_id'],
            'mac' => $mac,
            'minutes_added' => $result['minutes_added']
        ]);

    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error processing bottle'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>

