<?php
// SerTecApp - Response Helper

class Response {
    /**
     * Send success JSON response
     */
    public static function success($data = null, $message = null, $code = 200) {
        http_response_code($code);
        
        $response = ['success' => true];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return json_encode($response);
    }
    
    /**
     * Send error JSON response
     */
    public static function error($message, $code = 400, $errors = null) {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        // Log error in production
        if (Env::get('APP_ENV') === 'production') {
            error_log("API Error [{$code}]: {$message}");
        }
        
        return json_encode($response);
    }
    
    /**
     * Send validation error response
     */
    public static function validationError($errors) {
        return self::error('Errores de validación', 422, $errors);
    }
    
    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'No autorizado') {
        return self::error($message, 401);
    }
    
    /**
     * Send forbidden response
     */
    public static function forbidden($message = 'Acceso denegado') {
        return self::error($message, 403);
    }
    
    /**
     * Send not found response
     */
    public static function notFound($message = 'Recurso no encontrado') {
        return self::error($message, 404);
    }
    
    /**
     * Send server error response
     */
    public static function serverError($message = 'Error interno del servidor') {
        return self::error($message, 500);
    }
}
