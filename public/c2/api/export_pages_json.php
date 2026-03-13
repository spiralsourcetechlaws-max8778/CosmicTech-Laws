<?php
session_start();
if (!isset($_SESSION['c2_user'])) {
    http_response_code(401);
    die('Unauthorized');
}

$pagesDir = dirname(__DIR__, 3) . '/data/payloads/phishing_pages/';
$files = glob($pagesDir . '/*/index.html'); // each campaign has its own folder with index.html
$list = [];

foreach ($files as $f) {
    $campaign = basename(dirname($f));
    $list[] = [
        'campaign' => $campaign,
        'url' => '/phishing/pages/' . $campaign . '/',
        'path' => $f,
        'size' => filesize($f),
        'modified' => filemtime($f)
    ];
}

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="phishing_pages.json"');
echo json_encode($list, JSON_PRETTY_PRINT);
