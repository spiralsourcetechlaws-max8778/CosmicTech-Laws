<?php
require_once dirname(__DIR__, 3) . '/system/modules/C2Engine.php';
header('Content-Type: application/json');

$key = $_GET['key'] ?? '';
if ($key !== getenv('C2_API_KEY')) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$days = (int)($_GET['days'] ?? 7);
if ($days < 1) $days = 7;

$c2 = new C2Engine();
$stats = $c2->getTrafficStats($days);

echo json_encode(['status' => 'ok', 'data' => $stats]);
