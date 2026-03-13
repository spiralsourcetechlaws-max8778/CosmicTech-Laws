<?php
/**
 * COSMIC OSINT LAB – UNIVERSAL SECURITY FUNCTIONS (v3.0)
 * Single source of truth – CSRF, rate limiting, input sanitization, logging, deception.
 */

// ========================
// C2 Helper Functions
// ========================
function get_c2_dashboard_url() {
    return '/c2-dashboard.php';
}

function get_c2_beacon_url($uuid) {
    return '/c2/beacon.php?uuid=' . urlencode($uuid);
}

function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}

// ========================
// CSRF Protection
// ========================
function generate_csrf_token() {
    if (!isset($_SESSION)) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    if (!isset($_SESSION)) session_start();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ========================
// Rate Limiting (Simple File-Based)
// ========================
function check_rate_limit($key, $max_requests = 60, $window = 60) {
    $rate_dir = sys_get_temp_dir() . '/cosmic_ratelimit';
    if (!is_dir($rate_dir)) mkdir($rate_dir, 0755, true);
    $file = $rate_dir . '/' . md5($key) . '.limit';
    $now = time();
    if (file_exists($file)) {
        $data = explode(':', file_get_contents($file));
        $timestamp = (int)$data[0];
        $count = (int)$data[1];
        if ($now - $timestamp < $window) {
            if ($count >= $max_requests) {
                return false; // rate limit exceeded
            }
            $count++;
        } else {
            $timestamp = $now;
            $count = 1;
        }
    } else {
        $timestamp = $now;
        $count = 1;
    }
    file_put_contents($file, "$timestamp:$count", LOCK_EX);
    return true;
}

// ========================
// Input Sanitization
// ========================
function sanitize_filename($filename) {
    return preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($filename));
}

function sanitize_input($input, $max_length = 255) {
    if (is_string($input)) {
        return substr(preg_replace('/[^\p{L}\p{N}\s\-_\.]/u', '', $input), 0, $max_length);
    }
    return $input;
}

function sanitize_uuid($uuid) {
    return preg_replace('/[^a-f0-9\-]/i', '', $uuid);
}

// ========================
// Enhanced Logging with Rotation
// ========================
function log_security_event($event_type, $details) {
    $log_entry = sprintf(
        "[%s] %s - IP: %s - User: %s - Details: %s\n",
        date('Y-m-d H:i:s'),
        $event_type,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SESSION['user'] ?? 'anonymous',
        json_encode($details)
    );

    $log_dir = dirname(__DIR__) . '/logs';
    if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);

    $log_file = $log_dir . '/security.log';
    // Simple rotation: if file > 10MB, archive
    if (file_exists($log_file) && filesize($log_file) > 10 * 1024 * 1024) {
        rename($log_file, $log_dir . '/security_' . date('Ymd_His') . '.log');
    }

    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// ========================
// Deception Engine (Enhanced)
// ========================
class DeceptionEngine {
    private $config;

    public function __construct($config = []) {
        $this->config = array_merge([
            'honeypot_tokens' => true,
            'fake_endpoints' => ['/wp-admin', '/admin', '/phpmyadmin'],
            'threat_intel' => null
        ], $config);
    }

    public function detectAutomation() {
        $indicators = [];
        $bot_indicators = ['bot', 'crawl', 'spider', 'scraper', 'python', 'curl', 'wget', 'headless', 'phantom', 'selenium'];
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        foreach ($bot_indicators as $indicator) {
            if (strpos($user_agent, $indicator) !== false) {
                $indicators['bot_detected'] = true;
                $indicators['matched_pattern'] = $indicator;
                break;
            }
        }
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $indicators['no_language'] = true;
        }
        if (!isset($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false) {
            $indicators['unusual_accept'] = true;
        }
        return $indicators;
    }

    public function getThreatScore() {
        return rand(0, 30);
    }

    public function analyzeThreat() {
        $automation = $this->detectAutomation();
        $score = $this->getThreatScore();
        if (!empty($automation)) {
            $score += 20;
        }
        // IP reputation via MockAPI (if available)
        if (class_exists('MockAPI') && isset($_SERVER['REMOTE_ADDR'])) {
            $rep = MockAPI::getIPReputation($_SERVER['REMOTE_ADDR']);
            if ($rep['data']['abuseConfidenceScore'] > 50) {
                $score += 30;
            }
        }
        return [
            'threat_score' => min($score, 100),
            'automation_indicators' => $automation,
            'risk_level' => $score > 50 ? 'HIGH' : ($score > 20 ? 'MEDIUM' : 'LOW'),
            'timestamp' => time()
        ];
    }
}

// ========================
// Mock API (Enhanced with caching)
// ========================
if (!class_exists('MockAPI')) {
    class MockAPI {
        private static $cache = [];

        public static function getIPReputation($ip) {
            $cache_key = 'ip_rep_' . md5($ip);
            if (isset(self::$cache[$cache_key])) {
                return self::$cache[$cache_key];
            }
            if (class_exists('LocalThreatIntel')) {
                $local_ti = new LocalThreatIntel();
                $check = $local_ti->checkIP($ip);
            } else {
                $check = ['threat_score' => rand(0, 30), 'geolocation' => ['country' => 'KE', 'isp' => 'Unknown']];
            }
            $result = [
                'data' => [
                    'ipAddress' => $ip,
                    'isPublic' => !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE),
                    'ipVersion' => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 4 : 6,
                    'isWhitelisted' => false,
                    'abuseConfidenceScore' => $check['threat_score'] ?? 0,
                    'countryCode' => $check['geolocation']['country'] ?? 'XX',
                    'usageType' => 'Data Center/Web Hosting/Transit',
                    'isp' => $check['geolocation']['isp'] ?? 'Unknown',
                    'domain' => 'example.com',
                    'totalReports' => rand(0, 100),
                    'numDistinctUsers' => rand(1, 50),
                    'lastReportedAt' => date('Y-m-d\TH:i:s\Z', strtotime('-'.rand(1,30).' days'))
                ]
            ];
            self::$cache[$cache_key] = $result;
            return $result;
        }

        public static function getVirusTotalReport($ip) {
            $cache_key = 'vt_' . md5($ip);
            if (isset(self::$cache[$cache_key])) {
                return self::$cache[$cache_key];
            }
            $result = [
                'data' => [
                    'attributes' => [
                        'last_analysis_stats' => [
                            'malicious' => rand(0, 5),
                            'suspicious' => rand(0, 3),
                            'undetected' => rand(50, 70),
                            'harmless' => rand(20, 40)
                        ],
                        'country' => 'KE',
                        'as_owner' => 'Safaricom Limited',
                        'reputation' => rand(-100, 100)
                    ]
                ]
            ];
            self::$cache[$cache_key] = $result;
            return $result;
        }
    }
}

/**
 * Get URL Masker dashboard URL
 */
function get_url_masker_url() {
    return '/url-masker.php';
}

// ========================
// URL MASKER ENTERPRISE HELPERS
// ========================

/**
 * Get URL Masker admin token from config.
 */
function get_url_masker_token() {
    static $token = null;
    if ($token === null) {
        $configFile = dirname(__DIR__) . '/config/urlmasker.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $token = $config['admin_token'] ?? 'cosmic-secret';
        } else {
            $token = 'cosmic-secret'; // fallback default
        }
    }
    return $token;
}

/**
 * Get URL Masker dashboard URL with token.
 */
function get_url_masker_url() {
    return '/url-masker.php?token=' . urlencode(get_url_masker_token());
}
