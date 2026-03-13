<?php
/**
 * COSMIC RED TEAM OPERATIONS – MASTER MODULE v5.0
 * Industrial‑grade attack simulation, MITRE integration, interactive matrix,
 * C2 integration, campaign management, and reporting.
 *
 * @package    Cosmic\RedTeam
 * @version    5.0.0
 */
class RedTeamOperations {
    private $db;
    private $ttp_library;
    private $mitre_matrix;
    private $c2Engine;

    public function __construct($useDB = true, $c2Engine = null) {
        $this->initMitreDB();
        $this->loadMITREMatrix();
        $this->loadTTPLibrary();
        if ($useDB) {
            $this->initDatabase();
        }
        if ($c2Engine) {
            $this->c2Engine = $c2Engine;
        }
    }

    private function initDatabase() {
        $dbPath = dirname(__DIR__, 2) . '/data/redteam/campaigns.db';
        $dir = dirname($dbPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $this->db = new SQLite3($dbPath);
        $this->db->exec("CREATE TABLE IF NOT EXISTS campaigns (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            type TEXT,
            target TEXT,
            intensity INTEGER DEFAULT 50,
            start_time INTEGER,
            end_time INTEGER,
            success INTEGER DEFAULT 0,
            detected INTEGER DEFAULT 0,
            mitre_mapping TEXT,
            results TEXT,
            user TEXT DEFAULT 'system',
            tags TEXT DEFAULT ''
        )");
        $this->db->exec("CREATE TABLE IF NOT EXISTS attack_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            campaign_id INTEGER,
            timestamp INTEGER,
            event TEXT,
            details TEXT
        )");
        $this->db->exec("CREATE TABLE IF NOT EXISTS reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            campaign_id INTEGER,
            format TEXT,
            generated_at INTEGER,
            file_path TEXT,
            UNIQUE(campaign_id, format)
        )");
    }

    private function loadMITREMatrix() {
        $this->mitre_matrix = [
            'TA0001' => ['name' => 'Initial Access', 'techniques' => [
                'T1566' => 'Phishing', 'T1190' => 'Exploit Public-Facing App', 'T1133' => 'External Remote Services'
            ]],
            'TA0002' => ['name' => 'Execution', 'techniques' => [
                'T1059' => 'Command & Scripting Interpreter', 'T1106' => 'Native API', 'T1204' => 'User Execution'
            ]],
            'TA0003' => ['name' => 'Persistence', 'techniques' => [
                'T1547' => 'Boot or Logon Autostart', 'T1136' => 'Create Account', 'T1505' => 'Server Software Component'
            ]],
            'TA0004' => ['name' => 'Privilege Escalation', 'techniques' => [
                'T1548' => 'Abuse Elevation Control', 'T1068' => 'Exploitation for Privilege Escalation'
            ]],
            'TA0005' => ['name' => 'Defense Evasion', 'techniques' => [
                'T1562' => 'Impair Defenses', 'T1070' => 'Indicator Removal', 'T1027' => 'Obfuscated Files or Info'
            ]],
            'TA0006' => ['name' => 'Credential Access', 'techniques' => [
                'T1110' => 'Brute Force', 'T1555' => 'Credentials from Password Stores', 'T1003' => 'OS Credential Dumping'
            ]],
            'TA0007' => ['name' => 'Discovery', 'techniques' => [
                'T1083' => 'File & Directory Discovery', 'T1018' => 'Remote System Discovery', 'T1046' => 'Network Service Scanning'
            ]],
            'TA0008' => ['name' => 'Lateral Movement', 'techniques' => [
                'T1021' => 'Remote Services', 'T1091' => 'Replication Through Removable Media'
            ]],
            'TA0009' => ['name' => 'Collection', 'techniques' => [
                'T1560' => 'Archive Collected Data', 'T1113' => 'Screen Capture', 'T1115' => 'Clipboard Data'
            ]],
            'TA0011' => ['name' => 'Command and Control', 'techniques' => [
                'T1071' => 'Application Layer Protocol', 'T1095' => 'Non-Application Layer Protocol', 'T1573' => 'Encrypted Channel'
            ]],
            'TA0010' => ['name' => 'Exfiltration', 'techniques' => [
                'T1048' => 'Exfiltration Over Alternative Protocol', 'T1020' => 'Automated Exfiltration'
            ]],
            'TA0040' => ['name' => 'Impact', 'techniques' => [
                'T1485' => 'Data Destruction', 'T1490' => 'Inhibit System Recovery', 'T1486' => 'Data Encrypted for Impact'
            ]]
        ];
    }

    private function loadTTPLibrary() {
        $this->ttp_library = [
            'phishing' => [
                'technique' => 'T1566',
                'tactic' => 'TA0001',
                'description' => 'Spearphishing Attachment/Link',
                'difficulty' => 'Medium',
                'detection_rate' => 70,
                'tools' => ['Gophish', 'SET', 'King Phisher', 'Evilginx2']
            ],
            'bruteforce' => [
                'technique' => 'T1110',
                'tactic' => 'TA0006',
                'description' => 'Brute Force (SSH, RDP, etc.)',
                'difficulty' => 'Low',
                'detection_rate' => 85,
                'tools' => ['Hydra', 'Medusa', 'Ncrack', 'Crowbar']
            ],
            'sqli' => [
                'technique' => 'T1190',
                'tactic' => 'TA0001',
                'description' => 'SQL Injection',
                'difficulty' => 'Medium',
                'detection_rate' => 60,
                'tools' => ['sqlmap', 'Burp Suite', 'OWASP ZAP']
            ],
            'xss' => [
                'technique' => 'T1059.007',
                'tactic' => 'TA0002',
                'description' => 'Cross‑Site Scripting',
                'difficulty' => 'Medium',
                'detection_rate' => 50,
                'tools' => ['BeEF', 'XSSer', 'Burp Suite']
            ],
            'persistence_registry' => [
                'technique' => 'T1547.001',
                'tactic' => 'TA0003',
                'description' => 'Registry Run Keys',
                'difficulty' => 'Low',
                'detection_rate' => 40,
                'tools' => ['Metasploit', 'PowerShell']
            ],
            'credential_dumping' => [
                'technique' => 'T1003',
                'tactic' => 'TA0006',
                'description' => 'OS Credential Dumping',
                'difficulty' => 'High',
                'detection_rate' => 75,
                'tools' => ['Mimikatz', 'Meterpreter', 'LaZagne']
            ]
        ];
    }

    // ========== C2 INTEGRATION ==========
    public function linkToC2($c2Engine) {
        $this->c2Engine = $c2Engine;
    }

    public function getActiveC2Payloads() {
        if (!$this->c2Engine) return [];
        return $this->c2Engine->getActivePayloads();
    }

    public function linkPayloadToCampaign($campaignId, $payloadUuid) {
        if (!$this->db) return false;
        $campaign = $this->getCampaign($campaignId);
        if (!$campaign) return false;
        $results = json_decode($campaign['results'], true);
        $results['linked_payloads'][] = $payloadUuid;
        $stmt = $this->db->prepare("UPDATE campaigns SET results = ? WHERE id = ?");
        $stmt->bindValue(1, json_encode($results));
        $stmt->bindValue(2, $campaignId, SQLITE3_INTEGER);
        $stmt->execute();
        return true;
    }

    // ========== ATTACK SIMULATION ==========
    public function simulateAttack($scenario) {
        $type = $scenario['type'] ?? 'phishing';
        $target = $scenario['target'] ?? 'unknown';
        $intensity = (int)($scenario['intensity'] ?? 50);
        $campaignName = $scenario['name'] ?? 'Unnamed Campaign';
        
        $ttp = $this->ttp_library[$type] ?? $this->ttp_library['phishing'];
        $success_prob = max(0, min(100, 100 - $ttp['detection_rate'] + ($intensity / 2)));
        $detect_prob = $ttp['detection_rate'] - ($intensity / 3);
        
        $success = rand(0, 100) < $success_prob;
        $detected = rand(0, 100) < $detect_prob;
        
        $mitre = $this->mapAttack([$ttp['technique']]);
        $timeline = $this->generateTimeline($type, $intensity);
        
        $results = [
            'success' => $success,
            'detected' => $detected,
            'techniques_used' => [$ttp['technique']],
            'mitre_mapping' => $mitre,
            'timeline' => $timeline,
            'recommendations' => $this->generateRecommendations($type, $detected),
            'ttp_details' => $ttp
        ];
        
        $campaignId = $this->logCampaign([
            'name' => $campaignName,
            'type' => $type,
            'target' => $target,
            'intensity' => $intensity,
            'success' => $success ? 1 : 0,
            'detected' => $detected ? 1 : 0,
            'mitre_mapping' => json_encode($mitre),
            'results' => json_encode($results)
        ]);
        
        $results['campaign_id'] = $campaignId;
        return $results;
    }

    private function generateTimeline($type, $intensity) {
        $events = [];
        $now = time();
        switch ($type) {
            case 'phishing':
                $events[] = ['time' => date('H:i', $now), 'event' => 'Phishing campaign launched'];
                $events[] = ['time' => date('H:i', $now + 300), 'event' => 'Email delivered to ' . rand(50, 500) . ' targets'];
                $events[] = ['time' => date('H:i', $now + 900), 'event' => rand(1, 20) . ' users clicked link'];
                $events[] = ['time' => date('H:i', $now + 1800), 'event' => rand(0, 5) . ' users submitted credentials'];
                break;
            case 'bruteforce':
                $events[] = ['time' => date('H:i', $now), 'event' => 'Brute force attack started on target'];
                $events[] = ['time' => date('H:i', $now + 600), 'event' => rand(1000, 10000) . ' attempts made'];
                $events[] = ['time' => date('H:i', $now + 1200), 'event' => rand(0, 3) . ' successful logins'];
                break;
            default:
                $events[] = ['time' => date('H:i', $now), 'event' => 'Attack simulation started'];
                $events[] = ['time' => date('H:i', $now + 600), 'event' => 'Payload executed'];
        }
        return $events;
    }

    private function generateRecommendations($type, $detected) {
        $recs = [
            'phishing' => [
                'Implement DMARC/DKIM/SPF',
                'Deploy email security gateway',
                'Conduct user awareness training',
                'Enable MFA for all accounts'
            ],
            'bruteforce' => [
                'Enforce strong password policies',
                'Implement account lockout after failures',
                'Use fail2ban or similar',
                'Enable MFA'
            ],
            'sqli' => [
                'Use parameterized queries / prepared statements',
                'Input validation and sanitization',
                'Web Application Firewall (WAF)'
            ]
        ];
        $base = $recs[$type] ?? ['Review logs and enhance monitoring'];
        if ($detected) {
            $base[] = 'Investigate alerts – potential breach';
        }
        return $base;
    }

    // ========== CAMPAIGN MANAGEMENT ==========
    private function logCampaign($data) {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("INSERT INTO campaigns (name, type, target, intensity, start_time, success, detected, mitre_mapping, results) VALUES (:name, :type, :target, :intensity, :time, :success, :detected, :mitre, :results)");
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':type', $data['type']);
        $stmt->bindValue(':target', $data['target']);
        $stmt->bindValue(':intensity', $data['intensity']);
        $stmt->bindValue(':time', time());
        $stmt->bindValue(':success', $data['success']);
        $stmt->bindValue(':detected', $data['detected']);
        $stmt->bindValue(':mitre', $data['mitre_mapping']);
        $stmt->bindValue(':results', $data['results']);
        $stmt->execute();
        return $this->db->lastInsertRowID();
    }

    public function getCampaigns($limit = 50, $offset = 0) {
        if (!$this->db) return [];
        $res = $this->db->query("SELECT * FROM campaigns ORDER BY start_time DESC LIMIT $limit OFFSET $offset");
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $out[] = $row;
        }
        return $out;
    }

    public function getCampaign($id) {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT * FROM campaigns WHERE id = ?");
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $res = $stmt->execute();
        return $res->fetchArray(SQLITE3_ASSOC);
    }

    public function getRecentCampaigns($limit = 5) {
        return $this->getCampaigns($limit, 0);
    }

    public function deleteCampaign($id) {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM campaigns WHERE id = ?");
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $stmt->execute();
        return true;
    }

    // ========== MITRE INTEGRATION ==========
    public function mapAttack($techniques) {
        $mapping = [];
        foreach ($techniques as $tech) {
            foreach ($this->mitre_matrix as $tactic_id => $tactic) {
                if (isset($tactic['techniques'][$tech])) {
                    $mapping[] = [
                        'technique' => $tech,
                        'technique_name' => $tactic['techniques'][$tech],
                        'tactic' => $tactic['name'],
                        'tactic_id' => $tactic_id
                    ];
                }
            }
        }
        return $mapping;
    }

    public function getFullMitreMatrix() {
        return $this->mitre_matrix;
    }

    public function generateMITREMatrixHTML() {
        $html = '<div class="mitre-matrix" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px,1fr)); gap:15px;">';
        foreach ($this->mitre_matrix as $tactic_id => $tactic) {
            $html .= "<div class='tactic glass-panel' style='padding:15px;'>";
            $html .= "<h4 style='color:#ff00c8; margin:0 0 10px;'>{$tactic_id}: {$tactic['name']}</h4>";
            $html .= "<div class='techniques' style='display:flex; flex-wrap:wrap; gap:5px;'>";
            foreach ($tactic['techniques'] as $tech_id => $tech_name) {
                $html .= "<span class='technique' style='background:rgba(0,255,255,0.1); border:1px solid #0ff; border-radius:12px; padding:4px 8px; font-size:0.8em; cursor:pointer;' title='{$tech_name}' onclick=\"showTechniqueDetails('{$tech_id}')\">{$tech_id}</span>";
            }
            $html .= "</div></div>";
        }
        $html .= '</div>';
        return $html;
    }

    public function generateInteractiveMitreMatrix() {
        // Same as above but with more interactive elements
        return $this->generateMITREMatrixHTML();
    }

    // ========== STATISTICS ==========
    public function getAttackStats() {
        if (!$this->db) return [];
        $stats = [];
        $stats['total_campaigns'] = $this->db->querySingle("SELECT COUNT(*) FROM campaigns");
        $stats['successful'] = $this->db->querySingle("SELECT COUNT(*) FROM campaigns WHERE success = 1");
        $stats['detected'] = $this->db->querySingle("SELECT COUNT(*) FROM campaigns WHERE detected = 1");
        $stats['avg_intensity'] = $this->db->querySingle("SELECT AVG(intensity) FROM campaigns");
        $stats['by_type'] = [];
        $res = $this->db->query("SELECT type, COUNT(*) as count FROM campaigns GROUP BY type");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $stats['by_type'][$row['type']] = $row['count'];
        }
        return $stats;
    }

    // ========== REPORT GENERATION ==========
    public function generateReport($campaignId, $format = 'json') {
        $campaign = $this->getCampaign($campaignId);
        if (!$campaign) return false;
        
        $reportDir = dirname(__DIR__, 2) . '/data/redteam/reports/';
        if (!is_dir($reportDir)) mkdir($reportDir, 0755, true);
        
        $filename = "campaign_{$campaignId}_" . date('Ymd_His') . ".$format";
        $filepath = $reportDir . $filename;
        
        switch ($format) {
            case 'json':
                file_put_contents($filepath, json_encode($campaign, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                $fp = fopen($filepath, 'w');
                fputcsv($fp, array_keys($campaign));
                fputcsv($fp, $campaign);
                fclose($fp);
                break;
            case 'html':
                $html = "<html><body><h1>Campaign Report: {$campaign['name']}</h1><pre>" . print_r($campaign, true) . "</pre></body></html>";
                file_put_contents($filepath, $html);
                break;
            default:
                return false;
        }
        
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO reports (campaign_id, format, generated_at, file_path) VALUES (?, ?, ?, ?)");
        $stmt->bindValue(1, $campaignId, SQLITE3_INTEGER);
        $stmt->bindValue(2, $format, SQLITE3_TEXT);
        $stmt->bindValue(3, time(), SQLITE3_INTEGER);
        $stmt->bindValue(4, $filepath, SQLITE3_TEXT);
        $stmt->execute();
        
        return $filepath;
    }

    public function getReportPath($campaignId, $format = 'json') {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT file_path FROM reports WHERE campaign_id = ? AND format = ?");
        $stmt->bindValue(1, $campaignId, SQLITE3_INTEGER);
        $stmt->bindValue(2, $format, SQLITE3_TEXT);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        return $row ? $row['file_path'] : null;
    }

    private $mitreDb;

    private function initMitreDB() {
        $dbPath = dirname(__DIR__, 2) . '/data/mitre/mitre.db';
        if (file_exists($dbPath)) {
            $this->mitreDb = new SQLite3($dbPath);
        }
    }

    /**
     * Search MITRE techniques.
     */
    public function searchTechniques($query = '', $tactic = '') {
        if (!$this->mitreDb) return [];
        $sql = "SELECT * FROM techniques WHERE 1";
        $params = [];
        if ($query) {
            $sql .= " AND (id LIKE :q1 OR name LIKE :q2)";
            $params['q1'] = "%$query%";
            $params['q2'] = "%$query%";
        }
        if ($tactic) {
            $sql .= " AND tactics LIKE :tactic";
            $params['tactic'] = "%$tactic%";
        }
        $sql .= " ORDER BY id LIMIT 200";
        $stmt = $this->mitreDb->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, SQLITE3_TEXT);
        }
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    /**
     * Get technique details by ID.
     */
    public function getTechnique($id) {
        if (!$this->mitreDb) return null;
        $stmt = $this->mitreDb->prepare("SELECT * FROM techniques WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_TEXT);
        $res = $stmt->execute();
        return $res->fetchArray(SQLITE3_ASSOC);
    }

    /**
     * Get all tactics (unique).
     */
    public function getTactics() {
        if (!$this->mitreDb) return [];
        $res = $this->mitreDb->query("SELECT DISTINCT json_each.value as tactic FROM techniques, json_each(techniques.tactics) ORDER BY tactic");
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row[tactic];
        return $out;
    }

}
