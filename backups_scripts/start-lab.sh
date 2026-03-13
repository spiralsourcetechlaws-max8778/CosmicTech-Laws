#!/bin/bash
echo "COSMIC-OSINT-LAB Starter"
echo "========================"
echo "Project: $(pwd)"
echo "IP: $(hostname -I 2>/dev/null || echo '127.0.0.1')"

# Create minimal PHP test
cat > test_php.php << 'TEST'
<?php
echo "PHP is working!";
echo "<br>Version: " . phpversion();
echo "<br>Time: " . date('Y-m-d H:i:s');
?>
TEST

# Check if PHP is available
if command -v php &> /dev/null; then
    echo "PHP found: $(php --version | head -1)"
    echo "Starting server on port 8080..."
    php -S 0.0.0.0:8080
else
    echo "PHP not found! Using Python as fallback..."
    python3 -m http.server 8080
fi
