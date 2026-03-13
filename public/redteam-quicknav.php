<?php
require_once "includes/security_functions.php";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡ Red Team Quick Navigation - COSMIC-OSINT-LAB</title>
    <style>
        :root {
            --red: #ff0000;
            --green: #00ff00;
            --blue: #00ffff;
            --purple: #ff00ff;
            --dark: #0a0a0a;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: var(--dark);
            color: var(--green);
            overflow-x: hidden;
        }
        
        .quicknav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .nav-card {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid;
            border-radius: 10px;
            padding: 20px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            min-height: 180px;
            display: flex;
            flex-direction: column;
        }
        
        .nav-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 0, 0, 0.1) 50%, transparent 70%);
            animation: scan 3s linear infinite;
        }
        
        @keyframes scan {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .nav-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(255, 0, 0, 0.3);
        }
        
        .card-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .card-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .card-desc {
            font-size: 0.9em;
            color: #aaa;
            flex-grow: 1;
        }
        
        .card-footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 0, 0, 0.3);
            font-size: 0.8em;
            color: var(--blue);
            display: flex;
            justify-content: space-between;
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 5px;
        }
        
        .status-active { background: var(--green); }
        .status-warning { background: var(--purple); }
        .status-offline { background: #666; }
        
        .search-box {
            padding: 20px;
            text-align: center;
            background: rgba(0, 0, 0, 0.7);
            margin-bottom: 20px;
            border-bottom: 2px solid var(--red);
        }
        
        .search-input {
            width: 80%;
            max-width: 600px;
            padding: 12px 20px;
            background: #000;
            color: var(--green);
            border: 2px solid var(--red);
            border-radius: 25px;
            font-family: inherit;
            font-size: 16px;
        }
        
        .search-input:focus {
            outline: none;
            box-shadow: 0 0 10px var(--red);
        }
        
        .header {
            text-align: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, rgba(255,0,0,0.1), rgba(0,0,0,0.8));
            border-bottom: 3px solid var(--red);
        }
        
        .header h1 {
            color: var(--red);
            font-size: 2.5em;
            text-shadow: 0 0 10px var(--red);
            margin-bottom: 10px;
        }
        
        .quick-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            color: var(--blue);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--green);
        }
        
        .filter-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            background: #000;
            color: var(--green);
            border: 1px solid var(--red);
            border-radius: 20px;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.3s;
        }
        
        .filter-btn.active {
            background: var(--red);
            color: #000;
        }
        
        .filter-btn:hover {
            background: var(--red);
            color: #000;
        }
        
        @media (max-width: 768px) {
            .quicknav-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                padding: 10px;
            }
            
            .header h1 {
                font-size: 1.8em;
            }
            
            .search-input {
                width: 95%;
            }
        }
    </style>
</head>
<body class="cosmic-scroll-body">
    <div class="header">
        <h1>⚡ RED TEAM QUICK NAVIGATION</h1>
        <p>Fast access to all offensive security tools</p>
        
        <div class="quick-stats">
            <div class="stat-item">
                <div class="stat-value">12</div>
                <div>Tools Available</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">4</div>
                <div>Platforms</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">24/7</div>
                <div>Operational</div>
            </div>
        </div>
    </div>
    
    <div class="search-box">
        <input type="text" class="search-input" placeholder="🔍 Search tools (Trojan, Scanner, Exploit, etc.)" id="searchInput">
    </div>
    
    <div class="filter-buttons">
        <button class="filter-btn active" data-filter="all">All Tools</button>
        <button class="filter-btn" data-filter="trojan">Trojans</button>
        <button class="filter-btn" data-filter="scanner">Scanners</button>
        <button class="filter-btn" data-filter="exploit">Exploits</button>
        <button class="filter-btn" data-filter="analysis">Analysis</button>
        <button class="filter-btn" data-filter="windows">Windows</button>
        <button class="filter-btn" data-filter="linux">Linux</button>
        <button class="filter-btn" data-filter="android">Android</button>
    </div>
    
    <div class="quicknav-grid" id="navGrid">
        <!-- Trojan Tools -->
        <a href="trojan-dashboard.php" class="nav-card" data-category="trojan windows linux android">
            <div class="card-icon">🏴‍☠️</div>
            <div class="card-title">Trojan Generator</div>
            <div class="card-desc">Generate payloads for Windows, Linux, Android with persistence and encryption</div>
            <div class="card-footer">
                <span><span class="status-indicator status-active"></span> Online</span>
                <span>Multi-platform</span>
            </div>
        </a>
        
        <a href="payload-tester.php" class="nav-card" data-category="trojan analysis">
            <div class="card-icon">🧪</div>
            <div class="card-title">Payload Tester</div>
            <div class="card-desc">Analyze and test payloads in safe sandbox environment</div>
            <div class="card-footer">
                <span><span class="status-indicator status-active"></span> Online</span>
                <span>Security Analysis</span>
            </div>
        </a>
        
        <!-- Windows Tools -->
        <a href="trojan-dashboard.php?platform=windows" class="nav-card" data-category="windows trojan">
            <div class="card-icon">🪟</div>
            <div class="card-title">Windows Payloads</div>
            <div class="card-desc">EXE, PowerShell, BAT payloads with Windows persistence mechanisms</div>
            <div class="card-footer">
                <span><span class="status-indicator status-active"></span> Online</span>
                <span>.exe, .ps1, .bat</span>
            </div>
        </a>
        
        <!-- Linux Tools -->
        <a href="trojan-dashboard.php?platform=linux" class="nav-card" data-category="linux trojan">
            <div class="card-icon">🐧</div>
            <div class="card-title">Linux Payloads</div>
            <div class="card-desc">Shell scripts, ELF binaries, rootkits with systemd/cron persistence</div>
            <div class="card-footer">
                <span><span class="status-indicator status-active"></span> Online</span>
                <span>.sh, .elf, .py</span>
            </div>
        </a>
        
        <!-- Android Tools -->
        <a href="trojan-dashboard.php?platform=android" class="nav-card" data-category="android trojan">
            <div class="card-icon">🤖</div>
            <div class="card-title">Android Payloads</div>
            <div class="card-desc">APK backdoors, root exploits, persistence via boot receivers</div>
            <div class="card-footer">
                <span><span class="status-indicator status-active"></span> Online</span>
                <span>.apk, .jar</span>
            </div>
        </a>
        
        <!-- Scanner Tools -->
        <a href="lab.php" class="nav-card" data-category="scanner">
            <div class="card-icon">🔍</div>
            <div class="card-title">Network Scanner</div>
            <div class="card-desc">Port scanning, service detection, vulnerability assessment</div>
            <div class="card-footer">
                <span><span class="status-indicator status-active"></span> Online</span>
                <span>TCP/UDP</span>
            </div>
        </a>
        
        <!-- Exploit Tools -->
        <a href="redteam-dashboard.php" class="nav-card" data-category="exploit">
            <div class="card-icon">💣</div>
            <div class="card-title">Exploit Simulator</div>
            <div class="card-desc">Practice exploitation techniques (SQLi, XSS, RCE) in safe environment</div>
            <div class="card-footer">
                <span><span class="status-indicator status-active"></span> Online</span>
                <span>Training</span>
            </div>
        </a>
        
        <!-- Analysis Tools -->
        <a href="ai-dashboard.php" class="nav-card" data-category="analysis">
            <div class="card-icon">🤖</div>
            <div class="card-title">AI Analysis</div>
            <div class="card-desc">AI-powered threat analysis and attack pattern recognition</div>
            <div class="card-footer">
                <span><span class="status-indicator status-active"></span> Online</span>
                <span>Machine Learning</span>
            </div>
        </a>
        
        <!-- API Tools -->
        <a href="redteam-api.php?action=status" class="nav-card" data-category="analysis">
            <div class="card-icon">⚙️</div>
            <div class="card-title">API Endpoints</div>
            <div class="card-desc">REST API for programmatic access to all red team tools</div>
            <div class="card-footer">
                <span><span class="status-indicator status-active"></span> Online</span>
                <span>JSON/REST</span>
            </div>
        </a>
        
        <!-- Quick Generators -->
        <a href="trojan-dashboard.php?quick=reverse_shell" class="nav-card" data-category="trojan windows linux">
            <div class="card-icon">🐚</div>
            <div class="card-title">Quick Reverse Shell</div>
            <div class="card-desc">One-click reverse shell generator for immediate testing</div>
            <div class="card-footer">
                <span><span class="status-indicator status-active"></span> Online</span>
                <span>Fast Generation</span>
            </div>
        </a>
        
        <!-- Persistence Tools -->
        <a href="trojan-dashboard.php?type=windows_persistence" class="nav-card" data-category="windows trojan">
            <div class="card-icon">🔒</div>
            <div class="card-title">Windows Persistence</div>
            <div class="card-desc">Registry, services, scheduled tasks for permanent access</div>
            <div class="card-footer">
                <span><span class="status-indicator status-warning"></span> Advanced</span>
                <span>Permanent</span>
            </div>
        </a>
        
        <!-- Rootkit Tools -->
        <a href="trojan-dashboard.php?type=linux_rootkit" class="nav-card" data-category="linux trojan">
            <div class="card-icon">👁️</div>
            <div class="card-title">Linux Rootkit</div>
            <div class="card-desc">Kernel-level access and hiding techniques (educational)</div>
            <div class="card-footer">
                <span><span class="status-indicator status-warning"></span> Expert</span>
                <span>Kernel Mode</span>
            </div>
        </a>
    </div>
    
    <script>
        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                button.classList.add('active');
                
                // Filter cards
                const filter = button.getAttribute('data-filter');
                const cards = document.querySelectorAll('.nav-card');
                
                cards.forEach(card => {
                    if (filter === 'all' || card.getAttribute('data-category').includes(filter)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.nav-card');
            
            cards.forEach(card => {
                const title = card.querySelector('.card-title').textContent.toLowerCase();
                const desc = card.querySelector('.card-desc').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || desc.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Quick stats animation
        function animateStats() {
            const stats = document.querySelectorAll('.stat-value');
            stats.forEach(stat => {
                const target = parseInt(stat.textContent);
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    stat.textContent = Math.round(current);
                }, 20);
            });
        }
        
        // Initialize on load
        window.addEventListener('load', () => {
            animateStats();
            
            // Add click effects
            document.querySelectorAll('.nav-card').forEach(card => {
                card.addEventListener('click', () => {
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        card.style.transform = '';
                    }, 150);
                });
            });
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl + T = Trojan Generator
            if (e.ctrlKey && e.key === 't') {
                e.preventDefault();
                window.location.href = 'trojan-dashboard.php';
            }
            
            // Ctrl + S = Search
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            }
            
            // Escape = Clear search
            if (e.key === 'Escape') {
                document.getElementById('searchInput').value = '';
                document.getElementById('searchInput').dispatchEvent(new Event('input'));
            }
        });
    </script>
</body>
</html>
