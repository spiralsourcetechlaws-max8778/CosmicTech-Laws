<?php
/**
 * COSMIC OSINT LAB – UNIFIED SOVEREIGN DASHBOARD
 * Industrial‑grade entry point with real‑time stats, integrated modules,
 * and cosmic infinity design.
 */
session_start();
require_once __DIR__ . '/includes/security_functions.php';
require_once __DIR__ . '/includes/navigation.php';

// Module availability detection (lazy, no crashes)
$modules_status = [
    'trojan'    => file_exists(__DIR__ . '/trojan-dashboard.php'),
    'advanced'  => file_exists(__DIR__ . '/trojan-dashboard.php'),
    'android'   => file_exists(__DIR__ . '/../system/modules/AndroidTrojanModule.php'),
    'c2'        => file_exists(__DIR__ . '/c2-dashboard.php') && class_exists('SQLite3'),
    'redteam'   => file_exists(__DIR__ . '/redteam-dashboard.php'),
    'phishing'  => file_exists(__DIR__ . '/phishing-dashboard.php'),
    'urlmasker' => file_exists(__DIR__ . '/url-masker.php'),
    'threat'    => file_exists(__DIR__ . '/threat-intel.php'),
];

// System statistics
$payload_count = 0;
$payload_dir = dirname(__DIR__) . '/data/payloads';
if (is_dir($payload_dir)) {
    $files = glob($payload_dir . '/*');
    if ($files !== false) $payload_count = count($files);
}
$advanced_count = 0;
$adv_dir = dirname(__DIR__) . '/data/advanced_payloads';
if (is_dir($adv_dir)) {
    $files = glob($adv_dir . '/*');
    if ($files !== false) $advanced_count = count($files);
}
$disk_free = round(disk_free_space(__DIR__) / 1024 / 1024, 2);
$active_modules = count(array_filter($modules_status));

$stats = [
    'payloads' => $payload_count + $advanced_count,
    'active_modules' => $active_modules,
    'disk_free' => $disk_free . ' MB',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>🌀 COSMIC OSINT LAB – Sovereign Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/cosmic-c2.css">
    <style>
        :root {
            --cosmic-gold: #ffd700;
            --cosmic-silver: #c0c0c0;
            --cosmic-bronze: #cd7f32;
        }
        .hero {
            text-align: center;
            padding: 40px 20px;
            background: radial-gradient(ellipse at top, rgba(0,255,255,0.1), transparent);
        }
        .hero h1 {
            font-size: 3.5em;
            text-shadow: 0 0 20px #0ff, 0 0 40px #00f;
            animation: cosmicPulse 3s infinite;
        }
        @keyframes cosmicPulse {
            0% { text-shadow: 0 0 10px #0ff; }
            50% { text-shadow: 0 0 30px #0ff, 0 0 60px #f0f; }
            100% { text-shadow: 0 0 10px #0ff; }
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: rgba(0,20,40,0.7);
            backdrop-filter: blur(8px);
            border: 1px solid var(--cosmic-neon-cyan);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0 30px rgba(0,255,255,0.3);
            border-color: var(--cosmic-gold);
        }
        .stat-value {
            font-size: 3em;
            font-weight: bold;
            color: var(--cosmic-neon-green);
            text-shadow: 0 0 10px var(--cosmic-neon-green);
        }
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }
        .module-card {
            background: rgba(10,20,40,0.8);
            backdrop-filter: blur(8px);
            border: 1px solid var(--cosmic-neon-blue);
            border-radius: 24px;
            padding: 25px;
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .module-card:hover {
            transform: scale(1.02) translateY(-8px);
            border-color: var(--cosmic-neon-green);
            box-shadow: 0 20px 40px rgba(0,255,157,0.2);
        }
        .module-icon {
            font-size: 3.5em;
            margin-bottom: 15px;
        }
        .module-title {
            font-size: 1.6em;
            margin-bottom: 10px;
            color: var(--cosmic-neon-cyan);
        }
        .module-desc {
            color: #aaa;
            font-size: 0.9em;
        }
        .module-status {
            display: inline-block;
            margin-top: 15px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7em;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-active {
            background: rgba(0,255,0,0.2);
            color: #0f0;
            border: 1px solid #0f0;
        }
        .status-inactive {
            background: rgba(255,0,0,0.2);
            color: #f00;
            border: 1px solid #f00;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            padding: 20px;
            border-top: 1px solid rgba(0,255,255,0.2);
            color: #888;
        }
        @media (max-width: 768px) {
            .hero h1 { font-size: 2em; }
            .modules-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="main-content" style="max-width: 1400px; margin: 0 auto; padding: 20px;">
        <!-- Hero Section -->
        <div class="hero">
            <h1>🌀 COSMIC OSINT LAB</h1>
            <p style="color: var(--cosmic-neon-green); font-size: 1.2em;">Sovereign Security Operations Platform</p>
            <div style="margin-top: 20px;">
                <span class="beacon-pulse"></span> BEACON ACTIVE
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <?php foreach ($stats as $key => $value): ?>
            <div class="stat-card">
                <div class="stat-value"><?php echo $value; ?></div>
                <div class="stat-label"><?php echo strtoupper(str_replace('_', ' ', $key)); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Modules Grid -->
        <h2 style="color: var(--cosmic-neon-blue); margin-bottom: 20px;">🛠️ Integrated Modules</h2>
        <div class="modules-grid">
            <!-- Trojan Generator -->
            <a href="trojan-dashboard.php" class="module-card">
                <div class="module-icon">🏴‍☠️</div>
                <div class="module-title">Trojan Generator</div>
                <div class="module-desc">Advanced payload generation & encryption</div>
                <div class="module-status status-active">ACTIVE</div>
            </a>
            <!-- Advanced Payloads -->
            <a href="trojan-dashboard.php?advanced=1" class="module-card">
                <div class="module-icon">🧬</div>
                <div class="module-title">Advanced Payloads</div>
                <div class="module-desc">Meterpreter, APK, keylogger, persistence</div>
                <div class="module-status status-active">ACTIVE</div>
            </a>
            <!-- Android Trojan -->
            <a href="trojan-dashboard.php?advanced=1&type=android_custom" class="module-card">
                <div class="module-icon">📱</div>
                <div class="module-title">Android Trojan</div>
                <div class="module-desc">Custom APK backdoors with C2</div>
                <div class="module-status <?php echo $modules_status['android'] ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $modules_status['android'] ? 'ACTIVE' : 'INACTIVE'; ?>
                </div>
            </a>
            <!-- COSMIC C2 -->
            <a href="c2-dashboard.php" class="module-card">
                <div class="module-icon">🎮</div>
                <div class="module-title">COSMIC C2</div>
                <div class="module-desc">Command & Control – sovereign interface</div>
                <div class="module-status status-active">ACTIVE</div>
            </a>
            <!-- Red Team Operations -->
            <a href="redteam-dashboard.php" class="module-card">
                <div class="module-icon">🔴</div>
                <div class="module-title">Red Team Ops</div>
                <div class="module-desc">MITRE ATT&CK, attack simulation</div>
                <div class="module-status status-active">ACTIVE</div>
            </a>
            <!-- Phishing Campaigns -->
            <a href="phishing-dashboard.php" class="module-card">
                <div class="module-icon">🎣</div>
                <div class="module-title">Phishing Campaigns</div>
                <div class="module-desc">Clone pages, email templates, tracking</div>
                <div class="module-status status-active">ACTIVE</div>
            </a>
            <!-- URL Masker -->
            <a href="<?php echo get_url_masker_url(); ?>" class="module-card">
                <div class="module-icon">🔗</div>
                <div class="module-title">URL Masker</div>
                <div class="module-desc">Short links, QR codes, analytics</div>
                <div class="module-status status-active">ACTIVE</div>
            </a>
            <!-- Threat Intelligence -->
            <a href="threat-intel.php" class="module-card">
                <div class="module-icon">🛡️</div>
                <div class="module-title">Threat Intelligence</div>
                <div class="module-desc">IP reputation, deception analysis</div>
                <div class="module-status status-active">ACTIVE</div>
            </a>
            <!-- Virtual Lab -->
            <a href="lab.php" class="module-card">
                <div class="module-icon">🔬</div>
                <div class="module-title">Virtual Lab</div>
                <div class="module-desc">Isolated testing environment</div>
                <div class="module-status status-active">ACTIVE</div>
            </a>
        </div>

        <!-- Footer / Quick Nav -->
        <div class="footer">
            <span>⚡ COSMIC OSINT LAB – Sovereign Infinity Edition</span><br>
            <span style="font-size: 0.8em;">🔄 All modules integrated • Real‑time beaconing • Global scale</span>
        </div>
    </div>
    <script src="/assets/js/unified-quicknav.js" defer></script>
</body>
</html>
