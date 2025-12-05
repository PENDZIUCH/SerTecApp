<?php
// SerTecApp - Google OAuth Provider
// Implementation for Google Sign-In

require_once __DIR__ . '/SocialAuthProvider.php';

class GoogleAuth extends SocialAuthProvider {
    private $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
    private $tokenUrl = 'https://oauth2.googleapis.com/token';
    private $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
    
    public function __construct() {
        parent::__construct('google');
    }
    
    /**
     * Get Google authorization URL
     */
    public function getAuthorizationUrl() {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];
        
        return $this->authUrl . '?' . http_build_query($params);
    }
    
    /**
     * Handle OAuth callback from Google
     */
    public function handleCallback($code) {
        // Exchange code for tokens
        $tokens = $this->exchangeCodeForTokens($code);
        
        if (!$tokens || !isset($tokens['access_token'])) {
            throw new Exception('Failed to get access token from Google');
        }
        
        // Get user info
        $userInfo = $this->getUserInfo($tokens['access_token']);
        
        // Find or create user
        $user = $this->findOrCreateUser(
            $userInfo['email'],
            $userInfo['name'],
            $userInfo['id'],
            $tokens['access_token'],
            $tokens['refresh_token'] ?? null
        );
        
        // Generate JWT
        $jwt = $this->generateJWT($user);
        
        return [
            'token' => $jwt,
            'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'rol' => $user['rol']
            ]
        ];
    }
    
    /**
     * Exchange authorization code for access token
     */
    private function exchangeCodeForTokens($code) {
        $postData = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ];
        
        $ch = curl_init($this->tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return null;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Get user info from Google
     */
    protected function getUserInfo($accessToken) {
        $ch = curl_init($this->userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$accessToken}"
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get user info from Google');
        }
        
        return json_decode($response, true);
    }
}

// TODO: To enable Google OAuth:
// 1. Create OAuth credentials at https://console.cloud.google.com/
// 2. Add to .env:
//    GOOGLE_CLIENT_ID=your_client_id
//    GOOGLE_CLIENT_SECRET=your_client_secret
//    GOOGLE_REDIRECT_URI=http://localhost:3000/auth/google/callback
// 3. Add routes in api/index.php
