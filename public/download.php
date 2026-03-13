<?php
/**
 * COSMIC OSINT LAB – SECURE PAYLOAD DOWNLOAD (ENHANCED DEBUG)
 * Professional download handler with logging, error reporting, and file verification.
 */
require_once __DIR__ . '/includes/security_functions.php';

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Rate limiting (simple in‑memory)
$rateLimit = 10;
$rateWindow = 60;
$rateFile = sys_get_temp_dir() . '/download_ratelimit_' . md5($_SERVER['REMOTE_ADDR']);
if (file_exists($rateFile)) {
    $data = explode(':', file_get_contents($rateFile));
    if (count($data) == 2 && $data[0] > time() - $rateWindow) {
        if ($data[1] >= $rateLimit) {
            http_response_code(429);
            log_security_event('DOWNLOAD_RATE_LIMIT', ['ip' => $_SERVER['REMOTE_ADDR']]);
            die('Rate limit exceeded. Try again later.');
        }
        $count = $data[1] + 1;
        file_put_contents($rateFile, time() . ':' . $count);
    } else {
        file_put_contents($rateFile, time() . ':1');
    }
} else {
    file_put_contents($rateFile, time() . ':1');
}

$filename = $_GET['file'] ?? '';
if (empty($filename)) {
    http_response_code(400);
    die('No file specified.');
}

// Security: prevent directory traversal
$filename = basename($filename);
$allowed_dirs = [
    dirname(__DIR__) . '/data/payloads/',
    dirname(__DIR__) . '/data/advanced_payloads/',
    dirname(__DIR__) . '/data/keylogs/'
];

$filepath = null;
foreach ($allowed_dirs as $dir) {
    $test = $dir . $filename;
    if (file_exists($test)) {
        $filepath = $test;
        break;
    }
}

if (!$filepath) {
    http_response_code(404);
    log_security_event('DOWNLOAD_FILE_NOT_FOUND', [
        'filename' => $filename,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'searched_dirs' => $allowed_dirs
    ]);
    die('File not found. Checked: ' . implode(', ', $allowed_dirs));
}

// Verify file is readable
if (!is_readable($filepath)) {
    http_response_code(500);
    log_security_event('DOWNLOAD_FILE_NOT_READABLE', ['filename' => $filename, 'ip' => $_SERVER['REMOTE_ADDR']]);
    die('File exists but is not readable. Check permissions.');
}

// Log the download event
log_security_event('PAYLOAD_DOWNLOADED', [
    'filename' => $filename,
    'size' => filesize($filepath),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
]);

// Increment download counter
$counterFile = dirname(__DIR__) . '/data/download_counts.txt';
$counts = [];
if (file_exists($counterFile)) {
    $lines = file($counterFile, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        list($f, $c) = explode('|', $line);
        $counts[$f] = (int)$c;
    }
}
$counts[$filename] = ($counts[$filename] ?? 0) + 1;
$out = [];
foreach ($counts as $f => $c) {
    $out[] = $f . '|' . $c;
}
file_put_contents($counterFile, implode("\n", $out));

// Force download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');
if (!readfile($filepath)) {
    log_security_event('DOWNLOAD_READFILE_FAILED', ['filename' => $filename]);
    die('Failed to read file.');
}
exit;
