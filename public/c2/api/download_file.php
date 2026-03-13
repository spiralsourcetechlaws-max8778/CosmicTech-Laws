<?php
session_start();
if (!isset($_SESSION['c2_user'])) {
    http_response_code(401);
    die('Unauthorized');
}
require_once dirname(__DIR__, 3) . '/system/modules/C2Engine.php';
$id = (int)($_GET['id'] ?? 0);
$c2 = new C2Engine();
$file = $c2->downloadFile(null, $id); // null payload_uuid – we don't restrict by payload here for admin
if (!$file) {
    http_response_code(404);
    die('File not found');
}
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file['filename'] . '"');
header('Content-Length: ' . strlen($file['data']));
echo $file['data'];
