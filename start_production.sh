#!/bin/bash
# COSMIC OSINT LAB – Production Startup

cd "$(dirname "$0")"

# Kill existing processes
pkill -f "php.*8008" 2>/dev/null

# Start PHP server in background
nohup php -S 0.0.0.0:8008 -t public > logs/server.log 2>&1 &

echo "🌀 COSMIC OSINT LAB started"
echo "🌐 Access at: http://localhost:8008"
echo "📝 Logs: logs/server.log"
