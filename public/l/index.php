<?php
/**
 * COSMIC URL MASKER – Redirect Handler
 * Handles /l/xxxx requests.
 */
require_once __DIR__ . '/../includes/security_functions.php';
require_once __DIR__ . '/../../system/modules/CosmicUrlMasker.php';

$code = $_GET['code'] ?? '';
if (!$code) {
    http_response_code(400);
    die('No short code specified.');
}

$masker = new CosmicUrlMasker();
$target = $masker->redirect($code);

if ($target === false) {
    http_response_code(404);
    die('Short link not found or expired.');
}

// Log redirect event
log_security_event('URL_MASKER_REDIRECT', [
    'code' => $code,
    'target' => $target,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

header('Location: ' . $target);
exit;
