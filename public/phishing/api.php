<?php
/**
 * Phishing API – retrieve submissions for a campaign.
 */
require_once __DIR__ . '/../includes/security_functions.php';
require_once __DIR__ . '/../../system/modules/phishing/PhishingCampaign.php';

header('Content-Type: application/json');

$campaignId = isset($_GET['campaign']) ? (int)$_GET['campaign'] : 0;
if (!$campaignId) {
    die(json_encode(['error' => 'No campaign ID']));
}

$phish = new PhishingCampaign();
$targets = $phish->getTargets($campaignId, 10000);
$stats = $phish->getStats($campaignId);

echo json_encode([
    'campaign' => $stats['campaign'],
    'targets' => $targets,
    'stats' => $stats
], JSON_PRETTY_PRINT);
