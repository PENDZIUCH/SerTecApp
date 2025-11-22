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
        
        return Response::success([
            'data' => $clientes,
            'current_page' => (int)$page,
            'per_page' => (int)$perPage,
            'total' => (int)$total,
            'last_page' => ceil($total / $perPage)
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
            return Response::notFound('Cliente no encontrado');
        }
        
        return Response::success($cliente);
    }
    
    public function store() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Sanitizar
        $clean = Sanitizer::fields($data, [
            'nombre' => 'string',
            'razon_social' => 'string',
            'cuit' => 'cuit',
            'tipo' => 'string',
            'frecuencia_visitas' => 'int',
            'direccion' => 'string',
            'localidad' => 'string',
            'provincia' => 'string',
            'codigo_postal' => 'string',
            'telefono' => 'phone',
            'email' => 'email',
            'contacto_nombre' => 'string',
            'contacto_telefono' => 'phone',
            'estado' => 'string',
            'notas' => 'string'
        ]);
        
        // Validar
        $validator = new Validator($clean);
        $validator->validate([
            'nombre' => 'required|min:3|max:200',
            'tipo' => 'in:abonado,esporadico',
            'frecuencia_visitas' => 'integer|min:0|max:10',
            'email' => 'email',
            'cuit' => 'cuit'
        ]);
        
        if ($validator->fails()) {
            return Response::validationError($validator->errors());
        }
        
        $sql = "INSERT INTO clientes (nombre, razon_social, cuit, tipo, frecuencia_visitas,
                direccion, localidad, provincia, codigo_postal, telefono, email, 
                contacto_nombre, contacto_telefono, estado, notas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $clean['nombre'],
            $clean['razon_social'] ?? null,
            $clean['cuit'] ?? null,
            $clean['tipo'] ?? 'esporadico',
            $clean['frecuencia_visitas'] ?? 0,
            $clean['direccion'] ?? null,
            $clean['localidad'] ?? null,
            $clean['provincia'] ?? null,
            $clean['codigo_postal'] ?? null,
            $clean['telefono'] ?? null,
            $clean['email'] ?? null,
            $clean['contacto_nombre'] ?? null,
            $clean['contacto_telefono'] ?? null,
            $clean['estado'] ?? 'activo',
            $clean['notas'] ?? null
        ];
        
        $this->db->execute($sql, $params);
        $id = $this->db->lastInsertId();
        
        return $this->show($id);
    }
    
    public function update($id) {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $cliente = $this->db->fetchOne("SELECT id FROM clientes WHERE id = ?", [$id]);
        if (!$cliente) {
            return Response::notFound('Cliente no encontrado');
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
            return Response::error('No hay campos para actualizar', 400);
        }
        
        $params[] = $id;
        $sql = "UPDATE clientes SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->execute($sql, $params);
        
        return $this->show($id);
    }
    
    public function delete($id) {
        $this->db->execute("DELETE FROM clientes WHERE id = ?", [$id]);
        
        return Response::success(null, 'Cliente eliminado exitosamente');
    }
}
