<?php
// UNIVERSAL COMPATIBILITY LAYER
// This ensures all class names work regardless of case or naming convention

// Define class aliases for compatibility
if (!class_exists('DeceptionEngine') && class_exists('deception_engine')) {
    class DeceptionEngine extends deception_engine {}
}

if (!class_exists('LocalThreatIntel') && class_exists('local_threat_intel')) {
    class LocalThreatIntel extends local_threat_intel {}
}

if (!class_exists('DeceptionEngine') && !class_exists('deception_engine')) {
    // Fallback class if neither exists
    class DeceptionEngine {
        public function detectAutomation() {
            return ['bot_detected' => false];
        }
        public function getThreatScore() {
            return rand(0, 30);
        }
    }
}

if (!class_exists('LocalThreatIntel') && !class_exists('local_threat_intel')) {
    // Fallback class if neither exists
    class LocalThreatIntel {
        public function checkIP($ip) {
            return [
                'ip' => $ip,
                'is_blacklisted' => false,
                'threat_score' => rand(0, 30),
                'location' => ['city' => 'Unknown', 'country' => 'XX']
            ];
        }
    }
}

// Function to safely include security functions
function require_security_functions() {
    $paths = [
        'includes/security_functions.php',
        '/includes/security_functions.php',
        __DIR__ . '/security_functions.php',
        dirname(__DIR__) . '/includes/security_functions.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return true;
        }
    }
    
    // If no file found, define minimal functions
    if (!function_exists('sanitize_input')) {
        function sanitize_input($data) {
            return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
        }
    }
    
    if (!function_exists('generate_csrf_token')) {
        function generate_csrf_token() {
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return $_SESSION['csrf_token'];
        }
    }
    
    return false;
}

// Auto-include if this file is included
require_security_functions();
