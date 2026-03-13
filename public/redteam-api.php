<?php
require_once __DIR__ . '/includes/security_functions.php';
require_once __DIR__ . '/../system/modules/RedTeamOperations.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
if ($action === 'technique') {
    $techId = $_GET['id'] ?? '';
    // In production, load from MITRE JSON. Mock data for now.
    $details = [
        'T1566' => [
            'name' => 'Phishing',
            'description' => 'Adversaries may send phishing messages to gain access to victim systems.',
            'mitigations' => 'User training, email filtering, MFA.',
            'detection' => 'Monitor for suspicious emails and login attempts.'
        ],
        'T1059' => [
            'name' => 'Command and Scripting Interpreter',
            'description' => 'Adversaries may abuse command and script interpreters to execute commands.',
            'mitigations' => 'Application whitelisting, restrict PowerShell.',
            'detection' => 'Monitor process creation and script block logs.'
        ],
        // Add more as needed
    ];
    if (isset($details[$techId])) {
        echo json_encode($details[$techId]);
    } elseif ($action === 'search_mitre') {
        require_once __DIR__ . '/../system/modules/RedTeamOperations.php';
        $red = new RedTeamOperations();
        $query = $_GET['q'] ?? '';
        $tactic = $_GET['tactic'] ?? '';
        echo json_encode($red->searchTechniques($query, $tactic));
    } elseif ($action === 'get_technique') {
        require_once __DIR__ . '/../system/modules/RedTeamOperations.php';
        $red = new RedTeamOperations();
        $id = $_GET['id'] ?? '';
        if ($id) {
            echo json_encode($red->getTechnique($id));
        } else {
            echo json_encode(['error' => 'Missing ID']);
        }

    } else {
        echo json_encode(['name' => $techId, 'description' => 'No details available.', 'mitigations' => '', 'detection' => '']);
    }
    } elseif ($action === 'search_mitre') {
        require_once __DIR__ . '/../system/modules/RedTeamOperations.php';
        $red = new RedTeamOperations();
        $query = $_GET['q'] ?? '';
        $tactic = $_GET['tactic'] ?? '';
        echo json_encode($red->searchTechniques($query, $tactic));
    } elseif ($action === 'get_technique') {
        require_once __DIR__ . '/../system/modules/RedTeamOperations.php';
        $red = new RedTeamOperations();
        $id = $_GET['id'] ?? '';
        if ($id) {
            echo json_encode($red->getTechnique($id));
        } else {
            echo json_encode(['error' => 'Missing ID']);
        }

} else {
    echo json_encode(['error' => 'Invalid action']);
}
