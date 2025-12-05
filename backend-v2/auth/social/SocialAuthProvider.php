<?php
// SerTecApp - Social Auth Base Class
// Base para implementación de proveedores OAuth

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/jwt.php';

abstract class SocialAuthProvider {
    protected $db;
    protected $provider;
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    
    public function __construct($provider) {
        $this->db = Database::getInstance();
        $this->provider = $provider;
        $this->loadConfig();
    }
    
    /**
     * Load configuration from environment
     */
    protected function loadConfig() {
        $prefix = strtoupper($this->provider);
        $this->clientId = Env::get("{$prefix}_CLIENT_ID");
        $this->clientSecret = Env::get("{$prefix}_CLIENT_SECRET");
        $this->redirectUri = Env::get("{$prefix}_REDIRECT_URI");
    }
    
    /**
     * Get authorization URL
     * Must be implemented by each provider
     */
    abstract public function getAuthorizationUrl();
    
    /**
     * Handle OAuth callback
     * Must be implemented by each provider
     */
    abstract public function handleCallback($code);
    
    /**
     * Get user info from provider
     * Must be implemented by each provider
     */
    abstract protected function getUserInfo($accessToken);
    
    /**
     * Link social account to existing user
     */
    public function linkAccount($userId, $providerUserId, $accessToken, $refreshToken = null) {
        // Check if already linked
        $existing = $this->db->fetchOne(
            "SELECT id FROM social_auth WHERE user_id = ? AND provider = ?",
            [$userId, $this->provider]
        );
        
        if ($existing) {
            // Update tokens
            $this->db->update('social_auth', [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_expires_at' => date('Y-m-d H:i:s', time() + 3600)
            ], ['id' => $existing['id']]);
        } else {
            // Create new link
            $this->db->insert('social_auth', [
                'user_id' => $userId,
                'provider' => $this->provider,
                'provider_user_id' => $providerUserId,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_expires_at' => date('Y-m-d H:i:s', time() + 3600)
            ]);
        }
    }
    
    /**
     * Find or create user from social profile
     */
    protected function findOrCreateUser($email, $name, $providerUserId, $accessToken, $refreshToken = null) {
        // Try to find user by email
        $user = $this->db->fetchOne(
            "SELECT * FROM usuarios WHERE email = ?",
            [$email]
        );
        
        if (!$user) {
            // Create new user
            $userId = $this->db->insert('usuarios', [
                'nombre' => $name,
                'email' => $email,
                'password' => null, // No password for social auth users
                'rol' => 'tecnico', // Default role
                'activo' => 1
            ]);
            
            $user = $this->db->fetchOne(
                "SELECT * FROM usuarios WHERE id = ?",
                [$userId]
            );
        }
        
        // Link social account
        $this->linkAccount($user['id'], $providerUserId, $accessToken, $refreshToken);
        
        return $user;
    }
    
    /**
     * Generate JWT for social auth user
     */
    protected function generateJWT($user) {
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'rol' => $user['rol'],
            'social_auth' => true,
            'provider' => $this->provider
        ];
        
        return JWT::encode($payload);
    }
}
