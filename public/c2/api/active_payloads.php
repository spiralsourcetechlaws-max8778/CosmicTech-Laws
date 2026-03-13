<?php
/**
 * Quick API endpoint for active payloads (used by quicknav)
 */
require_once dirname(__DIR__, 3) . '/system/modules/C2Engine.php';
header('Content-Type: application/json');

$key = $_GET['key'] ?? '';
if ($key !== 'COSMIC-C2-SECRET-2026') {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$c2 = new C2Engine();
$payloads = $c2->getActivePayloads();
echo json_encode(['status' => 'ok', 'data' => $payloads]);
