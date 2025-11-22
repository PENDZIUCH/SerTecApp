# 🔐 Sistema de Autenticación - SerTecApp

## Arquitectura

```
Cliente → Authorization: Bearer <token> → API Router
                                            ↓
                                    AuthMiddleware
                                            ↓
                                    Valida JWT
                                            ↓
                                    Carga User
                                            ↓
                                    Controller
```

---

## Componentes

### 1. JWT Helper (`config/jwt.php`)
**Funcionalidades:**
- `JWT::encode($payload, $expiresIn)` - Generar token
- `JWT::decode($token)` - Decodificar y validar
- `JWT::validate($token)` - Validar (throw exception si inválido)
- `JWT::encodeRefresh($payload)` - Generar refresh token
- `JWT::getBearerToken()` - Extraer token del header

**Seguridad:**
- Algoritmo HS256 (HMAC SHA-256)
- Firma verificable
- Detección de manipulación
- Validación de expiración
- Secret desde .env

---

### 2. AuthMiddleware (`middleware/AuthMiddleware.php`)

**Métodos principales:**

#### `AuthMiddleware::required()`
Protege rutas - detiene ejecución si no autenticado.
```php
public function index() {
    AuthMiddleware::required();
    // Solo continúa si está autenticado
}
```

#### `AuthMiddleware::requireRole($roles)`
Protege por rol - admin, tecnico, etc.
```php
public function dashboard() {
    AuthMiddleware::requireRole('admin');
}
```

#### `AuthMiddleware::optional()`
Auth opcional - no falla si no hay token.
```php
public function productos() {
    AuthMiddleware::optional();
    if (is_authenticated()) {
        // Usuario logueado
    }
}
```

#### `AuthMiddleware::user()`
Obtener datos del usuario autenticado.
```php
$user = AuthMiddleware::user();
// ['id' => 1, 'nombre' => 'Juan', 'email' => '...', 'rol' => 'admin']
```

#### `AuthMiddleware::hasRole($roles)`
Verificar rol manualmente.
```php
if (AuthMiddleware::hasRole('admin')) {
    // Es admin
}
```

**Helpers globales:**
```php
auth_user()         // Retorna user o null
is_authenticated()  // Retorna bool
```

---

## Endpoints de Autenticación

### POST /api/auth/login
**Request:**
```json
{
  "email": "admin@sertecapp.com",
  "password": "admin123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login exitoso",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJh...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "nombre": "Admin",
      "email": "admin@sertecapp.com",
      "rol": "admin"
    }
  }
}
```

### GET /api/auth/me
**Headers:**
```
Authorization: Bearer <token>
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Admin",
    "email": "admin@sertecapp.com",
    "rol": "admin",
    "activo": 1
  }
}
```

### POST /api/auth/refresh
**Headers:**
```
Authorization: Bearer <refresh_token>
```

**Response:**
```json
{
  "success": true,
  "message": "Token renovado exitosamente",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "token_type": "Bearer",
    "expires_in": 86400
  }
}
```

---

## Errores de Autenticación

### 401 Unauthorized
```json
{
  "success": false,
  "message": "No autorizado"
}
```

**Causas:**
- Token no proporcionado
- Token inválido
- Token expirado
- Token manipulado
- Usuario no encontrado
- Usuario inactivo

### 403 Forbidden
```json
{
  "success": false,
  "message": "Acceso denegado"
}
```

**Causas:**
- Usuario sin permisos
- Rol insuficiente

---

## Variables de Entorno

```env
# JWT Configuration
JWT_SECRET=tu_clave_secreta_minimo_32_caracteres
JWT_EXPIRES_IN=86400           # 24 horas
JWT_REFRESH_EXPIRES_IN=604800  # 7 días
```

**Generar JWT_SECRET seguro:**
```bash
# Linux/Mac
openssl rand -base64 32

# Windows PowerShell
[Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Minimum 0 -Maximum 256 }))
```

---

## Uso en Frontend

### Login
```javascript
const login = async (email, password) => {
  const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });
  
  const data = await response.json();
  
  if (data.success) {
    localStorage.setItem('token', data.data.token);
    localStorage.setItem('refresh_token', data.data.refresh_token);
    localStorage.setItem('user', JSON.stringify(data.data.user));
  }
};
```

### Requests autenticados
```javascript
const fetchClientes = async () => {
  const token = localStorage.getItem('token');
  
  const response = await fetch('/api/clientes', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  return response.json();
};
```

### Refresh token automático
```javascript
const refreshToken = async () => {
  const refresh = localStorage.getItem('refresh_token');
  
  const response = await fetch('/api/auth/refresh', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${refresh}`
    }
  });
  
  const data = await response.json();
  
  if (data.success) {
    localStorage.setItem('token', data.data.token);
  }
};
```

---

## Roles del Sistema

- **admin** - Acceso completo
- **tecnico** - Gestión de órdenes y clientes
- **supervisor** - Vista y reportes
- **cliente** - Vista limitada de sus órdenes

---

## Testing con cURL

### Login
```bash
curl -X POST http://localhost/sertecapp/backend/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@sertecapp.com","password":"admin123"}'
```

### Request autenticado
```bash
curl http://localhost/sertecapp/backend/api/auth/me \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

---

## Seguridad

✅ **Implementado:**
- JWT con firma HMAC SHA-256
- Tokens expirables
- Validación de firma (anti-manipulación)
- Refresh tokens (larga duración)
- Password hashing (bcrypt)
- Rate limiting ready (.env)
- Logs de errores
- Usuario activo/inactivo

⚠️ **Recomendaciones producción:**
- HTTPS obligatorio
- Rotar JWT_SECRET periódicamente
- Implementar blacklist de tokens
- Rate limiting activo
- Logs centralizados
- 2FA (futuro)
