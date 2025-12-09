<!-- ============= FILE 4: admin.php (ADMIN DASHBOARD) ============= -->
<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

// Check login
if (!isset($_SESSION['admin_logged_in'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD)) {
            $_SESSION['admin_logged_in'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $error = "Invalid credentials!";
        }
    }
    
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-blue-50 to-cyan-100 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-md w-full border-4 border-blue-200">
            <div class="text-center mb-6">
                <div class="bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full p-4 inline-block mb-4">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent">Admin Login</h1>
                <p class="text-blue-600 mt-2">WiFi Vendo Management</p>
            </div>
            
            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-blue-700 font-medium mb-2">Username</label>
                    <input type="text" name="username" required class="w-full px-4 py-3 border-2 border-blue-200 rounded-lg focus:border-blue-500 focus:outline-none">
                </div>
                <div class="mb-6">
                    <label class="block text-blue-700 font-medium mb-2">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-3 border-2 border-blue-200 rounded-lg focus:border-blue-500 focus:outline-none">
                </div>
                <button type="submit" name="login" class="w-full bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white font-bold py-3 px-6 rounded-lg transition-all">
                    Login
                </button>
            </form>
            
            <div class="mt-4 text-center text-blue-400 text-sm">
                Default: admin / password
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['disconnect_session'])) {
        disconnectSession($_POST['session_id']);
        header('Location: admin.php?tab=sessions');
        exit;
    }
}

// Get data
$todayStats = getTodayStats();
$activeCount = getActiveSessionsCount();
$activeSessions = getAllActiveSessions();
$weeklyStats = getDailyStats(7);

// Current tab
$currentTab = $_GET['tab'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - WiFi Vendo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta http-equiv="refresh" content="30">
    <style>
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.3s ease-out; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-cyan-50 min-h-screen">
    
    <!-- Header -->
    <header class="bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-lg">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <div>
                        <h1 class="text-2xl font-bold">WiFi Vendo Dashboard</h1>
                        <p class="text-blue-100 text-sm">Plastic Bottle Management System</p>
                    </div>
                </div>
                <a href="?logout=1" class="bg-white text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg font-medium transition-all">
                    Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <div class="bg-white shadow-md border-b-2 border-blue-200">
        <div class="container mx-auto px-4">
            <nav class="flex gap-2 overflow-x-auto">
                <a href="?tab=dashboard" class="px-6 py-4 font-medium transition-all <?php echo $currentTab === 'dashboard' ? 'text-blue-600 border-b-4 border-blue-600' : 'text-gray-600 hover:text-blue-600'; ?>">
                    Dashboard
                </a>
                <a href="?tab=sessions" class="px-6 py-4 font-medium transition-all <?php echo $currentTab === 'sessions' ? 'text-blue-600 border-b-4 border-blue-600' : 'text-gray-600 hover:text-blue-600'; ?>">
                    Active Sessions
                </a>
                <a href="?tab=reports" class="px-6 py-4 font-medium transition-all <?php echo $currentTab === 'reports' ? 'text-blue-600 border-b-4 border-blue-600' : 'text-gray-600 hover:text-blue-600'; ?>">
                    Reports
                </a>
                <a href="?tab=settings" class="px-6 py-4 font-medium transition-all <?php echo $currentTab === 'settings' ? 'text-blue-600 border-b-4 border-blue-600' : 'text-gray-600 hover:text-blue-600'; ?>">
                    Settings
                </a>
            </nav>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6">
        
        <?php if ($currentTab === 'dashboard'): ?>
        <!-- Dashboard Tab -->
        <div class="fade-in">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Today's Bottles -->
                <div class="bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-medium opacity-90">Today's Bottles</h3>
                        <svg class="w-8 h-8 opacity-80" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M6 2h8l6 6v12c0 1.1-.9 2-2 2H6c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2zm7 7V3.5L18.5 9H13z"/>
                        </svg>
                    </div>
                    <div class="text-4xl font-bold"><?php echo $todayStats['total_bottles'] ?? 0; ?></div>
                    <div class="text-sm opacity-80 mt-1">Bottles collected today</div>
                </div>

                <!-- Active Users -->
                <div class="bg-gradient-to-br from-cyan-500 to-blue-500 rounded-2xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-medium opacity-90">Active Users</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="text-4xl font-bold"><?php echo $activeCount; ?></div>
                    <div class="text-sm opacity-80 mt-1">Currently connected</div>
                </div>

                <!-- Total Minutes -->
                <div class="bg-gradient-to-br from-blue-400 to-cyan-400 rounded-2xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-medium opacity-90">Total Minutes</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-4xl font-bold"><?php echo $todayStats['total_minutes'] ?? 0; ?></div>
                    <div class="text-sm opacity-80 mt-1">WiFi minutes given today</div>
                </div>

                <!-- Unique Users -->
                <div class="bg-gradient-to-br from-cyan-400 to-blue-400 rounded-2xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-medium opacity-90">Unique Users</h3>
                        <svg class="w-8 h-8 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="text-4xl font-bold"><?php echo $todayStats['unique_users'] ?? 0; ?></div>
                    <div class="text-sm opacity-80 mt-1">Different users today</div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Weekly Bottles Chart -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border-2 border-blue-200">
                    <h3 class="text-xl font-bold text-blue-700 mb-4">Weekly Bottles Collection</h3>
                    <canvas id="bottlesChart"></canvas>
                </div>

                <!-- Weekly Users Chart -->
                <div class="bg-white rounded-2xl p-6 shadow-lg border-2 border-blue-200">
                    <h3 class="text-xl font-bold text-blue-700 mb-4">Weekly Unique Users</h3>
                    <canvas id="usersChart"></canvas>
                </div>
            </div>

            <!-- Recent Sessions -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border-2 border-blue-200">
                <h3 class="text-xl font-bold text-blue-700 mb-4">Recent Active Sessions</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-blue-100 to-cyan-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-blue-700 font-semibold">MAC Address</th>
                                <th class="px-4 py-3 text-left text-blue-700 font-semibold">Bottles</th>
                                <th class="px-4 py-3 text-left text-blue-700 font-semibold">Time Left</th>
                                <th class="px-4 py-3 text-left text-blue-700 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($activeSessions, 0, 5) as $session): ?>
                            <tr class="border-b border-blue-100 hover:bg-blue-50">
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo substr($session['mac_address'], -12); ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo $session['bottle_count']; ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo $session['remaining_minutes']; ?> mins</td>
                                <td class="px-4 py-3">
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-medium">
                                        <?php echo $session['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php elseif ($currentTab === 'sessions'): ?>
        <!-- Active Sessions Tab -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border-2 border-blue-200 fade-in">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-blue-700">Active Sessions (<?php echo count($activeSessions); ?>)</h2>
                <button onclick="location.reload()" class="bg-gradient-to-r from-blue-500 to-cyan-500 text-white px-4 py-2 rounded-lg hover:from-blue-600 hover:to-cyan-600 transition-all">
                    Refresh
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-blue-100 to-cyan-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-blue-700 font-semibold">ID</th>
                            <th class="px-4 py-3 text-left text-blue-700 font-semibold">MAC Address</th>
                            <th class="px-4 py-3 text-left text-blue-700 font-semibold">Bottles</th>
                            <th class="px-4 py-3 text-left text-blue-700 font-semibold">Total Minutes</th>
                            <th class="px-4 py-3 text-left text-blue-700 font-semibold">Time Left</th>
                            <th class="px-4 py-3 text-left text-blue-700 font-semibold">Start Time</th>
                            <th class="px-4 py-3 text-left text-blue-700 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeSessions as $session): ?>
                        <tr class="border-b border-blue-100 hover:bg-blue-50">
                            <td class="px-4 py-3 text-sm text-gray-700"><?php echo $session['id']; ?></td>
                            <td class="px-4 py-3 text-sm font-mono text-gray-700"><?php echo $session['mac_address']; ?></td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?php echo $session['bottle_count']; ?></td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?php echo $session['total_minutes']; ?> mins</td>
                            <td class="px-4 py-3 text-sm font-bold text-blue-600"><?php echo $session['remaining_minutes']; ?> mins</td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?php echo date('g:i A', strtotime($session['start_time'])); ?></td>
                            <td class="px-4 py-3">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="session_id" value="<?php echo $session['id']; ?>">
                                    <button type="submit" name="disconnect_session" onclick="return confirm('Disconnect this user?')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs font-medium transition-all">
                                        Disconnect
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($currentTab === 'reports'): ?>
        <!-- Reports Tab -->
        <div class="space-y-6 fade-in">
            <!-- Weekly Report -->
            <div class="bg-white rounded-2xl p-6 shadow-lg border-2 border-blue-200">
                <h2 class="text-2xl font-bold text-blue-700 mb-4">7-Day Report</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-blue-100 to-cyan-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-blue-700 font-semibold">Date</th>
                                <th class="px-4 py-3 text-left text-blue-700 font-semibold">Bottles</th>
                                <th class="px-4 py-3 text-left text-blue-700 font-semibold">Minutes Given</th>
                                <th class="px-4 py-3 text-left text-blue-700 font-semibold">Unique Users</th>
                                <th class="px-4 py-3 text-left text-blue-700 font-semibold">Sessions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalBottles = 0;
                            $totalMinutes = 0;
                            foreach ($weeklyStats as $stat): 
                                $totalBottles += $stat['total_bottles'];
                                $totalMinutes += $stat['total_minutes_given'];
                            ?>
                            <tr class="border-b border-blue-100 hover:bg-blue-50">
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo date('M d, Y', strtotime($stat['stat_date'])); ?></td>
                                <td class="px-4 py-3 text-sm font-bold text-blue-600"><?php echo $stat['total_bottles']; ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo $stat['total_minutes_given']; ?> mins</td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo $stat['unique_users']; ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?php echo $stat['total_sessions']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="bg-gradient-to-r from-blue-100 to-cyan-100 font-bold">
                                <td class="px-4 py-3 text-blue-700">TOTAL</td>
                                <td class="px-4 py-3 text-blue-700"><?php echo $totalBottles; ?></td>
                                <td class="px-4 py-3 text-blue-700"><?php echo $totalMinutes; ?> mins</td>
                                <td class="px-4 py-3 text-blue-700" colspan="2"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Revenue Estimation -->
            <div class="bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl p-8 text-white shadow-lg">
                <h2 class="text-2xl font-bold mb-4">Revenue Estimation (7 Days)</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <div class="text-sm opacity-80 mb-1">Total Bottles Collected</div>
                        <div class="text-4xl font-bold"><?php echo $totalBottles; ?></div>
                    </div>
                    <div>
                        <div class="text-sm opacity-80 mb-1">Avg Bottles/Day</div>
                        <div class="text-4xl font-bold"><?php echo round($totalBottles / 7, 1); ?></div>
                    </div>
                    <div>
                        <div class="text-sm opacity-80 mb-1">Est. Monthly</div>
                        <div class="text-4xl font-bold"><?php echo round($totalBottles / 7 * 30); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php elseif ($currentTab === 'settings'): ?>
        <!-- Settings Tab -->
        <div class="bg-white rounded-2xl p-6 shadow-lg border-2 border-blue-200 fade-in">
            <h2 class="text-2xl font-bold text-blue-700 mb-6">System Settings</h2>
            
            <div class="space-y-6">
                <div class="bg-blue-50 border-2 border-blue-200 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-blue-700 mb-4">WiFi Settings</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-blue-600 font-medium mb-2">Minutes per Bottle</label>
                            <input type="number" value="<?php echo MINUTES_PER_BOTTLE; ?>" class="w-full px-4 py-2 border-2 border-blue-200 rounded-lg" readonly>
                        </div>
                        <div>
                            <label class="block text-blue-600 font-medium mb-2">Max Session Time (minutes)</label>
                            <input type="number" value="<?php echo MAX_SESSION_TIME; ?>" class="w-full px-4 py-2 border-2 border-blue-200 rounded-lg" readonly>
                        </div>
                    </div>
                </div>

                <div class="bg-cyan-50 border-2 border-cyan-200 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-blue-700 mb-4">Sensor Settings</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-blue-600 font-medium mb-2">Sensor Model</label>
                            <input type="text" value="E3F-DS100C4 NPN NO" class="w-full px-4 py-2 border-2 border-cyan-200 rounded-lg" readonly>
                        </div>
                        <div>
                            <label class="block text-blue-600 font-medium mb-2">GPIO Pin</label>
                            <input type="number" value="<?php echo SENSOR_PIN; ?>" class="w-full px-4 py-2 border-2 border-cyan-200 rounded-lg" readonly>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-100 border-2 border-blue-300 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-blue-700 mb-2">System Information</h3>
                    <div class="space-y-2 text-sm text-blue-600">
                        <p><strong>Database:</strong> <?php echo DB_NAME; ?></p>
                        <p><strong>Server:</strong> Raspberry Pi</p>
                        <p><strong>Version:</strong> 1.0.0</p>
                        <p><strong>Last Update:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <script>
        // Chart.js configurations
        <?php if ($currentTab === 'dashboard'): ?>
        const bottlesData = {
            labels: [<?php foreach ($weeklyStats as $s) echo "'" . date('M d', strtotime($s['stat_date'])) . "',"; ?>],
            datasets: [{
                label: 'Bottles Collected',
                data: [<?php foreach ($weeklyStats as $s) echo $s['total_bottles'] . ','; ?>],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            }]
        };

        const usersData = {
            labels: [<?php foreach ($weeklyStats as $s) echo "'" . date('M d', strtotime($s['stat_date'])) . "',"; ?>],
            datasets: [{
                label: 'Unique Users',
                data: [<?php foreach ($weeklyStats as $s) echo $s['unique_users'] . ','; ?>],
                borderColor: 'rgb(6, 182, 212)',
                backgroundColor: 'rgba(6, 182, 212, 0.1)',
                tension: 0.4,
                fill: true
            }]
        };

        new Chart(document.getElementById('bottlesChart'), {
            type: 'line',
            data: bottlesData,
            options: { responsive: true, plugins: { legend: { display: false } } }
        });

        new Chart(document.getElementById('usersChart'), {
            type: 'line',
            data: usersData,
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
        <?php endif; ?>
    </script>
</body>
</html>