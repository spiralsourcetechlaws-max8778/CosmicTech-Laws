/**
 * COSMIC UNIFIED QUICK NAVIGATION
 * Floating button with links to C2 management, payloads, and key dashboards.
 */
(function() {
    'use strict';

    // Configuration
    const links = [
        { url: '/c2-dashboard.php', icon: '🎮', label: 'C2 Dashboard' },
        { url: '/c2-dashboard.php?view=payloads', icon: '🎯', label: 'Active Payloads' },
        { url: '/c2-dashboard.php?view=listeners', icon: '📡', label: 'Listeners' },
        { url: '/c2-dashboard.php?view=tasks', icon: '⚡', label: 'Tasks' },
        { url: '/c2-dashboard.php?view=files', icon: '📁', label: 'Generated Payloads' },
        { url: '/redteam-dashboard.php', icon: '🔴', label: 'Red Team Ops' },
        { url: '/phishing-dashboard.php', icon: '🎣', label: 'Phishing' },
        { url: '/trojan-dashboard.php?advanced=1', icon: '🧬', label: 'Generate Payload' }
    ];

    let isOpen = false;

    function createButton() {
        const btn = document.createElement('div');
        btn.id = 'unified-quicknav-btn';
        btn.style.cssText = `
            position: fixed;
            bottom: 140px;
            right: 20px;
            background: rgba(0, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid #00ffff;
            border-radius: 50px;
            padding: 12px 24px;
            color: white;
            font-family: 'Share Tech Mono', monospace;
            cursor: pointer;
            z-index: 10002;
            box-shadow: 0 0 20px rgba(0,255,255,0.5);
            transition: all 0.3s;
        `;
        btn.innerHTML = '🚀 QUICK NAV ▼';
        btn.onmouseover = () => { btn.style.background = 'rgba(0,255,255,0.4)'; };
        btn.onmouseout = () => { btn.style.background = 'rgba(0,255,255,0.2)'; };
        btn.onclick = toggleMenu;
        document.body.appendChild(btn);
    }

    function toggleMenu() {
        const existing = document.getElementById('unified-quicknav-menu');
        if (existing) {
            existing.remove();
            isOpen = false;
            return;
        }
        isOpen = true;
        const menu = document.createElement('div');
        menu.id = 'unified-quicknav-menu';
        menu.style.cssText = `
            position: fixed;
            bottom: 200px;
            right: 20px;
            width: 280px;
            max-height: 400px;
            overflow-y: auto;
            background: rgba(10, 20, 40, 0.95);
            backdrop-filter: blur(10px);
            border: 2px solid #00ffff;
            border-radius: 16px;
            padding: 15px;
            color: #ccf;
            font-family: 'Share Tech Mono', monospace;
            z-index: 10001;
            box-shadow: 0 0 30px rgba(0,255,255,0.3);
        `;
        let html = '<h3 style="margin-top:0; color:#0ff; border-bottom:1px solid #0ff; padding-bottom:8px;">🚀 Quick Navigation</h3><ul style="list-style:none; padding:0;">';
        links.forEach(link => {
            html += `<li style="margin-bottom:8px;">
                <a href="${link.url}" style="display:flex; align-items:center; padding:8px 12px; background:rgba(0,255,255,0.05); border-radius:8px; color:#fff; text-decoration:none; transition:0.2s;" 
                   onmouseover="this.style.background='rgba(0,255,255,0.2)'" 
                   onmouseout="this.style.background='rgba(0,255,255,0.05)'">
                    <span style="font-size:1.3em; margin-right:12px;">${link.icon}</span>
                    <span style="flex:1;">${link.label}</span>
                    <span style="color:#0ff;">→</span>
                </a>
            </li>`;
        });
        html += '</ul>';
        menu.innerHTML = html;
        document.body.appendChild(menu);

        // Close when clicking outside
        setTimeout(() => {
            document.addEventListener('click', function closeOutside(e) {
                if (!menu.contains(e.target) && e.target.id !== 'unified-quicknav-btn') {
                    menu.remove();
                    document.removeEventListener('click', closeOutside);
                    isOpen = false;
                }
            });
        }, 100);
    }

    // Initialize on load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createButton);
    } else {
        createButton();
    }
})();
