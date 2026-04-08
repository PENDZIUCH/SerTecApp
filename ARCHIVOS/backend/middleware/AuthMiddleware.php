<?php
// SerTecApp - Authentication Middleware

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthMiddleware {
    /**
     * Request object for storing authenticated user data
     */
    public static $user = null;
    
    /**
     * Authenticate request
     * Validates JWT token and loads user data
     * 
     * @return bool True if authenticated, false otherwise
     */
    public static function authenticate() {
        try {
            // Get token from Authorization header
            $token = JWT::getBearerToken();
            
            if (!$token) {
                self::unauthorized('Token no proporcionado');
                return false;
            }
            
            // Decode and validate token
            $payload = JWT::validate($token);
            
            if (!$payload || !isset($payload['user_id'])) {
                self::unauthorized('Token inválido');
                return false;
            }
            
            // Load user from database
            $db = Database::getInstance();
            $user = $db->fetchOne(
                "SELECT id, nombre, email, rol, activo FROM usuarios WHERE id = ?",
                [$payload['user_id']]
            );
            
            if (!$user) {
                self::unauthorized('Usuario no encontrado');
                return false;
            }
            
            if (!$user['activo']) {
                self::unauthorized('Usuario inactivo');
                return false;
            }
            
            // Store user in request context
            self::$user = $user;
            
            return true;
            
        } catch (Exception $e) {
            error_log('Auth Middleware Error: ' . $e->getMessage());
            self::unauthorized('Error de autenticación');
            return false;
        }
    }
    
    /**
     * Require authentication for route
     * Call this at the start of protected routes
     * Terminates execution if not authenticated
     */
    public static function required() {
        if (!self::authenticate()) {
            exit(); // Stop execution after sending unauthorized response
        }
    }
    
    /**
     * Check if user has specific role
     * 
     * @param string|array $roles Role(s) required (admin, tecnico, etc)
     * @return bool
     */
    public static function hasRole($roles) {
        if (!self::$user) {
            return false;
        }
        
        $roles = is_array($roles) ? $roles : [$roles];
        return in_array(self::$user['rol'], $roles);
    }
    
    /**
     * Require specific role
     * Terminates execution if user doesn't have required role
     * 
     * @param string|array $roles Role(s) required
     */
    public static function requireRole($roles) {
        self::required(); // First check authentication
        
        if (!self::hasRole($roles)) {
            self::forbidden('No tienes permisos para acceder a este recurso');
            exit();
        }
    }
    
    /**
     * Get authenticated user
     * 
     * @return array|null User data or null
     */
    public static function user() {
        return self::$user;
    }
    
    /**
     * Get authenticated user ID
     * 
     * @return int|null User ID or null
     */
    public static function userId() {
        return self::$user ? self::$user['id'] : null;
    }
    
    /**
     * Check if current request is authenticated
     * 
     * @return bool
     */
    public static function check() {
        return self::$user !== null;
    }
    
    /**
     * Optional authentication
     * Tries to authenticate but doesn't fail if token is missing
     * Useful for routes that work differently for authenticated users
     * 
     * @return bool True if authenticated, false if not (but doesn't stop execution)
     */
    public static function optional() {
        try {
            $token = JWT::getBearerToken();
            
            if (!$token) {
                return false;
            }
            
            return self::authenticate();
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Send unauthorized response
     */
    private static function unauthorized($message = 'No autorizado') {
        echo Response::unauthorized($message);
    }
    
    /**
     * Send forbidden response
     */
    private static function forbidden($message = 'Acceso denegado') {
        echo Response::forbidden($message);
    }
    
    /**
     * Refresh token
     * Generate new token for authenticated user
     * 
     * @return string|false New token or false
     */
    public static function refreshToken() {
        if (!self::$user) {
            return false;
        }
        
        $payload = [
            'user_id' => self::$user['id'],
            'email' => self::$user['email'],
            'rol' => self::$user['rol']
        ];
        
        return JWT::encode($payload);
    }
}

/**
 * Global helper function to get authenticated user
 * 
 * @return array|null
 */
function auth_user() {
    return AuthMiddleware::user();
}

/**
 * Global helper to check authentication
 * 
 * @return bool
 */
function is_authenticated() {
    return AuthMiddleware::check();
}
