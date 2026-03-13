<?php
/**
 * COSMIC C2 – Helper Functions
 */
function get_c2_dashboard_url() {
    return '/c2-dashboard.php';
}

function get_c2_beacon_url($uuid) {
    return '/c2/beacon.php?uuid=' . urlencode($uuid);
}

