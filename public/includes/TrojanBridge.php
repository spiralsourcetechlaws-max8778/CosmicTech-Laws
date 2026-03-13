<?php
/**
 * COSMIC OSINT LAB - Trojan Module Bridge
 * Provides compatibility between old and new Trojan modules
 */

class TrojanBridge {
    
    private $enhanced_module = null;
    private $basic_module = null;
    private $use_enhanced = true;
    
    public function __construct($config = []) {
        // Try to load enhanced module first
        $enhanced_path = dirname(__DIR__) . '/system/modules/TrojanModule.php';
        
        if (file_exists($enhanced_path)) {
            require_once $enhanced_path;
            
            if (class_exists('EnhancedTrojanModule')) {
                $this->enhanced_module = new EnhancedTrojanModule($config);
                $this->use_enhanced = true;
                return;
            }
        }
        
        // Fallback to basic module
        if (file_exists(__DIR__ . '/TrojanGenerator.php')) {
            require_once __DIR__ . '/TrojanGenerator.php';
            
            if (class_exists('TrojanGenerator')) {
                $this->basic_module = new TrojanGenerator();
                $this->use_enhanced = false;
            }
        }
    }
    
    /**
     * Generate payload (compatible with both modules)
     */
    public function generate_payload($config) {
        if ($this->use_enhanced && $this->enhanced_module) {
            $enhanced_result = $this->enhanced_module->generate_advanced_payload($config);
            
            // Convert to old format for compatibility
            return [
                'payload' => $enhanced_result['payload'] ?? '',
                'filename' => $enhanced_result['filename'] ?? 'payload.sh',
                'size' => $enhanced_result['size'] ?? 0,
                'hash' => $enhanced_result['hash'] ?? ['md5' => '', 'sha256' => ''],
                'enhanced' => true,
                'signature' => $enhanced_result['signature'] ?? '',
                'av_score' => $enhanced_result['av_evasion_score'] ?? 0
            ];
        }
        
        if ($this->basic_module) {
            return $this->basic_module->generate_payload($config);
        }
        
        return [
            'error' => 'No Trojan module available',
            'payload' => '# Error: Trojan module not loaded',
            'filename' => 'error.txt',
            'size' => 0,
            'hash' => ['md5' => '', 'sha256' => '']
        ];
    }
    
    /**
     * Generate listener commands
     */
    public function generate_listener_command($type, $lhost, $lport) {
        if ($this->use_enhanced && $this->enhanced_module) {
            $config = [
                'type' => $type,
                'lhost' => $lhost,
                'lport' => $lport
            ];
            return $this->enhanced_module->generate_multi_listener($config);
        }
        
        if ($this->basic_module) {
            return $this->basic_module->generate_listener_command($type, $lhost, $lport);
        }
        
        return [
            'error' => 'No listener commands available',
            'netcat' => 'nc -lvnp ' . $lport
        ];
    }
    
    /**
     * Get available payload types
     */
    public function get_payload_types() {
        if ($this->use_enhanced && $this->enhanced_module) {
            $templates = $this->enhanced_module->get_payload_templates();
            
            // Convert to old format
            $types = [];
            foreach ($templates as $key => $template) {
                $types[$key] = [
                    'name' => $template['name'] ?? ucfirst($key),
                    'description' => $template['description'] ?? '',
                    'platforms' => $template['platforms'] ?? ['linux'],
                    'complexity' => $template['complexity'] ?? 'medium'
                ];
            }
            
            return $types;
        }
        
        if ($this->basic_module && method_exists($this->basic_module, 'get_payload_types')) {
            return $this->basic_module->get_payload_types();
        }
        
        // Default payload types
        return [
            'reverse_shell' => [
                'name' => 'Reverse Shell',
                'description' => 'Standard reverse shell connection',
                'platforms' => ['linux', 'windows', 'python'],
                'complexity' => 'low'
            ],
            'web_shell' => [
                'name' => 'Web Shell',
                'description' => 'PHP web backdoor',
                'platforms' => ['php'],
                'complexity' => 'low'
            ]
        ];
    }
    
    /**
     * Get encryption methods
     */
    public function get_encryption_methods() {
        if ($this->use_enhanced && $this->enhanced_module) {
            // Enhanced module should have this method
            if (method_exists($this->enhanced_module, 'get_encryption_methods')) {
                return $this->enhanced_module->get_encryption_methods();
            }
            
            return [
                'none' => 'No Encryption',
                'aes256' => 'AES-256',
                'xor' => 'XOR Obfuscation',
                'base64' => 'Base64 Encoding'
            ];
        }
        
        if ($this->basic_module && method_exists($this->basic_module, 'get_encryption_methods')) {
            return $this->basic_module->get_encryption_methods();
        }
        
        return [
            'none' => 'No Encryption',
            'base64' => 'Base64 Encoding'
        ];
    }
    
    /**
     * Get output formats
     */
    public function get_output_formats() {
        if ($this->use_enhanced && $this->enhanced_module) {
            // Enhanced module should have this method
            if (method_exists($this->enhanced_module, 'get_output_formats')) {
                return $this->enhanced_module->get_output_formats();
            }
            
            return [
                'sh' => 'Shell Script (.sh)',
                'ps1' => 'PowerShell (.ps1)',
                'exe' => 'Windows Executable (.exe)',
                'py' => 'Python Script (.py)',
                'apk' => 'Android APK (.apk)'
            ];
        }
        
        if ($this->basic_module && method_exists($this->basic_module, 'get_output_formats')) {
            return $this->basic_module->get_output_formats();
        }
        
        return [
            'sh' => 'Shell Script',
            'ps1' => 'PowerShell'
        ];
    }
    
    /**
     * Check if enhanced features are available
     */
    public function is_enhanced() {
        return $this->use_enhanced && $this->enhanced_module !== null;
    }
    
    /**
     * Get module version
     */
    public function get_version() {
        return $this->is_enhanced() ? '2.0 Enhanced' : '1.0 Basic';
    }
}
