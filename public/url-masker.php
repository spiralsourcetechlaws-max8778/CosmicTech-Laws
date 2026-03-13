<?php
/**
 * COSMIC URL MASKER – Enterprise Dashboard
 * Accessible via token (default: cosmic-secret)
 */
require_once __DIR__ . '/includes/security_functions.php';
require_once __DIR__ . '/../system/modules/CosmicUrlMasker.php';

// Token authentication (simple)
$config = require __DIR__ . '/../config/urlmasker.php'; // optional config file
$adminToken = $config['admin_token'] ?? 'cosmic-secret';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
if ($token !== $adminToken) {
    http_response_code(401);
    die('Unauthorized. Provide ?token=...');
}

$masker = new CosmicUrlMasker();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $target = $_POST['target_url'];
        $options = [
            'custom_code' => $_POST['custom_code'] ?: null,
            'expires_in' => (int)($_POST['expires'] ?? 0),
            'campaign_id' => $_POST['campaign_id'] ? (int)$_POST['campaign_id'] : null,
            'creator' => 'admin',
            'notes' => $_POST['notes'] ?? '',
            'password' => $_POST['password'] ?? '',
            'max_clicks' => (int)($_POST['max_clicks'] ?? 0),
            'utm_source' => $_POST['utm_source'] ?? '',
            'utm_medium' => $_POST['utm_medium'] ?? '',
            'utm_campaign' => $_POST['utm_campaign'] ?? '',
            'webhook_url' => $_POST['webhook_url'] ?? '',
            'title' => $_POST['title'] ?? '',
        ];
        try {
            $result = $masker->createShortLink($target, $options);
            $message = "Link created: <a href='{$result['short_url']}' target='_blank'>{$result['short_url']}</a>";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['link_id'];
        $masker->deleteLink($id);
        $message = "Link deleted.";
    }
}

$links = $masker->getLinks([], 100, 0);
?>
<!DOCTYPE html>
<html>
<head>
    <title>🌀 COSMIC URL Masker Enterprise</title>
    <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/cosmic-c2.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        .tab { padding:10px 20px; cursor:pointer; border:1px solid #0ff; display:inline-block; margin-right:5px; }
        .tab.active { background:#0ff; color:#000; }
        .tab-content { display:none; padding:20px; border:1px solid #0ff; margin-top:10px; }
        .tab-content.active { display:block; }
    </style>
</head>
<body class="cosmic-scroll-body">
    <div class="main-content" style="margin-left:20px;">
        <h1>🌀 COSMIC URL Masker Enterprise</h1>

        <?php if ($message): ?><div class="success-box"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error-box"><?php echo $error; ?></div><?php endif; ?>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('create')">Create Link</div>
            <div class="tab" onclick="switchTab('bulk')">Bulk Import</div>
            <div class="tab" onclick="switchTab('list')">Link List</div>
        </div>

        <div id="create" class="tab-content active">
            <div class="glass-panel">
                <h2>Create New Short Link</h2>
                <form method="post">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                        <div>
                            <div class="form-group">
                                <label>Target URL:</label>
                                <input type="url" name="target_url" required placeholder="https://...">
                            </div>
                            <div class="form-group">
                                <label>Custom Code (optional):</label>
                                <input type="text" name="custom_code" placeholder="e.g., promo2026">
                            </div>
                            <div class="form-group">
                                <label>Password (optional):</label>
                                <input type="text" name="password" placeholder="leave blank for no password">
                            </div>
                            <div class="form-group">
                                <label>Max Clicks (0 = unlimited):</label>
                                <input type="number" name="max_clicks" value="0" min="0">
                            </div>
                            <div class="form-group">
                                <label>Expires in (seconds, 0 = never):</label>
                                <input type="number" name="expires" value="0" min="0">
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label>Campaign ID (optional):</label>
                                <input type="number" name="campaign_id" placeholder="e.g., 1">
                            </div>
                            <div class="form-group">
                                <label>UTM Source:</label>
                                <input type="text" name="utm_source" placeholder="source">
                            </div>
                            <div class="form-group">
                                <label>UTM Medium:</label>
                                <input type="text" name="utm_medium" placeholder="medium">
                            </div>
                            <div class="form-group">
                                <label>UTM Campaign:</label>
                                <input type="text" name="utm_campaign" placeholder="campaign">
                            </div>
                            <div class="form-group">
                                <label>Webhook URL (optional):</label>
                                <input type="url" name="webhook_url" placeholder="https://...">
                            </div>
                            <div class="form-group">
                                <label>Title (for preview):</label>
                                <input type="text" name="title" placeholder="Link title">
                            </div>
                            <div class="form-group">
                                <label>Notes:</label>
                                <textarea name="notes" rows="3" placeholder="Internal notes"></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="create" class="btn btn-primary">Generate Short Link</button>
                </form>
            </div>
        </div>

        <div id="bulk" class="tab-content">
            <div class="glass-panel">
                <h2>Bulk Import (CSV)</h2>
                <p>Upload a CSV with one URL per line, or with columns: url, custom_code, password, etc.</p>
                <form method="post" enctype="multipart/form-data">
                    <input type="file" name="csv_file" accept=".csv" required>
                    <button type="submit" name="import" class="btn">Import</button>
                </form>
            </div>
        </div>

        <div id="list" class="tab-content">
            <div class="glass-panel">
                <h2>Existing Links</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th><th>Code</th><th>Target URL</th><th>Clicks</th><th>Created</th><th>Expires</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($links as $l): ?>
                        <tr>
                            <td><?php echo $l['id']; ?></td>
                            <td><a href="/l/<?php echo $l['code']; ?>" target="_blank"><?php echo $l['code']; ?></a></td>
                            <td><?php echo htmlspecialchars($l['target_url']); ?></td>
                            <td><?php echo $l['clicks']; ?></td>
                            <td><?php echo date('Y-m-d H:i', $l['created_at']); ?></td>
                            <td><?php echo $l['expires_at'] ? date('Y-m-d H:i', $l['expires_at']) : 'Never'; ?></td>
                            <td>
                                <a href="url-masker-stats.php?id=<?php echo $l['id']; ?>&token=<?php echo urlencode($adminToken); ?>" class="btn small">Stats</a>
                                <a href="#" onclick="showQR('<?php echo $l['code']; ?>')" class="btn small">QR</a>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="link_id" value="<?php echo $l['id']; ?>">
                                    <button type="submit" name="delete" class="btn small danger" onclick="return confirm('Delete this link?')">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="qrModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8);">
        <div style="background:#111; border:2px solid #0ff; padding:20px; width:300px; margin:100px auto; text-align:center;">
            <h3>QR Code</h3>
            <img id="qrImage" src="" style="width:200px; height:200px;">
            <br><button onclick="document.getElementById('qrModal').style.display='none'" class="btn">Close</button>
        </div>
    </div>

    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelector(`.tab[onclick*="'${tabId}'"]`).classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }
        function showQR(code) {
            fetch('/api/shortener.php?action=qr&code=' + code + '&key=<?php echo $adminToken; ?>')
                .then(r => r.json())
                .then(d => {
                    if (d.qr_url) {
                        document.getElementById('qrImage').src = d.qr_url;
                        document.getElementById('qrModal').style.display = 'block';
                    }
                });
        }
    </script>
</body>
</html>
