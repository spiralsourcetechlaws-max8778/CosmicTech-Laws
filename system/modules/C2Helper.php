<?php
/**
 * COSMIC C2 – Helper Functions (v2.0)
 * Enhanced with additional utilities and input validation.
 */

if (!function_exists('get_c2_dashboard_url')) {
    /**
     * Get the URL to the C2 dashboard.
     * @return string
     */
    function get_c2_dashboard_url() {
        return get_base_url() . '/c2-dashboard.php';
    }
}

if (!function_exists('get_c2_beacon_url')) {
    /**
     * Get the beacon endpoint URL for a given payload UUID.
     * @param string $uuid Payload UUID
     * @return string
     */
    function get_c2_beacon_url($uuid) {
        return get_base_url() . '/c2/beacon.php?uuid=' . urlencode($uuid);
    }
}

if (!function_exists('get_c2_api_url')) {
    /**
     * Get the C2 API endpoint URL.
     * @return string
     */
    function get_c2_api_url() {
        return get_base_url() . '/c2/api.php';
    }
}

if (!function_exists('generate_tracking_code')) {
    /**
     * Generate a unique tracking code (e.g., for phishing links).
     * @param int $length Bytes of randomness (output length ~ 4/3 * $length)
     * @return string URL-safe base64 encoded string
     */
    function generate_tracking_code($length = 6) {
        return rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');
    }
}

if (!function_exists('get_base_url')) {
    /**
     * Get the base URL of the application.
     * @return string
     */
    function get_base_url() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
}
