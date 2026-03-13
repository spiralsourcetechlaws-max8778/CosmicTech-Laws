/**
 * COSMIC C2 – QUICK NAVIGATION BUTTON
 * Shows active payloads and allows quick jump to beacon view.
 */
(function() {
    'use strict';

    let activePayloads = [];
    let isOpen = false;

    function fetchActivePayloads() {
        fetch('/c2/api/index.php?action=list_payloads&key=COSMIC-C2-SECRET-2026')
            .then(r => r.json())
            .then(data => {
                if (data.status === 'ok') {
                    activePayloads = data.data.filter(p => p.status === 'active');
                    updateButton();
                }
            })
            .catch(() => {});
    }

    function updateButton() {
        let btn = document.getElementById('c2-quicknav-btn');
        if (!btn) {
            btn = document.createElement('div');
            btn.id = 'c2-quicknav-btn';
            btn.style.cssText = `
                position: fixed;
                bottom: 80px;
                right: 20px;
                background: rgba(0, 255, 255, 0.2);
                backdrop-filter: blur(10px);
                border: 2px solid #00ffff;
                border-radius: 50px;
                padding: 12px 24px;
                color: white;
                font-family: 'Share Tech Mono', monospace;
                cursor: pointer;
                z-index: 10001;
                box-shadow: 0 0 20px rgba(0,255,255,0.5);
                transition: all 0.3s;
            `;
            btn.innerHTML = `🚀 PAYLOADS <span style="background:#ff00c8; border-radius:50%; padding:2px 8px; margin-left:8px;">${activePayloads.length}</span>`;
            btn.onmouseover = () => { btn.style.background = 'rgba(0,255,255,0.4)'; };
            btn.onmouseout = () => { btn.style.background = 'rgba(0,255,255,0.2)'; };
            btn.onclick = toggleDropdown;
            document.body.appendChild(btn);
        } else {
            btn.innerHTML = `🚀 PAYLOADS <span style="background:#ff00c8; border-radius:50%; padding:2px 8px; margin-left:8px;">${activePayloads.length}</span>`;
        }
    }

    function toggleDropdown() {
        const existing = document.getElementById('c2-quicknav-dropdown');
        if (existing) {
            existing.remove();
            isOpen = false;
            return;
        }
        isOpen = true;
        const dropdown = document.createElement('div');
        dropdown.id = 'c2-quicknav-dropdown';
        dropdown.style.cssText = `
            position: fixed;
            bottom: 140px;
            right: 20px;
            width: 300px;
            max-height: 400px;
            overflow-y: auto;
            background: rgba(10, 20, 40, 0.95);
            backdrop-filter: blur(10px);
            border: 2px solid #00ffff;
            border-radius: 16px;
            padding: 15px;
            color: #ccf;
            font-family: 'Share Tech Mono', monospace;
            z-index: 10000;
            box-shadow: 0 0 30px rgba(0,255,255,0.3);
        `;
        if (activePayloads.length === 0) {
            dropdown.innerHTML = '<p style="text-align:center;">No active payloads</p>';
        } else {
            let html = '<h3 style="margin-top:0; color:#0ff;">Active Payloads</h3><ul style="list-style:none; padding:0;">';
            activePayloads.forEach(p => {
                html += `<li style="margin-bottom:15px; padding:10px; background:rgba(0,255,255,0.05); border-radius:8px;">
                    <div><strong>${p.name || 'Unnamed'}</strong> <span style="color:#0ff;">(${p.type})</span></div>
                    <div style="font-size:0.8em; color:#aaa;">${p.platform} · ${p.lhost}:${p.lport}</div>
                    <div style="margin-top:8px; display:flex; gap:8px;">
                        <a href="/c2-dashboard.php?view=beacons&uuid=${p.uuid}" style="color:#0ff; text-decoration:none; border:1px solid #0ff; padding:4px 8px; border-radius:4px;">📶 Beacons</a>
                        <a href="/c2-dashboard.php?view=payloads&uuid=${p.uuid}" style="color:#0ff; text-decoration:none; border:1px solid #0ff; padding:4px 8px; border-radius:4px;">🎯 Details</a>
                    </div>
                </li>`;
            });
            html += '</ul>';
            dropdown.innerHTML = html;
        }
        document.body.appendChild(dropdown);

        // Close when clicking outside
        setTimeout(() => {
            document.addEventListener('click', function closeOutside(e) {
                if (!dropdown.contains(e.target) && e.target.id !== 'c2-quicknav-btn') {
                    dropdown.remove();
                    document.removeEventListener('click', closeOutside);
                    isOpen = false;
                }
            });
        }, 100);
    }

    // Initial fetch and then every 30 seconds
    fetchActivePayloads();
    setInterval(fetchActivePayloads, 30000);
})();
