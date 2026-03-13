<?php require_once __DIR__ . "/security_functions.php"; ?>
<?php
/**
 * COSMIC OSINT LAB - UNIFIED NAVIGATION SYSTEM v4.1
 * Includes C2 Dashboard and CLI quick links.
 */
function render_navigation($current_page = '') {
    $pages = [
        'home'      => ['/',                '🏠', 'Home'],
        'quicknav'  => ['redteam-quicknav.php', '⚡', 'Quick Nav'],
        'trojan'    => ['trojan-dashboard.php', '🏴‍☠️', 'Trojan Gen'],
        'advanced'  => ['trojan-dashboard.php?advanced=1', '🧬', 'Advanced'],
        'keylogger' => ['trojan-dashboard.php?advanced=1&type=keylogger', '⌨️', 'Keylogger'],
        'android'   => ['trojan-dashboard.php?advanced=1&type=android_custom', '📱', 'Android Trojan'],
        'redteam'   => ['redteam-dashboard.php', '🔴', 'Red Team Master'],
        'ai'        => ['ai-dashboard.php', '🤖', 'AI'],
        'lab'       => ['lab.php', '🔬', 'Lab'],
        'c2'        => ['c2-dashboard.php', '🌀', 'COSMIC C2'],
        'c2cli'     => ['c2-cli.sh', '⚡', 'C2 SOVEREIGN CLI'],
        'phishing'  => ['phishing-dashboard.php', '🎣', 'Phishing'],
        'url-shortener' => ['url-masker.php', '🔗', 'URL Shortener'],
        'doc_payloads' => ['trojan-dashboard.php?advanced=1&type=word_macro', '📄', 'Doc Payloads'],
        'urlmasker' => ['url-masker.php?token=' . get_url_masker_token(), '🔗', 'URL Masker'],
        'api'       => ['cosmic-api.php?module=system&action=status', '⚙️', 'API']
    ];

    $nav_html = '<nav class="main-nav" style="background:rgba(20,20,40,0.95); border-bottom:2px solid #00ffff; padding:10px 0; margin-bottom:20px;">';
    $nav_html .= '<div class="nav-container" style="display:flex; flex-wrap:wrap; justify-content:center; gap:15px;">';

    foreach ($pages as $key => $page) {
        $active = ($current_page === $key) ? 'active' : '';
        $nav_html .= sprintf(
            '<a href="%s" class="nav-item %s" style="color:#00ffff; text-decoration:none; padding:8px 15px; border-radius:5px; background:%s; transition:0.3s;" 
               onmouseover="this.style.background=\'#00ffff\'; this.style.color=\'#000\';" 
               onmouseout="this.style.background=\'rgba(0,255,255,0.1)\'; this.style.color=\'#00ffff\';" 
               title="%s">' .
            '<span style="font-size:1.2em; margin-right:5px;">%s</span><span>%s</span>' .
            '</a>',
            $page[0],
            $active,
            $active === 'active' ? '#00ffff' : 'rgba(0,255,255,0.1)',
            $page[2],
            $page[1],
            $page[2]
        );
    }

    $nav_html .= '</div></nav>';
    return $nav_html;
}

/**
 * Quick link generator – returns URL for common payloads
 */
function quick_link($type, $platform = '', $params = []) {
    $links = [
        'reverse_shell' => [
            'windows' => 'trojan-dashboard.php?type=reverse_shell&platform=windows&lport=4444',
            'linux'   => 'trojan-dashboard.php?type=reverse_shell&platform=linux&lport=4444',
            'android' => 'trojan-dashboard.php?type=reverse_shell&platform=android&lport=4444',
            'macos'   => 'trojan-dashboard.php?type=reverse_shell&platform=macos&lport=4444'
        ],
        'meterpreter' => [
            'windows' => 'trojan-dashboard.php?advanced=1&type=meterpreter_reverse_tcp&platform=windows',
            'linux'   => 'trojan-dashboard.php?advanced=1&type=meterpreter_reverse_tcp&platform=linux',
            'android' => 'trojan-dashboard.php?advanced=1&type=android_backdoor_full&platform=android',
            'macos'   => 'trojan-dashboard.php?advanced=1&type=meterpreter_reverse_tcp&platform=macos'
        ],
        'keylogger' => [
        'android'   => ['trojan-dashboard.php?advanced=1&type=android_custom', '📱', 'Android Trojan'],
            'windows' => 'trojan-dashboard.php?advanced=1&type=keylogger&platform=windows&format=ps1',
            'linux'   => 'trojan-dashboard.php?advanced=1&type=keylogger&platform=linux&format=py',
            'macos'   => 'trojan-dashboard.php?advanced=1&type=keylogger&platform=macos&format=py',
            'android' => 'trojan-dashboard.php?advanced=1&type=keylogger&platform=android&format=py'
        ],
        'persistence' => [
            'windows' => 'trojan-dashboard.php?advanced=1&type=persistence&platform=windows',
            'linux'   => 'trojan-dashboard.php?advanced=1&type=persistence&platform=linux',
            'macos'   => 'trojan-dashboard.php?advanced=1&type=persistence&platform=macos'
        ],
        'test' => 'payload-tester.php'
    ];

    if ($platform && isset($links[$type][$platform])) {
        $url = $links[$type][$platform];
    } elseif (isset($links[$type]) && !is_array($links[$type])) {
        $url = $links[$type];
    } else {
        $url = 'trojan-dashboard.php';
    }

    if (!empty($params)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
    }

    return $url;
}

/**
 * Generate a styled button for quick payload generation
 */
function quick_payload_button($label, $type, $platform, $icon = '⚡', $extra_params = []) {
    $url = quick_link($type, $platform, $extra_params);
    return sprintf(
        '<a href="%s" style="display:inline-block; background:#000; color:#0ff; border:1px solid #0ff; padding:10px 20px; margin:5px; border-radius:5px; text-decoration:none; font-family:monospace; transition:0.3s;" 
           onmouseover="this.style.background=\'#0ff\'; this.style.color=\'#000\';" 
           onmouseout="this.style.background=\'#000\'; this.style.color=\'#0ff\';">' .
        '<span style="font-size:1.2em; margin-right:8px;">%s</span>%s' .
        '</a>',
        $url,
        $icon,
        $label
    );
}
