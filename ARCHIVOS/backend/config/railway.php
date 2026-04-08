<?php
// SerTecApp - Railway Database URL Parser

require_once __DIR__ . '/env.php';

class RailwayConfig {
    /**
     * Parse Railway DATABASE_URL format:
     * mysql://username:password@host:port/database
     */
    public static function parseDatabaseUrl() {
        $databaseUrl = Env::get('DATABASE_URL');
        
        if (!$databaseUrl) {
            return false;
        }
        
        $parsed = parse_url($databaseUrl);
        
        if (!$parsed) {
            return false;
        }
        
        return [
            'host' => $parsed['host'] ?? 'localhost',
            'port' => $parsed['port'] ?? 3306,
            'database' => ltrim($parsed['path'] ?? '', '/'),
            'username' => $parsed['user'] ?? 'root',
            'password' => $parsed['pass'] ?? ''
        ];
    }
    
    /**
     * Get database config from DATABASE_URL or individual env vars
     */
    public static function getDatabaseConfig() {
        $railwayConfig = self::parseDatabaseUrl();
        
        if ($railwayConfig) {
            // Railway o Render detectado
            return [
                'host' => $railwayConfig['host'],
                'port' => $railwayConfig['port'],
                'database' => $railwayConfig['database'],
                'username' => $railwayConfig['username'],
                'password' => $railwayConfig['password']
            ];
        }
        
        // Configuración manual
        return [
            'host' => Env::required('DB_HOST'),
            'port' => Env::getInt('DB_PORT', 3306),
            'database' => Env::required('DB_NAME'),
            'username' => Env::required('DB_USER'),
            'password' => Env::get('DB_PASS', '')
        ];
    }
}
