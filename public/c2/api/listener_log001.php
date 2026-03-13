<?php


require_once dirname(__DIR__, 3) . '/system/modules/C2Engine.php';
header('Content-Type: text/plain');

$id = $_GET['id'] ?? 0;
if (!$id) die('No listener ID');

$c2 = new C2Engine();
echo $c2->getListenerLog($id, 100);
