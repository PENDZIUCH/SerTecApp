<?php
// SerTecApp - Taller Controller
// Gestión de equipos en taller/servicio técnico

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/Response.php';

class TallerController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * GET /taller
     * Listar equipos en taller
     */
    public function index() {
        AuthMiddleware::required();
        
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 15;
        $estado = $_GET['estado'] ?? null; // ingresado, en_reparacion, esperando_repuesto, listo, entregado
        $tecnicoId = $_GET['tecnico_id'] ?? null;
        
        $offset = ($page - 1) * $perPage;
        
        // Build query
        $where = ['1=1'];
        $params = [];
        
        if ($estado) {
            $where[] = 't.estado = ?';
            $params[] = $estado;
        }
        
        if ($tecnicoId) {
            $where[] = 't.tecnico_id = ?';
            $params[] = $tecnicoId;
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Get total
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM taller t WHERE {$whereClause}",
            $params
        )['total'];
        
        // Get data
        $params[] = $perPage;
        $params[] = $offset;
        
        $equipos = $this->db->fetchAll("
            SELECT 
                t.*,
                c.nombre as cliente_nombre,
                c.telefono as cliente_telefono,
                tec.nombre as tecnico_nombre,
                DATEDIFF(COALESCE(t.fecha_salida, CURDATE()), t.fecha_ingreso) as dias_en_taller
            FROM taller t
            LEFT JOIN clientes c ON t.cliente_id = c.id
            LEFT JOIN usuarios tec ON t.tecnico_id = tec.id
            WHERE {$whereClause}
            ORDER BY t.fecha_ingreso DESC
            LIMIT ? OFFSET ?
        ", $params);
        
        return Response::success([
            'data' => $equipos,
            'pagination' => [
                'total' => (int)$total,
                'per_page' => (int)$perPage,
                'current_page' => (int)$page,
                'last_page' => ceil($total / $perPage)
            ]
        ]);
    }
    
    /**
     * GET /taller/:id
     * Obtener equipo específico con historial
     */
    public function show($id) {
        AuthMiddleware::required();
        
        $equipo = $this->db->fetchOne("
            SELECT 
                t.*,
                c.nombre as cliente_nombre,
                c.razon_social as cliente_razon_social,
                c.telefono as cliente_telefono,
                c.email as cliente_email,
                c.direccion as cliente_direccion,
                tec.nombre as tecnico_nombre,
                tec.email as tecnico_email,
                DATEDIFF(COALESCE(t.fecha_salida, CURDATE()), t.fecha_ingreso) as dias_en_taller
            FROM taller t
            LEFT JOIN clientes c ON t.cliente_id = c.id
            LEFT JOIN usuarios tec ON t.tecnico_id = tec.id
            WHERE t.id = ?
        ", [$id]);
        
        if (!$equipo) {
            return Response::notFound('Equipo no encontrado');
        }
        
        return Response::success($equipo);
    }
    
    /**
     * POST /taller
     * Ingresar equipo al taller
     */
    public function store() {
        AuthMiddleware::required();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validations
        Validator::requireValid($data, [
            'cliente_id' => 'required|integer',
            'equipo' => 'required|max:255',
            'marca' => 'max:100',
            'modelo' => 'max:100',
            'problema_reportado' => 'required|max:500'
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
        $insertData = [
            'cliente_id' => $data['cliente_id'],
            'equipo' => $data['equipo'],
            'marca' => $data['marca'] ?? null,
            'modelo' => $data['modelo'] ?? null,
            'numero_serie' => $data['numero_serie'] ?? null,
            'problema_reportado' => $data['problema_reportado'],
            'diagnostico' => $data['diagnostico'] ?? null,
            'solucion' => $data['solucion'] ?? null,
            'fecha_ingreso' => $data['fecha_ingreso'] ?? date('Y-m-d'),
            'fecha_estimada' => $data['fecha_estimada'] ?? null,
            'fecha_salida' => null,
            'estado' => 'ingresado',
            'tecnico_id' => $data['tecnico_id'] ?? null,
            'observaciones' => $data['observaciones'] ?? null
        ];
        
        // Insert
        $id = $this->db->insert('taller', $insertData);
        
        // Get created record
        $equipo = $this->db->fetchOne(
            "SELECT * FROM taller WHERE id = ?",
            [$id]
        );
        
        return Response::success($equipo, 'Equipo ingresado al taller exitosamente', 201);
    }
    
    /**
     * PUT /taller/:id
     * Actualizar equipo en taller
     */
    public function update($id) {
        AuthMiddleware::required();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Check if exists
        $exists = $this->db->fetchOne(
            "SELECT * FROM taller WHERE id = ?",
            [$id]
        );
        
        if (!$exists) {
            return Response::notFound('Equipo no encontrado');
        }
        
        // Build update data
        $updateData = [];
        $allowedFields = ['equipo', 'marca', 'modelo', 'numero_serie', 
                          'problema_reportado', 'diagnostico', 'solucion',
                          'fecha_estimada', 'fecha_salida', 'estado', 
                          'tecnico_id', 'observaciones'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return Response::error('No hay campos para actualizar', 400);
        }
        
        // Validate estado if present
        if (isset($updateData['estado'])) {
            $estadosValidos = ['ingresado', 'en_reparacion', 'esperando_repuesto', 'listo', 'entregado'];
            if (!in_array($updateData['estado'], $estadosValidos)) {
                return Response::error('Estado inválido', 400);
            }
        }
        
        // Update
        $this->db->update('taller', $updateData, ['id' => $id]);
        
        // Get updated record
        $equipo = $this->db->fetchOne(
            "SELECT * FROM taller WHERE id = ?",
            [$id]
        );
        
        return Response::success($equipo, 'Equipo actualizado exitosamente');
    }
    
    /**
     * DELETE /taller/:id
     * Eliminar registro de taller
     */
    public function delete($id) {
        AuthMiddleware::requireRole('admin');
        
        $exists = $this->db->fetchOne(
            "SELECT id FROM taller WHERE id = ?",
            [$id]
        );
        
        if (!$exists) {
            return Response::notFound('Equipo no encontrado');
        }
        
        // Delete
        $this->db->delete('taller', ['id' => $id]);
        
        return Response::success(null, 'Registro eliminado exitosamente');
    }
    
    /**
     * POST /taller/:id/asignar-tecnico
     * Asignar técnico a equipo
     */
    public function asignarTecnico($id) {
        AuthMiddleware::required();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        Validator::requireValid($data, [
            'tecnico_id' => 'required|integer'
        ]);
        
        // Verify equipment exists
        $equipo = $this->db->fetchOne(
            "SELECT * FROM taller WHERE id = ?",
            [$id]
        );
        
        if (!$equipo) {
            return Response::notFound('Equipo no encontrado');
        }
        
        // Verify technician exists
        $tecnico = $this->db->fetchOne(
            "SELECT id, nombre FROM usuarios WHERE id = ? AND rol IN ('tecnico', 'admin')",
            [$data['tecnico_id']]
        );
        
        if (!$tecnico) {
            return Response::error('Técnico no encontrado', 404);
        }
        
        // Update
        $this->db->update('taller', [
            'tecnico_id' => $data['tecnico_id'],
            'estado' => 'en_reparacion'
        ], ['id' => $id]);
        
        return Response::success([
            'equipo_id' => $id,
            'tecnico_id' => $tecnico['id'],
            'tecnico_nombre' => $tecnico['nombre']
        ], 'Técnico asignado exitosamente');
    }
    
    /**
     * POST /taller/:id/cambiar-estado
     * Cambiar estado del equipo
     */
    public function cambiarEstado($id) {
        AuthMiddleware::required();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        Validator::requireValid($data, [
            'estado' => 'required|in:ingresado,en_reparacion,esperando_repuesto,listo,entregado'
        ]);
        
        $equipo = $this->db->fetchOne(
            "SELECT * FROM taller WHERE id = ?",
            [$id]
        );
        
        if (!$equipo) {
            return Response::notFound('Equipo no encontrado');
        }
        
        $updateData = ['estado' => $data['estado']];
        
        // Si estado es 'entregado', setear fecha de salida
        if ($data['estado'] === 'entregado' && !$equipo['fecha_salida']) {
            $updateData['fecha_salida'] = date('Y-m-d');
        }
        
        $this->db->update('taller', $updateData, ['id' => $id]);
        
        return Response::success([
            'estado_anterior' => $equipo['estado'],
            'estado_nuevo' => $data['estado']
        ], 'Estado actualizado exitosamente');
    }
    
    /**
     * GET /taller/estadisticas/por-tecnico
     * Estadísticas de equipos por técnico
     */
    public function estadisticasPorTecnico() {
        AuthMiddleware::required();
        
        $stats = $this->db->fetchAll("
            SELECT 
                tec.id as tecnico_id,
                tec.nombre as tecnico_nombre,
                COUNT(*) as total_equipos,
                SUM(CASE WHEN t.estado = 'en_reparacion' THEN 1 ELSE 0 END) as en_reparacion,
                SUM(CASE WHEN t.estado = 'listo' THEN 1 ELSE 0 END) as listos,
                SUM(CASE WHEN t.estado = 'entregado' THEN 1 ELSE 0 END) as entregados,
                AVG(DATEDIFF(COALESCE(t.fecha_salida, CURDATE()), t.fecha_ingreso)) as promedio_dias
            FROM taller t
            LEFT JOIN usuarios tec ON t.tecnico_id = tec.id
            WHERE t.tecnico_id IS NOT NULL
            GROUP BY tec.id, tec.nombre
            ORDER BY total_equipos DESC
        ");
        
        return Response::success($stats);
    }
    
    /**
     * GET /taller/pendientes
     * Equipos pendientes de entregar
     */
    public function pendientes() {
        AuthMiddleware::required();
        
        $equipos = $this->db->fetchAll("
            SELECT 
                t.*,
                c.nombre as cliente_nombre,
                c.telefono as cliente_telefono,
                tec.nombre as tecnico_nombre,
                DATEDIFF(CURDATE(), t.fecha_ingreso) as dias_en_taller
            FROM taller t
            LEFT JOIN clientes c ON t.cliente_id = c.id
            LEFT JOIN usuarios tec ON t.tecnico_id = tec.id
            WHERE t.estado IN ('ingresado', 'en_reparacion', 'esperando_repuesto', 'listo')
            ORDER BY t.fecha_ingreso ASC
        ");
        
        return Response::success($equipos);
    }
}
