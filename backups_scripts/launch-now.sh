#!/bin/bash
clear
echo "╔══════════════════════════════════════════════════════╗"
echo "║         🌀 COSMIC-OSINT-LAB READY TO LAUNCH         ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""
echo "📡 Starting server on: http://102.2.220.165:8080"
echo ""
echo "🌐 Available Pages:"
echo "   - Main Menu: /"
echo "   - AI Dashboard: /ai-dashboard.php"
echo "   - Red Team: /redteam-dashboard.php"
echo "   - Lab Interface: /lab.php"
echo "   - Enhanced Dashboard: /enhanced-dashboard.php"
echo ""
echo "🚀 Starting PHP server..."
echo ""

cd ~/COSMIC-OSINT-LAB@888/lab808
php -S 0.0.0.0:8080 -t public/
