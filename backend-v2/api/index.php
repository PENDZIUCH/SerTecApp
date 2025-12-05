<?php
// SerTecApp - API Entry Point
// Router principal de la API - Core Backend Pendziuch v1

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Sanitizer.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

// Load controllers
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ClientesController.php';
require_once __DIR__ . '/../controllers/OrdenesController.php';
require_once __DIR__ . '/../controllers/AbonosController.php';
require_once __DIR__ . '/../controllers/RepuestosController.php';
require_once __DIR__ . '/../controllers/TallerController.php';
require_once __DIR__ . '/../controllers/FacturacionController.php';
require_once __DIR__ . '/../controllers/ReportesController.php';

// Get request info
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);

// Router
try {
    switch (true) {
        // ==================== AUTH ROUTES ====================
        case $path === '/auth/login' && $method === 'POST':
            $controller = new AuthController();
            echo $controller->login();
            break;
            
        case $path === '/auth/me' && $method === 'GET':
            $controller = new AuthController();
            echo $controller->me();
            break;
            
        case $path === '/auth/refresh' && $method === 'POST':
            $controller = new AuthController();
            echo $controller->refresh();
            break;
        
        case $path === '/auth/logout' && $method === 'POST':
            $controller = new AuthController();
            echo $controller->logout();
            break;
            
        // ==================== CLIENTES ROUTES ====================
        case $path === '/clientes' && $method === 'GET':
            $controller = new ClientesController();
            echo $controller->index();
            break;
            
        case preg_match('#^/clientes/(\d+)$#', $path, $matches) && $method === 'GET':
            $controller = new ClientesController();
            echo $controller->show($matches[1]);
            break;
            
        case $path === '/clientes' && $method === 'POST':
            $controller = new ClientesController();
            echo $controller->store();
            break;
            
        case preg_match('#^/clientes/(\d+)$#', $path, $matches) && $method === 'PUT':
            $controller = new ClientesController();
            echo $controller->update($matches[1]);
            break;
            
        case preg_match('#^/clientes/(\d+)$#', $path, $matches) && $method === 'DELETE':
            $controller = new ClientesController();
            echo $controller->delete($matches[1]);
            break;
            
        // ==================== ÓRDENES ROUTES ====================
        case $path === '/ordenes' && $method === 'GET':
            $controller = new OrdenesController();
            echo $controller->index();
            break;
            
        case preg_match('#^/ordenes/(\d+)$#', $path, $matches) && $method === 'GET':
            $controller = new OrdenesController();
            echo $controller->show($matches[1]);
            break;
            
        case $path === '/ordenes' && $method === 'POST':
            $controller = new OrdenesController();
            echo $controller->store();
            break;
            
        case preg_match('#^/ordenes/(\d+)$#', $path, $matches) && $method === 'PUT':
            $controller = new OrdenesController();
            echo $controller->update($matches[1]);
            break;
            
        case preg_match('#^/ordenes/(\d+)$#', $path, $matches) && $method === 'DELETE':
            $controller = new OrdenesController();
            echo $controller->delete($matches[1]);
            break;
            
        // ==================== ABONOS ROUTES ====================
        case $path === '/abonos' && $method === 'GET':
            $controller = new AbonosController();
            echo $controller->index();
            break;
            
        case preg_match('#^/abonos/(\d+)$#', $path, $matches) && $method === 'GET':
            $controller = new AbonosController();
            echo $controller->show($matches[1]);
            break;
            
        case $path === '/abonos' && $method === 'POST':
            $controller = new AbonosController();
            echo $controller->store();
            break;
            
        case preg_match('#^/abonos/(\d+)$#', $path, $matches) && $method === 'PUT':
            $controller = new AbonosController();
            echo $controller->update($matches[1]);
            break;
            
        case preg_match('#^/abonos/(\d+)$#', $path, $matches) && $method === 'DELETE':
            $controller = new AbonosController();
            echo $controller->delete($matches[1]);
            break;
            
        case $path === '/abonos/proximos-vencer' && $method === 'GET':
            $controller = new AbonosController();
            echo $controller->proximosVencer();
            break;
            
        case preg_match('#^/abonos/(\d+)/renovar$#', $path, $matches) && $method === 'POST':
            $controller = new AbonosController();
            echo $controller->renovar($matches[1]);
            break;
            
        // ==================== REPUESTOS ROUTES ====================
        case $path === '/repuestos' && $method === 'GET':
            $controller = new RepuestosController();
            echo $controller->index();
            break;
            
        case preg_match('#^/repuestos/(\d+)$#', $path, $matches) && $method === 'GET':
            $controller = new RepuestosController();
            echo $controller->show($matches[1]);
            break;
            
        case $path === '/repuestos' && $method === 'POST':
            $controller = new RepuestosController();
            echo $controller->store();
            break;
            
        case preg_match('#^/repuestos/(\d+)$#', $path, $matches) && $method === 'PUT':
            $controller = new RepuestosController();
            echo $controller->update($matches[1]);
            break;
            
        case preg_match('#^/repuestos/(\d+)$#', $path, $matches) && $method === 'DELETE':
            $controller = new RepuestosController();
            echo $controller->delete($matches[1]);
            break;
            
        case preg_match('#^/repuestos/(\d+)/entrada$#', $path, $matches) && $method === 'POST':
            $controller = new RepuestosController();
            echo $controller->entrada($matches[1]);
            break;
            
        case preg_match('#^/repuestos/(\d+)/salida$#', $path, $matches) && $method === 'POST':
            $controller = new RepuestosController();
            echo $controller->salida($matches[1]);
            break;
            
        case $path === '/repuestos/alertas/stock-bajo' && $method === 'GET':
            $controller = new RepuestosController();
            echo $controller->stockBajo();
            break;
            
        // ==================== TALLER ROUTES ====================
        case $path === '/taller' && $method === 'GET':
            $controller = new TallerController();
            echo $controller->index();
            break;
            
        case preg_match('#^/taller/(\d+)$#', $path, $matches) && $method === 'GET':
            $controller = new TallerController();
            echo $controller->show($matches[1]);
            break;
            
        case $path === '/taller' && $method === 'POST':
            $controller = new TallerController();
            echo $controller->store();
            break;
            
        case preg_match('#^/taller/(\d+)$#', $path, $matches) && $method === 'PUT':
            $controller = new TallerController();
            echo $controller->update($matches[1]);
            break;
            
        case preg_match('#^/taller/(\d+)$#', $path, $matches) && $method === 'DELETE':
            $controller = new TallerController();
            echo $controller->delete($matches[1]);
            break;
            
        case preg_match('#^/taller/(\d+)/asignar-tecnico$#', $path, $matches) && $method === 'POST':
            $controller = new TallerController();
            echo $controller->asignarTecnico($matches[1]);
            break;
            
        case preg_match('#^/taller/(\d+)/cambiar-estado$#', $path, $matches) && $method === 'POST':
            $controller = new TallerController();
            echo $controller->cambiarEstado($matches[1]);
            break;
            
        case $path === '/taller/estadisticas/por-tecnico' && $method === 'GET':
            $controller = new TallerController();
            echo $controller->estadisticasPorTecnico();
            break;
            
        case $path === '/taller/pendientes' && $method === 'GET':
            $controller = new TallerController();
            echo $controller->pendientes();
            break;
            
        // ==================== FACTURACIÓN ROUTES ====================
        case $path === '/facturacion' && $method === 'GET':
            $controller = new FacturacionController();
            echo $controller->index();
            break;
            
        case preg_match('#^/facturacion/(\d+)$#', $path, $matches) && $method === 'GET':
            $controller = new FacturacionController();
            echo $controller->show($matches[1]);
            break;
            
        case $path === '/facturacion' && $method === 'POST':
            $controller = new FacturacionController();
            echo $controller->store();
            break;
            
        case preg_match('#^/facturacion/(\d+)$#', $path, $matches) && $method === 'PUT':
            $controller = new FacturacionController();
            echo $controller->update($matches[1]);
            break;
            
        case preg_match('#^/facturacion/(\d+)$#', $path, $matches) && $method === 'DELETE':
            $controller = new FacturacionController();
            echo $controller->delete($matches[1]);
            break;
            
        case preg_match('#^/facturacion/(\d+)/enviar-tango$#', $path, $matches) && $method === 'POST':
            $controller = new FacturacionController();
            echo $controller->enviarTango($matches[1]);
            break;
            
        case $path === '/facturacion/probar' && $method === 'POST':
            $controller = new FacturacionController();
            echo $controller->probar();
            break;
            
        case $path === '/facturacion/resumen-mes' && $method === 'GET':
            $controller = new FacturacionController();
            echo $controller->resumenMes();
            break;
            
        // ==================== REPORTES ROUTES ====================
        case $path === '/reportes/dashboard' && $method === 'GET':
            $controller = new ReportesController();
            echo $controller->dashboard();
            break;
            
        case $path === '/reportes/clientes-activos' && $method === 'GET':
            $controller = new ReportesController();
            echo $controller->clientesActivos();
            break;
            
        case $path === '/reportes/abonos-vencer' && $method === 'GET':
            $controller = new ReportesController();
            echo $controller->abonosVencer();
            break;
            
        case $path === '/reportes/taller-por-tecnico' && $method === 'GET':
            $controller = new ReportesController();
            echo $controller->tallerPorTecnico();
            break;
            
        case $path === '/reportes/facturacion-mes' && $method === 'GET':
            $controller = new ReportesController();
            echo $controller->facturacionMes();
            break;
            
        case $path === '/reportes/ordenes-trabajo' && $method === 'GET':
            $controller = new ReportesController();
            echo $controller->ordenesTrabaajo();
            break;
            
        case $path === '/reportes/repuestos-mas-usados' && $method === 'GET':
            $controller = new ReportesController();
            echo $controller->repuestosMasUsados();
            break;
            
        // ==================== DEFAULT ====================
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'Endpoint not found',
                'path' => $path,
                'method' => $method
            ]);
            break;
    }
} catch (ValidationException $e) {
    echo Response::validationError($e->getErrors());
} catch (Exception $e) {
    error_log('API Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    if (Env::getBool('APP_DEBUG')) {
        echo Response::serverError($e->getMessage());
    } else {
        echo Response::serverError();
    }
}
