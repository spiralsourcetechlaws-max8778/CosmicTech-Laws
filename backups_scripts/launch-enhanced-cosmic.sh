#!/bin/bash
echo "🚀 Launching Enhanced COSMIC OSINT LAB"
echo "======================================"

cd /home/kali/COSMIC-OSINT-LAB@888/lab808

# Check system requirements
echo "Checking system requirements..."
if ! command -v php &> /dev/null; then
    echo "❌ PHP not found. Installing..."
    pkg install php -y
fi

# Start services
echo "Starting services..."

# Start main API server
echo "📡 Starting API server on port 8080..."
php -S 0.0.0.0:8080 -t public > data/logs/api.log 2>&1 &
API_PID=$!

# Start background services
echo "🔧 Starting background services..."
php system/utilities/BackgroundService.php > data/logs/background.log 2>&1 &
BG_PID=$!

# Display dashboard
IP_ADDR=$(ifconfig | grep -oP 'inet \K[\d.]+' | grep -v '127.0.0.1' | head -1)
echo ""
echo "✅ COSMIC OSINT LAB v2.0 is running!"
echo "======================================"
echo ""
echo "🌐 Access Points:"
echo "   Dashboard: http://${IP_ADDR:-127.0.0.1}:8080/cosmic-unified-dashboard.php"
echo "   Trojan Generator: http://${IP_ADDR:-127.0.0.1}:8080/trojan-dashboard.php"
echo "   Red Team: http://${IP_ADDR:-127.0.0.1}:8080/redteam-dashboard.php"
echo "   API: http://${IP_ADDR:-127.0.0.1}:8080/cosmic-api.php"
echo ""
echo "📊 System Status:"
echo "   API Server: ✓ Running (PID: $API_PID)"
echo "   Background: ✓ Running (PID: $BG_PID)"
echo "   Payloads: $(find data/payloads -type f 2>/dev/null | wc -l)"
echo "   Logs: data/logs/"
echo ""
echo "🔒 Security Note:"
echo "   This lab is for authorized testing only"
echo "   Ensure proper isolation and permissions"
echo ""
echo "🛑 To stop: Press Ctrl+C"

# Trap shutdown
trap 'echo "Shutting down..."; kill $API_PID $BG_PID 2>/dev/null; exit 0' INT TERM

# Keep running
wait $API_PID
