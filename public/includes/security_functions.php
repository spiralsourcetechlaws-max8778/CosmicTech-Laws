<?php
/**
 * COSMIC OSINT LAB – UNIVERSAL SECURITY FUNCTIONS (Industrial Grade)
 * Single source of truth – no duplicates.
 */

// ======================== SESSION & CSRF ========================
if (!isset($_SESSION)) session_start();

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ======================== LOGGING ========================
function log_security_event($event_type, $details) {
    $log_dir = dirname(__DIR__) . '/logs';
    if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);
    $log_file = $log_dir . '/security.log';
    $entry = sprintf("[%s] %s - IP: %s - Details: %s\n",
        date('Y-m-d H:i:s'), $event_type,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        json_encode($details)
    );
    file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
}

// ======================== URL MASKER HELPERS ========================
function get_url_masker_token() {
    static $token = null;
    if ($token === null) {
        $configFile = dirname(__DIR__) . '/config/urlmasker.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $token = $config['admin_token'] ?? 'cosmic-secret';
        } else {
            $token = 'cosmic-secret';
        }
    }
    return $token;
}

function get_url_masker_url() {
    return '/url-masker.php?token=' . urlencode(get_url_masker_token());
}

// ======================== BASE URL HELPER ========================
function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}

// ======================== DECEPTION ENGINE ========================
class DeceptionEngine {
    public function detectAutomation() {
        $indicators = [];
        $bot_uas = ['bot','crawl','spider','scraper','python','curl','wget'];
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        foreach ($bot_uas as $bot) {
            if (strpos($ua, $bot) !== false) {
                $indicators['bot_detected'] = true;
                break;
            }
        }
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) $indicators['no_language'] = true;
        return $indicators;
    }
    public function getThreatScore() { return rand(0,30); }
    public function analyzeThreat() {
        $auto = $this->detectAutomation();
        $score = $this->getThreatScore();
        if (!empty($auto)) $score += 20;
        return ['threat_score'=>min($score,100), 'automation_indicators'=>$auto,
                'risk_level'=> $score>50?'HIGH':($score>20?'MEDIUM':'LOW'), 'timestamp'=>time()];
    }
}

// ======================== MOCK API (with caching) ========================
if (!class_exists('MockAPI')) {
    class MockAPI {
        private static $cache = [];
        public static function getIPReputation($ip) {
            $key = 'ip_rep_'.md5($ip);
            if (isset(self::$cache[$key])) return self::$cache[$key];
            $result = [
                'data' => [
                    'ipAddress' => $ip,
                    'abuseConfidenceScore' => rand(0,30),
                    'countryCode' => 'XX',
                    'isp' => 'Unknown',
                    'totalReports' => rand(0,100)
                ]
            ];
            self::$cache[$key] = $result;
            return $result;
        }
    }
}
