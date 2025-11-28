# 🔐 Social Authentication - SerTecApp

## Estructura Base Lista

Este backend ya tiene la estructura preparada para integrar login social con múltiples proveedores OAuth.

## Proveedores Disponibles

### ✅ Estructura Creada (Listos para configurar)
- **Google OAuth** - `GoogleAuth.php`
- **Facebook Login** - `FacebookAuth.php`

### 📋 Para Agregar en el Futuro
- **Apple Sign In** - Seguir el mismo patrón
- **Microsoft OAuth** - Seguir el mismo patrón

---

## 🚀 Cómo Habilitar un Proveedor

### Ejemplo: Google OAuth

#### 1. Crear Credenciales en Google

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Habilita "Google+ API"
4. Ve a "Credentials" → "Create Credentials" → "OAuth Client ID"
5. Configura:
   - Application type: Web application
   - Authorized redirect URIs: `http://localhost:3000/auth/google/callback`
6. Copia el `Client ID` y `Client Secret`

#### 2. Configurar `.env`

```env
# Google OAuth
GOOGLE_CLIENT_ID=your_client_id_here.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your_client_secret_here
GOOGLE_REDIRECT_URI=http://localhost:3000/auth/google/callback
```

#### 3. Agregar Rutas en `backend/api/index.php`

```php
// Google OAuth Routes
case $path === '/auth/google' && $method === 'GET':
    require_once __DIR__ . '/../auth/social/GoogleAuth.php';
    $auth = new GoogleAuth();
    $url = $auth->getAuthorizationUrl();
    header("Location: $url");
    exit();
    break;

case $path === '/auth/google/callback' && $method === 'GET':
    require_once __DIR__ . '/../auth/social/GoogleAuth.php';
    $auth = new GoogleAuth();
    $code = $_GET['code'] ?? null;
    
    if (!$code) {
        echo Response::error('Authorization code missing');
        break;
    }
    
    try {
        $result = $auth->handleCallback($code);
        echo Response::success($result);
    } catch (Exception $e) {
        echo Response::error('Google auth failed: ' . $e->getMessage());
    }
    break;
```

#### 4. Frontend Integration

```typescript
// En tu frontend (Next.js/React)
const handleGoogleLogin = () => {
  // Redirigir al backend que redirige a Google
  window.location.href = 'http://localhost:8000/api/auth/google';
};

// En la página de callback
const handleCallback = async () => {
  const params = new URLSearchParams(window.location.search);
  const token = params.get('token');
  
  if (token) {
    // Guardar token y redirigir al dashboard
    localStorage.setItem('jwt', token);
    router.push('/dashboard');
  }
};
```

---

## 📊 Base de Datos

La tabla `social_auth` ya está creada en la migración `002_auth_features.sql`.

```sql
CREATE TABLE social_auth (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    provider VARCHAR(50) NOT NULL,      -- 'google', 'facebook', etc
    provider_user_id VARCHAR(255) NOT NULL,
    access_token TEXT,
    refresh_token TEXT,
    token_expires_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_provider_user (provider, provider_user_id)
);
```

---

## 🔄 Flujo de Autenticación Social

```
1. Usuario → Click "Login with Google"
2. Frontend → Redirect a /api/auth/google
3. Backend → Redirect a Google OAuth
4. Usuario → Autoriza en Google
5. Google → Redirect a /api/auth/google/callback?code=XXX
6. Backend → Intercambia code por tokens
7. Backend → Obtiene info del usuario de Google
8. Backend → Busca o crea usuario en BD
9. Backend → Vincula cuenta social
10. Backend → Genera JWT
11. Backend → Devuelve JWT al frontend
12. Frontend → Guarda JWT y redirige a dashboard
```

---

## 🎯 Próximos Pasos para Implementar

1. **Google (Más común)**
   - Seguir pasos arriba
   - Testear flow completo
   - Agregar botón en frontend

2. **Facebook**
   - Crear app en developers.facebook.com
   - Configurar redirect URI
   - Agregar rutas similares

3. **Apple Sign In** (Futuro)
   - Requiere Apple Developer Account
   - Más complejo, pero sigue mismo patrón

4. **Microsoft** (Futuro)
   - Para empresas
   - Azure AD setup

---

## 🛡️ Seguridad

- ✅ Los tokens se guardan encriptados
- ✅ Los tokens expiran automáticamente
- ✅ Se usa HTTPS en producción
- ✅ Los secrets nunca se exponen al frontend
- ✅ Validación de redirect URIs

---

## 📝 Notas

- **Desarrollo**: Usa `http://localhost:3000` en redirect URIs
- **Producción**: Actualiza a tu dominio real (https://app.tudominio.com)
- **Testing**: Usa cuentas de prueba de cada proveedor
- **Rate Limits**: Google limita requests, cachea cuando sea posible

---

## 🚨 Troubleshooting

### Error: "redirect_uri_mismatch"
- Verifica que la URI en .env coincida EXACTAMENTE con la configurada en el proveedor
- Incluye http/https correcto
- No debe tener trailing slash

### Error: "invalid_client"
- Client ID o Secret incorrectos
- Verifica que copiaste completos
- No debe haber espacios

### Usuario sin email
- Algunos proveedores no dan email por default
- Ajusta los scopes solicitados
- Maneja caso de email null

---

**Estado**: ✅ Estructura lista, falta configurar credenciales

**Para habilitar**: Solo configurar .env y agregar rutas
