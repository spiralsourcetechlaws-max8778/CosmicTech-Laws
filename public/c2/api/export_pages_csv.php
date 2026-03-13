<?php
session_start();
if (!isset($_SESSION['c2_user'])) {
    http_response_code(401);
    die('Unauthorized');
}

$pagesDir = dirname(__DIR__, 3) . '/data/payloads/phishing_pages/';
$files = glob($pagesDir . '/*/index.html');
$out = fopen('php://output', 'w');
fputcsv($out, ['Campaign', 'URL', 'Size (bytes)', 'Modified']);

foreach ($files as $f) {
    $campaign = basename(dirname($f));
    fputcsv($out, [
        $campaign,
        '/phishing/pages/' . $campaign . '/',
        filesize($f),
        date('Y-m-d H:i:s', filemtime($f))
    ]);
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="phishing_pages.csv"');
fclose($out);
