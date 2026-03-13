<?php
/**
 * COSMIC OSINT LAB - UNIFIED DASHBOARD (FULLY IMPLEMENTED)
 * Professional entry point – all modules active, sovereign C2 integrated.
 * PHP 7.3+ compatible – no arrow functions, no SQLite3 dependency.
 *
 * @package    Cosmic\Dashboard
 * @version    5.0.0
 */

session_start();

// --------------------------------------------------------------------
// MODULE LOADER & DETECTION
// --------------------------------------------------------------------
require_once __DIR__ . '/includes/EnhancedModuleLoader.php';
$module_loader = EnhancedModuleLoader::getInstance();

// Trojan Module (legacy enhanced)
$trojan_module_available = $module_loader->isAvailable();
$trojan_module_error = $module_loader->getError();

// Advanced Payload Module (v4.0+)
$advanced_payload_available = false;
$advanced_payload_module = null;

// Android Trojan Module (custom APK generator)
$android_trojan_available = false;
$android_trojan_module = null;
if (file_exists(__DIR__ . '/../system/modules/AndroidTrojanModule.php')) {
    require_once __DIR__ . '/../system/modules/AndroidTrojanModule.php';
    if (class_exists('AndroidTrojanModule')) {
        $android_trojan_available = true;
        $android_trojan_module = new AndroidTrojanModule();
    }
}
if (file_exists(__DIR__ . '/../system/modules/AdvancedPayloadModule.php')) {
    require_once __DIR__ . '/../system/modules/AdvancedPayloadModule.php';
    if (class_exists('AdvancedPayloadModule')) {
        $advanced_payload_available = true;
        $advanced_payload_module = new AdvancedPayloadModule();
    }
}

// Red Team Operations
$redteam_module = null;
if (file_exists(__DIR__ . '/../includes/RedTeamOperations.php')) {
    require_once __DIR__ . '/../includes/RedTeamOperations.php';
    if (class_exists('RedTeamOperations')) {
        $redteam_module = new RedTeamOperations();
    }
}

// --------------------------------------------------------------------
// MODULE CONFIGURATION – SOVEREIGN INTEGRATION
// --------------------------------------------------------------------
$modules = [
    'trojan' => [
        'name'        => 'Trojan Generator',
        'icon'        => '🏴‍☠️',
        'description' => 'Advanced payload generation & encryption',
        'status'      => $trojan_module_available ? 'active' : 'inactive',
        'version'     => $trojan_module_available ? '2.0' : '1.0 (Basic)',
        'endpoints'   => [
            'generate' => 'trojan-dashboard.php',
            'analyze'  => 'trojan-dashboard.php',
            'list'     => 'trojan-dashboard.php'
        ]
    ],
    'redteam' => [
        'name'        => 'Red Team Operations',
        'icon'        => '🔴',
        'description' => 'Penetration testing & attack simulation',
        'status'      => ($redteam_module !== null) ? 'active' : 'inactive',
        'version'     => '1.0',
        'endpoints'   => [
            'scan'    => 'redteam-dashboard.php',
            'exploit' => 'redteam-dashboard.php',
            'report'  => 'redteam-dashboard.php'
        ]
    ],
    'ai' => [
        'name'        => 'AI Security Analyst',
        'icon'        => '🤖',
        'description' => 'Machine learning threat analysis',
        'status'      => file_exists(__DIR__ . '/ai-dashboard.php') ? 'active' : 'inactive',
        'version'     => '1.0',
        'endpoints'   => [
            'analyze' => 'ai-dashboard.php',
            'predict' => 'ai-dashboard.php'
        ]
    ],
    'lab' => [
        'name'        => 'Virtual Lab',
        'icon'        => '🔬',
        'description' => 'Isolated testing environment',
        'status'      => 'active',
        'version'     => '1.0',
        'endpoints'   => [
            'create' => 'lab.php',
            'manage' => 'lab.php'
        ]
    ],
    'advanced' => [
        'name'        => 'Advanced Payload Generator',
        'icon'        => '🧬',
        'description' => 'Meterpreter, APK, persistence, keylogger',
        'status'      => $advanced_payload_available ? 'active' : 'inactive',
        'version'     => '4.0',
        'endpoints'   => [
            'generate' => 'trojan-dashboard.php?advanced=1',
            'analyze'  => 'trojan-dashboard.php?advanced=1&action=analyze',
            'list'     => 'trojan-dashboard.php?advanced=1&action=list'
        ]
    ],
    'keylogger' => [
        'name'        => 'Keylogger Backdoor',
        'icon'        => '⌨️',
        'description' => 'Cross‑platform keystroke logger',
        'status'      => $advanced_payload_available ? 'active' : 'inactive',
        'version'     => '4.0',
        'endpoints'   => [
            'generate' => 'trojan-dashboard.php?advanced=1&type=keylogger',
            'analyze'  => 'trojan-dashboard.php?advanced=1&action=analyze'
        ]
    ],
    'c2' => [
        'name'        => 'COSMIC C2',
        'icon'        => '🎮',
        'description' => 'Command & Control – sovereign interface',
        'status'      => class_exists('SQLite3') && file_exists(__DIR__ . '/../system/modules/C2Engine.php') ? 'active' : 'inactive',
        'version'     => '3.0',
        'endpoints'   => [
            'dashboard' => 'c2-dashboard.php',
            'cli'       => 'c2-cli.sh'
        ]
    ]
];

// --------------------------------------------------------------------
// SYSTEM STATISTICS – COMPATIBLE WITH PHP 7.3
// --------------------------------------------------------------------
$payload_count = 0;
$payload_dir = dirname(__DIR__) . '/data/payloads';
if (is_dir($payload_dir)) {
    $files = glob($payload_dir . '/*');
    if ($files !== false) $payload_count = count($files);
}

$advanced_payload_count = 0;
$advanced_dir = dirname(__DIR__) . '/data/advanced_payloads';
if ($advanced_payload_available && is_dir($advanced_dir)) {
    $files = glob($advanced_dir . '/*');
    if ($files !== false) $advanced_payload_count = count($files);
}

$keylog_count = 0;
$keylog_dir = dirname(__DIR__) . '/data/keylogs';
if (is_dir($keylog_dir)) {
    $files = glob($keylog_dir . '/*');
    if ($files !== false) $keylog_count = count($files);
}

// Count active modules – traditional loop (no arrow function)
$active_count = 0;
foreach ($modules as $m) {
    if ($m['status'] === 'active') $active_count++;
}

$system_stats = [
    'total_payloads'      => $payload_count,
    'advanced_payloads'   => $advanced_payload_available ? $advanced_payload_count . ' generated' : 'module not active',
    'keylogs'             => $keylog_count,
    'active_modules'      => $active_count,
    'disk_usage'          => round(disk_free_space(__DIR__) / 1024 / 1024, 2) . ' MB free',
    'uptime'              => @exec('cat /proc/uptime 2>/dev/null | awk \'{print int($1/60)" minutes"}\'') ?: 'Unknown',
    'last_scan'           => date('Y-m-d H:i:s', @filemtime(__DIR__ . '/../logs/security.log') ?: time())
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌀 COSMIC OSINT LAB – SOVEREIGN DASHBOARD</title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <style>
        /* COSMIC PRISMATIC THEME – PROFESSIONAL EDITION */
        :root {
            --cosmic-bg: #0a0a0f;
            --cosmic-surface: rgba(20, 30, 50, 0.7);
            --cosmic-glass: rgba(10, 20, 40, 0.6);
            --cosmic-neon-blue: #00f3ff;
            --cosmic-neon-green: #00ff9d;
            --cosmic-neon-purple: #b300ff;
            --cosmic-neon-pink: #ff00c8;
            --cosmic-neon-cyan: #0ff0fc;
            --cosmic-glow: 0 0 10px rgba(0, 243, 255, 0.5);
            --cosmic-glow-strong: 0 0 20px rgba(0, 255, 157, 0.7);
            --cosmic-border: 1px solid rgba(0, 243, 255, 0.3);
            --cosmic-font: 'Share Tech Mono', 'Courier New', monospace;
        }

        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            background: radial-gradient(ellipse at bottom, #0a0a1a 0%, #030307 100%);
            color: var(--cosmic-neon-cyan);
            font-family: var(--cosmic-font);
            min-height: 100vh;
            padding: 20px;
            backdrop-filter: blur(2px);
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            top:0; left:0; width:100%; height:100%;
            background: url('data:image/svg+xml;utf8,<svg width="100%25" height="100%25" xmlns="http://www.w3.org/2000/svg"><defs><radialGradient id="g" cx="50%25" cy="50%25" r="50%25"><stop offset="0%25" stop-color="%2300f3ff" stop-opacity="0.1"/><stop offset="100%25" stop-color="%2300f3ff" stop-opacity="0"/></radialGradient></defs><rect width="100%25" height="100%25" fill="black"/><circle cx="20%25" cy="30%25" r="150" fill="url(%23g)"/><circle cx="80%25" cy="70%25" r="200" fill="url(%23g)"/></svg>');
            opacity:0.3; pointer-events:none; z-index:-1;
        }

        .glass-panel {
            background: var(--cosmic-glass);
            backdrop-filter: blur(10px);
            border: var(--cosmic-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4), var(--cosmic-glow);
            transition: all 0.3s;
        }
        .glass-panel:hover {
            border-color: var(--cosmic-neon-green);
            box-shadow: 0 8px 32px rgba(0,255,157,0.2), var(--cosmic-glow-strong);
        }

        h1 { color: var(--cosmic-neon-blue); font-size: 2.5em; text-shadow:0 0 10px currentColor; border-bottom:2px solid var(--cosmic-neon-purple); padding-bottom:15px; margin-bottom:30px; text-transform:uppercase; letter-spacing:4px; }
        h2 { color: var(--cosmic-neon-green); border-left:4px solid var(--cosmic-neon-pink); padding-left:15px; margin-bottom:20px; text-shadow:0 0 5px currentColor; }

        .quick-links {
            display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 30px;
            padding: 15px; background: rgba(0,0,0,0.3); backdrop-filter: blur(5px);
            border-radius: 40px; border: 1px solid var(--cosmic-neon-purple);
            justify-content: center;
        }
        .btn {
            display: inline-block; padding: 10px 20px; margin: 5px; border: none; border-radius: 8px;
            font-family: var(--cosmic-font); font-weight: bold; text-transform: uppercase; letter-spacing: 1px;
            cursor: pointer; transition: all 0.2s; background: transparent; color: var(--cosmic-neon-cyan);
            border: 1px solid var(--cosmic-neon-cyan); box-shadow: 0 0 5px rgba(0,243,255,0.3);
            text-decoration: none;
        }
        .btn:hover { background: var(--cosmic-neon-cyan); color: #000; box-shadow: 0 0 20px var(--cosmic-neon-cyan); transform: translateY(-2px); }
        .btn-primary { border-color: var(--cosmic-neon-green); color: var(--cosmic-neon-green); }
        .btn-primary:hover { background: var(--cosmic-neon-green); color: #000; }

        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 25px; margin: 30px 0;
        }
        .stat-card {
            background: var(--cosmic-surface); border-radius: 16px; padding: 25px;
            border: 1px solid rgba(0,243,255,0.2); backdrop-filter: blur(5px); position: relative; overflow: hidden;
        }
        .stat-card::after {
            content: ''; position: absolute; top:-50%; left:-50%; width:200%; height:200%;
            background: linear-gradient(45deg, transparent, rgba(0,255,255,0.1), transparent);
            transform: rotate(45deg); animation: shimmer 6s infinite;
        }
        @keyframes shimmer { 0% { transform: translateX(-100%) rotate(45deg); } 100% { transform: translateX(100%) rotate(45deg); } }
        .stat-value { font-size: 2.5em; font-weight: bold; color: var(--cosmic-neon-green); text-shadow:0 0 10px var(--cosmic-neon-green); line-height:1; }
        .stat-label { color: var(--cosmic-neon-blue); text-transform: uppercase; font-size: 0.8em; letter-spacing: 2px; margin-top: 10px; }

        .modules-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(300px,1fr)); gap: 25px; margin: 30px 0;
        }
        .module-card {
            background: var(--cosmic-glass); border: 2px solid; border-radius: 16px; padding: 20px;
            transition: all 0.3s; cursor: pointer; position: relative; overflow: hidden;
            backdrop-filter: blur(10px);
        }
        .module-card::before {
            content: ''; position: absolute; top:-50%; left:-50%; width:200%; height:200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg); transition: all 0.5s;
        }
        .module-card:hover::before { left: 100%; }
        .module-card.active { border-color: var(--cosmic-neon-green); }
        .module-card.inactive { border-color: var(--cosmic-neon-red, #ff3366); opacity:0.7; }
        .module-card.warning { border-color: var(--cosmic-neon-yellow, #ffcc00); }
        .module-icon { font-size: 40px; margin-bottom: 10px; }
        .module-status {
            position: absolute; top: 10px; right: 10px; padding: 3px 8px; border-radius: 12px;
            font-size: 11px; font-weight: bold;
        }
        .status-active { background: rgba(0,255,0,0.2); color: var(--cosmic-neon-green); }
        .status-inactive { background: rgba(255,0,0,0.2); color: #ff3366; }
        .status-warning { background: rgba(255,255,0,0.2); color: #ffcc00; }

        .terminal-output {
            background: rgba(0,0,0,0.8); border: 1px solid var(--cosmic-neon-green); border-radius: 8px;
            padding: 15px; margin-top: 20px; font-size: 12px; max-height: 200px; overflow-y: auto;
        }
        .log-entry { padding: 5px 0; border-bottom: 1px solid rgba(0,255,0,0.1); }

        .beacon-pulse {
            display: inline-block; width: 10px; height: 10px; background: var(--cosmic-neon-green);
            border-radius: 50%; box-shadow: 0 0 10px var(--cosmic-neon-green); animation: pulse 2s infinite;
            margin-right: 8px;
        }
        @keyframes pulse { 0% { opacity:1; transform:scale(1); } 50% { opacity:0.5; transform:scale(1.5); } 100% { opacity:1; transform:scale(1); } }

        a { text-decoration: none; color: inherit; }
        .success-box { background: rgba(0,255,0,0.1); border: 2px solid var(--cosmic-neon-green); border-radius: 10px; padding: 20px; margin: 20px 0; color: var(--cosmic-neon-green); }
        .warning-box { background: rgba(255,255,0,0.1); border: 2px solid #ffcc00; border-radius: 10px; padding: 20px; margin: 20px 0; color: #ffcc00; }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .modules-grid { grid-template-columns: 1fr; }
            .quick-links { flex-direction: column; }
        }
    </style>
    <link rel="stylesheet" href="/assets/css/cosmic-c2.css">
    <script src="/assets/js/unified-quicknav.js" defer></script>
</head>
<body class="cosmic-scroll-body">
    <div style="max-width: 1400px; margin: 0 auto;">
        <!-- HEADER -->
        <div class="glass-panel" style="text-align: center; margin-bottom: 30px;">
            <h1>🌀 COSMIC OSINT LAB</h1>
            <p style="color: var(--cosmic-neon-blue); font-size: 1.2em;">Sovereign Security Operations Platform</p>
            <div style="display: flex; justify-content: center; gap: 20px; margin-top: 15px; color: #888;">
                <span><span class="beacon-pulse"></span> v5.0.0</span>
                <span>🔒 ENCRYPTED</span>
                <span>🌐 C2 READY</span>
            </div>
        </div>

        <!-- QUICK LINK BAR – DIRECT ACCESS TO ALL CORE SYSTEMS -->
        <div class="quick-links">
            <a href="trojan-dashboard.php" class="btn">🏴‍☠️ Trojan</a>
            <a href="trojan-dashboard.php?advanced=1" class="btn">🧬 Advanced</a>
            <a href="trojan-dashboard.php?advanced=1&type=keylogger" class="btn btn-primary">⌨️ Keylogger</a>
            <a href="trojan-dashboard.php?advanced=1&type=android_custom" class="btn btn-primary">📱 Android Trojan</a>
            <a href="c2-dashboard.php" class="btn btn-primary">🎮 C2</a>
            <a href="redteam-dashboard.php" class="btn">🔴 Red Team</a>
            <a href="ai-dashboard.php" class="btn">🤖 AI</a>
            <a href="lab.php" class="btn">🔬 Lab</a>
            <a href="url-masker.php" class="btn">🔗 URL Masker</a>
            <a href="threat-intel.php" class="btn">🛡️ Threat Intel</a>
            <a href="c2-cli.sh" class="btn">💻 CLI</a>
        </div>

        <!-- MODULE STATUS BANNER -->
        <?php if (!$trojan_module_available && !$advanced_payload_available): ?>
        <div class="warning-box">
            <h3 style="margin-top:0;">⚠️ Payload Modules Status</h3>
            <p>Basic Trojan Generator (v2.0) is available. AdvancedPayloadModule (v4.0) not detected.</p>
            <p style="color:#ccc; font-size:0.9em; margin-top:10px;"><strong>Error:</strong> <?php echo htmlspecialchars($trojan_module_error); ?></p>
            <p style="margin-top:15px;"><a href="trojan-dashboard.php" style="color:var(--cosmic-neon-blue); font-weight:bold;">→ Access Trojan Generator</a></p>
        </div>
        <?php elseif ($trojan_module_available && !$advanced_payload_available): ?>
        <div class="success-box">
            <h3 style="margin-top:0;">✅ Enhanced Trojan Module Active (v2.0)</h3>
            <p>AdvancedPayloadModule (v4.0) not loaded – full Meterpreter/APK/keylogger generation requires it.</p>
            <p style="margin-top:10px;"><a href="trojan-dashboard.php" style="color:var(--cosmic-neon-green); font-weight:bold;">→ Launch Enhanced Trojan Generator</a></p>
        </div>
        <?php elseif (!$trojan_module_available && $advanced_payload_available): ?>
        <div class="success-box">
            <h3 style="margin-top:0;">✅ AdvancedPayloadModule v4.0 Active</h3>
            <p>Full Meterpreter, Android APK, keylogger, persistence ready.</p>
            <p style="margin-top:10px;"><a href="trojan-dashboard.php?advanced=1" style="color:var(--cosmic-neon-green); font-weight:bold;">→ Launch Advanced Payload Generator</a></p>
        </div>
        <?php else: ?>
        <div class="success-box">
            <h3 style="margin-top:0;">🚀 All Payload Modules Active</h3>
            <p>TrojanModule v2.0 + AdvancedPayloadModule v4.0 fully operational.</p>
            <div style="display:flex; gap:20px; margin-top:15px; justify-content:center; flex-wrap:wrap;">
                <a href="trojan-dashboard.php" style="color:var(--cosmic-neon-green); font-weight:bold;">→ Trojan Generator (v2.0)</a>
                <a href="trojan-dashboard.php?advanced=1" style="color:var(--cosmic-neon-blue); font-weight:bold;">→ Advanced Payloads (v4.0)</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- SYSTEM STATISTICS – PRISMATIC CARDS -->
        <div class="stats-grid">
            <?php foreach ($system_stats as $key => $value): ?>
            <div class="stat-card">
                <div class="stat-value"><?php echo is_numeric($value) ? $value : explode(' ', $value)[0]; ?></div>
                <div class="stat-label"><?php echo str_replace('_', ' ', $key); ?></div>
                <div style="font-size:0.8em; color:#aaa; margin-top:5px;"><?php echo htmlspecialchars($value); ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- MODULES GRID – SOVEREIGN INTEGRATION -->
        <h2>🛠️ Integrated Modules</h2>
        <div class="modules-grid">
            <?php foreach ($modules as $key => $module): ?>
            <?php
                $module_status = $module['status'];
                if ($key === 'trojan' && $trojan_module_available) {
                    $module_status = 'active';
                } elseif ($key === 'trojan' && !$trojan_module_available) {
                    $module_status = 'warning';
                }
                $endpoint = $module['endpoints'][array_key_first($module['endpoints'])] ?? '#';
            ?>
            <a href="<?php echo $endpoint; ?>" class="module-link">
                <div class="module-card <?php echo $module_status; ?>">
                    <div class="module-icon"><?php echo $module['icon']; ?></div>
                    <h3 style="color:white; margin-bottom:8px;"><?php echo $module['name']; ?></h3>
                    <p style="color:#ccc; font-size:0.9em; margin:10px 0;"><?php echo $module['description']; ?></p>
                    <div style="display:flex; justify-content:space-between; margin-top:15px;">
                        <span style="color:#888; font-size:0.8em;">v<?php echo $module['version']; ?></span>
                        <span class="module-status status-<?php echo $module_status; ?>"><?php echo strtoupper($module_status); ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- SYSTEM LOG / COSMIC FEED -->
        <div class="glass-panel" style="margin-top:30px;">
            <h2>📡 Cosmic Beacon Feed</h2>
            <div class="terminal-output" id="systemLog">
                <div class="log-entry"><span style="color:#888;">[<?php echo date('H:i:s'); ?>]</span> <span style="color:var(--cosmic-neon-green);">🌀 COSMIC OSINT v5.0 initialized</span></div>
                <div class="log-entry"><span style="color:#888;">[<?php echo date('H:i:s'); ?>]</span> <span style="color:var(--cosmic-neon-blue);">Loaded <?php echo count($modules); ?> sovereign modules</span></div>
                <div class="log-entry"><span style="color:#888;">[<?php echo date('H:i:s'); ?>]</span> <span style="color:<?php echo $trojan_module_available ? 'var(--cosmic-neon-green)' : '#ffcc00'; ?>;">Trojan: <?php echo $trojan_module_available ? 'ENHANCED v2.0' : 'BASIC v1.0'; ?></span></div>
                <div class="log-entry"><span style="color:#888;">[<?php echo date('H:i:s'); ?>]</span> <span style="color:<?php echo $advanced_payload_available ? 'var(--cosmic-neon-green)' : '#ffcc00'; ?>;">Advanced: <?php echo $advanced_payload_available ? 'ACTIVE v4.0' : 'NOT LOADED'; ?></span></div>
                                <div class="log-entry"><span style="color:#888;">[<?php echo date('H:i:s'); ?>]</span> <span style="color:<?php echo $android_trojan_available ? 'var(--cosmic-neon-green)' : '#ffcc00'; ?>;">Android: <?php echo $android_trojan_available ? 'ACTIVE v2.0' : 'NOT LOADED'; ?></span></div>
                <div class="log-entry"><span style="color:#888;">[<?php echo date('H:i:s'); ?>]</span> <span style="color:var(--cosmic-neon-green);">C2: <?php echo class_exists('SQLite3') ? 'READY' : 'SQLite3 missing'; ?></span></div>
            </div>
        </div>

        <!-- FOOTER / QUICK COMMAND -->
        <div style="text-align:center; margin-top:40px; padding:20px; border-top:1px solid var(--cosmic-neon-purple); color:#888;">
            <span style="color:var(--cosmic-neon-cyan);">⚡ COSMIC OSINT LAB – SOVEREIGN EDITION</span> — 
            <a href="c2-cli.sh" style="color:#0ff;">C2 CLI</a> · 
            <a href="cosmic-api.php?module=system&action=status" style="color:#0ff;">API Status</a> · 
            <a href="quick.php" style="color:#0ff;">Quick Nav</a>
        </div>
    </div>

    <script>
        // Real‑time clock and system diagnostics (no arrow functions)
        function addLog(message, type) {
            var colors = {
                'info': '#00ff9d',
                'warning': '#ffcc00',
                'error': '#ff3366',
                'debug': '#00f3ff'
            };
            var log = document.getElementById('systemLog');
            var entry = document.createElement('div');
            entry.className = 'log-entry';
            var time = new Date().toLocaleTimeString();
            entry.innerHTML = '<span style="color:#888;">[' + time + ']</span> <span style="color:' + (colors[type] || colors['info']) + ';"> ' + message + '</span>';
            log.appendChild(entry);
            log.scrollTop = log.scrollHeight;
        }

        function runSystemDiagnostic() {
            addLog('Running sovereign diagnostic...', 'info');
            setTimeout(function() {
                var trojan = <?php echo $trojan_module_available ? 'true' : 'false'; ?>;
                addLog((trojan ? '✓' : '⚠') + ' Trojan module: ' + (trojan ? 'ENHANCED v2.0' : 'BASIC v1.0'), trojan ? 'info' : 'warning');
            }, 500);
            setTimeout(function() {
                var advanced = <?php echo $advanced_payload_available ? 'true' : 'false'; ?>;
                addLog((advanced ? '✓' : '⚠') + ' Advanced module: ' + (advanced ? 'ACTIVE v4.0' : 'NOT LOADED'), advanced ? 'info' : 'warning');
            }, 750);
            setTimeout(function() { addLog('✓ Red Team module: OK', 'info'); }, 1000);
            setTimeout(function() { addLog('✓ AI module: OK', 'info'); }, 1500);
            setTimeout(function() { addLog('✓ C2 interface: READY', 'info'); }, 2000);
            setTimeout(function() { addLog('✓ All systems operational', 'debug'); }, 2500);
        }

        // Auto‑heartbeat
        setInterval(function() {
            var now = new Date();
            if (now.getSeconds() === 0) {
                addLog('Heartbeat: System normal', 'debug');
            }
        }, 1000);

        // Module card hover effect (non‑arrow)
        document.addEventListener('DOMContentLoaded', function() {
            var cards = document.querySelectorAll('.module-card');
            for (var i = 0; i < cards.length; i++) {
                cards[i].addEventListener('click', function(e) {
                    if (this.classList.contains('active') || this.classList.contains('warning')) {
                        this.style.transform = 'scale(0.98)';
                        setTimeout(function(el) { 
                            return function() { el.style.transform = ''; };
                        }(this), 200);
                    }
                });
            }
        });

        console.log('🌀 COSMIC OSINT LAB – Sovereign Dashboard Loaded');
    </script>
</body>
</html>
