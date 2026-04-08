<?php
// SerTecApp - Password Reset Controller
// Sistema de recuperación de contraseña

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';

class PasswordResetController {
    private $db;
    private $tokenExpiration = 900; // 15 minutos en segundos
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * POST /auth/request-reset
     * Solicitar reset de contraseña
     */
    public function requestReset() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar email
        Validator::requireValid($data, [
            'email' => 'required|email'
        ]);
        
        // Buscar usuario
        $user = $this->db->fetchOne(
            "SELECT id, email, nombre FROM usuarios WHERE email = ?",
            [$data['email']]
        );
        
        // Por seguridad, siempre responder lo mismo exista o no el usuario
        if (!$user) {
            return Response::success(null, 
                'Si el email existe, recibirás un enlace para restablecer tu contraseña'
            );
        }
        
        // Generar token único
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $this->tokenExpiration);
        
        // Invalidar tokens anteriores del usuario
        $this->db->execute(
            "UPDATE password_resets SET usado = 1 WHERE user_id = ? AND usado = 0",
            [$user['id']]
        );
        
        // Guardar token
        $this->db->insert('password_resets', [
            'user_id' => $user['id'],
            'token' => $token,
            'expires_at' => $expiresAt,
            'usado' => 0
        ]);
        
        // Enviar email (MOCK)
        $resetLink = $this->generateResetLink($token);
        $emailSent = $this->sendResetEmail($user['email'], $user['nombre'], $resetLink);
        
        if (!$emailSent) {
            error_log("Failed to send reset email to: " . $user['email']);
        }
        
        return Response::success(null, 
            'Si el email existe, recibirás un enlace para restablecer tu contraseña'
        );
    }
    
    /**
     * POST /auth/reset
     * Resetear contraseña con token
     */
    public function reset() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar
        Validator::requireValid($data, [
            'token' => 'required',
            'password' => 'required|min:6',
            'password_confirmation' => 'required'
        ]);
        
        // Verificar que las contraseñas coincidan
        if ($data['password'] !== $data['password_confirmation']) {
            return Response::error('Las contraseñas no coinciden', 400);
        }
        
        // Buscar token válido
        $resetRecord = $this->db->fetchOne("
            SELECT * FROM password_resets
            WHERE token = ?
            AND usado = 0
            AND expires_at > NOW()
        ", [$data['token']]);
        
        if (!$resetRecord) {
            return Response::error('Token inválido o expirado', 400);
        }
        
        // Actualizar contraseña
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $this->db->update('usuarios', [
            'password' => $hashedPassword
        ], ['id' => $resetRecord['user_id']]);
        
        // Marcar token como usado
        $this->db->update('password_resets', [
            'usado' => 1
        ], ['id' => $resetRecord['id']]);
        
        // Get user
        $user = $this->db->fetchOne(
            "SELECT id, nombre, email FROM usuarios WHERE id = ?",
            [$resetRecord['user_id']]
        );
        
        return Response::success([
            'user' => $user
        ], 'Contraseña restablecida exitosamente');
    }
    
    /**
     * GET /auth/verify-reset-token/:token
     * Verificar si un token es válido
     */
    public function verifyToken($token) {
        $resetRecord = $this->db->fetchOne("
            SELECT 
                pr.*,
                u.email,
                u.nombre,
                TIMESTAMPDIFF(SECOND, NOW(), pr.expires_at) as seconds_remaining
            FROM password_resets pr
            LEFT JOIN usuarios u ON pr.user_id = u.id
            WHERE pr.token = ?
            AND pr.usado = 0
            AND pr.expires_at > NOW()
        ", [$token]);
        
        if (!$resetRecord) {
            return Response::error('Token inválido o expirado', 400);
        }
        
        return Response::success([
            'valid' => true,
            'email' => $resetRecord['email'],
            'expires_in_seconds' => (int)$resetRecord['seconds_remaining']
        ]);
    }
    
    /**
     * Helper: Generate reset link
     */
    private function generateResetLink($token) {
        $frontendUrl = Env::get('FRONTEND_URL', 'http://localhost:3000');
        return "{$frontendUrl}/reset-password?token={$token}";
    }
    
    /**
     * Helper: Send reset email (MOCK)
     * TODO: Implement real email sending with SMTP/SendGrid/etc
     */
    private function sendResetEmail($email, $nombre, $resetLink) {
        // MOCK: Log email instead of sending
        $logMessage = "
===== PASSWORD RESET EMAIL =====
To: {$email}
Subject: Restablecer contraseña - SerTecApp

Hola {$nombre},

Recibimos una solicitud para restablecer tu contraseña.

Haz clic en el siguiente enlace para crear una nueva contraseña:
{$resetLink}

Este enlace expirará en 15 minutos.

Si no solicitaste este cambio, ignora este email.

Saludos,
El equipo de SerTecApp
================================
";
        
        error_log($logMessage);
        
        // TODO: Implement real email
        // return mail($email, $subject, $body, $headers);
        
        return true; // Mock success
    }
}
