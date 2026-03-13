#!/bin/bash
clear
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║         🌀 COSMIC-OSINT-LAB PROFESSIONAL EDITION           ║"
echo "║         Kali NetHunter ARM71 • Enhanced Security            ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
echo "📡 Starting Enhanced Security Stack..."
echo ""

# Kill existing servers
pkill -f "php -S" 2>/dev/null
pkill -f "http.server" 2>/dev/null

# Create necessary directories
mkdir -p logs data sessions cache reports

# Start PHP with enhanced configuration
cd /home/kali/COSMIC-OSINT-LAB@888/lab808

# Load environment
if [ -f "config/.env" ]; then
    export $(cat config/.env | xargs)
fi

# Check for enhancements
if [ ! -f "includes/security_functions.php" ]; then
    echo "⚠️  Running setup script first..."
    ./setup-enhancements.sh
fi

echo "🚀 Launching Enhanced Dashboard..."
echo ""
echo "🌐 Access URLs:"
echo "   Main Dashboard:    http://102.2.220.165:8080"
echo "   AI Dashboard:      http://102.2.220.165:8080/ai-dashboard.php"
echo "   Red Team Ops:      http://102.2.220.165:8080/redteam-ops.php"
echo "   MITRE Matrix:      http://102.2.220.165:8080/mitre-dashboard.php"
echo "   Threat Intel API:  http://102.2.220.165:8080/api/threat-intel.php"
echo ""
echo "🔧 Monitoring Console: tail -f logs/threat_detection.log"
echo "📊 Metrics:          tail -f logs/security_alerts.json"
echo ""

# Start server with error logging
php -S 0.0.0.0:8080 -t public/ 2>&1 | tee logs/server.log
