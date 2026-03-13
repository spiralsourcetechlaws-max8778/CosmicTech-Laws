#!/bin/bash
clear
echo "╔══════════════════════════════════════════════════════════╗"
echo "║         🌀 COSMIC-OSINT-LAB LAUNCHER                    ║"
echo "║         Local Intelligence • No External APIs           ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""
echo "📂 Project: $(pwd)"
echo "🌐 IP: 102.2.220.165"
echo "🚪 Port: 8080"
echo ""

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "⚠️  PHP not found! Installing..."
    
    # Try different package managers
    if command -v pkg &> /dev/null; then
        pkg install php -y
    elif command -v apt &> /dev/null; then
        apt update && apt install php php-cli -y
    elif command -v dnf &> /dev/null; then
        dnf install php -y
    else
        echo "❌ Cannot install PHP automatically"
        echo "📦 Please install PHP manually and try again"
        exit 1
    fi
fi

echo "🛑 Stopping existing servers..."
pkill -f "php -S.*8080" 2>/dev/null
pkill -f "python.*8080" 2>/dev/null
sleep 1

echo ""
echo "🔧 Checking for enhancements..."
if [ ! -f "config/threat_intel.json" ]; then
    echo "⚠️  Enhancements not found. Running setup..."
    if [ -f "setup-local-enhancements.sh" ]; then
        ./setup-local-enhancements.sh
    else
        echo "❌ Setup script not found!"
    fi
fi

echo ""
echo "🚀 Starting COSMIC-OSINT-LAB..."
echo ""
echo "📡 Access URLs:"
echo "   Enhanced Dashboard: http://102.2.220.165:8080/enhanced-dashboard.php"
echo "   AI Dashboard:       http://102.2.220.165:8080/ai-dashboard.php"
echo "   Red Team Dashboard: http://102.2.220.165:8080/redteam-dashboard.php"
echo "   Lab Interface:      http://102.2.220.165:8080/lab.php"
echo ""
echo "🔍 Monitor logs in another terminal:"
echo "   tail -f logs/sample_attacks.log"
echo ""
echo "⚡ Starting PHP server... (Press Ctrl+C to stop)"
echo ""

# Start PHP server
php -S 0.0.0.0:8080 -t public/ 2>&1
