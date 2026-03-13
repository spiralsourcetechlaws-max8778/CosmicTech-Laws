#!/bin/bash
# COSMIC OSINT LAB – Backup & Temp File Cleaner
# Moves all .backup*, .payload, .new files and obvious temporary files
# into a central backups/ folder in the project root.

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="$PROJECT_ROOT/backups/$(date +%Y%m%d_%H%M%S)"

if [ ! -d "$BACKUP_DIR" ]; then
    mkdir -p "$BACKUP_DIR"
fi

# Move backup files from public/
if [ -d "$PROJECT_ROOT/public" ]; then
    echo "Moving backup files from public/ ..."
    find "$PROJECT_ROOT/public" -maxdepth 1 -type f \( -name "*.backup*" -o -name "*.payload" -o -name "*.new" -o -name "*~" \) -exec mv -v {} "$BACKUP_DIR/" \;
    # Also move other obvious temporary files (you can add more patterns)
    find "$PROJECT_ROOT/public" -maxdepth 1 -type f -name "c2-dashboard*.php" ! -name "c2-dashboard.php" -exec mv -v {} "$BACKUP_DIR/" \;
    find "$PROJECT_ROOT/public" -maxdepth 1 -type f -name "sedxKmHfq" -exec mv -v {} "$BACKUP_DIR/" \; 2>/dev/null
fi

# Move backup files from includes/
if [ -d "$PROJECT_ROOT/public/includes" ]; then
    echo "Moving backup files from includes/ ..."
    find "$PROJECT_ROOT/public/includes" -maxdepth 1 -type f -name "*.backup*" -exec mv -v {} "$BACKUP_DIR/" \;
fi

# Move backup files from system/modules/
if [ -d "$PROJECT_ROOT/system/modules" ]; then
    echo "Moving backup files from system/modules/ ..."
    find "$PROJECT_ROOT/system/modules" -maxdepth 1 -type f -name "*.backup*" -exec mv -v {} "$BACKUP_DIR/" \;
fi

# Move backup files from system/modules/phishing/
if [ -d "$PROJECT_ROOT/system/modules/phishing" ]; then
    echo "Moving backup files from phishing module ..."
    find "$PROJECT_ROOT/system/modules/phishing" -maxdepth 1 -type f -name "*.backup*" -exec mv -v {} "$BACKUP_DIR/" \;
fi

# Move backup files from system/modules/advanced/
if [ -d "$PROJECT_ROOT/system/modules/advanced" ]; then
    echo "Moving backup files from advanced module ..."
    find "$PROJECT_ROOT/system/modules/advanced" -maxdepth 1 -type f -name "*.backup*" -exec mv -v {} "$BACKUP_DIR/" \;
fi

echo "✅ All backup files moved to $BACKUP_DIR"
