#!/bin/bash
clear
echo "╔══════════════════════════════════════════════════════╗"
echo "║      🌀 COSMIC-OSINT-LAB LAUNCHER v2.0             ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""
echo "📂 Project: /home/kali/COSMIC-OSINT-LAB@888/lab808"
echo "🌐 IP: 102.2.220.165"
echo "🚪 Port: 8080"
echo "🔗 Creating symbolic links..."

# Create symlinks if they don't exist
cd /home/kali/COSMIC-OSINT-LAB@888/lab808

# Remove broken symlinks
find . -maxdepth 1 -type l -exec test ! -e {} \; -delete

# Create new symlinks
for file in public/*.php; do
    if [ -f "$file" ]; then
        base=$(basename "$file")
        ln -sf "$file" "$base" 2>/dev/null
        echo "  ✓ Linked: $base"
    fi
done

# Link directories
ln -sf public/includes includes 2>/dev/null
ln -sf public/js js 2>/dev/null

echo ""
echo "🛑 Stopping existing servers..."
pkill -f "http.server.*8080" 2>/dev/null
pkill -f "python.*8080" 2>/dev/null
sleep 1

echo ""
echo "🚀 Starting HTTP server..."
echo ""
echo "📡 Access URLs:"
echo "   Dashboard: http://102.2.220.165:8080/index.html"
echo "   AI Dashboard: http://102.2.220.165:8080/ai-dashboard.php"
echo "   Red Team: http://102.2.220.165:8080/redteam-dashboard.php"
echo "   Lab: http://102.2.220.165:8080/lab.php"
echo "   API: http://102.2.220.165:8080/redteam-api.php"
echo ""
echo "📋 Available files:"
ls *.php 2>/dev/null | xargs -I {} echo "  - {}" || echo "  (No PHP files found)"

echo ""
echo "🔧 Server starting... (Press Ctrl+C to stop)"
echo ""

# Start server
python3 -m http.server 8080 --bind 0.0.0.0
