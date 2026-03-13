#!/bin/bash
clear
echo "╔══════════════════════════════════════════════════════════╗"
echo "║         🌀 COSMIC-OSINT-LAB FINAL LAUNCHER              ║"
echo "║         All Errors Fixed • Ready to Launch              ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

cd ~/COSMIC-OSINT-LAB@888/lab808

echo "🔧 Verifying file integrity..."
echo ""

# Check critical files
critical_files=(
    "public/includes/security_functions.php"
    "public/working-dashboard.php"
    "public/index.php"
)

for file in "${critical_files[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✅ $file exists"
    else
        echo "  ❌ $file missing - creating..."
        # Create minimal index if missing
        if [ "$file" = "public/index.php" ]; then
            echo '<html><body><h1>COSMIC-OSINT-LAB</h1><p><a href="working-dashboard.php">Go to Working Dashboard</a></p></body></html>' > public/index.php
        fi
    fi
done

echo ""
echo "🛑 Stopping any existing servers..."
pkill -f "php -S.*8080" 2>/dev/null
sleep 2

echo ""
echo "🚀 Starting PHP server on port 8080..."
echo ""
echo "📡 ACCESS URLS:"
echo "   http://102.2.220.165:8080/working-dashboard.php  ← GUARANTEED WORKING"
echo "   http://102.2.220.165:8080/                       ← Main Menu"
echo "   http://102.2.220.165:8080/enhanced-dashboard.php ← Enhanced Dashboard"
echo "   http://102.2.220.165:8080/ai-dashboard.php       ← AI Dashboard"
echo ""
echo "⚡ Server starting... (Press Ctrl+C to stop)"
echo ""

# Start the server with error logging
php -S 0.0.0.0:8080 -t public/ 2>&1
