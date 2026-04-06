<?php
/**
 * COSMIC THREAT INTELLIGENCE – INDUSTRIAL GRADE
 * Provides real‑time threat analysis, IP reputation, deception detection,
 * and integration with C2 & Red Team.
 */
require_once __DIR__ . '/includes/security_functions.php';
require_once __DIR__ . '/../system/modules/advanced/ThreatIntelModule.php';

session_start();
if (!isset($_SESSION['c2_user'])) {
    header('Location: login.php');
    exit;
}

$intel = new LocalThreatIntel();
$deception = new DeceptionEngine();

// Get current IP info
$ip = $_SERVER['REMOTE_ADDR'] ?? '8.8.8.8';
$ipInfo = $intel->checkIP($ip);
$uaInfo = $intel->analyzeUserAgent($_SERVER['HTTP_USER_AGENT'] ?? '');
$threatScore = $deception->getThreatScore();

// Mock VT report
$vtReport = MockAPI::getVirusTotalReport($ip);

// Get recent IPs from C2 (if available)
$c2Payloads = [];
if (class_exists('C2Engine')) {
    $c2 = new C2Engine();
    $beacons = $c2->getBeacons(null, 20);
    foreach ($beacons as $b) {
        $c2Payloads[] = $intel->checkIP($b['ip']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🌀 COSMIC Threat Intelligence</title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/cosmic-c2.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body class="cosmic-scroll-body">
<?php require_once __DIR__ . "/includes/navigation.php"; echo render_cosmic_navigation(); ?>
    <div class="main-content" style="margin-left:20px;">
        <h1>🛡️ COSMIC Threat Intelligence</h1>

        <!-- Quick Stats -->
        <div class="cards-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $ipInfo['threat_score']; ?></div>
                <div class="stat-label">Your Threat Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $deception->getThreatScore(); ?></div>
                <div class="stat-label">Deception Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($c2Payloads); ?></div>
                <div class="stat-label">Recent C2 Beacons</div>
            </div>
        </div>

        <!-- Current IP Analysis -->
        <div class="glass-panel">
            <h2>🌐 Your IP: <?php echo $ip; ?></h2>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div>
                    <h3>Reputation</h3>
                    <p><strong>Blacklisted:</strong> <?php echo $ipInfo['is_blacklisted'] ? 'YES' : 'NO'; ?></p>
                    <p><strong>Tor Exit:</strong> <?php echo $ipInfo['is_tor_exit'] ? 'YES' : 'NO'; ?></p>
                    <p><strong>Threat Score:</strong> <?php echo $ipInfo['threat_score']; ?>/100</p>
                    <h4>Recommendations</h4>
                    <ul>
                        <?php foreach ($ipInfo['recommendations'] as $rec): ?>
                            <li><?php echo $rec; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h3>Geolocation</h3>
                    <p><strong>Country:</strong> <?php echo $ipInfo['geolocation']['country_name']; ?></p>
                    <p><strong>City:</strong> <?php echo $ipInfo['geolocation']['city']; ?></p>
                    <p><strong>ISP:</strong> <?php echo $ipInfo['geolocation']['isp']; ?></p>
                    <p><strong>ASN:</strong> <?php echo $ipInfo['geolocation']['asn']; ?></p>
                </div>
            </div>
        </div>

        <!-- Deception Analysis -->
        <div class="glass-panel">
            <h2>🕵️ Deception Analysis</h2>
            <pre style="background:#000; padding:15px;"><?php print_r($deception->analyzeThreat()); ?></pre>
        </div>

        <!-- Mock VirusTotal -->
        <div class="glass-panel">
            <h2>📊 Mock VirusTotal Report</h2>
            <pre style="background:#000; padding:15px;"><?php print_r($vtReport); ?></pre>
        </div>

        <!-- Recent C2 Beacons -->
        <?php if (!empty($c2Payloads)): ?>
        <div class="glass-panel">
            <h2>📶 Recent C2 Beacons</h2>
            <table>
                <thead><tr><th>IP</th><th>Country</th><th>Threat Score</th><th>Blacklisted</th></tr></thead>
                <tbody>
                <?php foreach ($c2Payloads as $c): ?>
                    <tr>
                        <td><?php echo $c['ip']; ?></td>
                        <td><?php echo $c['geolocation']['country_name']; ?></td>
                        <td><?php echo $c['threat_score']; ?></td>
                        <td><?php echo $c['is_blacklisted'] ? 'YES' : 'NO'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div style="text-align:center; margin-top:20px;">
            <a href="index.php" class="btn">🏠 Dashboard</a>
            <a href="c2-dashboard.php" class="btn">🎮 C2</a>
        </div>
    </div>
</body>
</html>
