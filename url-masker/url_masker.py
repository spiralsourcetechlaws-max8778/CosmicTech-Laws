#!/usr/bin/env python3
"""
Industrial‑grade URL Masking Tool
For authorized penetration testing only.
"""

import os
import sys
import subprocess
import json
import time
import argparse
import requests
from urllib.parse import urlparse

# ---------- Configuration ----------
NGROK_PATH = "ngrok"               # assuming ngrok is in $PATH
SSH_CMD = "ssh"                     # OpenSSH client
HOMograph_API = None                # Not used; we use a local table

# ---------- Helper Functions ----------
def check_dependency(cmd, name):
    """Check if a command-line tool is available."""
    try:
        subprocess.run([cmd, "--version"], capture_output=True, check=True)
    except (subprocess.SubprocessError, FileNotFoundError):
        print(f"[!] Required dependency '{name}' not found. Install it first.")
        return False
    return True

def print_banner():
    print(r"""
    __  __           _      _____       _          
   |  \/  |         | |    |  __ \     | |         
   | \  / |_ __ __ _| | ___| |__) |   _| | ___  ___ 
   | |\/| | '__/ _` | |/ _ \  _  / | | | |/ _ \/ __|
   | |  | | | | (_| | |  __/ | \ \ |_| | |  __/\__ \
   |_|  |_|_|  \__,_|_|\___|_|  \_\__,_|_|\___||___/
    """)
    print(":: URL Masker – Divine Cosmic Tech Edition ::\n")

# ---------- Tunneling Methods ----------
def start_ngrok_tunnel(local_url, subdomain=None):
    """
    Spawn an ngrok tunnel to local_url.
    If subdomain is provided, attempt to use it (requires paid plan).
    Returns the public ngrok URL or None on failure.
    """
    if not check_dependency(NGROK_PATH, "ngrok"):
        return None

    # Parse local URL to get host and port
    parsed = urlparse(local_url)
    if not parsed.hostname or not parsed.port:
        print("[!] Invalid local URL. Must include port (e.g., http://0.0.0.0:8008/...)")
        return None
    addr = f"{parsed.hostname}:{parsed.port}"

    # Build ngrok command
    cmd = [NGROK_PATH, "http", addr, "--log=stdout", "--log-level=info"]
    if subdomain:
        cmd.extend(["--subdomain", subdomain])

    print(f"[*] Starting ngrok tunnel to {addr} ...")
    proc = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True)

    # Wait for the tunnel to be established and extract public URL
    public_url = None
    for line in proc.stdout:
        print(f"[ngrok] {line.strip()}")
        if "started tunnel" in line and "url=" in line:
            # Example: "tunnel session started with url=https://abc123.ngrok.io"
            parts = line.split("url=")
            if len(parts) > 1:
                public_url = parts[1].strip()
                break
        time.sleep(0.1)

    if public_url:
        print(f"\n[+] ngrok tunnel established: {public_url}")
        print("[*] Keep this process running. Press Ctrl+C to stop.")
        try:
            proc.wait()
        except KeyboardInterrupt:
            proc.terminate()
            print("\n[!] Tunnel closed.")
    else:
        print("[!] Failed to get ngrok URL. Check ngrok logs above.")
        proc.terminate()
    return public_url

def start_serveo_tunnel(local_url, subdomain=None):
    """
    Create an SSH tunnel to serveo.net.
    Optionally specify a custom subdomain (first come first served).
    """
    if not check_dependency(SSH_CMD, "ssh"):
        return None

    parsed = urlparse(local_url)
    if not parsed.hostname or not parsed.port:
        print("[!] Invalid local URL.")
        return None

    remote_port = 80  # serveo forwards HTTP on port 80
    if subdomain:
        remote_part = f"{subdomain}:{remote_port}:{parsed.hostname}:{parsed.port}"
    else:
        remote_part = f"{remote_port}:{parsed.hostname}:{parsed.port}"

    cmd = [
        SSH_CMD, "-o", "StrictHostKeyChecking=no",
        "-R", remote_part,
        "serveo.net"
    ]
    print(f"[*] Connecting to serveo.net with: {' '.join(cmd)}")
    proc = subprocess.Popen(cmd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True)

    public_url = None
    for line in proc.stdout:
        print(f"[serveo] {line.strip()}")
        if "Forwarding HTTP traffic from" in line:
            # Example: "Forwarding HTTP traffic from https://mycustom.serveo.net"
            parts = line.split("from ")
            if len(parts) > 1:
                public_url = parts[1].strip()
                break
        time.sleep(0.1)

    if public_url:
        print(f"\n[+] serveo tunnel active: {public_url}")
        print("[*] Keep this process running. Press Ctrl+C to stop.")
        try:
            proc.wait()
        except KeyboardInterrupt:
            proc.terminate()
            print("\n[!] Tunnel closed.")
    else:
        print("[!] Failed to get serveo URL.")
        proc.terminate()
    return public_url

# ---------- Reverse Proxy Setup (VPS) ----------
def deploy_reverse_proxy(server_ip, server_user, local_url, proxy_domain):
    """
    Set up Nginx reverse proxy on a remote server.
    Requires SSH access and sudo privileges on the server.
    """
    print("[*] Deploying reverse proxy on remote server...")
    parsed = urlparse(local_url)
    if not parsed.hostname or not parsed.port:
        print("[!] Invalid local URL.")
        return

    # Build nginx config snippet
    config = f"""
server {{
    listen 80;
    server_name {proxy_domain};

    location / {{
        proxy_pass http://{parsed.hostname}:{parsed.port};
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }}
}}
"""
    # Write config locally, then scp and enable
    local_config = "/tmp/reverse_proxy.conf"
    with open(local_config, "w") as f:
        f.write(config)

    # Copy to server
    remote_tmp = f"/home/{server_user}/proxy_temp.conf"
    subprocess.run(["scp", local_config, f"{server_user}@{server_ip}:{remote_tmp}"], check=True)

    # Move to nginx sites-available and enable
    cmds = f"""
sudo mv {remote_tmp} /etc/nginx/sites-available/{proxy_domain}
sudo ln -sf /etc/nginx/sites-available/{proxy_domain} /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
"""
    ssh_cmd = ["ssh", f"{server_user}@{server_ip}", cmds]
    result = subprocess.run(ssh_cmd, capture_output=True, text=True)
    print(result.stdout)
    if result.returncode != 0:
        print("[!] Failed to configure nginx on remote server.")
        print(result.stderr)
    else:
        print(f"[+] Reverse proxy active at http://{proxy_domain}")
        print(f"[!] Ensure DNS A record for {proxy_domain} points to {server_ip}")

# ---------- Homograph Domain Suggestions ----------
def suggest_homograph_domains(legitimate_domain):
    """
    Generate a list of lookalike domains using Unicode confusables.
    This is a simplified example; a real implementation would use
    a mapping table like https://www.unicode.org/Public/security/latest/confusables.txt
    """
    confusables = {
        'a': ['à', 'á', 'â', 'ã', 'ä', 'å', 'ɑ', 'α', 'а'],  # Cyrillic 'a' is included
        'b': ['Ь', 'ƅ', 'β'],  # Cyrillic soft sign looks like b
        'c': ['с', 'ϲ', 'ⅽ'],  # Cyrillic s
        'e': ['е', 'ё', 'є', 'é', 'è', 'ê', 'ë', 'ē'],  # Cyrillic e
        'g': ['ɡ', 'ġ', 'ğ'],
        'i': ['і', 'ï', 'í', 'ì', 'î', 'ī'],  # Cyrillic i
        'l': ['ӏ', 'ⅼ'],  # Cyrillic palochka
        'o': ['о', 'ο', 'ө', 'ō', 'ø', '0'],  # Cyrillic o, omicron
        'p': ['р', 'ρ'],  # Cyrillic rho
        's': ['ѕ', 'ʂ', 'š'],
        'x': ['х', 'ⅹ'],  # Cyrillic ha
        'y': ['у', 'ү', 'ý'],  # Cyrillic u
    }

    suggestions = []
    # For each character in the domain, if it can be replaced, create a variant
    for idx, char in enumerate(legitimate_domain):
        if char.lower() in confusables:
            for replacement in confusables[char.lower()]:
                new_domain = legitimate_domain[:idx] + replacement + legitimate_domain[idx+1:]
                suggestions.append(new_domain)
    # Add some extra tricks: e.g., add a hyphen, change TLD (requires registration)
    suggestions.append(legitimate_domain.replace('.com', '.co'))
    suggestions.append(legitimate_domain.replace('.com', '.org'))
    # Remove duplicates
    suggestions = list(set(suggestions))
    return suggestions[:15]  # Limit to first 15

# ---------- Main ----------
def main():
    print_banner()
    parser = argparse.ArgumentParser(description="Mask a local phishing URL into a legitimate-looking one.")
    parser.add_argument("local_url", help="Local URL to mask (e.g., http://0.0.0.0:8008/phishing.php)")
    parser.add_argument("--method", choices=["ngrok", "serveo", "vps", "homograph"], default="ngrok",
                        help="Masking method to use")
    parser.add_argument("--subdomain", help="Desired subdomain for ngrok/serveo")
    parser.add_argument("--vps-ip", help="IP address of your VPS (for reverse proxy)")
    parser.add_argument("--vps-user", help="SSH username for VPS")
    parser.add_argument("--proxy-domain", help="Your domain that points to the VPS")
    parser.add_argument("--legitimate-domain", help="Domain to impersonate (for homograph suggestions)")
    args = parser.parse_args()

    if args.method == "ngrok":
        start_ngrok_tunnel(args.local_url, args.subdomain)
    elif args.method == "serveo":
        start_serveo_tunnel(args.local_url, args.subdomain)
    elif args.method == "vps":
        if not (args.vps_ip and args.vps_user and args.proxy_domain):
            print("[!] For VPS method you need --vps-ip, --vps-user, and --proxy-domain")
            return
        deploy_reverse_proxy(args.vps_ip, args.vps_user, args.local_url, args.proxy_domain)
    elif args.method == "homograph":
        if not args.legitimate_domain:
            print("[!] For homograph method you need --legitimate-domain")
            return
        suggestions = suggest_homograph_domains(args.legitimate_domain)
        print("\n[+] Suggested lookalike domains (register one that fits):")
        for d in suggestions:
            print(f"   {d}")
    else:
        print("[!] Unknown method.")

if __name__ == "__main__":
    main()
