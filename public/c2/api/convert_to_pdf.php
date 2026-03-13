<?php
session_start();
if (!isset($_SESSION['c2_user'])) {
    http_response_code(401);
    die('Unauthorized');
}

$campaign = $_GET['campaign'] ?? '';
if (!$campaign) die('No campaign specified');
$campaign = preg_replace('/[^a-zA-Z0-9_-]/', '', $campaign); // sanitize

$baseDir = dirname(__DIR__, 3) . '/data/payloads/phishing_pages/';
$filepath = $baseDir . $campaign . '/index.html';

if (!file_exists($filepath) || strpos(realpath($filepath), realpath($baseDir)) !== 0) {
    http_response_code(404);
    die('File not found');
}

// Use pdfshift.io API (free tier)
$apiKey = 'your_api_key_here'; // Replace with your key
$html = file_get_contents($filepath);

$post = json_encode([
    'source' => $html,
    'landscape' => false,
    'format' => 'A4'
]);

$ch = curl_init('https://api.pdfshift.io/v3/convert/pdf');
curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ':');
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$pdf = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(500);
    die('PDF conversion failed: ' . $pdf);
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $campaign . '.pdf"');
echo $pdf;
