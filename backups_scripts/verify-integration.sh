#!/bin/bash
echo "🌀 COSMIC OSINT LAB - Integration Verification"
echo "=============================================="

cd /home/kali/COSMIC-OSINT-LAB@888/lab808

echo "1. Checking file structure..."
echo "============================"
ls -la public/
echo ""
ls -la system/
echo ""
ls -la includes/

echo ""
echo "2. Checking PHP syntax..."
echo "========================="
find public -name "*.php" -exec php -l {} \; 2>/dev/null | grep -v "No syntax errors"

echo ""
echo "3. Checking module availability..."
echo "=================================="
php -r '
$files = [
    "system/modules/TrojanModule.php",
    "public/includes/TrojanGenerator.php",
    "public/includes/TrojanBridge.php",
    "public/includes/security_functions.php"
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ " . $file . "\n";
    } else {
        echo "❌ " . $file . " (MISSING)\n";
    }
}
'

echo ""
echo "4. Testing dashboard accessibility..."
echo "===================================="
timeout 5 php -S 127.0.0.1:9999 -t public > /tmp/test_server.log 2>&1 &
SERVER_PID=$!
sleep 2

echo "Testing endpoints:"
curl -s http://127.0.0.1:9999/ | grep -o "<title>[^<]*</title>" && echo "✅ index.html"
curl -s http://127.0.0.1:9999/index.php | grep -o "<title>[^<]*</title>" && echo "✅ index.php"
curl -s http://127.0.0.1:9999/trojan-dashboard.php | grep -o "<title>[^<]*</title>" && echo "✅ trojan-dashboard.php"
curl -s http://127.0.0.1:9999/lab.php | grep -o "<title>[^<]*</title>" && echo "✅ lab.php"

kill $SERVER_PID 2>/dev/null

echo ""
echo "5. Integration status..."
echo "======================="
php -r '
function check_integration() {
    $status = [];
    
    // Check enhanced module
    $enhanced_path = "system/modules/TrojanModule.php";
    if (file_exists($enhanced_path)) {
        require_once $enhanced_path;
        $status["enhanced_module"] = class_exists("EnhancedTrojanModule") ? "✅ LOADED" : "❌ CLASS NOT FOUND";
    } else {
        $status["enhanced_module"] = "❌ FILE NOT FOUND";
    }
    
    // Check bridge
    $bridge_path = "public/includes/TrojanBridge.php";
    if (file_exists($bridge_path)) {
        require_once $bridge_path;
        $status["bridge"] = class_exists("TrojanBridge") ? "✅ LOADED" : "❌ CLASS NOT FOUND";
    } else {
        $status["bridge"] = "❌ FILE NOT FOUND";
    }
    
    // Check security functions
    $sec_path = "public/includes/security_functions.php";
    if (file_exists($sec_path)) {
        require_once $sec_path;
        $status["security_functions"] = function_exists("load_enhanced_trojan_module") ? "✅ ENHANCED" : "✅ BASIC";
    } else {
        $status["security_functions"] = "❌ FILE NOT FOUND";
    }
    
    return $status;
}

$results = check_integration();
foreach ($results as $key => $value) {
    echo str_pad($key, 25) . ": " . $value . "\n";
}
'

echo ""
echo "✅ Verification complete!"
echo ""
echo "To start the enhanced system:"
echo "  cd /home/kali/COSMIC-OSINT-LAB@888/lab808"
echo "  ./launch-enhanced-cosmic.sh"
echo ""
echo "Access at: http://localhost:8080"
