<?php
// SerTecApp - Auth Controller

class AuthController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Sanitizar inputs
        $clean = Sanitizer::fields($data, [
            'email' => 'email',
            'password' => 'string'
        ]);
        
        // Validar
        $validator = new Validator($clean);
        $validator->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);
        
        if ($validator->fails()) {
            return Response::validationError($validator->errors());
        }
        
        $user = $this->db->fetchOne(
            "SELECT * FROM usuarios WHERE email = ? AND activo = 1",
            [$clean['email']]
        );
        
        if (!$user || !password_verify($clean['password'], $user['password_hash'])) {
            return Response::unauthorized('Credenciales inválidas');
        }
        
        // Generate JWT token
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'rol' => $user['rol']
        ];
        
        $token = JWT::encode($payload);
        $refreshToken = JWT::encodeRefresh($payload);
        
        return Response::success([
            'token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => Env::getInt('JWT_EXPIRES_IN', 86400),
            'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'rol' => $user['rol']
            ]
        ], 'Login exitoso');
    }
    
    public function me() {
        // Use middleware to authenticate
        AuthMiddleware::required();
        
        $user = AuthMiddleware::user();
        
        return Response::success($user);
    }
    
    public function refresh() {
        // Use refresh token to get new access token
        AuthMiddleware::required();
        
        $newToken = AuthMiddleware::refreshToken();
        
        if (!$newToken) {
            return Response::error('No se pudo generar nuevo token', 500);
        }
        
        return Response::success([
            'token' => $newToken,
            'token_type' => 'Bearer',
            'expires_in' => Env::getInt('JWT_EXPIRES_IN', 86400)
        ], 'Token renovado exitosamente');
    }

}
