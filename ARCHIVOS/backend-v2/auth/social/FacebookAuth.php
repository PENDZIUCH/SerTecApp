<?php
// SerTecApp - Facebook OAuth Provider
// Implementation for Facebook Login

require_once __DIR__ . '/SocialAuthProvider.php';

class FacebookAuth extends SocialAuthProvider {
    private $authUrl = 'https://www.facebook.com/v18.0/dialog/oauth';
    private $tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token';
    private $userInfoUrl = 'https://graph.facebook.com/v18.0/me';
    
    public function __construct() {
        parent::__construct('facebook');
    }
    
    /**
     * Get Facebook authorization URL
     */
    public function getAuthorizationUrl() {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'email,public_profile',
            'response_type' => 'code'
        ];
        
        return $this->authUrl . '?' . http_build_query($params);
    }
    
    /**
     * Handle OAuth callback from Facebook
     */
    public function handleCallback($code) {
        // Exchange code for token
        $tokens = $this->exchangeCodeForTokens($code);
        
        if (!$tokens || !isset($tokens['access_token'])) {
            throw new Exception('Failed to get access token from Facebook');
        }
        
        // Get user info
        $userInfo = $this->getUserInfo($tokens['access_token']);
        
        // Find or create user
        $user = $this->findOrCreateUser(
            $userInfo['email'] ?? '',
            $userInfo['name'],
            $userInfo['id'],
            $tokens['access_token'],
            null
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
     * Exchange code for access token
     */
    private function exchangeCodeForTokens($code) {
        $url = $this->tokenUrl . '?' . http_build_query([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
    
    /**
     * Get user info from Facebook
     */
    protected function getUserInfo($accessToken) {
        $url = $this->userInfoUrl . '?' . http_build_query([
            'fields' => 'id,name,email,picture',
            'access_token' => $accessToken
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

// TODO: To enable Facebook OAuth:
// 1. Create app at https://developers.facebook.com/
// 2. Add to .env:
//    FACEBOOK_CLIENT_ID=your_app_id
//    FACEBOOK_CLIENT_SECRET=your_app_secret
//    FACEBOOK_REDIRECT_URI=http://localhost:3000/auth/facebook/callback
