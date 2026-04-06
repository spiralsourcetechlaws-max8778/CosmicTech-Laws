#!/bin/bash
# COSMIC OSINT LAB – Industrial Grade Health Check

echo "🌀 COSMIC OSINT LAB Health Check"
echo "================================"
echo ""

# Check PHP version
echo -n "PHP Version: "
php -v | head -1

# Check required extensions
echo -n "SQLite3: "
php -m | grep -q sqlite3 && echo "✅" || echo "❌"
echo -n "cURL: "
php -m | grep -q curl && echo "✅" || echo "❌"
echo -n "OpenSSL: "
php -m | grep -q openssl && echo "✅" || echo "❌"

# Check directories
echo ""
echo "Directory Permissions:"
for dir in data/payloads data/logs data/keylogs data/c2 data/phishing data/urlmasker; do
    if [ -d "$dir" ]; then
        echo "  ✅ $dir"
    else
        echo "  ❌ $dir (missing)"
        mkdir -p "$dir"
        chmod 755 "$dir"
    fi
done

# Check C2 database
echo ""
echo "C2 Database:"
if [ -f data/c2/c2.db ]; then
    echo "  ✅ Database exists"
    USER_COUNT=$(sqlite3 data/c2/c2.db "SELECT COUNT(*) FROM users;" 2>/dev/null || echo "0")
    echo "  👤 Users: $USER_COUNT"
else
    echo "  ❌ Database missing – creating..."
    php -r "require_once 'system/modules/C2Engine.php'; new C2Engine();"
fi

# Check PHP server
echo ""
echo "PHP Server:"
if pgrep -f "php.*8008" > /dev/null; then
    echo "  ✅ Running on port 8008"
else
    echo "  ⚠️ Not running – start with: php -S 0.0.0.0:8008 -t public &"
fi

# Check modules
echo ""
echo "Core Modules:"
for mod in AdvancedPayloadModule C2Engine CosmicUrlMasker PhishingCampaign RedTeamOperations; do
    if [ -f "system/modules/$mod.php" ] || [ -f "system/modules/phishing/$mod.php" ]; then
        echo "  ✅ $mod"
    else
        echo "  ❌ $mod missing"
    fi
done

echo ""
echo "================================"
echo "🌐 Access URLs:"
echo "   Unified:    http://localhost:8008/"
echo "   C2:         http://localhost:8008/c2-dashboard.php"
echo "   Red Team:   http://localhost:8008/redteam-dashboard.php"
echo "   Phishing:   http://localhost:8008/phishing-dashboard.php"
echo "   URL Masker: http://localhost:8008/url-masker.php?token=cosmic-secret"
echo "   Threat Intel: http://localhost:8008/threat-intel.php"
echo "================================"
