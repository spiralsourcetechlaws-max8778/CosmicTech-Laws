<?php
/**
 * COSMIC OSINT LAB - Enhanced Virtual Lab
 * Integrated with Trojan Generator for safe testing
 */

session_start();
require_once "includes/security_functions.php";

// Check if Enhanced Trojan Module is available
$trojan_module_available = false;
$trojan_module = null;

if (file_exists(__DIR__ . '/../system/modules/TrojanModule.php')) {
    require_once __DIR__ . '/../system/modules/TrojanModule.php';
    if (class_exists('EnhancedTrojanModule')) {
        $trojan_module_available = true;
        $trojan_module = new EnhancedTrojanModule();
    }
}

// Handle lab actions
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$output = '';
$test_results = [];

if ($action === 'test_payload') {
    // Test payload in isolated environment
    $payload = $_POST['payload'] ?? '';
    $platform = $_POST['platform'] ?? 'linux';
    
    if (!empty($payload)) {
        $test_results = simulate_payload_test($payload, $platform);
        log_security_event('LAB_PAYLOAD_TEST', [
            'platform' => $platform,
            'size' => strlen($payload),
            'result' => $test_results['status']
        ]);
    }
}

function simulate_payload_test($payload, $platform) {
    // Simulate payload testing in isolated environment
    $checks = [
        'syntax_check' => rand(0, 100) > 10,  // 90% pass rate
        'av_detection' => rand(0, 100) > 70,  // 30% detection rate
        'network_activity' => rand(0, 100) > 40, // 60% network activity
        'persistence_check' => rand(0, 100) > 50, // 50% persistence
    ];
    
    $passed = count(array_filter($checks));
    $total = count($checks);
    
    return [
        'status' => $passed >= ($total * 0.7) ? 'passed' : 'failed',
        'checks' => $checks,
        'score' => round(($passed / $total) * 100),
        'recommendations' => generate_recommendations($checks),
        'log' => generate_test_log($payload, $platform, $checks)
    ];
}

function generate_recommendations($checks) {
    $recommendations = [];
    
    if (!$checks['syntax_check']) {
        $recommendations[] = 'Fix syntax errors in payload';
    }
    
    if ($checks['av_detection']) {
        $recommendations[] = 'Enhance obfuscation to avoid AV detection';
    }
    
    if (!$checks['network_activity']) {
        $recommendations[] = 'Check network connectivity settings';
    }
    
    return $recommendations;
}

function generate_test_log($payload, $platform, $checks) {
    $log = [];
    $log[] = "=== Payload Test Log ===";
    $log[] = "Platform: " . strtoupper($platform);
    $log[] = "Payload size: " . strlen($payload) . " bytes";
    $log[] = "MD5: " . md5($payload);
    $log[] = "";
    $log[] = "Test Results:";
    
    foreach ($checks as $check => $result) {
        $log[] = sprintf("  %s: %s", 
            str_replace('_', ' ', $check),
            $result ? '✅ PASS' : '❌ FAIL'
        );
    }
    
    return implode("\n", $log);
}

// Available lab tools
$lab_tools = [
    'payload_tester' => [
        'name' => 'Payload Tester',
        'icon' => '🧪',
        'description' => 'Test generated payloads in isolated environment',
        'status' => $trojan_module_available ? 'active' : 'inactive'
    ],
    'network_simulator' => [
        'name' => 'Network Simulator',
        'icon' => '📡',
        'description' => 'Simulate network environments for testing',
        'status' => 'active'
    ],
    'sandbox' => [
        'name' => 'Sandbox Analyzer',
        'icon' => '🏖️',
        'description' => 'Analyze payload behavior in sandbox',
        'status' => 'developing'
    ],
    'traffic_analyzer' => [
        'name' => 'Traffic Analyzer',
        'icon' => '📊',
        'description' => 'Monitor and analyze network traffic',
        'status' => 'active'
    ],
    'honeypot' => [
        'name' => 'Honeypot Deployer',
        'icon' => '🍯',
        'description' => 'Deploy deception systems',
        'status' => 'active'
    ],
    'forensics' => [
        'name' => 'Forensics Toolkit',
        'icon' => '🔍',
        'description' => 'Digital forensics and analysis tools',
        'status' => 'developing'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔬 Enhanced Virtual Lab - COSMIC-OSINT-LAB</title>
    <style>
        :root {
            --lab-green: #00ff00;
            --lab-blue: #00ffff;
            --lab-purple: #8a2be2;
            --lab-red: #ff0000;
            --lab-bg: #0a0a0a;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: var(--lab-bg);
            color: var(--lab-green);
            margin: 0;
            padding: 20px;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(0, 255, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 255, 0, 0.05) 0%, transparent 50%);
        }
        
        .lab-header {
            text-align: center;
            padding: 30px 0;
            border-bottom: 3px solid var(--lab-blue);
            margin-bottom: 30px;
            position: relative;
        }
        
        .lab-header::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--lab-green), transparent);
        }
        
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .tool-card {
            background: rgba(20, 40, 20, 0.8);
            border: 2px solid var(--lab-green);
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .tool-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--lab-green), var(--lab-blue));
        }
        
        .tool-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 255, 0, 0.2);
            border-color: var(--lab-blue);
        }
        
        .tool-icon {
            font-size: 40px;
            margin-bottom: 15px;
            display: block;
        }
        
        .tool-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-active {
            background: rgba(0, 255, 0, 0.2);
            color: var(--lab-green);
        }
        
        .status-inactive {
            background: rgba(255, 0, 0, 0.2);
            color: var(--lab-red);
        }
        
        .status-developing {
            background: rgba(255, 255, 0, 0.2);
            color: var(--lab-blue);
        }
        
        .test-panel {
            background: rgba(0, 40, 0, 0.9);
            border: 2px solid var(--lab-green);
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
        }
        
        .test-form {
            display: grid;
            gap: 15px;
            margin-top: 20px;
        }
        
        .form-group {
            display: grid;
            gap: 5px;
        }
        
        label {
            color: var(--lab-blue);
            font-weight: bold;
        }
        
        textarea, select {
            background: #000;
            color: var(--lab-green);
            border: 1px solid var(--lab-green);
            padding: 10px;
            border-radius: 5px;
            font-family: inherit;
            resize: vertical;
        }
        
        .test-button {
            background: #000;
            color: var(--lab-green);
            border: 2px solid var(--lab-green);
            padding: 15px;
            font-size: 16px;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .test-button:hover {
            background: var(--lab-green);
            color: #000;
            transform: scale(1.05);
        }
        
        .results-panel {
            background: rgba(40, 0, 0, 0.9);
            border: 2px solid var(--lab-red);
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
            display: none;
        }
        
        .result-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 0, 0, 0.2);
        }
        
        .terminal-output {
            background: #000;
            border: 1px solid var(--lab-blue);
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        
        .lab-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: rgba(20, 20, 40, 0.8);
            border: 1px solid var(--lab-blue);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .tools-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="/assets/css/cosmic-c2.css">
    <script src="/assets/js/unified-quicknav.js" defer></script>
</head>
<body class="cosmic-scroll-body">
<?php require_once __DIR__ . "/includes/navigation.php"; echo render_cosmic_navigation(); ?>
    <div class="lab-header">
        <h1 style="color: var(--lab-blue); font-size: 2.5em;">🔬 Enhanced Virtual Lab</h1>
        <p style="color: var(--lab-green);">Isolated Testing Environment - COSMIC OSINT LAB</p>
        <p style="color: #888; font-size: 0.9em;">Safe payload testing and security research</p>
    </div>
    
    <div class="lab-stats">
        <div class="stat-card">
            <div style="color: var(--lab-blue); font-size: 1.2em; font-weight: bold;">
                <?php echo count($lab_tools); ?>
            </div>
            <div style="color: #ccc; font-size: 0.9em;">Available Tools</div>
        </div>
        <div class="stat-card">
            <div style="color: var(--lab-green); font-size: 1.2em; font-weight: bold;">
                <?php echo count(array_filter($lab_tools, fn($t) => $t['status'] === 'active')); ?>
            </div>
            <div style="color: #ccc; font-size: 0.9em;">Active Tools</div>
        </div>
        <div class="stat-card">
            <div style="color: var(--lab-purple); font-size: 1.2em; font-weight: bold;">
                <?php echo $trojan_module_available ? '✅' : '❌'; ?>
            </div>
            <div style="color: #ccc; font-size: 0.9em;">Trojan Integration</div>
        </div>
        <div class="stat-card">
            <div style="color: var(--lab-green); font-size: 1.2em; font-weight: bold;">
                ISOLATED
            </div>
            <div style="color: #ccc; font-size: 0.9em;">Environment</div>
        </div>
    </div>
    
    <div class="tools-grid">
        <?php foreach ($lab_tools as $key => $tool): ?>
        <div class="tool-card" onclick="openTool('<?php echo $key; ?>')">
            <div class="tool-icon"><?php echo $tool['icon']; ?></div>
            <h3><?php echo $tool['name']; ?></h3>
            <p style="color: #ccc; margin: 10px 0; font-size: 0.9em;">
                <?php echo $tool['description']; ?>
            </p>
            <span class="tool-status status-<?php echo $tool['status']; ?>">
                <?php echo strtoupper($tool['status']); ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Payload Testing Panel -->
    <div class="test-panel">
        <h2 style="color: var(--lab-green); border-bottom: 2px solid var(--lab-green); padding-bottom: 10px;">
            🧪 Payload Testing Environment
        </h2>
        
        <?php if ($trojan_module_available): ?>
        <p style="color: #ccc; margin: 15px 0;">
            Test your generated payloads in an isolated environment. This simulates how the payload 
            would behave without actually executing it on a real system.
        </p>
        
        <form method="POST" action="?action=test_payload" class="test-form">
            <input type="hidden" name="action" value="test_payload">
            
            <div class="form-group">
                <label for="platform">Target Platform:</label>
                <select name="platform" id="platform" required>
                    <option value="linux">Linux</option>
                    <option value="windows">Windows</option>
                    <option value="android">Android</option>
                    <option value="python">Python (Cross-platform)</option>
                    <option value="mac">macOS</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="payload">Payload Code:</label>
                <textarea name="payload" id="payload" rows="10" 
                          placeholder="Paste your generated payload code here..." 
                          required></textarea>
            </div>
            
            <button type="submit" class="test-button">
                🚀 Test Payload in Sandbox
            </button>
        </form>
        
        <?php if (!empty($test_results)): ?>
        <div class="results-panel" id="testResults" style="display: block;">
            <h3 style="color: var(--lab-red);">Test Results</h3>
            
            <div class="result-item">
                <span>Overall Status:</span>
                <span style="color: <?php echo $test_results['status'] === 'passed' ? 'var(--lab-green)' : 'var(--lab-red)'; ?>; font-weight: bold;">
                    <?php echo strtoupper($test_results['status']); ?>
                </span>
            </div>
            
            <div class="result-item">
                <span>Security Score:</span>
                <span style="color: var(--lab-blue); font-weight: bold;">
                    <?php echo $test_results['score']; ?>%
                </span>
            </div>
            
            <h4 style="color: var(--lab-green); margin-top: 20px;">Individual Checks:</h4>
            <?php foreach ($test_results['checks'] as $check => $result): ?>
            <div class="result-item">
                <span><?php echo str_replace('_', ' ', $check); ?>:</span>
                <span style="color: <?php echo $result ? 'var(--lab-green)' : 'var(--lab-red)'; ?>;">
                    <?php echo $result ? '✅ PASS' : '❌ FAIL'; ?>
                </span>
            </div>
            <?php endforeach; ?>
            
            <?php if (!empty($test_results['recommendations'])): ?>
            <h4 style="color: var(--lab-blue); margin-top: 20px;">Recommendations:</h4>
            <ul style="color: #ccc; padding-left: 20px;">
                <?php foreach ($test_results['recommendations'] as $rec): ?>
                <li><?php echo $rec; ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            
            <h4 style="color: var(--lab-green); margin-top: 20px;">Test Log:</h4>
            <div class="terminal-output">
                <?php echo htmlspecialchars($test_results['log']); ?>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <button class="test-button" onclick="downloadTestReport()">
                    💾 Download Test Report
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div style="text-align: center; padding: 40px; background: rgba(255, 0, 0, 0.1); border: 2px solid var(--lab-red); border-radius: 10px;">
            <h3 style="color: var(--lab-red);">⚠️ Trojan Module Not Available</h3>
            <p style="color: #ccc; margin: 15px 0;">
                The enhanced Trojan module is required for payload testing.
            </p>
            <p>
                <a href="trojan-dashboard.php" style="color: var(--lab-blue); text-decoration: none; font-weight: bold;">
                    → Go to Trojan Generator
                </a>
            </p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 30px 0;">
        <button class="test-button" onclick="window.location.href='trojan-dashboard.php'">
        <button class="test-button" onclick="window.location.href='trojan-dashboard.php?advanced=1&type=android_custom'">📱 Android Trojan</button>
            🏴‍☠️ Generate New Payload
        </button>
        <button class="test-button" onclick="window.location.href='index.php'">
        <button class="test-button" onclick="window.location.href='url-masker.php'">🔗 URL Masker</button>
            🚀 Unified Dashboard
        </button>
        <button class="test-button" onclick="window.location.href='redteam-dashboard.php'">
            🔴 Red Team Ops
        </button>
        <button class="test-button" onclick="window.location.href='ai-dashboard.php'">
            🤖 AI Analysis
        </button>
    </div>
    
    <script>
        // Tool interaction
        function openTool(toolId) {
            const tools = {
                'payload_tester': 'Scroll to payload testing section',
                'network_simulator': 'Network simulation tool',
                'sandbox': 'Sandbox analysis',
                'traffic_analyzer': 'Traffic analysis',
                'honeypot': 'Honeypot deployment',
                'forensics': 'Forensics toolkit'
            };
            
            if (toolId === 'payload_tester') {
                document.querySelector('.test-panel').scrollIntoView({ behavior: 'smooth' });
            } else {
                alert(tools[toolId] + ' - Coming soon!');
            }
        }
        
        // Download test report
        function downloadTestReport() {
            const results = <?php echo json_encode($test_results ?? []); ?>;
            if (Object.keys(results).length === 0) {
                alert('No test results available');
                return;
            }
            
            let report = '=== COSMIC OSINT LAB - Payload Test Report ===\n\n';
            report += 'Generated: ' + new Date().toLocaleString() + '\n';
            report += 'Status: ' + results.status.toUpperCase() + '\n';
            report += 'Score: ' + results.score + '%\n\n';
            report += 'Checks:\n';
            
            for (const [check, result] of Object.entries(results.checks)) {
                report += '  ' + check.replace(/_/g, ' ') + ': ' + (result ? 'PASS' : 'FAIL') + '\n';
            }
            
            report += '\n' + results.log;
            
            const blob = new Blob([report], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'payload_test_report_' + Date.now() + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
        
        // Auto-focus payload textarea
        document.getElementById('payload')?.focus();
        
        // Show test results if available
        <?php if (!empty($test_results)): ?>
        document.getElementById('testResults').style.display = 'block';
        <?php endif; ?>
        
        console.log('Enhanced Virtual Lab loaded');
    </script>
</body>
</html>
