<?php
/**
 * COSMIC C2 – Execute Payload API Endpoint
 * Securely executes a payload file on the local machine and returns output.
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$csrfToken = $_POST['csrf'] ?? '';
if (!validate_csrf_token($csrfToken)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

if (!isset($_POST['filename'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing filename']);
    exit;
}

$filename = sanitize_filename($_POST['filename']);
$payloadDir = dirname(__DIR__, 3) . '/data/payloads/';
$advancedDir = dirname(__DIR__, 3) . '/data/advanced_payloads/';
$filePath = '';

if (file_exists($payloadDir . $filename)) {
    $filePath = $payloadDir . $filename;
} elseif (file_exists($advancedDir . $filename)) {
    $filePath = $advancedDir . $filename;
} else {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'File not found']);
    exit;
}

$extension = pathinfo($filename, PATHINFO_EXTENSION);
$output = '';
$exitCode = -1;

switch (strtolower($extension)) {
    case 'sh':
    case 'bash':
        $cmd = '/bin/bash ' . escapeshellarg($filePath) . ' 2>&1';
        exec($cmd, $outputLines, $exitCode);
        $output = implode("\n", $outputLines);
        break;
    case 'ps1':
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = 'powershell -ExecutionPolicy Bypass -File ' . escapeshellarg($filePath) . ' 2>&1';
        } else {
            $cmd = 'pwsh -File ' . escapeshellarg($filePath) . ' 2>&1';
        }
        exec($cmd, $outputLines, $exitCode);
        $output = implode("\n", $outputLines);
        break;
    case 'py':
    case 'python':
        $cmd = 'python3 ' . escapeshellarg($filePath) . ' 2>&1';
        exec($cmd, $outputLines, $exitCode);
        $output = implode("\n", $outputLines);
        break;
    case 'exe':
    case 'elf':
    case 'bin':
        $cmd = escapeshellarg($filePath) . ' 2>&1';
        exec($cmd, $outputLines, $exitCode);
        $output = implode("\n", $outputLines);
        break;
    default:
        $output = "Unsupported file type for execution.";
        $exitCode = 1;
}

$logDir = dirname(__DIR__, 3) . '/data/logs/';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
$logFile = $logDir . 'payload_execution.log';
$logEntry = sprintf("[%s] User: %s | File: %s | Exit: %d | Output: %s\n",
    date('Y-m-d H:i:s'),
    $_SESSION['user'] ?? 'unknown',
    $filename,
    $exitCode,
    substr($output, 0, 200)
);
file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

echo json_encode([
    'status'    => $exitCode === 0 ? 'success' : 'error',
    'exit_code' => $exitCode,
    'output'    => $output
]);
