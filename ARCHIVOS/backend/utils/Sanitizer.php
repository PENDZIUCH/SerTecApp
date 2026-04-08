<?php
// SerTecApp - Input Sanitizer

class Sanitizer {
    /**
     * Sanitize string: trim, remove HTML tags and special chars
     */
    public static function string($value) {
        if ($value === null) return null;
        return htmlspecialchars(trim(strip_tags($value)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize email
     */
    public static function email($value) {
        if ($value === null) return null;
        return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitize integer
     */
    public static function int($value) {
        if ($value === null) return null;
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize float
     */
    public static function float($value) {
        if ($value === null) return null;
        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Sanitize phone: keep only numbers, +, -, (, ), spaces
     */
    public static function phone($value) {
        if ($value === null) return null;
        return preg_replace('/[^0-9+\-() ]/', '', trim($value));
    }
    
    /**
     * Sanitize CUIT/CUIL: keep only numbers and dashes
     */
    public static function cuit($value) {
        if ($value === null) return null;
        return preg_replace('/[^0-9\-]/', '', trim($value));
    }
    
    /**
     * Sanitize URL
     */
    public static function url($value) {
        if ($value === null) return null;
        return filter_var(trim($value), FILTER_SANITIZE_URL);
    }
    
    /**
     * Sanitize boolean: convert to true/false
     */
    public static function bool($value) {
        if ($value === null) return null;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
    
    /**
     * Sanitize array of strings
     */
    public static function array($value) {
        if (!is_array($value)) return [];
        return array_map([self::class, 'string'], $value);
    }
    
    /**
     * Remove SQL injection attempts (complementary to prepared statements)
     */
    public static function sql($value) {
        if ($value === null) return null;
        $value = self::string($value);
        // Remove common SQL keywords from user input
        $dangerous = ['DROP', 'DELETE', 'TRUNCATE', 'EXEC', 'EXECUTE', '--', '/*', '*/', 'xp_', 'sp_'];
        return str_ireplace($dangerous, '', $value);
    }
    
    /**
     * Sanitize fecha: formato YYYY-MM-DD
     */
    public static function date($value) {
        if ($value === null) return null;
        $value = trim($value);
        // Validar formato básico
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }
        return null;
    }
    
    /**
     * Sanitize datetime: formato YYYY-MM-DD HH:MM:SS
     */
    public static function datetime($value) {
        if ($value === null) return null;
        $value = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }
        return null;
    }
    
    /**
     * Sanitize file path: prevent directory traversal
     */
    public static function path($value) {
        if ($value === null) return null;
        $value = trim($value);
        // Remove ../ and ..\
        $value = str_replace(['../', '..\\'], '', $value);
        return $value;
    }
    
    /**
     * Sanitize multiple fields based on rules
     * 
     * Example:
     * $clean = Sanitizer::fields($data, [
     *     'nombre' => 'string',
     *     'email' => 'email',
     *     'telefono' => 'phone'
     * ]);
     */
    public static function fields($data, $rules) {
        $sanitized = [];
        
        foreach ($rules as $field => $type) {
            if (!isset($data[$field])) {
                $sanitized[$field] = null;
                continue;
            }
            
            $value = $data[$field];
            
            switch ($type) {
                case 'string':
                    $sanitized[$field] = self::string($value);
                    break;
                case 'email':
                    $sanitized[$field] = self::email($value);
                    break;
                case 'int':
                    $sanitized[$field] = self::int($value);
                    break;
                case 'float':
                    $sanitized[$field] = self::float($value);
                    break;
                case 'phone':
                    $sanitized[$field] = self::phone($value);
                    break;
                case 'cuit':
                    $sanitized[$field] = self::cuit($value);
                    break;
                case 'url':
                    $sanitized[$field] = self::url($value);
                    break;
                case 'bool':
                    $sanitized[$field] = self::bool($value);
                    break;
                case 'date':
                    $sanitized[$field] = self::date($value);
                    break;
                case 'datetime':
                    $sanitized[$field] = self::datetime($value);
                    break;
                case 'array':
                    $sanitized[$field] = self::array($value);
                    break;
                default:
                    $sanitized[$field] = self::string($value);
            }
        }
        
        return $sanitized;
    }
}
