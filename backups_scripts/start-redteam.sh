#!/bin/bash
clear
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║         🚀 RED TEAM SUITE - OPTIMIZED LAUNCHER              ║"
echo "║         Fast Navigation • Pre-configured • Secure           ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

cd ~/COSMIC-OSINT-LAB@888/lab808

echo "🔍 Checking system..."
if [ ! -f "public/includes/TrojanGenerator.php" ]; then
    echo "❌ TrojanGenerator.php not found!"
    exit 1
fi

echo "✅ All modules verified"
echo ""
echo "🛑 Stopping existing servers..."
pkill -f "php -S.*8080" 2>/dev/null
sleep 2

echo ""
echo "🚀 Starting optimized server..."
echo ""
echo "⚡ QUICK ACCESS:"
echo "   Ctrl+T in browser → Trojan Generator"
echo "   Ctrl+S in browser → Search tools"
echo "   Esc in browser → Clear search"
echo ""
echo "📡 PRIMARY URLS:"
echo "   Quick Navigation: http://102.2.220.165:8080/redteam-quicknav.php"
echo "   Trojan Generator: http://102.2.220.165:8080/trojan-dashboard.php"
echo "   Windows Payloads: http://102.2.220.165:8080/trojan-dashboard.php?platform=windows"
echo "   Linux Payloads:   http://102.2.220.165:8080/trojan-dashboard.php?platform=linux"
echo "   Android Payloads: http://102.2.220.165:8080/trojan-dashboard.php?platform=android"
echo ""
echo "⚡ Server starting... (Press Ctrl+C to stop)"
echo ""

# Start with optimized settings
php -S 0.0.0.0:8080 -t public/ 2>&1
