<?php
/**
 * COSMIC URL MASKER v3.0 – ENTERPRISE INDUSTRIAL GRADE
 * Features:
 *   - Full REST API (with token auth)
 *   - QR code generation
 *   - GeoIP tracking (MaxMind)
 *   - Webhook notifications on click
 *   - Link expiration, password protection, max clicks
 *   - UTM parameter appending
 *   - Custom domains per link
 *   - Bulk import / export
 *   - Click statistics (country, city, ASN)
 *   - Rate limiting per IP
 *   - Admin dashboard with token authentication (no C2 required)
 */
class CosmicUrlMasker {
    private $db;
    private $config;
    private $geoipReader = null;

    public function __construct($config = []) {
        $this->config = array_merge([
            'db_path'       => dirname(__DIR__, 2) . '/data/urlmasker/urlmasker.db',
            'base_url'      => $this->getBaseUrl(),
            'code_length'   => 6,
            'code_chars'    => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'geoip_db'      => dirname(__DIR__, 2) . '/data/geoip/GeoLite2-City.mmdb',
            'enable_geoip'  => file_exists(dirname(__DIR__, 2) . '/data/geoip/GeoLite2-City.mmdb'),
            'rate_limit'    => 10,         // clicks per minute per IP
            'admin_token'   => 'cosmic-secret',  // change this in production!
        ], $config);

        $this->connect();
        $this->initGeoIP();
    }

    private function connect() {
        try {
            $this->db = new SQLite3($this->config['db_path']);
            $this->db->busyTimeout(5000);
        } catch (Exception $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    private function initGeoIP() {
        if ($this->config['enable_geoip'] && class_exists('GeoIp2\Database\Reader')) {
            try {
                $this->geoipReader = new GeoIp2\Database\Reader($this->config['geoip_db']);
            } catch (Exception $e) {
                error_log('GeoIP init failed: ' . $e->getMessage());
            }
        }
    }

    private function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . '/l/';
    }

    private function generateCode($length = null) {
        $length = $length ?? $this->config['code_length'];
        $chars = $this->config['code_chars'];
        $max = strlen($chars) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $max)];
        }
        return $code;
    }

    public function isCodeAvailable($code) {
        $stmt = $this->db->prepare("SELECT id FROM short_links WHERE code = :code");
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        $res = $stmt->execute();
        return !$res->fetchArray();
    }

    /**
     * Create a short link with advanced options.
     */
    public function createShortLink($targetUrl, $options = []) {
        if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid target URL');
        }

        $defaults = [
            'custom_code'   => null,
            'expires_in'    => 0,
            'campaign_id'   => null,
            'creator'       => null,
            'notes'         => null,
            'password'      => '',
            'max_clicks'    => 0,
            'utm_source'    => '',
            'utm_medium'    => '',
            'utm_campaign'  => '',
            'custom_domain' => '',
            'webhook_url'   => '',
            'title'         => '',
        ];
        $opts = array_merge($defaults, $options);

        // Handle custom code
        if ($opts['custom_code']) {
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $opts['custom_code'])) {
                throw new InvalidArgumentException('Custom code can only contain letters, numbers, underscore, hyphen');
            }
            if (!$this->isCodeAvailable($opts['custom_code'])) {
                throw new RuntimeException('Custom code already taken');
            }
            $code = $opts['custom_code'];
        } else {
            do {
                $code = $this->generateCode();
            } while (!$this->isCodeAvailable($code));
        }

        $expires_at = $opts['expires_in'] ? time() + $opts['expires_in'] : null;

        $stmt = $this->db->prepare("
            INSERT INTO short_links 
            (code, target_url, campaign_id, created_by, created_at, expires_at, notes, password, max_clicks,
             utm_source, utm_medium, utm_campaign, custom_domain, webhook_url, title)
            VALUES (:code, :url, :campaign, :creator, :time, :expires, :notes, :pass, :max,
                    :usource, :umedium, :ucampaign, :cdomain, :webhook, :title)
        ");
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        $stmt->bindValue(':url', $targetUrl, SQLITE3_TEXT);
        $stmt->bindValue(':campaign', $opts['campaign_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':creator', $opts['creator'], SQLITE3_TEXT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':expires', $expires_at, SQLITE3_INTEGER);
        $stmt->bindValue(':notes', $opts['notes'], SQLITE3_TEXT);
        $stmt->bindValue(':pass', $opts['password'], SQLITE3_TEXT);
        $stmt->bindValue(':max', $opts['max_clicks'], SQLITE3_INTEGER);
        $stmt->bindValue(':usource', $opts['utm_source'], SQLITE3_TEXT);
        $stmt->bindValue(':umedium', $opts['utm_medium'], SQLITE3_TEXT);
        $stmt->bindValue(':ucampaign', $opts['utm_campaign'], SQLITE3_TEXT);
        $stmt->bindValue(':cdomain', $opts['custom_domain'], SQLITE3_TEXT);
        $stmt->bindValue(':webhook', $opts['webhook_url'], SQLITE3_TEXT);
        $stmt->bindValue(':title', $opts['title'], SQLITE3_TEXT);
        $stmt->execute();

        $id = $this->db->lastInsertRowID();
        $shortUrl = ($opts['custom_domain'] ?: $this->config['base_url']) . $code;

        return [
            'id' => $id,
            'code' => $code,
            'short_url' => $shortUrl,
            'target_url' => $targetUrl,
        ];
    }

    /**
     * Redirect a short code to its target and log the click.
     */
    public function redirect($code, $password = null) {
        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $this->checkRateLimit($ip);

        $stmt = $this->db->prepare("SELECT * FROM short_links WHERE code = :code");
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        $res = $stmt->execute();
        $link = $res->fetchArray(SQLITE3_ASSOC);
        if (!$link) {
            return false;
        }

        // Check expiration
        if ($link['expires_at'] && $link['expires_at'] < time()) {
            return false;
        }
        // Check max clicks
        if ($link['max_clicks'] > 0 && $link['clicks'] >= $link['max_clicks']) {
            return false;
        }
        // Check password
        if (!empty($link['password']) && $password !== $link['password']) {
            return false;
        }

        // Get geo data
        $geo = $this->getGeoIP($ip);

        // Log click with geo info
        $stmt = $this->db->prepare("
            INSERT INTO clicks (link_id, ip, user_agent, referer, clicked_at, country, city, latitude, longitude, asn)
            VALUES (:link_id, :ip, :ua, :ref, :time, :country, :city, :lat, :lon, :asn)
        ");
        $stmt->bindValue(':link_id', $link['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmt->bindValue(':ua', $_SERVER['HTTP_USER_AGENT'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':ref', $_SERVER['HTTP_REFERER'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':country', $geo['country'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':city', $geo['city'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':lat', $geo['latitude'] ?? 0, SQLITE3_FLOAT);
        $stmt->bindValue(':lon', $geo['longitude'] ?? 0, SQLITE3_FLOAT);
        $stmt->bindValue(':asn', $geo['asn'] ?? '', SQLITE3_TEXT);
        $stmt->execute();

        // Update click count and last clicked
        $this->db->exec("UPDATE short_links SET clicks = clicks + 1, last_clicked = " . time() . " WHERE id = " . $link['id']);

        // Trigger webhook if set
        if (!empty($link['webhook_url'])) {
            $this->fireWebhook($link, $geo);
        }

        // Append UTM parameters
        $target = $link['target_url'];
        $params = [];
        if (!empty($link['utm_source'])) $params['utm_source'] = $link['utm_source'];
        if (!empty($link['utm_medium'])) $params['utm_medium'] = $link['utm_medium'];
        if (!empty($link['utm_campaign'])) $params['utm_campaign'] = $link['utm_campaign'];
        if (!empty($params)) {
            $separator = (strpos($target, '?') === false) ? '?' : '&';
            $target .= $separator . http_build_query($params);
        }

        return $target;
    }

    private function checkRateLimit($ip) {
        if ($this->config['rate_limit'] <= 0) return;
        $date = date('Y-m-d H:i'); // minute granularity
        $stmt = $this->db->prepare("SELECT count FROM rate_limits WHERE ip = :ip AND minute = :minute");
        $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
        $stmt->bindValue(':minute', $date, SQLITE3_TEXT);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        $count = $row ? $row['count'] : 0;

        if ($count >= $this->config['rate_limit']) {
            throw new RuntimeException('Rate limit exceeded. Try later.');
        }

        if ($row) {
            $this->db->exec("UPDATE rate_limits SET count = count + 1 WHERE ip = '$ip' AND minute = '$date'");
        } else {
            $stmt = $this->db->prepare("INSERT INTO rate_limits (ip, minute, count) VALUES (:ip, :minute, 1)");
            $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
            $stmt->bindValue(':minute', $date, SQLITE3_TEXT);
            $stmt->execute();
        }
    }

    private function getGeoIP($ip) {
        if (!$this->geoipReader || $ip === '127.0.0.1' || $ip === '::1') {
            return ['country' => 'Local', 'city' => 'Localhost'];
        }
        try {
            $record = $this->geoipReader->city($ip);
            return [
                'country'   => $record->country->name,
                'city'      => $record->city->name,
                'latitude'  => $record->location->latitude,
                'longitude' => $record->location->longitude,
                'asn'       => $record->traits->autonomousSystemNumber ?? '',
            ];
        } catch (Exception $e) {
            return ['country' => 'Unknown', 'city' => 'Unknown'];
        }
    }

    private function fireWebhook($link, $geo) {
        $payload = json_encode([
            'event'    => 'click',
            'link_id'  => $link['id'],
            'code'     => $link['code'],
            'target'   => $link['target_url'],
            'ip'       => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'geo'      => $geo,
            'time'     => time(),
        ]);
        $ch = curl_init($link['webhook_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    // ========== API METHODS ==========

    public function getLinks($filters = [], $limit = 100, $offset = 0) {
        $sql = "SELECT * FROM short_links";
        $where = [];
        $params = [];
        if (isset($filters['campaign_id'])) {
            $where[] = "campaign_id = :cid";
            $params[':cid'] = $filters['campaign_id'];
        }
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, SQLITE3_INTEGER);
        }
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $links = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $links[] = $row;
        return $links;
    }

    public function getLink($id) {
        $stmt = $this->db->prepare("SELECT * FROM short_links WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $res = $stmt->execute();
        return $res->fetchArray(SQLITE3_ASSOC);
    }

    public function getLinkStats($linkId, $from = null, $to = null) {
        $sql = "SELECT * FROM clicks WHERE link_id = :link_id";
        $params = [':link_id' => $linkId];
        if ($from) {
            $sql .= " AND clicked_at >= :from";
            $params[':from'] = $from;
        }
        if ($to) {
            $sql .= " AND clicked_at <= :to";
            $params[':to'] = $to;
        }
        $sql .= " ORDER BY clicked_at DESC";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? SQLITE3_INTEGER : SQLITE3_TEXT);
        }
        $res = $stmt->execute();
        $clicks = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $clicks[] = $row;
        return $clicks;
    }

    public function deleteLink($linkId) {
        $stmt = $this->db->prepare("DELETE FROM short_links WHERE id = :id");
        $stmt->bindValue(':id', $linkId, SQLITE3_INTEGER);
        $stmt->execute();
        return true;
    }

    public function updateLink($id, $data) {
        $allowed = ['target_url', 'password', 'max_clicks', 'expires_at', 'webhook_url', 'utm_source', 'utm_medium', 'utm_campaign', 'title', 'notes'];
        $sets = [];
        $params = [':id' => $id];
        foreach ($data as $k => $v) {
            if (in_array($k, $allowed)) {
                $sets[] = "$k = :$k";
                $params[":$k"] = $v;
            }
        }
        if (empty($sets)) return false;
        $sql = "UPDATE short_links SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, SQLITE3_TEXT);
        }
        $stmt->execute();
        return true;
    }

    public function exportStats($linkId, $format = 'csv') {
        $clicks = $this->getLinkStats($linkId);
        if ($format === 'csv') {
            $out = fopen('php://temp', 'w');
            fputcsv($out, ['Time', 'IP', 'Country', 'City', 'User Agent', 'Referer', 'ASN']);
            foreach ($clicks as $c) {
                fputcsv($out, [
                    date('Y-m-d H:i:s', $c['clicked_at']),
                    $c['ip'],
                    $c['country'],
                    $c['city'],
                    $c['user_agent'],
                    $c['referer'],
                    $c['asn']
                ]);
            }
            rewind($out);
            return stream_get_contents($out);
        } elseif ($format === 'json') {
            return json_encode($clicks, JSON_PRETTY_PRINT);
        }
        return '';
    }

    public function generateQRCode($code, $size = 150) {
        $shortUrl = $this->config['base_url'] . $code;
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($shortUrl);
    }

    public function bulkCreate($urls) {
        $results = [];
        foreach ($urls as $url) {
            try {
                $results[] = $this->createShortLink($url);
            } catch (Exception $e) {
                $results[] = ['error' => $e->getMessage(), 'url' => $url];
            }
        }
        return $results;
    }

    // ========== ADMIN AUTH ==========
    public function checkAdminToken($token) {
        return $token === $this->config['admin_token'];
    }
}
