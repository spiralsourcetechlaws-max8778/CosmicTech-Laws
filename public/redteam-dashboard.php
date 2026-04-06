<?php
/**
 * COSMIC RED TEAM – Industrial MITRE ATT&CK Dashboard
 * Full attack simulation, campaign management, and interactive MITRE matrix.
 */
require_once __DIR__ . '/includes/security_functions.php';
require_once __DIR__ . '/includes/navigation.php';
require_once dirname(__DIR__) . '/system/modules/RedTeamOperations.php';
require_once dirname(__DIR__) . '/system/modules/advanced/ThreatIntelModule.php';

$redteam = new RedTeamOperations(true);
$intel = new LocalThreatIntel();
$deception = new DeceptionEngine();

$threat_data = $intel->checkIP($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
$analysis = $deception->analyzeThreat();
$campaigns = $redteam->getCampaigns(20, 0);
$stats = $redteam->getAttackStats();

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🔴 COSMIC Red Team – Industrial MITRE</title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/cosmic-c2.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        .tab-container { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .tab { padding: 10px 20px; background: rgba(0,255,255,0.1); border: 1px solid #0ff; border-radius: 8px 8px 0 0; cursor: pointer; }
        .tab.active { background: rgba(0,255,255,0.3); color: #000; }
        .tab-content { display: none; padding: 20px; background: rgba(0,20,40,0.7); border-radius: 0 8px 8px 8px; }
        .tab-content.active { display: block; }
        .mitre-matrix { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr)); gap: 15px; }
        .tactic-card { background: rgba(0,0,0,0.6); border: 1px solid #0ff; border-radius: 12px; padding: 15px; }
        .technique { background: rgba(0,255,255,0.1); border-radius: 20px; padding: 4px 10px; margin: 3px; display: inline-block; cursor: pointer; font-size: 0.8em; }
        .technique:hover { background: #0ff; color: #000; }
    </style>
</head>
<body>
<div class="main-content" style="max-width: 1400px; margin: 0 auto; padding: 20px;">
    <?php echo render_cosmic_navigation('redteam'); ?>
    <h1>🔴 COSMIC Red Team – Industrial MITRE</h1>

    <!-- Threat Status Bar -->
    <div class="quick-links" style="justify-content: flex-start; margin-bottom: 20px;">
        <span>🌐 IP: <?php echo $_SERVER['REMOTE_ADDR']; ?></span>
        <span class="<?php echo $threat_data['is_blacklisted'] ? 'bad' : 'good'; ?>">
            Threat: <?php echo $threat_data['threat_score']; ?>/100
        </span>
        <span>Deception: <?php echo $analysis['threat_score']; ?>/100</span>
    </div>

    <!-- Statistics Cards -->
    <div class="cards-grid">
        <div class="stat-card"><div class="stat-value"><?php echo $stats['total_campaigns'] ?? 0; ?></div><div>Total Campaigns</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo $stats['successful'] ?? 0; ?></div><div>Successful</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo $stats['detected'] ?? 0; ?></div><div>Detected</div></div>
        <div class="stat-card"><div class="stat-value"><?php echo round($stats['avg_intensity'] ?? 0, 1); ?></div><div>Avg Intensity</div></div>
    </div>

    <!-- Tabs -->
    <div class="tab-container">
        <div class="tab active" onclick="switchTab('simulate')">⚔️ Simulate Attack</div>
        <div class="tab" onclick="switchTab('campaigns')">📁 Campaigns</div>
        <div class="tab" onclick="switchTab('mitre')">📋 MITRE Matrix</div>
        <div class="tab" onclick="switchTab('threat')">🛡️ Threat Intel</div>
    </div>

    <!-- Simulate Tab -->
    <div id="simulate" class="tab-content active">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div class="glass-panel">
                <h2>⚔️ New Attack Simulation</h2>
                <form method="post">
                    <div class="form-group"><label>Campaign Name:</label><input type="text" name="campaign_name" required></div>
                    <div class="form-group">
                        <label>Attack Type:</label>
                        <select name="attack_type">
                            <option value="phishing">Phishing (T1566)</option>
                            <option value="bruteforce">Brute Force (T1110)</option>
                            <option value="sqli">SQL Injection (T1190)</option>
                            <option value="xss">XSS (T1059.007)</option>
                            <option value="credential_dumping">Credential Dumping (T1003)</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Target:</label><input type="text" name="target" required></div>
                    <div class="form-group"><label>Intensity (1-100):</label><input type="range" name="intensity" min="1" max="100" value="50"></div>
                    <button type="submit" name="simulate" class="btn btn-primary">🚀 Run Simulation</button>
                </form>
            </div>
            <?php if ($simulation_result): ?>
            <div class="glass-panel">
                <h3 style="color:#ff00c8;">Results</h3>
                <p><strong>Attack:</strong> <?php echo $simulation_result['ttp_details']['description']; ?> (<?php echo $simulation_result['techniques_used'][0]; ?>)</p>
                <p><strong>Success:</strong> <span class="<?php echo $simulation_result['success'] ? 'good' : 'bad'; ?>"><?php echo $simulation_result['success'] ? '✅' : '❌'; ?></span></p>
                <p><strong>Detection:</strong> <span class="<?php echo $simulation_result['detected'] ? 'bad' : 'good'; ?>"><?php echo $simulation_result['detected'] ? '⚠️' : '✅'; ?></span></p>
                <h4>MITRE Mapping:</h4>
                <pre><?php print_r($simulation_result['mitre_mapping']); ?></pre>
                <h4>Recommendations:</h4>
                <ul><?php foreach ($simulation_result['recommendations'] as $rec): ?><li><?php echo $rec; ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Campaigns Tab -->
    <div id="campaigns" class="tab-content">
        <div class="glass-panel">
            <h2>📁 Campaign History</h2>
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Target</th><th>Start</th><th>Success</th><th>Detected</th></tr></thead>
                <tbody>
                <?php foreach ($campaigns as $c):
                    $results = json_decode($c['results'], true);
                ?>
                <tr>
                    <td><?php echo $c['id']; ?></td>
                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                    <td><?php echo $c['type']; ?></td>
                    <td><?php echo $c['target']; ?></td>
                    <td><?php echo date('Y-m-d H:i', $c['start_time']); ?></td>
                    <td class="<?php echo $results['success'] ? 'good' : 'bad'; ?>"><?php echo $results['success'] ? '✅' : '❌'; ?></td>
                    <td class="<?php echo $results['detected'] ? 'bad' : 'good'; ?>"><?php echo $results['detected'] ? '⚠️' : '✅'; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MITRE Matrix Tab -->
    <div id="mitre" class="tab-content">
        <div class="glass-panel">
            <h2>📋 MITRE ATT&CK Enterprise Matrix</h2>
            <div id="mitre-matrix-container">
                <?php echo $redteam->generateInteractiveMitreMatrix(); ?>
            </div>
            <div id="technique-details" style="display:none; margin-top:20px;" class="glass-panel">
                <h3 id="tech-title"></h3>
                <p id="tech-description"></p>
                <p><strong>Mitigations:</strong> <span id="tech-mitigations"></span></p>
                <p><strong>Detection:</strong> <span id="tech-detection"></span></p>
            </div>
        </div>
    </div>

    <!-- Threat Intel Tab -->
    <div id="threat" class="tab-content">
        <div class="glass-panel">
            <h2>🛡️ Live Threat Intelligence</h2>
            <pre><?php print_r($threat_data); ?></pre>
            <pre><?php print_r($analysis); ?></pre>
        </div>
    </div>
</div>
<script>
function switchTab(tabId) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelector(`.tab[onclick*="'${tabId}'"]`).classList.add('active');
    document.getElementById(tabId).classList.add('active');
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
</body>
</html>
