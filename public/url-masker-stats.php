<?php
require_once __DIR__ . '/includes/security_functions.php';
require_once __DIR__ . '/../system/modules/CosmicUrlMasker.php';

$config = require __DIR__ . '/../config/urlmasker.php';
$adminToken = $config['admin_token'] ?? 'cosmic-secret';

$token = $_GET['token'] ?? '';
if ($token !== $adminToken) {
    http_response_code(401);
    die('Unauthorized');
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) die('No link ID');

$masker = new CosmicUrlMasker();
$link = $masker->getLink($id);
if (!$link) die('Link not found');

$clicks = $masker->getLinkStats($id);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Link Statistics</title>
    <link rel="stylesheet" href="/assets/css/cosmic-c2.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body>
    <div style="padding:20px;">
        <h1>Statistics for <?php echo htmlspecialchars($link['code']); ?></h1>
        <p>Target: <?php echo htmlspecialchars($link['target_url']); ?></p>
        <p>Total Clicks: <?php echo count($clicks); ?></p>
        <a href="url-masker.php?token=<?php echo urlencode($adminToken); ?>" class="btn">← Back</a>
        <div style="margin:20px 0;">
            <canvas id="clicksChart" width="400" height="200"></canvas>
        </div>
        <table>
            <thead>
                <tr><th>Time</th><th>IP</th><th>Country</th><th>City</th><th>User Agent</th><th>Referer</th></tr>
            </thead>
            <tbody>
                <?php foreach ($clicks as $c): ?>
                <tr>
                    <td><?php echo date('Y-m-d H:i:s', $c['clicked_at']); ?></td>
                    <td><?php echo $c['ip']; ?></td>
                    <td><?php echo $c['country'] ?: '-'; ?></td>
                    <td><?php echo $c['city'] ?: '-'; ?></td>
                    <td><?php echo htmlspecialchars($c['user_agent']); ?></td>
                    <td><?php echo htmlspecialchars($c['referer']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
        const dates = <?php echo json_encode(array_column($clicks, 'clicked_at')); ?>;
        const counts = dates.reduce((acc, ts) => {
            const day = new Date(ts * 1000).toLocaleDateString();
            acc[day] = (acc[day] || 0) + 1;
            return acc;
        }, {});
        new Chart(document.getElementById('clicksChart'), {
            type: 'line',
            data: {
                labels: Object.keys(counts),
                datasets: [{
                    label: 'Clicks per day',
                    data: Object.values(counts),
                    borderColor: '#0ff',
                    backgroundColor: 'rgba(0,255,255,0.1)'
                }]
            }
        });
    </script>
</body>
</html>
