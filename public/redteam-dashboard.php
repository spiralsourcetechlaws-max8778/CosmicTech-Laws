<?php
/**
 * COSMIC RED TEAM OPS – MASTER DASHBOARD v3.0
 * Professional-grade attack simulation, real-time stats, MITRE visualizer, C2 integration.
 */
require_once __DIR__ . "/includes/security_functions.php";
require_once __DIR__ . "/../system/modules/RedTeamOperations.php";
require_once __DIR__ . "/../system/modules/advanced/ThreatIntelModule.php";

$intel = new LocalThreatIntel();
$threat_data = $intel->checkIP($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
$deception = new DeceptionEngine();
$threat_score = $deception->getThreatScore();

$redteam = new RedTeamOperations(true); // enable DB logging

// Try to connect to C2 engine if available
$c2 = null;
if (file_exists(dirname(__DIR__) . '/system/modules/C2Engine.php')) {
    require_once dirname(__DIR__) . '/system/modules/C2Engine.php';
    if (class_exists('C2Engine')) {
        $c2 = new C2Engine();
        $redteam->linkToC2($c2);
    }
}

// Handle simulation form
$simulation_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simulate'])) {
    $scenario = [
        'type' => $_POST['attack_type'] ?? 'phishing',
        'target' => $_POST['target'] ?? '192.168.1.1',
        'intensity' => (int)($_POST['intensity'] ?? 50),
        'name' => $_POST['campaign_name'] ?? 'Manual Campaign'
    ];
    $simulation_result = $redteam->simulateAttack($scenario);
}

// Handle report generation
if (isset($_GET['export'])) {
    $campaignId = (int)$_GET['campaign'];
    $format = $_GET['format'] ?? 'json';
    $path = $redteam->generateReport($campaignId, $format);
    if ($path && file_exists($path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        readfile($path);
        exit;
    }
}

$campaigns = $redteam->getCampaigns(20, 0);
$stats = $redteam->getAttackStats();
$active_payloads = $c2 ? $redteam->getActiveC2Payloads() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔴 COSMIC Red Team – Master Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/cosmic-c2.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="/assets/js/unified-quicknav.js" defer></script>
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .metric-card {
            background: rgba(0,20,40,0.7);
            border: 1px solid #00ffff;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }
        .metric-value {
            font-size: 2.5em;
            color: #00ff9d;
        }
        .metric-label {
            color: #0ff;
            text-transform: uppercase;
        }
        .chart-container {
            background: rgba(0,20,40,0.5);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-bar input, .filter-bar select {
            background: #000;
            color: #0ff;
            border: 1px solid #0ff;
            padding: 8px;
            border-radius: 5px;
        }
    </style>
</head>
<body class="cosmic-scroll-body">
    <div style="max-width: 1600px; margin: 0 auto; padding: 20px;">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap;">
            <h1 style="color:#00ffff; margin:0;">🔴 COSMIC Red Team Master Dashboard</h1>
            <div class="quick-links">
                <a href="c2-dashboard.php" class="btn">🎮 C2</a>
                <a href="phishing-dashboard.php" class="btn">🎣 Phishing</a>
                <a href="redteam-scenario-guide.php" class="btn">📘 Guide</a>
                <a href="index.php" class="btn">🏠 Main</a>
            </div>
        </div>

        <!-- Threat Status Bar -->
        <div class="quick-links" style="justify-content: flex-start; background: rgba(0,0,0,0.3); border-color:#ff00c8;">
            <span>🌐 IP: <?php echo $_SERVER['REMOTE_ADDR']; ?></span>
            <span class="<?php echo $threat_data['is_blacklisted'] ? 'bad' : 'good'; ?>">
                Threat: <?php echo $threat_data['threat_score']; ?>/100
            </span>
            <span>Deception: <?php echo $threat_score; ?>/100</span>
            <span>Active C2 Payloads: <?php echo count($active_payloads); ?></span>
        </div>

        <!-- Metrics Cards -->
        <div class="dashboard-grid">
            <div class="metric-card">
                <div class="metric-value"><?php echo $stats['total_campaigns'] ?? 0; ?></div>
                <div class="metric-label">Total Campaigns</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $stats['successful'] ?? 0; ?></div>
                <div class="metric-label">Successful</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $stats['detected'] ?? 0; ?></div>
                <div class="metric-label">Detected</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo round($stats['avg_intensity'] ?? 0, 1); ?></div>
                <div class="metric-label">Avg Intensity</div>
            </div>
        </div>

        <!-- Attack Type Distribution Chart -->
        <div class="chart-container">
            <h2>Attack Type Distribution</h2>
            <canvas id="attackChart" style="height:200px; width:100%;"></canvas>
        </div>

        <!-- Main Tabs -->
        <div class="tab-container" style="margin-top:30px;">
            <div class="tab active" onclick="switchTab('simulate')">⚔️ Simulate Attack</div>
            <div class="tab" onclick="switchTab('campaigns')">📁 Campaigns</div>
            <div class="tab" onclick="switchTab('mitre')">📋 MITRE Matrix</div>
            <div class="tab" onclick="switchTab('threat')">🛡️ Threat Intel</div>
            <div class="tab" onclick="switchTab('c2')">🎮 C2 Integration</div>
        </div>

        <!-- Simulate Attack Tab -->
        <div id="simulate" class="tab-content active">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <!-- Form -->
                <div class="glass-panel">
                    <h2>⚔️ New Attack Simulation</h2>
                    <form method="post">
                        <div style="margin-bottom:15px;">
                            <label>Campaign Name:</label>
                            <input type="text" name="campaign_name" placeholder="Red Team Exercise #1" required>
                        </div>
                        <div style="margin-bottom:15px;">
                            <label>Attack Type:</label>
                            <select name="attack_type">
                                <option value="phishing">Phishing (T1566)</option>
                                <option value="bruteforce">Brute Force (T1110)</option>
                                <option value="sqli">SQL Injection (T1190)</option>
                                <option value="xss">XSS (T1059.007)</option>
                                <option value="persistence_registry">Registry Persistence (T1547.001)</option>
                                <option value="credential_dumping">Credential Dumping (T1003)</option>
                            </select>
                        </div>
                        <div style="margin-bottom:15px;">
                            <label>Target (IP/domain):</label>
                            <input type="text" name="target" placeholder="192.168.1.1" required>
                        </div>
                        <div style="margin-bottom:15px;">
                            <label>Intensity (1-100):</label>
                            <input type="range" name="intensity" min="1" max="100" value="50">
                        </div>
                        <button type="submit" name="simulate" class="btn btn-primary">🚀 Run Simulation</button>
                    </form>
                </div>
                <!-- Results -->
                <?php if ($simulation_result): ?>
                <div class="glass-panel">
                    <h3 style="color:#ff00c8;">Simulation Results</h3>
                    <p><strong>Campaign ID:</strong> <?php echo $simulation_result['campaign_id']; ?></p>
                    <p><strong>Attack:</strong> <?php echo $simulation_result['ttp_details']['description']; ?> (<?php echo $simulation_result['techniques_used'][0]; ?>)</p>
                    <p><strong>Success:</strong> <span class="<?php echo $simulation_result['success'] ? 'good' : 'bad'; ?>"><?php echo $simulation_result['success'] ? '✅ SUCCESS' : '❌ FAILED'; ?></span></p>
                    <p><strong>Detection:</strong> <span class="<?php echo $simulation_result['detected'] ? 'bad' : 'good'; ?>"><?php echo $simulation_result['detected'] ? '⚠️ DETECTED' : '✅ STEALTH'; ?></span></p>
                    <h4>Timeline:</h4>
                    <ul>
                        <?php foreach ($simulation_result['timeline'] as $event): ?>
                        <li>[<?php echo $event['time']; ?>] <?php echo $event['event']; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <h4>MITRE Mapping:</h4>
                    <pre style="background:#000; padding:10px; border-radius:8px;"><?php print_r($simulation_result['mitre_mapping']); ?></pre>
                    <h4>Recommendations:</h4>
                    <ul>
                        <?php foreach ($simulation_result['recommendations'] as $rec): ?>
                        <li><?php echo $rec; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Campaigns Tab -->
        <div id="campaigns" class="tab-content">
            <h2>📁 Campaign History</h2>
            <div class="filter-bar">
                <input type="text" id="campaign-search" placeholder="Search campaigns..." onkeyup="filterCampaigns()">
                <select id="campaign-type-filter" onchange="filterCampaigns()">
                    <option value="">All Types</option>
                    <option value="phishing">Phishing</option>
                    <option value="bruteforce">Brute Force</option>
                    <option value="sqli">SQLi</option>
                    <option value="xss">XSS</option>
                </select>
                <button class="btn" onclick="exportSelected()">📤 Export Selected</button>
            </div>
            <table id="campaigns-table">
                <thead><tr><th><input type="checkbox" id="select-all"></th><th>ID</th><th>Name</th><th>Type</th><th>Target</th><th>Time</th><th>Success</th><th>Detected</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($campaigns as $c): 
                    $results = json_decode($c['results'], true);
                ?>
                <tr data-type="<?php echo $c['type']; ?>" data-name="<?php echo strtolower($c['name']); ?>">
                    <td><input type="checkbox" class="campaign-select" value="<?php echo $c['id']; ?>"></td>
                    <td><?php echo $c['id']; ?></td>
                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                    <td><?php echo $c['type']; ?></td>
                    <td><?php echo $c['target']; ?></td>
                    <td><?php echo date('Y-m-d H:i', $c['start_time']); ?></td>
                    <td class="<?php echo $results['success'] ? 'good' : 'bad'; ?>"><?php echo $results['success'] ? '✅' : '❌'; ?></td>
                    <td class="<?php echo $results['detected'] ? 'bad' : 'good'; ?>"><?php echo $results['detected'] ? '⚠️' : '✅'; ?></td>
                    <td>
                        <a href="?export=1&campaign=<?php echo $c['id']; ?>&format=json" class="btn small">📄 JSON</a>
                        <a href="?export=1&campaign=<?php echo $c['id']; ?>&format=html" class="btn small">📄 HTML</a>
                        <button class="btn small" onclick="deleteCampaign(<?php echo $c['id']; ?>)">🗑️</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- MITRE ATT&CK Tab -->
        <div id="mitre" class="tab-content">
            <h2>📋 MITRE ATT&CK Interactive Matrix</h2>
            <div style="margin-bottom:20px;">
                <input type="text" id="mitre-search" placeholder="Search technique (e.g., T1566)" style="padding:8px; width:300px; background:#000; color:#0ff; border:1px solid #0ff;">
                <select id="tactic-filter" style="padding:8px; background:#000; color:#0ff; border:1px solid #0ff;">
                    <option value="">All Tactics</option>
                    <?php
                    $matrix = $redteam->getFullMitreMatrix();
                    foreach ($matrix as $tactic_id => $tactic) {
                        echo "<option value=\"$tactic_id\">{$tactic['name']}</option>";
                    }
                    ?>
                </select>
                <button class="btn" onclick="filterMitre()">Filter</button>
            </div>
            <div id="mitre-matrix-container">
                <?php echo $redteam->generateInteractiveMitreMatrix(); ?>
            </div>
            <div id="technique-details" style="margin-top:20px; display:none;" class="glass-panel">
                <h3 id="tech-title"></h3>
                <p id="tech-description"></p>
                <p><strong>Mitigations:</strong> <span id="tech-mitigations"></span></p>
                <p><strong>Detection:</strong> <span id="tech-detection"></span></p>
            </div>
        </div>
        </div>

        <!-- Threat Intel Tab -->
        <div id="threat" class="tab-content">
            <h2>🛡️ Threat Intelligence</h2>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div class="glass-panel">
                    <h3>Current IP: <?php echo $_SERVER['REMOTE_ADDR']; ?></h3>
                    <pre style="background:#000; padding:15px;"><?php print_r($threat_data); ?></pre>
                </div>
                <div class="glass-panel">
                    <h3>Deception Analysis</h3>
                    <?php $analysis = $deception->analyzeThreat(); ?>
                    <pre style="background:#000; padding:15px;"><?php print_r($analysis); ?></pre>
                </div>
            </div>
        </div>

        <!-- C2 Integration Tab -->
        <div id="c2" class="tab-content">
            <h2>🎮 C2 Integration</h2>
            <?php if ($c2): ?>
            <div class="glass-panel">
                <h3>Active C2 Payloads (<?php echo count($active_payloads); ?>)</h3>
                <table>
                    <thead><tr><th>UUID</th><th>Name</th><th>Type</th><th>Last Seen</th><th>Link</th></tr></thead>
                    <tbody>
                        <?php foreach ($active_payloads as $p): ?>
                        <tr>
                            <td><?php echo substr($p['uuid'],0,8); ?>…</td>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td><?php echo $p['type']; ?></td>
                            <td><?php echo $p['last_seen'] ? date('H:i:s', $p['last_seen']) : 'Never'; ?></td>
                            <td><a href="c2-dashboard.php?view=payloads&uuid=<?php echo $p['uuid']; ?>" class="btn small">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p>C2 engine not available.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelector(`.tab[onclick*="'${tabId}'"]`).classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }

        function filterCampaigns() {
            let search = document.getElementById('campaign-search').value.toLowerCase();
            let typeFilter = document.getElementById('campaign-type-filter').value;
            document.querySelectorAll('#campaigns-table tbody tr').forEach(row => {
                let name = row.dataset.name || '';
                let type = row.dataset.type || '';
                let matchesSearch = name.includes(search);
                let matchesType = typeFilter === '' || type === typeFilter;
                row.style.display = matchesSearch && matchesType ? '' : 'none';
            });
        }

        document.getElementById('select-all').addEventListener('change', function(e) {
            document.querySelectorAll('.campaign-select').forEach(cb => cb.checked = e.target.checked);
        });

        function exportSelected() {
            let selected = Array.from(document.querySelectorAll('.campaign-select:checked')).map(cb => cb.value);
            if (selected.length === 0) return;
            let format = prompt("Enter export format (json/csv/html):", "json");
            if (!format) return;
            window.location.href = `redteam-api.php?action=export&ids=${selected.join(',')}&format=${format}`;
        }

        function deleteCampaign(id) {
            if (!confirm('Delete this campaign?')) return;
            fetch(`redteam-api.php?action=delete&id=${id}`).then(() => location.reload());
        }

        // Chart for attack distribution
        const ctx = document.getElementById('attackChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_keys($stats['by_type'] ?? [])); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($stats['by_type'] ?? [])); ?>,
                    backgroundColor: ['#00ffff', '#00ff9d', '#ff00c8', '#b300ff', '#ff3366']
                }]
            }
        });
    </script>
</body>
</html>

<!-- Phishing Campaigns Widget -->
<div class="glass-panel" style="margin-top:30px;">
    <h2>🎣 Recent Phishing Campaigns</h2>
    <?php
    // Get campaigns from PhishingCampaign
    require_once __DIR__ . '/../system/modules/phishing/PhishingCampaign.php';
    $phish = new PhishingCampaign();
    $campaigns = $phish->getCampaigns();
    ?>
    <table>
        <thead><tr><th>Name</th><th>Clone URL</th><th>Status</th><th>Clicks</th><th>Submissions</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($campaigns, 0, 5) as $c): ?>
        <tr>
            <td><?php echo htmlspecialchars($c['name']); ?></td>
            <td><a href="<?php echo $c['clone_path']; ?>" target="_blank">View</a></td>
            <td><?php echo $c['status']; ?></td>
            <td><?php echo $c['clicks']; ?></td>
            <td><?php echo $c['submissions']; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
function filterMitre() {
    let search = document.getElementById('mitre-search').value.toLowerCase();
    let tactic = document.getElementById('tactic-filter').value;
    document.querySelectorAll('.tactic').forEach(t => {
        if (tactic && t.dataset.tactic !== tactic) {
            t.style.display = 'none';
            return;
        }
        let show = false;
        t.querySelectorAll('.technique').forEach(tech => {
            let techId = tech.dataset.technique.toLowerCase();
            if (search === '' || techId.includes(search)) {
                tech.style.display = 'inline-block';
                show = true;
            } else {
                tech.style.display = 'none';
            }
        });
        t.style.display = show ? 'block' : 'none';
    });
}
function showTechniqueDetails(techId) {
    fetch('/redteam-api.php?action=technique&id=' + techId)
        .then(r => r.json())
        .then(d => {
            document.getElementById('tech-title').innerText = d.name;
            document.getElementById('tech-description').innerText = d.description;
            document.getElementById('tech-mitigations').innerText = d.mitigations;
            document.getElementById('tech-detection').innerText = d.detection;
            document.getElementById('technique-details').style.display = 'block';
        });
}
</script>
