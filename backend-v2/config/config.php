<?php
// SerTecApp - Configuration Manager

class Config {
    private static $config = null;
    
    public static function load() {
        if (self::$config !== null) {
            return;
        }
        
        self::$config = [];
        
        // Load from .env file if exists (local development)
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes
                $value = trim($value, '"\'');
                
                self::$config[$key] = $value;
                
                // Also set in $_ENV for consistency
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                }
            }
        }
    }
    
    public static function get($key, $default = null) {
        self::load();
        
        // Priority: Railway env vars > .env file > default
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        if (isset(self::$config[$key])) {
            return self::$config[$key];
        }
        
        return $default;
    }
    
    public static function getRequired($key) {
        $value = self::get($key);
        
        if ($value === null) {
            throw new Exception("Required config key '$key' is missing");
        }
        
        return $value;
    }
    
    public static function isProduction() {
        return self::get('APP_ENV', 'development') === 'production';
    }
    
    public static function isDebug() {
        return filter_var(self::get('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN);
    }
}
