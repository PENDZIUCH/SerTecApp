<?php
// SerTecApp - Abonos Controller
// Gestión completa de abonos y suscripciones de clientes

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';

class AbonosController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * GET /abonos
     * Listar todos los abonos (paginado)
     */
    public function index() {
        AuthMiddleware::required();
        
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 15;
        $estado = $_GET['estado'] ?? null; // activo, vencido, suspendido
        $clienteId = $_GET['cliente_id'] ?? null;
        
        $offset = ($page - 1) * $perPage;
        
        // Build query
        $where = ['1=1'];
        $params = [];
        
        if ($estado) {
            $where[] = 'a.estado = ?';
            $params[] = $estado;
        }
        
        if ($clienteId) {
            $where[] = 'a.cliente_id = ?';
            $params[] = $clienteId;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total count
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM abonos a WHERE {$whereClause}",
            $params
        )['total'];
        
        // Get data with client info
        $params[] = $perPage;
        $params[] = $offset;
        
        $abonos = $this->db->fetchAll("
            SELECT 
                a.*,
                c.nombre as cliente_nombre,
                c.razon_social as cliente_razon_social,
                cf.color as color_frecuencia,
                cf.nombre as frecuencia_nombre
            FROM abonos a
            LEFT JOIN clientes c ON a.cliente_id = c.id
            LEFT JOIN colores_frecuencias cf ON a.frecuencia_visitas = cf.frecuencia_visitas
            WHERE {$whereClause}
            ORDER BY a.created_at DESC
            LIMIT ? OFFSET ?
        ", $params);
        
        return Response::success([
            'data' => $abonos,
            'pagination' => [
                'total' => (int)$total,
                'per_page' => (int)$perPage,
                'current_page' => (int)$page,
                'last_page' => ceil($total / $perPage)
            ]
        ]);
    }
    
    /**
     * GET /abonos/:id
     * Obtener un abono específico
     */
    public function show($id) {
        AuthMiddleware::required();
        
        $abono = $this->db->fetchOne("
            SELECT 
                a.*,
                c.nombre as cliente_nombre,
                c.razon_social as cliente_razon_social,
                c.telefono as cliente_telefono,
                c.email as cliente_email,
                cf.color as color_frecuencia,
                cf.nombre as frecuencia_nombre
            FROM abonos a
            LEFT JOIN clientes c ON a.cliente_id = c.id
            LEFT JOIN colores_frecuencias cf ON a.frecuencia_visitas = cf.frecuencia_visitas
            WHERE a.id = ?
        ", [$id]);
        
        if (!$abono) {
            return Response::notFound('Abono no encontrado');
        }
        
        return Response::success($abono);
    }
    
    /**
     * POST /abonos
     * Crear nuevo abono
     */
    public function store() {
        AuthMiddleware::required();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validations
        Validator::requireValid($data, [
            'cliente_id' => 'required|integer',
            'frecuencia_visitas' => 'required|integer|in:1,2,3',
            'monto' => 'required|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_vencimiento' => 'date',
            'estado' => 'in:activo,vencido,suspendido'
        ]);
        
        // Verify client exists
        $cliente = $this->db->fetchOne(
            "SELECT id FROM clientes WHERE id = ?",
            [$data['cliente_id']]
        );
        
        if (!$cliente) {
            return Response::error('Cliente no encontrado', 404);
        }
        
        // Set defaults
        $estado = $data['estado'] ?? 'activo';
        $fechaVencimiento = $data['fecha_vencimiento'] ?? date('Y-m-d', strtotime('+30 days'));
        $observaciones = $data['observaciones'] ?? null;
        
        // Insert
        $id = $this->db->insert('abonos', [
            'cliente_id' => $data['cliente_id'],
            'frecuencia_visitas' => $data['frecuencia_visitas'],
            'monto' => $data['monto'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_vencimiento' => $fechaVencimiento,
            'estado' => $estado,
            'observaciones' => $observaciones
        ]);
        
        // Get created record
        $abono = $this->db->fetchOne(
            "SELECT * FROM abonos WHERE id = ?",
            [$id]
        );
        
        return Response::success($abono, 'Abono creado exitosamente', 201);
    }
    
    /**
     * PUT /abonos/:id
     * Actualizar abono
     */
    public function update($id) {
        AuthMiddleware::required();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Check if exists
        $exists = $this->db->fetchOne(
            "SELECT id FROM abonos WHERE id = ?",
            [$id]
        );
        
        if (!$exists) {
            return Response::notFound('Abono no encontrado');
        }
        
        // Validations (all optional for update)
        if (isset($data['cliente_id'])) {
            Validator::requireValid(['cliente_id' => $data['cliente_id']], [
                'cliente_id' => 'required|integer'
            ]);
        }
        
        if (isset($data['frecuencia_visitas'])) {
            Validator::requireValid(['frecuencia_visitas' => $data['frecuencia_visitas']], [
                'frecuencia_visitas' => 'required|integer|in:1,2,3'
            ]);
        }
        
        if (isset($data['monto'])) {
            Validator::requireValid(['monto' => $data['monto']], [
                'monto' => 'required|numeric|min:0'
            ]);
        }
        
        if (isset($data['estado'])) {
            Validator::requireValid(['estado' => $data['estado']], [
                'estado' => 'in:activo,vencido,suspendido'
            ]);
        }
        
        // Build update data
        $updateData = [];
        $allowedFields = ['cliente_id', 'frecuencia_visitas', 'monto', 'fecha_inicio', 
                          'fecha_vencimiento', 'estado', 'observaciones'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return Response::error('No hay campos para actualizar', 400);
        }
        
        // Update
        $this->db->update('abonos', $updateData, ['id' => $id]);
        
        // Get updated record
        $abono = $this->db->fetchOne(
            "SELECT * FROM abonos WHERE id = ?",
            [$id]
        );
        
        return Response::success($abono, 'Abono actualizado exitosamente');
    }
    
    /**
     * DELETE /abonos/:id
     * Eliminar abono (soft delete)
     */
    public function delete($id) {
        AuthMiddleware::requireRole('admin'); // Solo admins pueden eliminar
        
        $exists = $this->db->fetchOne(
            "SELECT id FROM abonos WHERE id = ?",
            [$id]
        );
        
        if (!$exists) {
            return Response::notFound('Abono no encontrado');
        }
        
        // Soft delete - cambiar estado a 'suspendido'
        $this->db->update('abonos', [
            'estado' => 'suspendido'
        ], ['id' => $id]);
        
        return Response::success(null, 'Abono suspendido exitosamente');
    }
    
    /**
     * GET /abonos/proximos-vencer
     * Obtener abonos próximos a vencer (útil para alertas)
     */
    public function proximosVencer() {
        AuthMiddleware::required();
        
        $dias = $_GET['dias'] ?? 7; // Próximos 7 días por default
        
        $abonos = $this->db->fetchAll("
            SELECT 
                a.*,
                c.nombre as cliente_nombre,
                c.telefono as cliente_telefono,
                DATEDIFF(a.fecha_vencimiento, CURDATE()) as dias_restantes
            FROM abonos a
            LEFT JOIN clientes c ON a.cliente_id = c.id
            WHERE a.estado = 'activo'
            AND a.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY a.fecha_vencimiento ASC
        ", [$dias]);
        
        return Response::success($abonos);
    }
    
    /**
     * POST /abonos/:id/renovar
     * Renovar abono (extender vencimiento)
     */
    public function renovar($id) {
        AuthMiddleware::required();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $abono = $this->db->fetchOne(
            "SELECT * FROM abonos WHERE id = ?",
            [$id]
        );
        
        if (!$abono) {
            return Response::notFound('Abono no encontrado');
        }
        
        // Validar meses de renovación
        Validator::requireValid($data, [
            'meses' => 'required|integer|min:1|max:12'
        ]);
        
        $meses = $data['meses'];
        $nuevaFecha = date('Y-m-d', strtotime($abono['fecha_vencimiento'] . " +{$meses} months"));
        
        // Update
        $this->db->update('abonos', [
            'fecha_vencimiento' => $nuevaFecha,
            'estado' => 'activo'
        ], ['id' => $id]);
        
        $abonoActualizado = $this->db->fetchOne(
            "SELECT * FROM abonos WHERE id = ?",
            [$id]
        );
        
        return Response::success($abonoActualizado, "Abono renovado por {$meses} mes(es)");
    }
}
