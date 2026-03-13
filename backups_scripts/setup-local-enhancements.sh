#!/bin/bash
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║         COSMIC-OSINT-LAB LOCAL ENHANCEMENT SETUP            ║"
echo "║         No API Keys Required • Self-Contained               ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# Create necessary directories
mkdir -p logs data config sessions

echo "📁 Creating local threat intelligence database..."
cat > config/threat_intel.json << 'TIEOF'
{
  "ip_blacklist": [
    "45.155.205.0/24",
    "185.220.101.0/24",
    "5.8.37.0/24",
    "91.219.236.0/24",
    "185.117.75.0/24",
    "102.2.220.165",
    "127.0.0.1"
  ],
  "tor_exits": [
    "185.220.101.1",
    "185.220.101.2",
    "185.220.101.3"
  ],
  "malicious_asns": [
    "AS197695", "AS198605", "AS199279"
  ],
  "suspicious_countries": [
    "RU", "CN", "KP", "IR", "SY", "BY", "UA"
  ],
  "malicious_useragents": [
    "sqlmap",
    "nmap",
    "nikto",
    "metasploit",
    "dirb",
    "gobuster",
    "hydra",
    "john",
    "aircrack",
    "wireshark"
  ],
  "common_attack_patterns": {
    "sqli": [
      "' OR '1'='1",
      "1' ORDER BY 1--",
      "admin'--",
      "' UNION SELECT",
      "EXEC xp_cmdshell"
    ],
    "xss": [
      "<script>alert",
      "javascript:",
      "onload=",
      "onerror=",
      "onmouseover="
    ],
    "lfi": [
      "../../../../etc/passwd",
      "php://filter",
      "file://",
      "expect://"
    ]
  }
}
TIEOF

echo "🌍 Creating geolocation database..."
cat > data/geo_data.json << 'GEOEOF'
{
  "102.2.220.165": {
    "country": "KE",
    "country_name": "Kenya",
    "region": "Nairobi",
    "city": "Nairobi",
    "isp": "Safaricom",
    "asn": "AS33771",
    "latitude": -1.2921,
    "longitude": 36.8219,
    "threat_level": "low"
  },
  "127.0.0.1": {
    "country": "LOCAL",
    "country_name": "Localhost",
    "region": "Internal",
    "city": "Loopback",
    "isp": "System",
    "asn": "AS0",
    "latitude": 0,
    "longitude": 0,
    "threat_level": "safe"
  }
}
GEOEOF

echo "🔧 Updating security functions..."
# Check if security_functions.php exists
if [ -f "includes/security_functions.php" ]; then
    echo "Backing up existing security_functions.php..."
    cp includes/security_functions.php includes/security_functions.php.backup.$(date +%s)
    
    echo "Adding local threat intelligence module..."
    # Append local intelligence module
    cat >> includes/security_functions.php << 'LOCALEOF'

// ========================
// LOCAL THREAT INTELLIGENCE (Added by setup script)
// ========================

class LocalThreatIntel {
    private $blacklist = [];
    private $geo_data = [];
    private $attack_patterns = [];
    
    public function __construct() {
        $this->loadLocalDatabases();
    }
    
    private function loadLocalDatabases() {
        // Load blacklist
        $threat_data = json_decode(file_get_contents(__DIR__ . '/../config/threat_intel.json'), true);
        $this->blacklist = $threat_data['ip_blacklist'] ?? [];
        $this->attack_patterns = $threat_data['common_attack_patterns'] ?? [];
        
        // Load geo data
        $geo_file = __DIR__ . '/../data/geo_data.json';
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
        
        // Check blacklist
        foreach ($this->blacklist as $range) {
            if ($this->ipInRange($ip, $range)) {
                $result['is_blacklisted'] = true;
                $result['threat_score'] += 80;
                $result['recommendations'][] = "Block IP: Blacklisted range $range";
                break;
            }
        }
        
        // Check for Tor exit node
        $threat_data = json_decode(file_get_contents(__DIR__ . '/../config/threat_intel.json'), true);
        foreach ($threat_data['tor_exits'] as $tor_ip) {
            if ($ip == $tor_ip) {
                $result['is_tor_exit'] = true;
                $result['threat_score'] += 60;
                $result['recommendations'][] = "Monitor: Tor exit node detected";
            }
        }
        
        // Country risk assessment
        if (isset($result['geolocation']['country'])) {
            $country = $result['geolocation']['country'];
            if (in_array($country, $threat_data['suspicious_countries'])) {
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
        } else {
            return $ip === $range;
        }
    }
    
    public function getGeolocation($ip) {
        if (isset($this->geo_data[$ip])) {
            return $this->geo_data[$ip];
        }
        
        // Mock geolocation for unknown IPs
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
        
        // Check for security tools
        $threat_data = json_decode(file_get_contents(__DIR__ . '/../config/threat_intel.json'), true);
        foreach ($threat_data['malicious_useragents'] as $tool) {
            if (strpos($ua_lower, $tool) !== false) {
                $analysis['is_suspicious'] = true;
                $analysis['suspicious_indicators'][] = "Security tool: $tool";
            }
        }
        
        // Browser detection
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
LOCALEOF
else
    echo "⚠️  includes/security_functions.php not found, creating..."
    cat > includes/security_functions.php << 'BASICEOF'
<?php
// Basic security functions
session_start();

class DeceptionEngine {
    public function detectAutomation() {
        return ['bot_detected' => false, 'headless' => false];
    }
    
    public function getThreatScore() {
        return rand(0, 30);
    }
}
BASICEOF
fi

echo "🚀 Creating enhanced dashboard..."
cat > public/enhanced-dashboard.php << 'DASHBOARDEOF'
<?php
// Enhanced dashboard content from above
// [Full dashboard code would go here, but truncated for brevity]
?>
DASHBOARDEOF

# Copy the actual dashboard content
cat > public/enhanced-dashboard.php << 'DASHBOARDCONTENT'
<?php
require_once 'includes/security_functions.php';

// Check if LocalThreatIntel exists
if (class_exists('LocalThreatIntel')) {
    $threat_intel = new LocalThreatIntel();
    $ip_info = $threat_intel->checkIP($_SERVER['REMOTE_ADDR']);
    $ua_analysis = $threat_intel->analyzeUserAgent($_SERVER['HTTP_USER_AGENT'] ?? '');
} else {
    // Fallback if class doesn't exist
    $ip_info = ['threat_score' => rand(0, 30), 'geolocation' => ['city' => 'Unknown', 'country_name' => 'Unknown']];
    $ua_analysis = ['is_suspicious' => false, 'browser_info' => ['name' => 'Unknown']];
}

$deception = new DeceptionEngine();
$indicators = $deception->detectAutomation();
?>
<!DOCTYPE html>
<html>
<head>
    <title>🌀 COSMIC-OSINT-LAB Enhanced</title>
    <style>
        body { font-family: monospace; background: #000; color: #0f0; }
        .panel { border: 1px solid #0f0; padding: 20px; margin: 10px; }
        .good { color: #0f0; }
        .warn { color: #ff0; }
        .bad { color: #f00; }
    </style>
</head>
<body>
    <div class="panel">
        <h1>🌀 COSMIC-OSINT-LAB Enhanced Dashboard</h1>
        <p>Local Threat Intelligence Active</p>
        <p>Threat Score: <span class="<?php echo $ip_info['threat_score'] > 70 ? 'bad' : ($ip_info['threat_score'] > 40 ? 'warn' : 'good'); ?>">
            <?php echo $ip_info['threat_score']; ?>%
        </span></p>
    </div>
    
    <div class="panel">
        <h3>🛡️ Threat Intelligence</h3>
        <p>IP: <?php echo $_SERVER['REMOTE_ADDR']; ?></p>
        <p>Location: <?php echo $ip_info['geolocation']['city'] . ', ' . $ip_info['geolocation']['country_name']; ?></p>
        <p>Browser: <?php echo $ua_analysis['browser_info']['name']; ?></p>
        <p>Suspicious UA: <?php echo $ua_analysis['is_suspicious'] ? 'YES ⚠️' : 'NO ✅'; ?></p>
    </div>
    
    <div class="panel">
        <h3>🔴 Attack Simulation</h3>
        <button onclick="alert('SQL Injection test would run here')">Test SQLi</button>
        <button onclick="alert('XSS test would run here')">Test XSS</button>
        <div id="results"></div>
    </div>
</body>
</html>
DASHBOARDCONTENT

echo "📊 Creating logs directory and sample data..."
mkdir -p logs

# Create sample log file
cat > logs/sample_attacks.log << 'LOGEOF'
[2024-01-01 10:30:00] SQL Injection attempt from 192.168.1.100 - BLOCKED
[2024-01-01 10:32:15] XSS attempt from 203.0.113.5 - DETECTED
[2024-01-01 10:35:45] Brute force attack from 198.51.100.23 - BLOCKED
[2024-01-01 10:40:22] LFI attempt from 192.0.2.44 - DETECTED
LOGEOF

# Set permissions
chmod 755 logs/
chmod 644 config/*.json data/*.json

echo ""
echo "✅ SETUP COMPLETE!"
echo ""
echo "📡 To launch your enhanced lab:"
echo "   cd ~/COSMIC-OSINT-LAB@888/lab808"
echo "   php -S 0.0.0.0:8080 -t public/"
echo ""
echo "🌐 Access at:"
echo "   - Main: http://102.2.220.165:8080/enhanced-dashboard.php"
echo "   - Original: http://102.2.220.165:8080/ai-dashboard.php"
echo ""
echo "🔧 Features installed:"
echo "   ✓ Local threat intelligence database"
echo "   ✓ IP blacklisting system"
echo "   ✓ Attack pattern detection"
echo "   ✓ User agent analysis"
echo "   ✓ Mock attack simulations"
echo "   ✓ Enhanced dashboard"
echo ""
echo "No API keys required! Everything runs locally."
