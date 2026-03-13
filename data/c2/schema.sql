-- COSMIC C2 DATABASE SCHEMA
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS payloads (
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
    status TEXT DEFAULT 'active'
);

CREATE TABLE IF NOT EXISTS beacons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payload_uuid TEXT NOT NULL,
    ip TEXT,
    user_agent TEXT,
    hostname TEXT,
    username TEXT,
    os_info TEXT,
    pid INTEGER,
    architecture TEXT,
    beacon_time INTEGER,
    FOREIGN KEY(payload_uuid) REFERENCES payloads(uuid)
);

CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payload_uuid TEXT NOT NULL,
    command TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at INTEGER,
    executed_at INTEGER,
    result TEXT,
    FOREIGN KEY(payload_uuid) REFERENCES payloads(uuid)
);

CREATE TABLE IF NOT EXISTS listeners (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    lhost TEXT,
    lport INTEGER,
    protocol TEXT DEFAULT 'tcp',
    status TEXT DEFAULT 'stopped',
    pid INTEGER,
    created_at INTEGER
);
