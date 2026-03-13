<?php
echo "Verifying include paths...\n\n";

$test_files = [
    'public/working-dashboard.php',
    'public/redteam-dashboard.php',
    'public/ai-dashboard.php',
    'public/lab.php',
    'public/enhanced-dashboard.php',
    'public/redteam-api.php'
];

foreach ($test_files as $file) {
    if (file_exists($file)) {
        echo "Checking $file: ";
        
        // Check first few lines for include statements
        $content = file_get_contents($file);
        if (strpos($content, 'require_once "includes/security_functions.php"') !== false ||
            strpos($content, "require_once 'includes/security_functions.php'") !== false) {
            echo "✅ Correct include found\n";
        } else {
            echo "⚠️  No correct include found\n";
        }
    } else {
        echo "❌ $file does not exist\n";
    }
}

echo "\n✅ Verification complete!\n";
