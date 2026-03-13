<?php
/**
 * COSMIC C2 – Listener Log API Endpoint (v2.0)
 * Fetches listener log for dashboard (authenticated, CSRF-protected).
 */

session_start();
require_once dirname(__DIR__, 3) . '/system/modules/C2Engine.php';
require_once dirname(__DIR__, 3) . '/includes/security_functions.php';

header('Content-Type: text/plain');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    die('Unauthorized');
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_GET['csrf'] ?? '';
if (!validate_csrf_token($csrfToken)) {
    http_response_code(403);
    die('Invalid CSRF token');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    die('No valid listener ID');
}

$c2 = new C2Engine();
$log = $c2->getListenerLog($id, 100);

log_security_event('C2_LISTENER_LOG', [
    'listener_id' => $id,
    'user'        => $_SESSION['user'],
    'ip'          => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

echo $log;
