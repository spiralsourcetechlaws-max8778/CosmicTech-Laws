#!/usr/bin/php
<?php
/**
 * Import MITRE ATT&CK STIX data into SQLite.
 */
$dataDir = __DIR__ . '/../data/mitre/';
$jsonFile = $dataDir . 'enterprise-attack.json';
if (!file_exists($jsonFile)) {
    die("MITRE JSON not found. Run download first.\n");
}

$stix = json_decode(file_get_contents($jsonFile), true);
$objects = $stix['objects'];

$dbPath = $dataDir . 'mitre.db';
$db = new SQLite3($dbPath);
$db->exec("DROP TABLE IF EXISTS techniques");
$db->exec("CREATE TABLE techniques (
    id TEXT PRIMARY KEY,
    name TEXT,
    description TEXT,
    tactics TEXT,
    platforms TEXT,
    mitigations TEXT,
    detection TEXT,
    url TEXT
)");

$stmt = $db->prepare("INSERT INTO techniques (id, name, description, tactics, platforms, url) VALUES (:id, :name, :desc, :tactics, :platforms, :url)");

$count = 0;
foreach ($objects as $obj) {
    if ($obj['type'] === 'attack-pattern') {
        $id = $obj['external_references'][0]['external_id'] ?? $obj['id'];
        $name = $obj['name'];
        $desc = $obj['description'] ?? '';
        $tactics = [];
        $platforms = $obj['x_mitre_platforms'] ?? [];
        if (isset($obj['kill_chain_phases'])) {
            foreach ($obj['kill_chain_phases'] as $phase) {
                if ($phase['kill_chain_name'] === 'mitre-attack') {
                    $tactics[] = $phase['phase_name'];
                }
            }
        }
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':desc', $desc);
        $stmt->bindValue(':tactics', json_encode($tactics));
        $stmt->bindValue(':platforms', json_encode($platforms));
        $stmt->bindValue(':url', 'https://attack.mitre.org/techniques/' . $id);
        $stmt->execute();
        $count++;
    }
}
echo "Imported $count techniques.\n";
