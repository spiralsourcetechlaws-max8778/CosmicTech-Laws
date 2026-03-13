#!/bin/bash
clear
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║         🏴‍☠️ TROJAN GENERATOR LAUNCHER                     ║"
echo "║         COSMIC-OSINT-LAB Red Team Module                    ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

cd ~/COSMIC-OSINT-LAB@888/lab808

echo "🔍 Checking dependencies..."
if [ ! -f "public/includes/TrojanGenerator.php" ]; then
    echo "❌ TrojanGenerator.php not found!"
    exit 1
fi

echo "✅ All dependencies satisfied"
echo ""
echo "🛑 Stopping existing servers..."
pkill -f "php -S.*8080" 2>/dev/null
sleep 2

echo ""
echo "🚀 Starting Trojan Generator module..."
echo ""
echo "📡 ACCESS URLS:"
echo "   Trojan Dashboard:  http://102.2.220.165:8080/trojan-dashboard.php"
echo "   Payload Tester:    http://102.2.220.165:8080/payload-tester.php"
echo "   Main Menu:         http://102.2.220.165:8080/"
echo "   Red Team Dashboard: http://102.2.220.165:8080/redteam-dashboard.php"
echo ""
echo "⚡ Starting server... (Press Ctrl+C to stop)"
echo ""

# Create necessary directories
mkdir -p generated_payloads/{windows,linux,mac,android,python}
mkdir -p logs/trojan_generation

# Start the server
php -S 0.0.0.0:8080 -t public/
