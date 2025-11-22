<?php
// SerTecApp - Environment Configuration Loader

class Env {
    private static $vars = [];
    private static $loaded = false;
    
    public static function load($path = null) {
        if (self::$loaded) return;
        
        if ($path === null) {
            $path = __DIR__ . '/../.env';
        }
        
        if (!file_exists($path)) {
            // En producción (Railway) usar variables de entorno del sistema
            self::$vars = $_ENV;
            self::$loaded = true;
            return;
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) continue;
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes
                $value = trim($value, '"\'');
                
                self::$vars[$key] = $value;
                
                // También setear en $_ENV para compatibilidad
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
        
        self::$loaded = true;
    }
    
    public static function get($key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        // Prioridad: self::$vars > $_ENV > getenv() > default
        if (isset(self::$vars[$key])) {
            return self::$vars[$key];
        }
        
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        return $default;
    }
    
    public static function getInt($key, $default = 0) {
        return (int) self::get($key, $default);
    }
    
    public static function getBool($key, $default = false) {
        $value = self::get($key, $default);
        
        if (is_bool($value)) return $value;
        
        return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
    }
    
    public static function required($key) {
        $value = self::get($key);
        
        if ($value === null || $value === '') {
            throw new Exception("Environment variable '$key' is required but not set");
        }
        
        return $value;
    }
}
