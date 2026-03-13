<?php
header('Content-Type: application/json');
require_once '../includes/security_functions.php';

$action = $_GET['action'] ?? '';

switch($action) {
    case 'check-ip':
        $ip = $_GET['ip'] ?? $_SERVER['REMOTE_ADDR'];
        $ti = new ThreatIntelligence();
        echo json_encode($ti->checkIPReputation($ip));
        break;
        
    case 'geolocation':
        $ip = $_GET['ip'] ?? $_SERVER['REMOTE_ADDR'];
        $ti = new ThreatIntelligence();
        echo json_encode($ti->getGeolocation($ip));
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
