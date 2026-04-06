<?php
/**
 * COSMIC OSINT LAB – Unified Navigation (Industrial Grade)
 */
require_once __DIR__ . '/security_functions.php';

if (!function_exists('render_cosmic_navigation')) {
    function render_cosmic_navigation($current = '') {
        $items = [
            'home'      => ['/', '🏠', 'Home'],
            'trojan'    => ['trojan-dashboard.php', '🏴‍☠️', 'Trojan'],
            'advanced'  => ['trojan-dashboard.php?advanced=1', '🧬', 'Advanced'],
            'android'   => ['trojan-dashboard.php?advanced=1&type=android_custom', '📱', 'Android'],
            'c2'        => ['c2-dashboard.php', '🎮', 'C2'],
            'redteam'   => ['redteam-dashboard.php', '🔴', 'Red Team'],
            'phishing'  => ['phishing-dashboard.php', '🎣', 'Phishing'],
            'urlmasker' => [get_url_masker_url(), '🔗', 'URL Masker'],
            'threat'    => ['threat-intel.php', '🛡️', 'Threat Intel'],
            'lab'       => ['lab.php', '🔬', 'Lab'],
        ];
        $html = '<div class="cosmic-nav" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; padding:10px; background:rgba(0,0,0,0.3); border-radius:8px;">';
        foreach ($items as $key => $item) {
            $active = ($current === $key) ? ' style="background:#0ff; color:#000;"' : '';
            $html .= "<a href='{$item[0]}' class='btn'$active>{$item[1]} {$item[2]}</a>";
        }
        $html .= '</div>';
        return $html;
    }
}
