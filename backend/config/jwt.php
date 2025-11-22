<?php
// SerTecApp - JWT Helper

require_once __DIR__ . '/env.php';

class JWT {
    /**
     * Generate JWT token
     * 
     * @param array $payload Data to encode (user_id, email, rol, etc)
     * @param int|null $expiresIn Seconds until expiration (default from .env)
     * @return string JWT token
     */
    public static function encode($payload, $expiresIn = null) {
        $secret = Env::required('JWT_SECRET');
        
        if ($expiresIn === null) {
            $expiresIn = Env::getInt('JWT_EXPIRES_IN', 86400); // 24h default
        }
        
        // Header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        
        // Payload with expiration
        $payload['iat'] = time(); // Issued at
        $payload['exp'] = time() + $expiresIn; // Expiration
        
        // Encode
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        // Signature
        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
        $signatureEncoded = self::base64UrlEncode($signature);
        
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }
    
    /**
     * Decode and validate JWT token
     * 
     * @param string $token JWT token
     * @return array|false Decoded payload or false if invalid
     */
    public static function decode($token) {
        $secret = Env::required('JWT_SECRET');
        
        // Split token
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;
        
        // Verify signature
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $secret, true);
        
        if (!hash_equals($expectedSignature, $signature)) {
            return false; // Signature mismatch - token manipulated
        }
        
        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        
        if (!$payload) {
            return false;
        }
        
        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false; // Token expired
        }
        
        return $payload;
    }
    
    /**
     * Validate token and return payload
     * Throws exception on invalid token
     * 
     * @param string $token JWT token
     * @return array Decoded payload
     * @throws Exception
     */
    public static function validate($token) {
        $payload = self::decode($token);
        
        if ($payload === false) {
            throw new Exception('Token inválido o expirado');
        }
        
        return $payload;
    }
    
    /**
     * Generate refresh token (longer expiration)
     * 
     * @param array $payload Data to encode
     * @return string JWT refresh token
     */
    public static function encodeRefresh($payload) {
        $expiresIn = Env::getInt('JWT_REFRESH_EXPIRES_IN', 604800); // 7 days default
        return self::encode($payload, $expiresIn);
    }
    
    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Extract token from Authorization header
     * 
     * @return string|null Token or null if not found
     */
    public static function getBearerToken() {
        $headers = null;
        
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } elseif (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            // Fallback for nginx
            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if (substr($key, 0, 5) === 'HTTP_') {
                    $header = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                    $headers[$header] = $value;
                }
            }
        }
        
        // Check Authorization header
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        } else {
            return null;
        }
        
        // Extract Bearer token
        if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
