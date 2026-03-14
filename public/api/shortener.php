<?php
/**
 * COSMIC URL SHORTENER – REST API
 * Endpoints:
 *   POST /api/shortener.php?action=create
 *   GET  /api/shortener.php?action=list
 *   GET  /api/shortener.php?action=get&id=xx
 *   GET  /api/shortener.php?action=stats&id=xx
 *   DELETE /api/shortener.php?action=delete&id=xx
 *   GET  /api/shortener.php?action=qr&code=xx
 */
require_once __DIR__ . '/../includes/security_functions.php';
require_once __DIR__ . '/../../system/modules/CosmicUrlShortener.php';

header('Content-Type: application/json');

// Simple API key auth (same as C2)
$apiKey = $_GET['key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($apiKey !== getenv('C2_API_KEY')) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$shortener = new CosmicUrlShortener();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) $input = $_POST;
            $result = $shortener->createShortLink($input['target_url'], $input);
            echo json_encode(['status' => 'ok', 'data' => $result]);
            break;

        case 'list':
            $limit = (int)($_GET['limit'] ?? 100);
            $offset = (int)($_GET['offset'] ?? 0);
            $links = $shortener->getLinks([], $limit, $offset);
            echo json_encode(['status' => 'ok', 'data' => $links]);
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $link = $shortener->getLink($id);
            if ($link) {
                echo json_encode(['status' => 'ok', 'data' => $link]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Not found']);
            }
            break;

        case 'stats':
            $id = (int)($_GET['id'] ?? 0);
            $from = $_GET['from'] ?? null;
            $to = $_GET['to'] ?? null;
            $clicks = $shortener->getLinkStats($id, $from, $to);
            echo json_encode(['status' => 'ok', 'data' => $clicks]);
            break;

        case 'delete':
            $id = (int)($_GET['id'] ?? 0);
            $shortener->deleteLink($id);
            echo json_encode(['status' => 'ok', 'message' => 'Deleted']);
            break;

        case 'qr':
            $code = $_GET['code'] ?? '';
            if (!$code) {
                echo json_encode(['error' => 'No code']);
                break;
            }
            $url = $shortener->generateQRCode($code);
            echo json_encode(['status' => 'ok', 'qr_url' => $url]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
