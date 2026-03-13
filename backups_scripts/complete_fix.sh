#!/bin/bash
echo "=== COSMIC OSINT LAB COMPLETE FIX ==="
echo ""

# 1. Clean up all malformed files
echo "1. Cleaning malformed filenames..."
cd /home/kali/COSMIC-OSINT-LAB@888/lab808

# Remove trailing punctuation from all files
find . -maxdepth 1 -name "*," -exec sh -c 'mv "$1" "${1%%,}"' _ {} \;
find . -maxdepth 1 -name "*]" -exec sh -c 'mv "$1" "${1%%]}"' _ {} \;
find . -maxdepth 1 -name "*[" -exec sh -c 'mv "$1" "${1%%[}"' _ {} \;

# 2. Fix PHP syntax errors
echo "2. Fixing PHP syntax errors..."
cd public
php -l trojan-dashboard.php 2>&1 | grep -A5 -B5 "Parse error"

# If error persists, use this common fix:
if grep -q "unexpected token" trojan-dashboard.php; then
    sed -i 's/<?/<?php/g' trojan-dashboard.php
    sed -i '/^$/d' trojan-dashboard.php
fi

# 3. Remove duplicate menu entries
echo "3. Removing duplicate menu entries..."
# Find and edit the main navigation file
cd ..
NAV_FILE=$(find . -type f \( -name "*.php" -o -name "*.html" \) -exec grep -l "Trojan Generator" {} \; | head -1)

if [ -n "$NAV_FILE" ]; then
    echo "Found navigation in: $NAV_FILE"
    # Remove duplicate entries
    sed -i '/🏴‍☠️ Trojan Generator/{n;N;/🏴‍☠️ Trojan Generator/d}' "$NAV_FILE"
    sed -i '/Trojan Generator.*Trojan Generator/d' "$NAV_FILE"
fi

# 4. Verify fixes
echo "4. Verifying fixes..."
echo "--- Directory structure ---"
ls -la | grep -E "Dashboard|Trojan"

echo ""
echo "--- PHP syntax check ---"
find . -name "*.php" -exec php -l {} \; 2>/dev/null | grep -v "No syntax errors"

echo ""
echo "=== FIX COMPLETE ==="
