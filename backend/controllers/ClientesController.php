<?php
// SerTecApp - Clientes Controller

class ClientesController {
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
        
        if (isset($_GET['tipo'])) {
            $where[] = "tipo = ?";
            $params[] = $_GET['tipo'];
        }
        
        if (isset($_GET['estado'])) {
            $where[] = "estado = ?";
            $params[] = $_GET['estado'];
        }
        
        if (isset($_GET['search'])) {
            $where[] = "(nombre LIKE ? OR razon_social LIKE ? OR cuit LIKE ?)";
            $search = '%' . $_GET['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM clientes $whereClause",
            $params
        )['count'];
        
        $clientes = $this->db->fetchAll(
            "SELECT * FROM clientes $whereClause ORDER BY nombre LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        
        return json_encode([
            'success' => true,
            'data' => [
                'data' => $clientes,
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => (int)$total,
                'last_page' => ceil($total / $perPage)
            ]
        ]);
    }
    
    public function show($id) {
        $cliente = $this->db->fetchOne(
            "SELECT c.*, 
                    a.id as abono_id, a.monto_mensual, a.fecha_inicio, a.estado as abono_estado,
                    cf.color_hex, cf.color_nombre
             FROM clientes c
             LEFT JOIN abonos a ON c.id = a.cliente_id AND a.estado = 'activo'
             LEFT JOIN config_frecuencias cf ON c.frecuencia_visitas = cf.frecuencia_visitas
             WHERE c.id = ?",
            [$id]
        );
        
        if (!$cliente) {
            http_response_code(404);
            return json_encode([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ]);
        }
        
        return json_encode([
            'success' => true,
            'data' => $cliente
        ]);
    }
    
    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $required = ['nombre'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                http_response_code(400);
                return json_encode([
                    'success' => false,
                    'message' => "El campo $field es requerido"
                ]);
            }
        }
        
        $sql = "INSERT INTO clientes (nombre, razon_social, cuit, tipo, frecuencia_visitas,
                direccion, localidad, provincia, codigo_postal, telefono, email, 
                contacto_nombre, contacto_telefono, estado, notas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['nombre'],
            $data['razon_social'] ?? null,
            $data['cuit'] ?? null,
            $data['tipo'] ?? 'esporadico',
            $data['frecuencia_visitas'] ?? 0,
            $data['direccion'] ?? null,
            $data['localidad'] ?? null,
            $data['provincia'] ?? null,
            $data['codigo_postal'] ?? null,
            $data['telefono'] ?? null,
            $data['email'] ?? null,
            $data['contacto_nombre'] ?? null,
            $data['contacto_telefono'] ?? null,
            $data['estado'] ?? 'activo',
            $data['notas'] ?? null
        ];
        
        $this->db->execute($sql, $params);
        $id = $this->db->lastInsertId();
        
        return $this->show($id);
    }
    
    public function update($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $cliente = $this->db->fetchOne("SELECT id FROM clientes WHERE id = ?", [$id]);
        if (!$cliente) {
            http_response_code(404);
            return json_encode([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ]);
        }
        
        $fields = [];
        $params = [];
        
        $allowedFields = ['nombre', 'razon_social', 'cuit', 'tipo', 'frecuencia_visitas',
                         'direccion', 'localidad', 'provincia', 'codigo_postal', 'telefono',
                         'email', 'contacto_nombre', 'contacto_telefono', 'estado', 'notas'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            http_response_code(400);
            return json_encode([
                'success' => false,
                'message' => 'No hay campos para actualizar'
            ]);
        }
        
        $params[] = $id;
        $sql = "UPDATE clientes SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->execute($sql, $params);
        
        return $this->show($id);
    }
    
    public function delete($id) {
        $this->db->execute("DELETE FROM clientes WHERE id = ?", [$id]);
        
        return json_encode([
            'success' => true,
            'message' => 'Cliente eliminado exitosamente'
        ]);
    }
}
