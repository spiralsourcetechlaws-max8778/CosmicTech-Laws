<?php
/**
 * COSMIC OSINT LAB - Enhanced Module Loader
 * PROFESSIONAL EDITION – portable relative paths, no absolute hardcoding.
 */
class EnhancedModuleLoader {
    
    private static $instance = null;
    private $trojan_module_available = false;
    private $trojan_module_error = '';
    private $advanced_module_available = false;
    
    private function __construct() {
        $this->checkTrojanModule();
        $this->checkAdvancedModule();
    }
    
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    
    private function checkTrojanModule() {
        // Relative from public/includes to system/modules
        $base = dirname(__DIR__, 2);
        $path = $base . '/system/modules/TrojanModule.php';
        if (file_exists($path)) {
            require_once $path;
            $this->trojan_module_available = class_exists('EnhancedTrojanModule');
            $this->trojan_module_error = $this->trojan_module_available ? '' : 'EnhancedTrojanModule class not found';
        } else {
            $this->trojan_module_error = 'TrojanModule.php not found at: ' . $path;
        }
    }
    
    private function checkAdvancedModule() {
        $base = dirname(__DIR__, 2);
        $path = $base . '/system/modules/AdvancedPayloadModule.php';
        if (file_exists($path)) {
            require_once $path;
            $this->advanced_module_available = class_exists('AdvancedPayloadModule');
        }
    }
    
    public function isAvailable() { return $this->trojan_module_available; }
    public function getError() { return $this->trojan_module_error; }
    public function isAdvancedAvailable() { return $this->advanced_module_available; }
    
    public function getPayloadTypes() {
        if ($this->trojan_module_available) {
            $module = new EnhancedTrojanModule();
            return $module->get_payload_types();
        }
        return [];
    }
    
    public function getEncryptionMethods() {
        if ($this->trojan_module_available) {
            $module = new EnhancedTrojanModule();
            return $module->get_encryption_methods();
        }
        return ['none' => 'No encryption'];
    }
    
    public function getOutputFormats() {
        if ($this->trojan_module_available) {
            $module = new EnhancedTrojanModule();
            return $module->get_output_formats();
        }
        return ['sh' => 'Shell Script'];
    }
}
