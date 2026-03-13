#!/bin/bash
echo "🌀 COSMIC OSINT LAB - Backup Organization System"
echo "================================================"

cd /home/kali/COSMIC-OSINT-LAB@888/lab808/public

# Create today's backup folder
TODAY=$(date +%Y%m%d)
BACKUP_ROOT="/home/kali/COSMIC-OSINT-LAB@888/lab808/backups"
DAILY_BACKUP="$BACKUP_ROOT/$TODAY"

mkdir -p "$DAILY_BACKUP/php_files"
mkdir -p "$DAILY_BACKUP/html_files"
mkdir -p "$DAILY_BACKUP/scripts"
mkdir -p "$DAILY_BACKUP/configs"
mkdir -p "$DAILY_BACKUP/logs"
mkdir -p "$DAILY_BACKUP/other"

echo "1. Moving PHP backup files..."
find . -maxdepth 1 -name "*.php.backup*" -o -name "*.php.old" -o -name "*.php.corrupted" | while read file; do
    if [ -f "$file" ]; then
        mv "$file" "$DAILY_BACKUP/php_files/"
        echo "  Moved: $file"
    fi
done

echo ""
echo "2. Moving HTML backup files..."
find . -maxdepth 1 -name "*.html.backup*" -o -name "*.html.old" | while read file; do
    if [ -f "$file" ]; then
        mv "$file" "$DAILY_BACKUP/html_files/"
        echo "  Moved: $file"
    fi
done

echo ""
echo "3. Moving script files..."
find . -maxdepth 1 -name "*.sh" -o -name "fix_*.sh" | while read file; do
    if [ -f "$file" ]; then
        mv "$file" "$DAILY_BACKUP/scripts/"
        echo "  Moved: $file"
    fi
done

echo ""
echo "4. Moving config files..."
find . -maxdepth 1 -name "*.config" -o -name "*.conf" -o -name "*.cfg" | while read file; do
    if [ -f "$file" ]; then
        mv "$file" "$DAILY_BACKUP/configs/"
        echo "  Moved: $file"
    fi
done

echo ""
echo "5. Cleaning up miscellaneous..."
# Move any remaining backup-like files
for file in *; do
    if [[ "$file" =~ \.backup[0-9]*$ ]] || [[ "$file" =~ \.old$ ]] || [[ "$file" =~ \.corrupted$ ]]; then
        mv "$file" "$DAILY_BACKUP/other/"
        echo "  Moved: $file"
    fi
done

echo ""
echo "=== Backup Summary ==="
echo "PHP backups:   $(ls -1 "$DAILY_BACKUP/php_files/" 2>/dev/null | wc -l)"
echo "HTML backups:  $(ls -1 "$DAILY_BACKUP/html_files/" 2>/dev/null | wc -l)"
echo "Scripts:       $(ls -1 "$DAILY_BACKUP/scripts/" 2>/dev/null | wc -l)"
echo "Configs:       $(ls -1 "$DAILY_BACKUP/configs/" 2>/dev/null | wc -l)"
echo "Other:         $(ls -1 "$DAILY_BACKUP/other/" 2>/dev/null | wc -l)"

echo ""
echo "=== Current public directory ==="
ls -la | grep -E "^[^-]" | head -20
echo "..."
