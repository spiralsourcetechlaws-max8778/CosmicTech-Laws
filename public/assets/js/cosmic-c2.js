/* ==========================================================================
   COSMIC C2 v3.0 – SOVEREIGN INTERACTIVITY
   No scroll modifications – pure UI enhancements
   ========================================================================== */
(function() {
    'use strict';

    // Auto-refresh stats and beacons every 10 seconds
    setInterval(function() {
        if (window.location.pathname.includes('c2-dashboard.php')) {
            refreshStats();
            refreshBeacons();
        }
    }, 10000);

    function refreshStats() {
        fetch('/c2/api/index.php?action=stats&key=COSMIC-C2-SECRET-2026')
            .then(r => r.json())
            .then(d => {
                if (d.status === 'ok') {
                    document.querySelectorAll('[data-stat]').forEach(el => {
                        const key = el.dataset.stat;
                        if (d.data[key] !== undefined) {
                            el.innerText = d.data[key];
                        }
                    });
                }
            })
            .catch(() => {});
    }

    function refreshBeacons() {
        const tbody = document.querySelector('#beacons-table tbody');
        if (!tbody) return;
        fetch('/c2/api/index.php?action=beacons&limit=50&key=COSMIC-C2-SECRET-2026')
            .then(r => r.json())
            .then(d => {
                if (d.status === 'ok' && d.data) {
                    let html = '';
                    d.data.forEach(b => {
                        html += `<tr>
                            <td>${new Date(b.beacon_time * 1000).toLocaleTimeString()}</td>
                            <td>${b.payload_uuid.substr(0,8)}…</td>
                            <td>${b.ip}</td>
                            <td>${b.country || '?'}</td>
                            <td>${b.hostname || '-'}</td>
                            <td>${b.username || '-'}</td>
                            <td>${b.os_info || '-'}</td>
                        </tr>`;
                    });
                    tbody.innerHTML = html;
                }
            })
            .catch(() => {});
    }

    // Modal handlers
    window.openTaskModal = function(uuid) {
        const modal = document.getElementById('taskModal');
        if (!modal) return;
        document.getElementById('task-payload-uuid').value = uuid;
        modal.style.display = 'block';
    };

    window.closeModal = function() {
        document.querySelectorAll('.modal').forEach(m => m.style.display = 'none');
    };

    window.deletePayload = function(uuid) {
        if (confirm('Permanently delete this payload and all its beacons?')) {
            fetch(`/c2/api/index.php?action=delete_payload&uuid=${uuid}&key=COSMIC-C2-SECRET-2026`)
                .then(() => location.reload());
        }
    };

    window.deleteTask = function(id) {
        if (confirm('Delete task?')) {
            fetch(`/c2/api/index.php?action=delete_task&id=${id}&key=COSMIC-C2-SECRET-2026`, {method:'POST'})
                .then(() => location.reload());
        }
    };

    window.startListener = function(id) {
        fetch('/c2/api/index.php?action=start_listener', {
            method: 'POST',
            body: `id=${id}&key=COSMIC-C2-SECRET-2026`,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(() => location.reload());
    };

    window.stopListener = function(id) {
        fetch('/c2/api/index.php?action=stop_listener', {
            method: 'POST',
            body: `id=${id}&key=COSMIC-C2-SECRET-2026`,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        }).then(() => location.reload());
    };

    window.deleteListener = function(id) {
        if (confirm('Delete listener?')) {
            fetch('/c2/api/index.php?action=delete_listener', {
                method: 'POST',
                body: `id=${id}&key=COSMIC-C2-SECRET-2026`,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).then(() => location.reload());
        }
    };

    // Close modal on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') window.closeModal();
    });

    // Initialize on load
    document.addEventListener('DOMContentLoaded', function() {
        refreshStats();
        refreshBeacons();
    });
})();
