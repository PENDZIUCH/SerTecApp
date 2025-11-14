<?php
// SerTecApp - Ordenes Controller

class OrdenesController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index() {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 15;
        $offset = ($page - 1) * $perPage;
        
        $where = [];
        $params = [];
        
        if (isset($_GET['estado'])) {
            $where[] = "ot.estado = ?";
            $params[] = $_GET['estado'];
        }
        
        if (isset($_GET['cliente_id'])) {
            $where[] = "ot.cliente_id = ?";
            $params[] = $_GET['cliente_id'];
        }
        
        if (isset($_GET['tecnico_id'])) {
            $where[] = "ot.tecnico_id = ?";
            $params[] = $_GET['tecnico_id'];
        }
        
        if (isset($_GET['sincronizado'])) {
            $where[] = "ot.sincronizado = ?";
            $params[] = $_GET['sincronizado'] === 'true' ? 1 : 0;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $ordenes = $this->db->fetchAll(
            "SELECT ot.*, 
                    c.nombre as cliente_nombre,
                    u.nombre as tecnico_nombre
             FROM ordenes_trabajo ot
             JOIN clientes c ON ot.cliente_id = c.id
             JOIN usuarios u ON ot.tecnico_id = u.id
             $whereClause
             ORDER BY ot.fecha_trabajo DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM ordenes_trabajo ot $whereClause",
            $params
        )['count'];
        
        return json_encode([
            'success' => true,
            'data' => [
                'data' => $ordenes,
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => (int)$total,
                'last_page' => ceil($total / $perPage)
            ]
        ]);
    }
    
    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['numero_parte', 'cliente_id', 'tecnico_id', 'fecha_trabajo', 'descripcion_trabajo'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                http_response_code(400);
                return json_encode([
                    'success' => false,
                    'message' => "El campo $field es requerido"
                ]);
            }
        }
        
        // Insert orden
        $sql = "INSERT INTO ordenes_trabajo (numero_parte, cliente_id, tecnico_id, fecha_trabajo,
                hora_inicio, hora_fin, equipo_marca, equipo_modelo, equipo_serie,
                descripcion_trabajo, observaciones, estado, total, sincronizado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['numero_parte'],
            $data['cliente_id'],
            $data['tecnico_id'],
            $data['fecha_trabajo'],
            $data['hora_inicio'] ?? null,
            $data['hora_fin'] ?? null,
            $data['equipo_marca'] ?? null,
            $data['equipo_modelo'] ?? null,
            $data['equipo_serie'] ?? null,
            $data['descripcion_trabajo'],
            $data['observaciones'] ?? null,
            $data['estado'] ?? 'pendiente',
            0, // total calculado después
            $data['sincronizado'] ?? true
        ];
        
        $this->db->execute($sql, $params);
        $ordenId = $this->db->lastInsertId();
        
        // Insert repuestos si hay
        $total = 0;
        if (isset($data['repuestos']) && is_array($data['repuestos'])) {
            foreach ($data['repuestos'] as $repuesto) {
                $subtotal = $repuesto['cantidad'] * $repuesto['precio_unitario'];
                $total += $subtotal;
                
                $this->db->execute(
                    "INSERT INTO orden_repuestos (orden_trabajo_id, repuesto_id, cantidad, precio_unitario, subtotal)
                     VALUES (?, ?, ?, ?, ?)",
                    [$ordenId, $repuesto['repuesto_id'], $repuesto['cantidad'], 
                     $repuesto['precio_unitario'], $subtotal]
                );
            }
            
            // Update total
            $this->db->execute(
                "UPDATE ordenes_trabajo SET total = ? WHERE id = ?",
                [$total, $ordenId]
            );
        }
        
        return json_encode([
            'success' => true,
            'data' => ['id' => $ordenId],
            'message' => 'Orden creada exitosamente'
        ]);
    }
}
