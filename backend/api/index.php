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
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Sanitizer.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ClientesController.php';
require_once __DIR__ . '/../controllers/OrdenesController.php';

// Get request info
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/api', '', $path);

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
            
        case $path === '/auth/refresh' && $method === 'POST':
            $controller = new AuthController();
            echo $controller->refresh();
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
            
        case $path === '/ordenes' && $method === 'POST':
            $controller = new OrdenesController();
            echo $controller->store();
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
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
