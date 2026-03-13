#!/bin/bash
clear
echo "╔══════════════════════════════════════════════════════════╗"
echo "║         🌀 COSMIC-OSINT-LAB FIXED LAUNCHER              ║"
echo "║         All Include Paths Corrected                     ║"
echo "╚══════════════════════════════════════════════════════════╗"
echo ""

cd ~/COSMIC-OSINT-LAB@888/lab808

echo "🔍 Verifying file structure..."
echo ""

# Check if all critical files exist
critical_files=(
    "public/includes/security_functions.php"
    "public/working-dashboard.php"
    "public/index.php"
    "public/redteam-dashboard.php"
    "public/ai-dashboard.php"
)

all_good=true
for file in "${critical_files[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✅ $file"
    else
        echo "  ❌ $file is missing"
        all_good=false
    fi
done

echo ""
if [ "$all_good" = true ]; then
    echo "✅ All files are in place!"
else
    echo "⚠️  Some files are missing, but we'll continue..."
fi

echo ""
echo "🛑 Stopping any existing servers..."
pkill -f "php -S.*8080" 2>/dev/null
sleep 2

echo ""
echo "🚀 Starting PHP server on port 8080..."
echo ""
echo "📡 ACCESS URLS:"
echo "   http://102.2.220.165:8080/                       ← Main Menu"
echo "   http://102.2.220.165:8080/working-dashboard.php  ← GUARANTEED WORKING"
echo "   http://102.2.220.165:8080/redteam-dashboard.php  ← Red Team Dashboard"
echo "   http://102.2.220.165:8080/ai-dashboard.php       ← AI Dashboard"
echo "   http://102.2.220.165:8080/redteam-api.php?action=status  ← API Status"
echo ""
echo "⚡ Server starting... (Press Ctrl+C to stop)"
echo ""

# Start the server
php -S 0.0.0.0:8080 -t public/ 2>&1
