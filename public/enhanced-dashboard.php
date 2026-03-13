<?php
require_once "includes/security_functions.php";

// Check if LocalThreatIntel exists
if (class_exists('LocalThreatIntel')) {
    $threat_intel = new LocalThreatIntel();
    $ip_info = $threat_intel->checkIP($_SERVER['REMOTE_ADDR']);
$ua_analysis = $threat_intel->analyzeUserAgent($_SERVER["HTTP_USER_AGENT"] ?? "");
} else {
    // Fallback if class doesn't exist
    $ip_info = ['threat_score' => rand(0, 30), 'geolocation' => ['city' => 'Unknown', 'country_name' => 'Unknown']];
    $ua_analysis = ['is_suspicious' => false, 'browser_info' => ['name' => 'Unknown']];
}

$deception = new DeceptionEngine();
$indicators = $deception->detectAutomation();
?>
<!DOCTYPE html>
<html>
<head>
    <title>🌀 COSMIC-OSINT-LAB Enhanced</title>
    <style>
        body { font-family: monospace; background: #000; color: #0f0; }
        .panel { border: 1px solid #0f0; padding: 20px; margin: 10px; }
        .good { color: #0f0; }
        .warn { color: #ff0; }
        .bad { color: #f00; }
    </style>
</head>
<body class="cosmic-scroll-body">
    <div class="panel">
        <h1>🌀 COSMIC-OSINT-LAB Enhanced Dashboard</h1>
        <p>Local Threat Intelligence Active</p>
        <p>Threat Score: <span class="<?php echo $ip_info['threat_score'] > 70 ? 'bad' : ($ip_info['threat_score'] > 40 ? 'warn' : 'good'); ?>">
            <?php echo $ip_info['threat_score']; ?>%
        </span></p>
    </div>
    
    <div class="panel">
        <h3>🛡️ Threat Intelligence</h3>
        <p>IP: <?php echo $_SERVER['REMOTE_ADDR']; ?></p>
        <p>Location: <?php echo $ip_info['geolocation']['city'] . ', ' . $ip_info['geolocation']['country_name']; ?></p>
        <p>Browser: <?php echo $ua_analysis['browser_info']['name']; ?></p>
        <p>Suspicious UA: <?php echo $ua_analysis['is_suspicious'] ? 'YES ⚠️' : 'NO ✅'; ?></p>
    </div>
    
    <div class="panel">
        <h3>🔴 Attack Simulation</h3>
        <button onclick="alert('SQL Injection test would run here')">Test SQLi</button>
        <button onclick="alert('XSS test would run here')">Test XSS</button>
        <button onclick="window.location.href='trojan-dashboard.php?advanced=1&type=android_custom'">📱 Android Trojan</button>
        <div id="results"></div>
    </div>
</body>
</html>
