# 🔐 Auth - Autenticación

Endpoints para login, gestión de tokens y recuperación de contraseña.

---

## 📋 Tabla de Contenidos

- [POST /auth/login](#post-authlogin) - Login
- [GET /auth/me](#get-authme) - Usuario actual
- [POST /auth/refresh](#post-authrefresh) - Renovar token
- [POST /auth/logout](#post-authlogout) - Cerrar sesión
- [POST /auth/request-reset](#post-authrequest-reset) - Solicitar reset de contraseña
- [POST /auth/reset](#post-authreset) - Resetear contraseña
- [GET /auth/verify-reset-token/:token](#get-authverify-reset-tokentoken) - Verificar token de reset

---

## POST /auth/login

Autenticar usuario y obtener tokens JWT.

### Request

```http
POST /api/auth/login
Content-Type: application/json
```

```json
{
  "email": "admin@sertecapp.com",
  "password": "admin123"
}
```

### Validaciones

- `email`: requerido, formato email válido
- `password`: requerido, mínimo 6 caracteres

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "nombre": "Admin",
      "email": "admin@sertecapp.com",
      "rol": "admin"
    }
  },
  "message": "Login exitoso"
}
```

### Response 401 - Credenciales Inválidas

```json
{
  "success": false,
  "message": "Credenciales inválidas"
}
```

### Response 422 - Validación Fallida

```json
{
  "success": false,
  "message": "Errores de validación",
  "errors": {
    "email": ["El campo email es requerido"],
    "password": ["El campo password debe tener al menos 6 caracteres"]
  }
}
```

### Notas

- El token expira en 24 horas (configurable en `.env`)
- El refresh token expira en 7 días
- Guardar ambos tokens en el cliente
- Usar `refresh_token` cuando `token` expire

---

## GET /auth/me

Obtener información del usuario autenticado.

### Request

```http
GET /api/auth/me
Authorization: Bearer {token}
```

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "id": 1,
    "nombre": "Admin",
    "email": "admin@sertecapp.com",
    "rol": "admin",
    "activo": true
  }
}
```

### Response 401 - No Autenticado

```json
{
  "success": false,
  "message": "Token no proporcionado"
}
```

### Notas

- Útil para verificar si el token sigue válido
- Usar en el inicio de la aplicación para cargar usuario

---

## POST /auth/refresh

Renovar access token usando refresh token.

### Request

```http
POST /api/auth/refresh
Authorization: Bearer {refresh_token}
```

Sin body requerido.

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGci...",
    "expires_in": 86400
  }
}
```

### Response 401 - Token Inválido

```json
{
  "success": false,
  "message": "Token inválido o expirado"
}
```

### Notas

- Llamar automáticamente cuando el token principal expira (401)
- Actualizar el token guardado con el nuevo
- Si refresh también falla → redirigir a login

---

## POST /auth/logout

Cerrar sesión (invalidar token).

### Request

```http
POST /api/auth/logout
Authorization: Bearer {token}
```

Sin body requerido.

### Response 200 - Éxito

```json
{
  "success": true,
  "message": "Sesión cerrada correctamente"
}
```

### Notas

- Eliminar tokens del cliente después de logout
- Redirigir a página de login
- **Estado actual:** Endpoint implementado pero token no se invalida en servidor (stateless JWT)

---

## POST /auth/request-reset

Solicitar reset de contraseña (envía email con token).

### Request

```http
POST /api/auth/request-reset
Content-Type: application/json
```

```json
{
  "email": "usuario@example.com"
}
```

### Validaciones

- `email`: requerido, formato válido

### Response 200 - Siempre Éxito

```json
{
  "success": true,
  "message": "Si el email existe, recibirás un enlace para restablecer tu contraseña"
}
```

**⚠️ Importante:** Por seguridad, siempre responde lo mismo exista o no el email.

### Notas

- Token de reset expira en **15 minutos**
- Email incluye link: `{FRONTEND_URL}/reset-password?token=XXX`
- **Estado actual:** Mock - emails se loggean en lugar de enviarse
- Tokens anteriores del usuario se invalidan automáticamente

---

## POST /auth/reset

Resetear contraseña usando token recibido por email.

### Request

```http
POST /api/auth/reset
Content-Type: application/json
```

```json
{
  "token": "abc123...",
  "password": "nuevaPassword123",
  "password_confirmation": "nuevaPassword123"
}
```

### Validaciones

- `token`: requerido
- `password`: requerido, mínimo 6 caracteres
- `password_confirmation`: requerido, debe coincidir con `password`

### Response 200 - Éxito

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 5,
      "nombre": "Juan Pérez",
      "email": "juan@example.com"
    }
  },
  "message": "Contraseña restablecida exitosamente"
}
```

### Response 400 - Token Inválido/Expirado

```json
{
  "success": false,
  "message": "Token inválido o expirado"
}
```

### Response 400 - Contraseñas No Coinciden

```json
{
  "success": false,
  "message": "Las contraseñas no coinciden"
}
```

### Notas

- Token se marca como usado después de reset exitoso
- Usuario puede hacer login inmediatamente con nueva contraseña
- Redirigir a login después de reset exitoso

---

## GET /auth/verify-reset-token/:token

Verificar si un token de reset es válido (útil para UI).

### Request

```http
GET /api/auth/verify-reset-token/abc123...
```

### Response 200 - Token Válido

```json
{
  "success": true,
  "data": {
    "valid": true,
    "email": "usuario@example.com",
    "expires_in_seconds": 850
  }
}
```

### Response 400 - Token Inválido

```json
{
  "success": false,
  "message": "Token inválido o expirado"
}
```

### Notas

- Llamar antes de mostrar formulario de reset
- Si inválido, mostrar mensaje de error y link para solicitar nuevo token
- `expires_in_seconds` para mostrar countdown

---

## 🔄 Flujo Completo de Password Reset

```
1. Usuario → Click "Olvidé mi contraseña"
2. Frontend → POST /auth/request-reset {email}
3. Backend → Genera token, envía email (mock)
4. Usuario → Recibe email con link + token
5. Usuario → Click en link → Frontend abre /reset-password?token=XXX
6. Frontend → GET /auth/verify-reset-token/{token} (validar)
7. Si válido → Mostrar formulario
8. Usuario → Ingresa nueva contraseña
9. Frontend → POST /auth/reset {token, password, password_confirmation}
10. Backend → Actualiza contraseña, invalida token
11. Frontend → Redirige a login con mensaje de éxito
```

---

## 🔐 Seguridad

### Tokens JWT

- Firmados con `HS256`
- Secret configurable en `.env` (`JWT_SECRET`)
- Incluyen: `user_id`, `email`, `rol`, `iat`, `exp`
- No modificables sin conocer el secret

### Password Reset

- Tokens de un solo uso
- Expiran en 15 minutos
- Hasheados en base de datos
- Se invalidan todos los tokens anteriores al generar uno nuevo

### Contraseñas

- Hasheadas con `bcrypt` (PASSWORD_DEFAULT)
- Mínimo 6 caracteres (recomendado: aumentar a 8+)
- No se almacenan en texto plano

---

## 🚀 Integración Frontend

### Login Example (React/Next.js)

```typescript
const handleLogin = async (email: string, password: string) => {
  try {
    const response = await fetch('http://localhost:8000/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });
    
    const data = await response.json();
    
    if (data.success) {
      localStorage.setItem('token', data.data.token);
      localStorage.setItem('refresh_token', data.data.refresh_token);
      localStorage.setItem('user', JSON.stringify(data.data.user));
      
      // Redirect to dashboard
      router.push('/dashboard');
    } else {
      setError(data.message);
    }
  } catch (error) {
    setError('Error de conexión');
  }
};
```

### Axios Interceptor para Auto-Refresh

```typescript
axios.interceptors.response.use(
  response => response,
  async error => {
    if (error.response?.status === 401) {
      const refreshToken = localStorage.getItem('refresh_token');
      
      try {
        const response = await axios.post('/api/auth/refresh', {}, {
          headers: { Authorization: `Bearer ${refreshToken}` }
        });
        
        localStorage.setItem('token', response.data.data.token);
        
        // Retry original request
        error.config.headers.Authorization = `Bearer ${response.data.data.token}`;
        return axios(error.config);
      } catch (refreshError) {
        // Refresh failed, redirect to login
        localStorage.clear();
        window.location.href = '/login';
      }
    }
    
    return Promise.reject(error);
  }
);
```

---

## 📋 Tabla de Referencia Rápida

| Endpoint | Auth | Descripción | Body |
|----------|------|-------------|------|
| `POST /auth/login` | ❌ No | Login y obtener tokens | email, password |
| `GET /auth/me` | ✅ Sí | Info usuario actual | - |
| `POST /auth/refresh` | ✅ Refresh Token | Renovar access token | - |
| `POST /auth/logout` | ✅ Sí | Cerrar sesión | - |
| `POST /auth/request-reset` | ❌ No | Solicitar reset password | email |
| `POST /auth/reset` | ❌ No | Resetear password | token, password, password_confirmation |
| `GET /auth/verify-reset-token/:token` | ❌ No | Validar token reset | - |

---

**Estado:** ✅ Implementado y testeado  
**Última actualización:** Noviembre 27, 2025
