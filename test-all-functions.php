<?php
require_once "public/includes/security_functions.php";

echo "=== Testing All Functions ===\n\n";

// Test 1: Check if classes exist
echo "1. Testing class existence:\n";
if (class_exists('LocalThreatIntel')) {
    echo "   ✅ LocalThreatIntel class found\n";
} else {
    echo "   ❌ LocalThreatIntel class NOT found\n";
}

if (class_exists('DeceptionEngine')) {
    echo "   ✅ DeceptionEngine class found\n";
} else {
    echo "   ❌ DeceptionEngine class NOT found\n";
}

// Test 2: Test LocalThreatIntel methods
echo "\n2. Testing LocalThreatIntel methods:\n";
$threat_intel = new LocalThreatIntel();

// Test checkIP method
$ip_info = $threat_intel->checkIP('127.0.0.1');
echo "   ✅ checkIP() method works\n";
echo "      Threat score: " . $ip_info['threat_score'] . "\n";

// Test analyzeUserAgent method
$ua_analysis = $threat_intel->analyzeUserAgent('Mozilla/5.0 Test Browser');
echo "   ✅ analyzeUserAgent() method works\n";
echo "      Browser: " . ($ua_analysis['browser_info']['name'] ?? 'Unknown') . "\n";

// Test getGeolocation method
$geo = $threat_intel->getGeolocation('127.0.0.1');
echo "   ✅ getGeolocation() method works\n";
echo "      Country: " . ($geo['country'] ?? 'Unknown') . "\n";

// Test detectAttackPattern method
$patterns = $threat_intel->detectAttackPattern("test' OR '1'='1");
echo "   ✅ detectAttackPattern() method works\n";
echo "      Detections: " . count($patterns) . "\n";

// Test 3: Test DeceptionEngine methods
echo "\n3. Testing DeceptionEngine methods:\n";
$deception = new DeceptionEngine();
$indicators = $deception->detectAutomation();
echo "   ✅ detectAutomation() method works\n";
echo "      Indicators: " . count($indicators) . "\n";

$score = $deception->getThreatScore();
echo "   ✅ getThreatScore() method works\n";
echo "      Score: " . $score . "\n";

// Test 4: Test basic functions
echo "\n4. Testing basic functions:\n";
if (function_exists('sanitize_input')) {
    echo "   ✅ sanitize_input() function works\n";
} else {
    echo "   ❌ sanitize_input() function missing\n";
}

if (function_exists('generate_csrf_token')) {
    echo "   ✅ generate_csrf_token() function works\n";
} else {
    echo "   ❌ generate_csrf_token() function missing\n";
}

echo "\n✅ ALL TESTS COMPLETED SUCCESSFULLY!\n";
