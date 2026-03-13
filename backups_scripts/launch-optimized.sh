#!/bin/bash
cd ~/COSMIC-OSINT-LAB@888/lab808

# Clear screen and show banner
clear
cat << "BANNER"
╔══════════════════════════════════════════════════════════════════════╗
║                                                                      ║
║   ░█████╗░░█████╗░░██████╗███╗░░░███╗██╗░█████╗  ██╗░░░░░░█████╗░   ║
║   ██╔══██╗██╔══██╗██╔════╝████╗░████║██║██╔══██╗  ██║░░░░░██╔══██╗   ║
║   ██║░░╚═╝██║░░██║╚█████╗░██╔████╔██║██║███████║  ██║░░░░░███████║   ║
║   ██║░░██╗██║░░██║░╚═══██╗██║╚██╔╝██║██║██╔══██║  ██║░░░░░██╔══██║   ║
║   ╚█████╔╝╚█████╔╝██████╔╝██║░╚═╝░██║██║██║░░██║  ███████╗██║░░██║   ║
║   ░╚════╝░░╚════╝░╚═════╝░╚═╝░░░░░╚═╝╚═╝╚═╝░░╚═╝  ╚══════╝╚═╝░░╚═╝   ║
║                                                                      ║
║                    O S I N T - L A B   v3.0                          ║
║                    Red Team Suite - Optimized                        ║
╚══════════════════════════════════════════════════════════════════════╝
BANNER

echo ""
echo "🔍 System Diagnostics:"
echo "----------------------"

# Check PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php --version | head -1 | cut -d' ' -f2)
    echo "✅ PHP $PHP_VERSION"
else
    echo "❌ PHP not found"
    exit 1
fi

# Check directories
DIRS=("public/includes" "generated_payloads" "logs")
for dir in "${DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "✅ $dir"
    else
        echo "⚠️  Creating $dir..."
        mkdir -p "$dir"
    fi
done

# Check critical files
FILES=("public/includes/TrojanGenerator.php" "public/trojan-dashboard.php" "public/redteam-quicknav.php")
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $(basename "$file")"
    else
        echo "❌ Missing: $file"
        exit 1
    fi
done

echo ""
echo "🚀 Performance Optimization:"
echo "---------------------------"

# Clear cache
echo "🔄 Clearing cache..."
find . -name "*.php" -exec touch {} \; 2>/dev/null

# Optimize session handling
echo "⚡ Optimizing sessions..."
rm -f sessions/* 2>/dev/null

echo ""
echo "🛑 Stopping existing instances..."
pkill -f "php -S.*8080" 2>/dev/null
sleep 2

echo ""
echo "🎯 Launch Configuration:"
echo "-----------------------"
echo "📡 Primary URL:    http://102.2.220.165:8080/"
echo "⚡ Quick Nav:      http://102.2.220.165:8080/redteam-quicknav.php"
echo "🏴‍☠️ Trojan Gen:    http://102.2.220.165:8080/trojan-dashboard.php"
echo ""
echo "🎮 Keyboard Shortcuts:"
echo "  • Ctrl+T → Trojan Generator"
echo "  • Ctrl+S → Search tools"
echo "  • Esc    → Clear search"
echo ""
echo "📁 Payload Directories:"
echo "  • Windows:  generated_payloads/windows/"
echo "  • Linux:    generated_payloads/linux/"
echo "  • Android:  generated_payloads/android/"
echo ""
echo "⏱️  Starting optimized server..."
echo "--------------------------------"

# Start with performance optimizations
export PHP_INI_SCAN_DIR=""
exec php -S 0.0.0.0:8080 -t public/ -d output_buffering=4096 \
  -d realpath_cache_size=4096K \
  -d realpath_cache_ttl=600 \
  -d opcache.enable=1 \
  -d opcache.memory_consumption=128 \
  -d opcache.interned_strings_buffer=8 \
  -d opcache.max_accelerated_files=10000 \
  -d opcache.revalidate_freq=2 \
  -d opcache.fast_shutdown=1 2>&1
