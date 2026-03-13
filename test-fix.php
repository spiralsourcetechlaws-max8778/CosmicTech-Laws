<?php
require_once "public/includes/compatibility.php";

echo "Testing compatibility...\n";

// Test if classes exist
if (class_exists('DeceptionEngine')) {
    echo "✅ DeceptionEngine class found\n";
    $deception = new DeceptionEngine();
    $indicators = $deception->detectAutomation();
    echo "   Bot detected: " . ($indicators['bot_detected'] ?? 'false') . "\n";
} else {
    echo "❌ DeceptionEngine class NOT found\n";
}

if (class_exists('LocalThreatIntel')) {
    echo "✅ LocalThreatIntel class found\n";
    $threat_intel = new LocalThreatIntel();
    $ip_info = $threat_intel->checkIP('127.0.0.1');
    echo "   Threat score: " . $ip_info['threat_score'] . "\n";
} else {
    echo "❌ LocalThreatIntel class NOT found\n";
}

// Test functions
if (function_exists('sanitize_input')) {
    echo "✅ sanitize_input function found\n";
} else {
    echo "❌ sanitize_input function NOT found\n";
}

if (function_exists('generate_csrf_token')) {
    echo "✅ generate_csrf_token function found\n";
} else {
    echo "❌ generate_csrf_token function NOT found\n";
}

echo "\n✅ Compatibility test complete!\n";
