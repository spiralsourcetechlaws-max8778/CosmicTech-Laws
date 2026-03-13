<?php
/**
 * COSMIC Red Team Ops – Real Scenario Usage Guide
 */
require_once __DIR__ . '/includes/security_functions.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>🔴 Red Team Ops – Real Scenario Guide</title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/cosmic-c2.css">
    <style>
        .guide-step { margin-bottom:30px; padding:20px; background:rgba(0,255,255,0.05); border-left:4px solid #0ff; }
        .command { background:#000; color:#0f0; padding:10px; border-radius:5px; font-family:monospace; }
    </style>
</head>
<body class="cosmic-scroll-body">
    <div class="main-content" style="margin-left:20px;">
        <h1>🔴 Red Team Operations – Real Scenario Guide</h1>
        
        <div class="glass-panel">
            <h2>Scenario: Simulated Phishing Attack with Beaconing Payload</h2>
            <p>This guide walks through a complete red team exercise using the COSMIC OSINT LAB tools.</p>
        </div>
        
        <div class="guide-step">
            <h3>Step 1: Generate a Phishing Page</h3>
            <p>Use the <strong>Phishing Dashboard</strong> to clone a legitimate login page.</p>
            <div class="command">http://localhost:8008/phishing-dashboard.php</div>
            <p>Enter the target URL (e.g., https://office.com/login) and a campaign name. The system will create a clone with credential harvesting.</p>
        </div>
        
        <div class="guide-step">
            <h3>Step 2: Create a C2‑Enabled Payload</h3>
            <p><strong>Quick C2 Link:</strong> After generation, you can directly access the C2 dashboard via the toggle panel.</p>
            <p>Go to the <strong>Advanced Payload Generator</strong> and create a meterpreter reverse TCP payload with C2 enabled.</p>
            <div class="command">http://localhost:8008/trojan-dashboard.php?advanced=1</div>
            <p>Set LHOST to your attacker IP, LPORT to 4444, check "Enable C2 beaconing". Generate and download the payload.</p>
        </div>
        
        <div class="guide-step">
            <h3>Step 3: Start a Listener and Wait for Beacon</h3>
            <p>On the generation result page, click <strong>Create Listener</strong>. The system will start a listener and show its status. Once the payload runs on the target, the page will update showing the connection.</p>
        </div>
        
        <div class="guide-step">
            <h3>Step 4: Send the Phishing Email</h3>
            <p>Use the Phishing Dashboard to generate an email template. Copy the phishing link (your cloned page URL) and send it to the target (simulated).</p>
        </div>
        
        <div class="guide-step">
            <h3>Step 5: Monitor C2 and Harvested Data</h3>
            <p>When the target clicks the link and enters credentials, they are captured in <code>/phishing/logs/</code>. The payload beacons to your C2 dashboard, where you can issue commands and view results.</p>
            <div class="command">http://localhost:8008/c2-dashboard.php</div>
        </div>
        
        <div class="guide-step">
            <h3>Step 6: Analyze with Red Team Dashboard</h3>
            <p>Visit the <strong>Red Team Dashboard</strong> to see threat intelligence, MITRE mapping, and campaign statistics.</p>
            <div class="command">http://localhost:8008/redteam-dashboard.php</div>
            <p>Here you can simulate additional attacks (bruteforce, SQLi) and generate reports.</p>
        </div>
        
        <div class="glass-panel">
            <h3>Pro Tips</h3>
            <ul>
                <li>Use the <strong>Quick Nav</strong> button (bottom right) to jump between active payloads.</li>
                <li>In C2 dashboard, you can add tasks (e.g., "whoami", "ls") to active payloads.</li>
                <li>All logs and credentials are stored under <code>/data/</code> for later analysis.</li>
                <li>For DNS beaconing, install dnscat2 on your attacker machine and use the EnhancedC2Comm module.</li>
            </ul>
        </div>
        
        <div style="margin-top:30px;">
            <a href="redteam-dashboard.php" class="btn btn-primary">← Back to Red Team Dashboard</a>
        </div>
    </div>
</body>
</html>
