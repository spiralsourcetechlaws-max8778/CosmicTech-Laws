#!/bin/bash
echo "🚀 COSMIC-OSINT-LAB ENHANCEMENT INSTALLATION"
echo "=========================================="

# Create enhanced directory structure
mkdir -p {logs,data,config,sessions,cache,reports}

# Install PHP if not present
if ! command -v php &> /dev/null; then
    echo "Installing PHP..."
    sudo apt update && apt install -y php php-curl php-gd php-mysql php-cli
fi

# Install required PHP extensions
echo "Installing PHP extensions..."
sudo apt install -y php-curl php-gd php-mbstring php-xml php-zip

# Create enhanced files
echo "Creating enhanced modules..."

# Security functions
cat > includes/security_functions.php << 'SECURITY'
<?php
// [Previous security functions code here]
?>
SECURITY

# Create API endpoints
mkdir -p api
cat > api/threat-intel.php << 'API'
<?php
header('Content-Type: application/json');
require_once '../includes/security_functions.php';

$action = $_GET['action'] ?? '';

switch($action) {
    case 'check-ip':
        $ip = $_GET['ip'] ?? $_SERVER['REMOTE_ADDR'];
        $ti = new ThreatIntelligence();
        echo json_encode($ti->checkIPReputation($ip));
        break;
        
    case 'geolocation':
        $ip = $_GET['ip'] ?? $_SERVER['REMOTE_ADDR'];
        $ti = new ThreatIntelligence();
        echo json_encode($ti->getGeolocation($ip));
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
API

# Setup cron jobs for automated tasks
echo "Setting up automated tasks..."
(crontab -l 2>/dev/null; echo "0 * * * * php /home/kali/COSMIC-OSINT-LAB@888/lab808/cron/update_threat_intel.php") | crontab -
(crontab -l 2>/dev/null; echo "*/5 * * * * php /home/kali/COSMIC-OSINT-LAB@888/lab808/cron/cleanup_sessions.php") | crontab -

# Set permissions
chmod 755 logs/ data/ sessions/
chmod 644 config/.env

echo "✅ Enhancement installation complete!"
echo ""
echo "Next steps:"
echo "1. Edit config/.env with your API keys"
echo "2. Run: php -S 0.0.0.0:8080 -t public/"
echo "3. Access at: http://102.2.220.165:8080"
