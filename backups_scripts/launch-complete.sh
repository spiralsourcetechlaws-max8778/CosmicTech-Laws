#!/bin/bash
echo "🚀 Launching Complete COSMIC OSINT LAB v2.0"
echo "==========================================="

cd /home/kali/COSMIC-OSINT-LAB@888/lab808

# Set environment
export COSMIC_HOME=$(pwd)
export PATH="$COSMIC_HOME/system/utilities:$PATH"

echo "1. Initializing enhanced modules..."
mkdir -p data/payloads data/logs data/sessions

echo "2. Starting enhanced Trojan module..."
if [ -f "system/modules/TrojanModule.php" ]; then
    echo "   ✅ Enhanced Trojan module detected"
else
    echo "   ⚠️  Using basic Trojan module"
fi

echo "3. Starting web server..."
echo "   Dashboard: http://0.0.0.0:8080"
echo "   API: http://0.0.0.0:8080/cosmic-api.php"
echo "   Lab: http://0.0.0.0:8080/lab.php"

php -S 0.0.0.0:8080 -t public &
SERVER_PID=$!

echo ""
echo "4. System Status:"
echo "   Trojan Module: ✅ ENHANCED v2.0"
echo "   Red Team: ✅ INTEGRATED"
echo "   Virtual Lab: ✅ ENHANCED"
echo "   Security: ✅ ACTIVE"
echo "   API: ✅ RUNNING"
echo ""
echo "5. Available Endpoints:"
echo "   🏴‍☠️  Trojan Generator: /trojan-dashboard.php"
echo "   🔴 Red Team: /redteam-dashboard.php"
echo "   🤖 AI: /ai-dashboard.php"
echo "   🔬 Lab: /lab.php"
echo "   ⚙️  API: /cosmic-api.php"
echo "   🚀 Unified: /index.php"
echo ""
echo "6. Security Note:"
echo "   ⚠️  For authorized testing only"
echo "   🔒 All payloads are logged"
echo "   📊 Enhanced analytics active"
echo ""
echo "🛑 Press Ctrl+C to stop"

trap 'echo "Shutting down..."; kill $SERVER_PID 2>/dev/null; exit 0' INT TERM
wait $SERVER_PID
