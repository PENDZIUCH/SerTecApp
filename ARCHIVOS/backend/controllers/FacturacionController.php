<?php
// SerTecApp - Facturación Controller
// Sistema de facturación con mock de integración Tango

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';

class FacturacionController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * GET /facturacion
     * Listar facturas
     */
    public function index() {
        AuthMiddleware::required();
        
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 15;
        $clienteId = $_GET['cliente_id'] ?? null;
        $desde = $_GET['desde'] ?? null;
        $hasta = $_GET['hasta'] ?? null;
        
        $offset = ($page - 1) * $perPage;
        
        // Build query
        $where = ['1=1'];
        $params = [];
        
        if ($clienteId) {
            $where[] = 'f.cliente_id = ?';
            $params[] = $clienteId;
        }
        
        if ($desde) {
            $where[] = 'f.fecha >= ?';
            $params[] = $desde;
        }
        
        if ($hasta) {
            $where[] = 'f.fecha <= ?';
            $params[] = $hasta;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM facturas f WHERE {$whereClause}",
            $params
        )['total'];
        
        // Get data
        $params[] = $perPage;
        $params[] = $offset;
        
        $facturas = $this->db->fetchAll("
            SELECT 
                f.*,
                c.nombre as cliente_nombre,
                c.razon_social as cliente_razon_social,
                c.cuit as cliente_cuit
            FROM facturas f
            LEFT JOIN clientes c ON f.cliente_id = c.id
            WHERE {$whereClause}
            ORDER BY f.fecha DESC, f.numero DESC
            LIMIT ? OFFSET ?
        ", $params);
        
        return Response::success([
            'data' => $facturas,
            'pagination' => [
                'total' => (int)$total,
                'per_page' => (int)$perPage,
                'current_page' => (int)$page,
                'last_page' => ceil($total / $perPage)
            ]
        ]);
    }
    
    /**
     * GET /facturacion/:id
     * Obtener factura específica con items
     */
    public function show($id) {
        AuthMiddleware::required();
        
        $factura = $this->db->fetchOne("
            SELECT 
                f.*,
                c.nombre as cliente_nombre,
                c.razon_social as cliente_razon_social,
                c.cuit as cliente_cuit,
                c.direccion as cliente_direccion,
                c.telefono as cliente_telefono,
                c.email as cliente_email
            FROM facturas f
            LEFT JOIN clientes c ON f.cliente_id = c.id
            WHERE f.id = ?
        ", [$id]);
        
        if (!$factura) {
            return Response::notFound('Factura no encontrada');
        }
        
        // Get items
        $items = $this->db->fetchAll("
            SELECT * FROM factura_items
            WHERE factura_id = ?
            ORDER BY id ASC
        ", [$id]);
        
        $factura['items'] = $items;
        
        return Response::success($factura);
    }
    
    /**
     * POST /facturacion
     * Crear nueva factura
     */
    public function store() {
        AuthMiddleware::required();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validations
        Validator::requireValid($data, [
            'cliente_id' => 'required|integer',
            'tipo' => 'required|in:A,B,C',
            'items' => 'required'
        ]);
        
        if (!is_array($data['items']) || empty($data['items'])) {
            return Response::error('Debe incluir al menos un item', 400);
        }
        
        // Verify client
        $cliente = $this->db->fetchOne(
            "SELECT * FROM clientes WHERE id = ?",
            [$data['cliente_id']]
        );
        
        if (!$cliente) {
            return Response::error('Cliente no encontrado', 404);
        }
        
        // Calculate totals
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            if (!isset($item['cantidad']) || !isset($item['precio_unitario'])) {
                return Response::error('Items deben tener cantidad y precio_unitario', 400);
            }
            $subtotal += $item['cantidad'] * $item['precio_unitario'];
        }
        
        $iva = $data['tipo'] === 'A' ? $subtotal * 0.21 : 0;
        $total = $subtotal + $iva;
        
        // Generate invoice number (simple sequential)
        $lastNumber = $this->db->fetchOne("
            SELECT MAX(numero) as last_number 
            FROM facturas 
            WHERE tipo = ?
        ", [$data['tipo']])['last_number'] ?? 0;
        
        $numero = $lastNumber + 1;
        
        // Insert factura
        $facturaData = [
            'numero' => $numero,
            'tipo' => $data['tipo'],
            'cliente_id' => $data['cliente_id'],
            'fecha' => $data['fecha'] ?? date('Y-m-d'),
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total,
            'observaciones' => $data['observaciones'] ?? null,
            'tango_id' => null, // Se llenará cuando se integre Tango
            'tango_status' => 'pendiente',
            'enviada' => false
        ];
        
        $facturaId = $this->db->insert('facturas', $facturaData);
        
        // Insert items
        foreach ($data['items'] as $item) {
            $this->db->insert('factura_items', [
                'factura_id' => $facturaId,
                'descripcion' => $item['descripcion'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio_unitario'],
                'subtotal' => $item['cantidad'] * $item['precio_unitario']
            ]);
        }
        
        // Get created factura with items
        $factura = $this->db->fetchOne(
            "SELECT * FROM facturas WHERE id = ?",
            [$facturaId]
        );
        
        $factura['items'] = $this->db->fetchAll(
            "SELECT * FROM factura_items WHERE factura_id = ?",
            [$facturaId]
        );
        
        return Response::success($factura, 'Factura creada exitosamente', 201);
    }
    
    /**
     * PUT /facturacion/:id
     * Actualizar factura (solo antes de enviar a Tango)
     */
    public function update($id) {
        AuthMiddleware::required();
        
        $factura = $this->db->fetchOne(
            "SELECT * FROM facturas WHERE id = ?",
            [$id]
        );
        
        if (!$factura) {
            return Response::notFound('Factura no encontrada');
        }
        
        // No permitir modificar si ya fue enviada a Tango
        if ($factura['tango_id']) {
            return Response::error('No se puede modificar una factura ya enviada a Tango', 400);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Solo permitir actualizar observaciones si no hay tango_id
        if (isset($data['observaciones'])) {
            $this->db->update('facturas', [
                'observaciones' => $data['observaciones']
            ], ['id' => $id]);
        }
        
        $facturaActualizada = $this->db->fetchOne(
            "SELECT * FROM facturas WHERE id = ?",
            [$id]
        );
        
        return Response::success($facturaActualizada, 'Factura actualizada');
    }
    
    /**
     * DELETE /facturacion/:id
     * Anular factura
     */
    public function delete($id) {
        AuthMiddleware::requireRole('admin');
        
        $factura = $this->db->fetchOne(
            "SELECT * FROM facturas WHERE id = ?",
            [$id]
        );
        
        if (!factura) {
            return Response::notFound('Factura no encontrada');
        }
        
        // No permitir eliminar si ya fue enviada a Tango
        if ($factura['tango_id']) {
            return Response::error('No se puede eliminar una factura ya enviada a Tango. Debe anularse desde Tango.', 400);
        }
        
        // Delete items first
        $this->db->delete('factura_items', ['factura_id' => $id]);
        
        // Delete factura
        $this->db->delete('facturas', ['id' => $id]);
        
        return Response::success(null, 'Factura eliminada exitosamente');
    }
    
    /**
     * POST /facturacion/:id/enviar-tango
     * Enviar factura a Tango (MOCK)
     */
    public function enviarTango($id) {
        AuthMiddleware::required();
        
        $factura = $this->db->fetchOne(
            "SELECT f.*, c.cuit, c.razon_social 
             FROM facturas f 
             LEFT JOIN clientes c ON f.cliente_id = c.id
             WHERE f.id = ?",
            [$id]
        );
        
        if (!$factura) {
            return Response::notFound('Factura no encontrada');
        }
        
        if ($factura['tango_id']) {
            return Response::error('Factura ya fue enviada a Tango', 400);
        }
        
        // Get items
        $items = $this->db->fetchAll(
            "SELECT * FROM factura_items WHERE factura_id = ?",
            [$id]
        );
        
        // MOCK: Simulate Tango API call
        $mockTangoResponse = $this->mockTangoApi($factura, $items);
        
        if ($mockTangoResponse['success']) {
            // Update factura with Tango data
            $this->db->update('facturas', [
                'tango_id' => $mockTangoResponse['tango_id'],
                'tango_status' => 'enviada',
                'enviada' => true
            ], ['id' => $id]);
            
            return Response::success([
                'factura_id' => $id,
                'tango_id' => $mockTangoResponse['tango_id'],
                'status' => 'enviada'
            ], 'Factura enviada a Tango exitosamente');
        } else {
            return Response::error('Error al enviar a Tango: ' . $mockTangoResponse['error'], 500);
        }
    }
    
    /**
     * POST /facturacion/probar
     * Probar creación de factura sin guardar (preview)
     */
    public function probar() {
        AuthMiddleware::required();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validations
        Validator::requireValid($data, [
            'cliente_id' => 'required|integer',
            'tipo' => 'required|in:A,B,C',
            'items' => 'required'
        ]);
        
        // Calculate preview
        $subtotal = 0;
        foreach ($data['items'] as $item) {
            $subtotal += $item['cantidad'] * $item['precio_unitario'];
        }
        
        $iva = $data['tipo'] === 'A' ? $subtotal * 0.21 : 0;
        $total = $subtotal + $iva;
        
        return Response::success([
            'preview' => true,
            'tipo' => $data['tipo'],
            'items_count' => count($data['items']),
            'subtotal' => round($subtotal, 2),
            'iva' => round($iva, 2),
            'total' => round($total, 2)
        ], 'Preview de factura generado');
    }
    
    /**
     * GET /facturacion/resumen-mes
     * Resumen de facturación del mes
     */
    public function resumenMes() {
        AuthMiddleware::required();
        
        $mes = $_GET['mes'] ?? date('Y-m');
        
        $resumen = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total_facturas,
                SUM(total) as total_facturado,
                SUM(CASE WHEN tipo = 'A' THEN total ELSE 0 END) as facturado_tipo_a,
                SUM(CASE WHEN tipo = 'B' THEN total ELSE 0 END) as facturado_tipo_b,
                SUM(CASE WHEN tipo = 'C' THEN total ELSE 0 END) as facturado_tipo_c,
                SUM(CASE WHEN enviada = 1 THEN 1 ELSE 0 END) as facturas_enviadas,
                SUM(CASE WHEN enviada = 0 THEN 1 ELSE 0 END) as facturas_pendientes
            FROM facturas
            WHERE DATE_FORMAT(fecha, '%Y-%m') = ?
        ", [$mes]);
        
        return Response::success($resumen);
    }
    
    /**
     * Mock Tango API
     * Simula la integración con Tango
     */
    private function mockTangoApi($factura, $items) {
        // Simulate API delay
        usleep(100000); // 0.1 seconds
        
        // Generate mock Tango ID
        $tangoId = 'TGO-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // Simulate 95% success rate
        if (rand(1, 100) <= 95) {
            return [
                'success' => true,
                'tango_id' => $tangoId,
                'message' => 'Factura generada exitosamente en Tango'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Error de conexión con servidor Tango'
            ];
        }
    }
}
