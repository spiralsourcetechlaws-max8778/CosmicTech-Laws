<?php
/**
 * COSMIC THREAT INTELLIGENCE MODULE
 * Loaded only when needed – hidden from main dashboard.
 */
class LocalThreatIntel {
    private $blacklist = [];
    private $geo_data = [];
    private $attack_patterns = [];
    
    public function __construct() {
        $this->loadLocalDatabases();
    }
    
    private function loadLocalDatabases() {
        $config_path = dirname(__DIR__, 3) . '/config/threat_intel.json';
        if (file_exists($config_path)) {
            $threat_data = json_decode(file_get_contents($config_path), true);
            $this->blacklist = $threat_data['ip_blacklist'] ?? [];
            $this->attack_patterns = $threat_data['common_attack_patterns'] ?? [];
        }
        $geo_file = dirname(__DIR__, 3) . '/data/geo_data.json';
        if (file_exists($geo_file)) {
            $this->geo_data = json_decode(file_get_contents($geo_file), true);
        }
    }
    
    public function checkIP($ip) {
        $result = [
            'ip' => $ip,
            'is_blacklisted' => false,
            'is_tor_exit' => false,
            'threat_score' => 0,
            'geolocation' => $this->getGeolocation($ip),
            'recommendations' => []
        ];
        foreach ($this->blacklist as $range) {
            if ($this->ipInRange($ip, $range)) {
                $result['is_blacklisted'] = true;
                $result['threat_score'] += 80;
                $result['recommendations'][] = "Block IP: Blacklisted range $range";
                break;
            }
        }
        $config_path = dirname(__DIR__, 3) . '/config/threat_intel.json';
        if (file_exists($config_path)) {
            $threat_data = json_decode(file_get_contents($config_path), true);
            foreach ($threat_data['tor_exits'] ?? [] as $tor_ip) {
                if ($ip == $tor_ip) {
                    $result['is_tor_exit'] = true;
                    $result['threat_score'] += 60;
                    $result['recommendations'][] = "Monitor: Tor exit node detected";
                }
            }
        }
        if (isset($result['geolocation']['country'])) {
            $country = $result['geolocation']['country'];
            $suspicious = $threat_data['suspicious_countries'] ?? [];
            if (in_array($country, $suspicious)) {
                $result['threat_score'] += 40;
                $result['recommendations'][] = "High-risk country: $country";
            }
        }
        return $result;
    }
    
    private function ipInRange($ip, $range) {
        if (strpos($range, '/') !== false) {
            list($subnet, $bits) = explode('/', $range);
            $ip_long = ip2long($ip);
            $subnet_long = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            return ($ip_long & $mask) == ($subnet_long & $mask);
        }
        return $ip === $range;
    }
    
    public function getGeolocation($ip) {
        if (isset($this->geo_data[$ip])) {
            return $this->geo_data[$ip];
        }
        return [
            'country' => 'XX',
            'country_name' => 'Unknown',
            'region' => 'Unknown',
            'city' => 'Unknown',
            'isp' => 'Unknown ISP',
            'asn' => 'AS' . rand(1000, 99999),
            'latitude' => 0,
            'longitude' => 0,
            'threat_level' => 'unknown'
        ];
    }
    
    public function detectAttackPattern($input) {
        $detections = [];
        foreach ($this->attack_patterns as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (stripos($input, $pattern) !== false) {
                    $detections[] = [
                        'type' => $type,
                        'pattern' => $pattern,
                        'input' => substr($input, 0, 50)
                    ];
                }
            }
        }
        return $detections;
    }
    
    public function analyzeUserAgent($user_agent) {
        $analysis = [
            'is_suspicious' => false,
            'suspicious_indicators' => [],
            'browser_info' => []
        ];
        $ua_lower = strtolower($user_agent);
        $config_path = dirname(__DIR__, 3) . '/config/threat_intel.json';
        if (file_exists($config_path)) {
            $threat_data = json_decode(file_get_contents($config_path), true);
            foreach ($threat_data['malicious_useragents'] ?? [] as $tool) {
                if (strpos($ua_lower, $tool) !== false) {
                    $analysis['is_suspicious'] = true;
                    $analysis['suspicious_indicators'][] = "Security tool: $tool";
                }
            }
        }
        if (strpos($ua_lower, 'chrome') !== false) {
            $analysis['browser_info']['name'] = 'Chrome';
        } elseif (strpos($ua_lower, 'firefox') !== false) {
            $analysis['browser_info']['name'] = 'Firefox';
        } elseif (strpos($ua_lower, 'safari') !== false) {
            $analysis['browser_info']['name'] = 'Safari';
        } else {
            $analysis['browser_info']['name'] = 'Unknown';
            $analysis['suspicious_indicators'][] = "Uncommon browser";
        }
        return $analysis;
    }
}

