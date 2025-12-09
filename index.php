<!-- 
===============================================
PLASTIC BOTTLE WIFI VENDO - COMPLETE SYSTEM
===============================================
Files needed:
1. config.php - Database configuration
2. index.php - User interface (this file)
3. admin.php - Admin dashboard
4. sensor.php - Sensor API endpoint
5. functions.php - Helper functions

Installation on Raspberry Pi:
sudo cp -r * /var/www/bottle-wifi/
sudo chown -R www-data:www-data /var/www/bottle-wifi
sudo chmod -R 755 /var/www/bottle-wifi
===============================================
-->


<!-- ============= FILE 3: index.php (USER INTERFACE) ============= -->
<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

$mac_address = getClientMAC();
$ip_address = getClientIP();

// Get current session
$session = getCurrentSession($mac_address);

// Handle bottle insertion via button (for testing)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insert_bottle'])) {
    $result = insertBottle($mac_address);
    header('Location: index.php?inserted=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plastic Bottle WiFi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
        .animate-spin { animation: spin 1s linear infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
        .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease-out; }
    </style>
    <?php if ($session && $session['is_active']): ?>
    <meta http-equiv="refresh" content="5">
    <?php endif; ?>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-cyan-50 to-blue-100 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-md w-full border-4 border-blue-200 fade-in">
        
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-center mb-4">
                <div class="bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full p-4">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                    </svg>
                </div>
            </div>
            <h1 class="text-4xl font-bold text-center mb-2 bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent">
                Plastic Bottle WiFi
            </h1>
            <p class="text-center text-blue-600 font-medium mb-2">Recycle & Connect</p>
            <div class="bg-gradient-to-r from-blue-100 to-cyan-100 rounded-lg p-3 text-center">
                <span class="text-blue-700 font-bold text-lg">1 Bottle = <?php echo MINUTES_PER_BOTTLE; ?> minutes WiFi</span>
            </div>
        </div>

        <?php if ($session && $session['is_active'] && $session['seconds_left'] > 0): ?>
            <!-- Active Session -->
            <div class="mb-6 fade-in">
                <!-- Connected Banner -->
                <div class="bg-gradient-to-r from-blue-500 to-cyan-500 text-white p-6 rounded-2xl mb-4 shadow-lg">
                    <div class="flex items-center justify-center gap-3 mb-3">
                        <svg class="w-8 h-8 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span class="text-2xl font-bold">CONNECTED</span>
                    </div>
                    <p class="text-center text-sm opacity-90">
                        You're connected to WiFi. Enjoy browsing!
                    </p>
                </div>

                <!-- Session Info -->
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl p-6 border-2 border-blue-300 mb-4">
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="text-center">
                            <div class="text-blue-600 text-sm font-medium mb-1">Status</div>
                            <div class="bg-green-100 text-green-700 font-bold py-2 px-4 rounded-lg">Active</div>
                        </div>
                        <div class="text-center">
                            <div class="text-blue-600 text-sm font-medium mb-1">Time Left</div>
                            <div class="bg-blue-100 text-blue-700 font-bold py-2 px-4 rounded-lg text-2xl">
                                <?php echo formatTime($session['seconds_left']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t-2 border-blue-200 pt-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-blue-600 text-sm">Bottles Inserted:</span>
                            <span class="text-blue-700 font-bold"><?php echo $session['bottle_count']; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-blue-600 text-sm">Total Minutes:</span>
                            <span class="text-blue-700 font-bold"><?php echo $session['total_minutes']; ?> mins</span>
                        </div>
                    </div>
                </div>

                <!-- Network Info -->
                <div class="bg-gradient-to-r from-blue-100 to-cyan-100 rounded-xl p-4 mb-4 border border-blue-300">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-blue-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                        </svg>
                        <div>
                            <div class="text-blue-700 font-bold">Network: PLASTIC_BOTTLE_WiFi</div>
                            <div class="text-blue-600 text-sm">IP: <?php echo $ip_address; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Add More Time -->
                <form method="POST">
                    <button type="submit" name="insert_bottle" class="w-full bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white font-bold py-4 px-6 rounded-xl transition-all transform hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                        </svg>
                        Add More Time
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- No Active Session -->
            <div class="mb-6">
                <!-- Stats Display -->
                <div class="bg-gradient-to-br from-blue-100 to-cyan-50 rounded-2xl p-6 mb-6 border-2 border-blue-300">
                    <div class="text-center mb-4">
                        <div class="text-6xl font-bold text-blue-600">0</div>
                        <div class="text-blue-600 font-medium mt-2">Minutes Available</div>
                    </div>
                    <div class="text-center text-blue-500 text-sm">
                        Insert a bottle to get started
                    </div>
                </div>

                <!-- Insert Bottle Button -->
                <form method="POST">
                    <button type="submit" name="insert_bottle" class="w-full bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white font-bold py-8 px-8 rounded-2xl transition-all transform hover:scale-105 shadow-lg flex flex-col items-center justify-center gap-3">
                        <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 2h8l6 6v12c0 1.1-.9 2-2 2H6c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2zm7 7V3.5L18.5 9H13z"/>
                        </svg>
                        <span class="text-2xl">Insert Your Bottle</span>
                        <span class="text-sm opacity-90">Sensor will detect automatically</span>
                    </button>
                </form>

                <!-- Instructions -->
                <div class="mt-6 bg-blue-50 rounded-xl p-4 border border-blue-200">
                    <h3 class="text-blue-700 font-bold mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        How it works:
                    </h3>
                    <ul class="text-blue-600 text-sm space-y-1">
                        <li>• Insert plastic bottle into the machine</li>
                        <li>• Get <?php echo MINUTES_PER_BOTTLE; ?> minutes of WiFi access</li>
                        <li>• Connect to "PLASTIC_BOTTLE_WiFi" network</li>
                        <li>• Add more bottles anytime to extend</li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- WiFi Status Icon -->
        <div class="mt-6 text-center">
            <?php if ($session && $session['is_active'] && $session['seconds_left'] > 0): ?>
            <svg class="w-12 h-12 mx-auto text-blue-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
            </svg>
            <?php else: ?>
            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"></path>
            </svg>
            <p class="text-gray-500 text-sm mt-2">Not Connected</p>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-blue-400 text-xs">
            <p>Session ID: <?php echo substr($mac_address, -8); ?></p>
            <p class="mt-1">Help save the environment🌍</p>
        </div>
    </div>

    <script>
        // Auto refresh when session is active
        <?php if ($session && $session['is_active']): ?>
        setTimeout(function() {
            window.location.reload();
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>