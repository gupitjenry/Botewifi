<?php
/**
 * Custom Captive Portal - Your Design
 * This handles the redirect and authorization
 */
require_once 'config.php';
require_once 'functions.php';

$mac = getClientMAC();
$ip = getClientIP();

// Check if user has active session
$session = getCurrentSession($mac);

if ($session && $session['is_active'] && $session['seconds_left'] > 0) {
    // User has time - grant internet access
    grantInternetAccess($mac, $ip);
    
    // Redirect to success page with your design
    header('Location: index.php');
    exit;
} else {
    // Show your custom payment page
    include 'index.php';
}

function grantInternetAccess($mac, $ip) {
    $mac_clean = escapeshellarg($mac);
    $ip_clean = escapeshellarg($ip);
    
    // Add iptables rule
    exec("sudo iptables -I FORWARD -m mac --mac-source {$mac_clean} -j ACCEPT 2>&1");
    
    // Or use IP-based rule
    exec("sudo iptables -I FORWARD -s {$ip_clean} -j ACCEPT 2>&1");
    
    // Log access grant
    $db = getDB();
    $stmt = $db->prepare("UPDATE wifi_sessions SET ip_address = ? WHERE mac_address = ? AND is_active = 1");
    $stmt->execute([$ip, $mac]);
}
?>