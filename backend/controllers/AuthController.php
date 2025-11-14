<?php
// SerTecApp - Auth Controller

class AuthController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            http_response_code(400);
            return json_encode([
                'success' => false,
                'message' => 'Email y password requeridos'
            ]);
        }
        
        $user = $this->db->fetchOne(
            "SELECT * FROM usuarios WHERE email = ? AND activo = 1",
            [$data['email']]
        );
        
        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            http_response_code(401);
            return json_encode([
                'success' => false,
                'message' => 'Credenciales inválidas'
            ]);
        }
        
        // Generate JWT token (simplified)
        $token = $this->generateToken($user);
        
        return json_encode([
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'nombre' => $user['nombre'],
                    'email' => $user['email'],
                    'rol' => $user['rol']
                ]
            ]
        ]);
    }
    
    public function me() {
        $token = $this->getBearerToken();
        
        if (!$token) {
            http_response_code(401);
            return json_encode([
                'success' => false,
                'message' => 'No autenticado'
            ]);
        }
        
        $userId = $this->validateToken($token);
        
        if (!$userId) {
            http_response_code(401);
            return json_encode([
                'success' => false,
                'message' => 'Token inválido'
            ]);
        }
        
        $user = $this->db->fetchOne(
            "SELECT id, nombre, email, rol, activo FROM usuarios WHERE id = ?",
            [$userId]
        );
        
        return json_encode([
            'success' => true,
            'data' => $user
        ]);
    }
    
    private function generateToken($user) {
        // Simplified JWT - en producción usar librería real
        $payload = base64_encode(json_encode([
            'user_id' => $user['id'],
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ]));
        
        return 'Bearer.' . $payload;
    }
    
    private function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 2) return false;
        
        $payload = json_decode(base64_decode($parts[1]), true);
        
        if ($payload['exp'] < time()) return false;
        
        return $payload['user_id'];
    }
    
    private function getBearerToken() {
        $headers = apache_request_headers();
        
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s+(.+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
}
