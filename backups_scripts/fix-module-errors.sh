#!/bin/bash
echo "🔧 Fixing Trojan Module Errors"
echo "=============================="

cd /home/kali/COSMIC-OSINT-LAB@888/lab808

echo "1. Checking current module status..."
if [ -f "system/modules/TrojanModule.php" ]; then
    echo "✅ TrojanModule.php exists"
    php -l system/modules/TrojanModule.php
else
    echo "❌ TrojanModule.php not found"
fi

echo ""
echo "2. Creating data directories..."
mkdir -p data/payloads data/logs data/sessions

echo ""
echo "3. Testing dashboard..."
timeout 5 php -S 127.0.0.1:9999 -t public > /tmp/test.log 2>&1 &
SERVER_PID=$!
sleep 2

echo "Testing endpoints:"
curl -s http://127.0.0.1:9999/ | grep -o "<title>[^<]*</title>" && echo "✅ index.html"
curl -s http://127.0.0.1:9999/index.php | grep -o "<title>[^<]*</title>" && echo "✅ index.php (Unified)"
curl -s http://127.0.0.1:9999/cosmic-unified-dashboard.php | grep -o "<title>[^<]*</title>" && echo "✅ cosmic-unified-dashboard.php"

kill $SERVER_PID 2>/dev/null

echo ""
echo "4. Checking error logs..."
if [ -f "/tmp/test.log" ]; then
    grep -i "error\|warning\|undefined" /tmp/test.log | head -5
fi

echo ""
echo "5. Module verification..."
php -r '
// Test module loading
$module_path = "system/modules/TrojanModule.php";
if (file_exists($module_path)) {
    require_once $module_path;
    
    if (class_exists("EnhancedTrojanModule")) {
        try {
            $module = new EnhancedTrojanModule();
            echo "✅ EnhancedTrojanModule loaded successfully\n";
            
            // Test methods
            $methods = ["get_payload_types", "get_encryption_methods", "generate_payload"];
            foreach ($methods as $method) {
                if (method_exists($module, $method)) {
                    echo "   ✅ Method $method exists\n";
                } else {
                    echo "   ❌ Method $method missing\n";
                }
            }
        } catch (Exception $e) {
            echo "❌ Error creating instance: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ EnhancedTrojanModule class not found\n";
    }
} else {
    echo "❌ Module file not found\n";
}
'

echo ""
echo "✅ Fix script complete!"
echo ""
echo "To start the system:"
echo "  php -S 0.0.0.0:8080 -t public"
echo ""
echo "Access at: http://localhost:8080/cosmic-unified-dashboard.php"
