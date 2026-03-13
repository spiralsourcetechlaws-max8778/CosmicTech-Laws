<?php
/**
 * COSMIC Threat Intelligence – AJAX Endpoint
 * Returns an HTML snippet for the collapsible panel.
 */
require_once __DIR__ . '/../includes/security_functions.php';
require_once __DIR__ . '/../../system/modules/advanced/ThreatIntelModule.php';

$intel = new LocalThreatIntel();
$deception = new DeceptionEngine();

$ip = $_SERVER['REMOTE_ADDR'] ?? '8.8.8.8';
$ipInfo = $intel->checkIP($ip);
$analysis = $deception->analyzeThreat();
$vtReport = MockAPI::getVirusTotalReport($ip);
?>
<div style="background:rgba(0,0,0,0.8); border:1px solid #b300ff; border-radius:8px; padding:20px;">
    <h3 style="color:#ff00c8;">🛡️ COSMIC Threat Intelligence</h3>
    <p><strong>IP:</strong> <?php echo $ip; ?> – Threat Score: <?php echo $ipInfo['threat_score']; ?>/100</p>
    <p><strong>Blacklisted:</strong> <?php echo $ipInfo['is_blacklisted'] ? 'YES' : 'NO'; ?></p>
    <p><strong>Tor Exit:</strong> <?php echo $ipInfo['is_tor_exit'] ? 'YES' : 'NO'; ?></p>
    <p><strong>Deception Score:</strong> <?php echo $analysis['threat_score']; ?></p>
    <details>
        <summary>Full Details</summary>
        <pre style="background:#000; padding:10px;"><?php print_r($ipInfo); ?></pre>
        <pre style="background:#000; padding:10px;"><?php print_r($analysis); ?></pre>
        <pre style="background:#000; padding:10px;"><?php print_r($vtReport); ?></pre>
    </details>
</div>
