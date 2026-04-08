<?php
// SerTecApp - Repuestos Controller
// Gestión de inventario de repuestos

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';

class RepuestosController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * GET /repuestos
     * Listar repuestos con filtros y paginación
     */
    public function index() {
        AuthMiddleware::required();
        
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 15;
        $search = $_GET['search'] ?? null;
        $stockBajo = $_GET['stock_bajo'] ?? null; // true/false
        
        $offset = ($page - 1) * $perPage;
        
        // Build query
        $where = ['1=1'];
        $params = [];
        
        if ($search) {
            $where[] = '(r.codigo LIKE ? OR r.descripcion LIKE ? OR r.marca LIKE ?)';
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if ($stockBajo === 'true') {
            $where[] = 'r.stock_actual <= r.stock_minimo';
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM repuestos r WHERE {$whereClause}",
            $params
        )['total'];
        
        // Get data
        $params[] = $perPage;
        $params[] = $offset;
        
        $repuestos = $this->db->fetchAll("
            SELECT 
                r.*,
                CASE 
                    WHEN r.stock_actual <= 0 THEN 'sin_stock'
                    WHEN r.stock_actual <= r.stock_minimo THEN 'stock_bajo'
                    WHEN r.stock_actual >= r.stock_maximo THEN 'stock_alto'
                    ELSE 'stock_normal'
                END as estado_stock
            FROM repuestos r
            WHERE {$whereClause}
            ORDER BY r.descripcion ASC
            LIMIT ? OFFSET ?
        ", $params);
        
        return Response::success([
            'data' => $repuestos,
            'pagination' => [
                'total' => (int)$total,
                'per_page' => (int)$perPage,
                'current_page' => (int)$page,
                'last_page' => ceil($total / $perPage)
            ]
        ]);
    }
    
    /**
     * GET /repuestos/:id
     * Obtener un repuesto específico con historial
     */
    public function show($id) {
        AuthMiddleware::required();
        
        $repuesto = $this->db->fetchOne("
            SELECT 
                r.*,
                CASE 
                    WHEN r.stock_actual <= 0 THEN 'sin_stock'
                    WHEN r.stock_actual <= r.stock_minimo THEN 'stock_bajo'
                    WHEN r.stock_actual >= r.stock_maximo THEN 'stock_alto'
                    ELSE 'stock_normal'
                END as estado_stock
            FROM repuestos r
            WHERE r.id = ?
        ", [$id]);
        
        if (!$repuesto) {
            return Response::notFound('Repuesto no encontrado');
        }
        
        // Get recent movements (últimos 20)
        $movimientos = $this->db->fetchAll("
            SELECT 
                m.*,
                u.nombre as usuario_nombre
            FROM movimientos_repuestos m
            LEFT JOIN usuarios u ON m.usuario_id = u.id
            WHERE m.repuesto_id = ?
            ORDER BY m.created_at DESC
            LIMIT 20
        ", [$id]);
        
        $repuesto['movimientos'] = $movimientos;
        
        return Response::success($repuesto);
    }
    
    /**
     * POST /repuestos
     * Crear nuevo repuesto
     */
    public function store() {
        AuthMiddleware::required();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validations
        Validator::requireValid($data, [
            'codigo' => 'required|alphanumeric|max:50',
            'descripcion' => 'required|max:255',
            'precio_costo' => 'numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'stock_actual' => 'integer|min:0',
            'stock_minimo' => 'integer|min:0',
            'stock_maximo' => 'integer|min:0'
        ]);
        
        // Check if code already exists
        $exists = $this->db->fetchOne(
            "SELECT id FROM repuestos WHERE codigo = ?",
            [$data['codigo']]
        );
        
        if ($exists) {
            return Response::error('El código de repuesto ya existe', 400);
        }
        
        // Set defaults
        $insertData = [
            'codigo' => $data['codigo'],
            'descripcion' => $data['descripcion'],
            'marca' => $data['marca'] ?? null,
            'modelo' => $data['modelo'] ?? null,
            'precio_costo' => $data['precio_costo'] ?? 0,
            'precio_venta' => $data['precio_venta'],
            'stock_actual' => $data['stock_actual'] ?? 0,
            'stock_minimo' => $data['stock_minimo'] ?? 5,
            'stock_maximo' => $data['stock_maximo'] ?? 100,
            'ubicacion' => $data['ubicacion'] ?? null,
            'observaciones' => $data['observaciones'] ?? null
        ];
        
        // Insert
        $id = $this->db->insert('repuestos', $insertData);
        
        // Create initial movement if stock > 0
        if ($insertData['stock_actual'] > 0) {
            $this->registrarMovimiento(
                $id,
                'entrada',
                $insertData['stock_actual'],
                'Stock inicial',
                null
            );
        }
        
        // Get created record
        $repuesto = $this->db->fetchOne(
            "SELECT * FROM repuestos WHERE id = ?",
            [$id]
        );
        
        return Response::success($repuesto, 'Repuesto creado exitosamente', 201);
    }
    
    /**
     * PUT /repuestos/:id
     * Actualizar repuesto
     */
    public function update($id) {
        AuthMiddleware::required();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Check if exists
        $exists = $this->db->fetchOne(
            "SELECT * FROM repuestos WHERE id = ?",
            [$id]
        );
        
        if (!$exists) {
            return Response::notFound('Repuesto no encontrado');
        }
        
        // Build update data
        $updateData = [];
        $allowedFields = ['codigo', 'descripcion', 'marca', 'modelo', 
                          'precio_costo', 'precio_venta', 'stock_minimo', 
                          'stock_maximo', 'ubicacion', 'observaciones'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return Response::error('No hay campos para actualizar', 400);
        }
        
        // Validate if present
        if (isset($updateData['precio_venta'])) {
            Validator::requireValid(['precio_venta' => $updateData['precio_venta']], [
                'precio_venta' => 'required|numeric|min:0'
            ]);
        }
        
        // Update
        $this->db->update('repuestos', $updateData, ['id' => $id]);
        
        // Get updated record
        $repuesto = $this->db->fetchOne(
            "SELECT * FROM repuestos WHERE id = ?",
            [$id]
        );
        
        return Response::success($repuesto, 'Repuesto actualizado exitosamente');
    }
    
    /**
     * DELETE /repuestos/:id
     * Eliminar repuesto
     */
    public function delete($id) {
        AuthMiddleware::requireRole('admin');
        
        $exists = $this->db->fetchOne(
            "SELECT id FROM repuestos WHERE id = ?",
            [$id]
        );
        
        if (!$exists) {
            return Response::notFound('Repuesto no encontrado');
        }
        
        // Check if used in orders
        $usedInOrders = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM orden_repuestos WHERE repuesto_id = ?",
            [$id]
        )['count'];
        
        if ($usedInOrders > 0) {
            return Response::error('No se puede eliminar: repuesto usado en órdenes de trabajo', 400);
        }
        
        // Delete
        $this->db->delete('repuestos', ['id' => $id]);
        
        return Response::success(null, 'Repuesto eliminado exitosamente');
    }
    
    /**
     * POST /repuestos/:id/entrada
     * Registrar entrada de stock
     */
    public function entrada($id) {
        AuthMiddleware::required();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        Validator::requireValid($data, [
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'required|max:255'
        ]);
        
        $repuesto = $this->db->fetchOne(
            "SELECT * FROM repuestos WHERE id = ?",
            [$id]
        );
        
        if (!$repuesto) {
            return Response::notFound('Repuesto no encontrado');
        }
        
        // Update stock
        $nuevoStock = $repuesto['stock_actual'] + $data['cantidad'];
        $this->db->update('repuestos', [
            'stock_actual' => $nuevoStock
        ], ['id' => $id]);
        
        // Register movement
        $this->registrarMovimiento(
            $id,
            'entrada',
            $data['cantidad'],
            $data['motivo'],
            $data['orden_compra'] ?? null
        );
        
        return Response::success([
            'stock_anterior' => $repuesto['stock_actual'],
            'cantidad_ingresada' => $data['cantidad'],
            'stock_actual' => $nuevoStock
        ], 'Entrada registrada exitosamente');
    }
    
    /**
     * POST /repuestos/:id/salida
     * Registrar salida de stock
     */
    public function salida($id) {
        AuthMiddleware::required();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        Validator::requireValid($data, [
            'cantidad' => 'required|integer|min:1',
            'motivo' => 'required|max:255'
        ]);
        
        $repuesto = $this->db->fetchOne(
            "SELECT * FROM repuestos WHERE id = ?",
            [$id]
        );
        
        if (!$repuesto) {
            return Response::notFound('Repuesto no encontrado');
        }
        
        // Check stock availability
        if ($repuesto['stock_actual'] < $data['cantidad']) {
            return Response::error('Stock insuficiente', 400);
        }
        
        // Update stock
        $nuevoStock = $repuesto['stock_actual'] - $data['cantidad'];
        $this->db->update('repuestos', [
            'stock_actual' => $nuevoStock
        ], ['id' => $id]);
        
        // Register movement
        $this->registrarMovimiento(
            $id,
            'salida',
            $data['cantidad'],
            $data['motivo'],
            $data['orden_trabajo_id'] ?? null
        );
        
        return Response::success([
            'stock_anterior' => $repuesto['stock_actual'],
            'cantidad_retirada' => $data['cantidad'],
            'stock_actual' => $nuevoStock,
            'alerta_stock_bajo' => $nuevoStock <= $repuesto['stock_minimo']
        ], 'Salida registrada exitosamente');
    }
    
    /**
     * GET /repuestos/alertas/stock-bajo
     * Obtener repuestos con stock bajo
     */
    public function stockBajo() {
        AuthMiddleware::required();
        
        $repuestos = $this->db->fetchAll("
            SELECT 
                r.*,
                (r.stock_minimo - r.stock_actual) as cantidad_faltante
            FROM repuestos r
            WHERE r.stock_actual <= r.stock_minimo
            ORDER BY r.stock_actual ASC
        ");
        
        return Response::success($repuestos);
    }
    
    /**
     * Helper: Registrar movimiento de repuesto
     */
    private function registrarMovimiento($repuestoId, $tipo, $cantidad, $motivo, $referenciaId = null) {
        $userId = AuthMiddleware::userId();
        
        $this->db->insert('movimientos_repuestos', [
            'repuesto_id' => $repuestoId,
            'tipo' => $tipo,
            'cantidad' => $cantidad,
            'motivo' => $motivo,
            'referencia_id' => $referenciaId,
            'usuario_id' => $userId
        ]);
    }
}
