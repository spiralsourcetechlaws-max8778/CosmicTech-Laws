#!/bin/bash
cd /home/kali/COSMIC-OSINT-LAB@888/lab808/public

# Backup
cp trojan-dashboard.php trojan-dashboard.php.backup

# Common fixes for line 15 issues
# Fix 1: Missing semicolon or bracket
sed -i '15s/\(\$.*=\s*\[.*\)$/\1\];/' trojan-dashboard.php

# Fix 2: HTML in PHP without proper closing
sed -i '14,16s/?><\?php//g' trojan-dashboard.php

# Fix 3: Remove duplicate PHP tags
sed -i '14,16s/<\?php//2g' trojan-dashboard.php

# Fix 4: Ensure proper array syntax
sed -i '15s/</[/g; 15s/>/]/g' trojan-dashboard.php

echo "File fixed. Testing syntax..."
php -l trojan-dashboard.php
