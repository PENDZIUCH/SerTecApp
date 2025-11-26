<?php
// SerTecApp - API Entry Point
// Router principal de la API

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
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ClientesController.php';
require_once __DIR__ . '/../controllers/OrdenesController.php';
require_once __DIR__ . '/../controllers/RepuestosController.php';

// Get request info
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Normalize path - remove base paths for both local and production
// Supports:
// - /SerTecApp/backend/api (Mac local)
// - /SerTecApp/backend (Mac local)
// - /backend/api (Production hosting)
// - /backend (Production hosting)
$path = str_replace('/SerTecApp/backend/api', '', $path);
$path = str_replace('/SerTecApp/backend', '', $path);
$path = str_replace('/backend/api', '', $path);
$path = str_replace('/backend', '', $path);
$path = $path ?: '/';

// Router
try {
    switch (true) {
        // Auth routes
        case $path === '/auth/login' && $method === 'POST':
            $controller = new AuthController();
            echo $controller->login();
            break;
            
        case $path === '/auth/me' && $method === 'GET':
            $controller = new AuthController();
            echo $controller->me();
            break;
            
        // Clientes routes
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
            
        // Ordenes routes
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
            
        // Repuestos routes
        case $path === '/repuestos' && $method === 'GET':
            $controller = new RepuestosController();
            echo $controller->index();
            break;
            
        case preg_match('#^/repuestos/(\d+)$#', $path, $matches) && $method === 'GET':
            $controller = new RepuestosController();
            echo $controller->show($matches[1]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
