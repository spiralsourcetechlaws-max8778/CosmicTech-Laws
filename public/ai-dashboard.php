<?php
/**
 * COSMIC OSINT LAB - AI Security Analyst Dashboard
 * FIXED: Include security_functions for DeceptionEngine
 */
require_once __DIR__ . '/includes/security_functions.php';

$deception = new DeceptionEngine();
$automation = $deception->detectAutomation();
$threat_score = $deception->getThreatScore();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🤖 COSMIC AI Security</title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <style>
        body { background: #0a0a0f; color: #b300ff; font-family: 'Share Tech Mono', monospace; padding: 20px; }
        .glass { background: rgba(20,0,40,0.7); backdrop-filter: blur(10px); border:1px solid #b300ff; border-radius:16px; padding:20px; }
        h1 { color: #00ff9d; text-shadow:0 0 10px #00ff9d; }
    </style>
</head>
<body class="cosmic-scroll-body">
    <div style="max-width:1200px; margin:0 auto;">
        <h1>🤖 COSMIC AI Security Analyst</h1>
        <div class="glass">
            <h2 style="color:#00f3ff;">Automation Detection</h2>
            <pre style="background:#000; padding:15px; border-radius:8px;"><?php print_r($automation); ?></pre>
            <p><strong>AI Threat Score:</strong> <?php echo $threat_score; ?>/100</p>
        </div>
    </div>
</body>
</html>
