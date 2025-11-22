<?php
// SerTecApp - Auth Middleware Usage Examples

/**
 * EJEMPLO 1: Proteger una ruta completa
 * Usar al inicio del método del controller
 */
class EjemploController {
    public function index() {
        // Requerir autenticación - detiene ejecución si no está autenticado
        AuthMiddleware::required();
        
        // Si llegamos aquí, el usuario está autenticado
        $user = AuthMiddleware::user();
        echo "Usuario: " . $user['nombre'];
    }
}

/**
 * EJEMPLO 2: Requerir rol específico
 */
class AdminController {
    public function dashboard() {
        // Solo permite admin
        AuthMiddleware::requireRole('admin');
        
        // Si llegamos aquí, el usuario es admin
        $user = auth_user(); // Helper global
    }
    
    public function reportes() {
        // Permite admin o supervisor
        AuthMiddleware::requireRole(['admin', 'supervisor']);
    }
}

/**
 * EJEMPLO 3: Autenticación opcional
 * La ruta funciona sin auth, pero cambia comportamiento si está autenticado
 */
class ProductosController {
    public function index() {
        // Intentar autenticar pero no fallar si no hay token
        AuthMiddleware::optional();
        
        if (is_authenticated()) {
            // Usuario autenticado - mostrar precios especiales
            $user = auth_user();
            echo "Bienvenido " . $user['nombre'];
        } else {
            // Usuario anónimo - precios regulares
            echo "Visitante";
        }
    }
}

/**
 * EJEMPLO 4: Verificar permisos manualmente
 */
class DocumentosController {
    public function show($id) {
        AuthMiddleware::required();
        
        $documento = $this->getDocumento($id);
        
        // Verificar si es dueño o admin
        $user = AuthMiddleware::user();
        
        if ($documento['user_id'] !== $user['id'] && !AuthMiddleware::hasRole('admin')) {
            echo Response::forbidden('No puedes ver este documento');
            exit();
        }
        
        // Mostrar documento
        echo Response::success($documento);
    }
}

/**
 * EJEMPLO 5: En el router (api/index.php)
 * Proteger grupos de rutas
 */

// En api/index.php:

// Rutas públicas (sin auth)
switch (true) {
    case $path === '/auth/login':
        // No requiere auth
        break;
}

// Rutas protegidas
switch (true) {
    case preg_match('#^/clientes#', $path):
        // Todas las rutas de clientes requieren auth
        AuthMiddleware::required();
        
        // Procesar ruta normalmente
        if ($path === '/clientes' && $method === 'GET') {
            $controller = new ClientesController();
            echo $controller->index();
        }
        break;
}

/**
 * EJEMPLO 6: Obtener información del usuario autenticado
 */
class PerfilController {
    public function show() {
        AuthMiddleware::required();
        
        // Método 1: Usando el middleware
        $userId = AuthMiddleware::userId();
        
        // Método 2: Usando helper global
        $user = auth_user();
        
        // Método 3: Acceso completo
        $userData = AuthMiddleware::user();
        
        return Response::success([
            'id' => $userData['id'],
            'nombre' => $userData['nombre'],
            'email' => $userData['email'],
            'rol' => $userData['rol']
        ]);
    }
}
