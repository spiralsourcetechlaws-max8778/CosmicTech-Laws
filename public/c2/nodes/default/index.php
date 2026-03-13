<?php
/**
 * COSMIC C2 – Sub‑Node Default Landing Page (v2.0)
 * Redirects to the main C2 dashboard nodes view.
 * Includes basic security headers and existence check.
 *
 * File: /public/c2/nodes/default/index.php
 */

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Optional: verify that this node is properly configured
$nodeName = basename(__DIR__);
$nodeConfigFile = __DIR__ . '/config.json';
if (file_exists($nodeConfigFile)) {
    // Node has custom config – could display info instead of redirect
    // For simplicity, we still redirect.
}

// Redirect to main dashboard nodes view
header('Location: /c2-dashboard.php?view=nodes');
exit;
