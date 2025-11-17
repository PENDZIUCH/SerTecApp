<?php
// SerTecApp - Repuestos Controller

class RepuestosController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function index() {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 100;
        $offset = ($page - 1) * $perPage;
        
        $where = [];
        $params = [];
        
        if (isset($_GET['search'])) {
            $where[] = "(codigo LIKE ? OR descripcion LIKE ?)";
            $search = '%' . $_GET['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM repuestos $whereClause",
            $params
        )['count'];
        
        $repuestos = $this->db->fetchAll(
            "SELECT * FROM repuestos $whereClause ORDER BY descripcion LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );
        
        return json_encode([
            'success' => true,
            'data' => [
                'data' => $repuestos,
                'current_page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => (int)$total,
                'last_page' => ceil($total / $perPage)
            ]
        ]);
    }
    
    public function show($id) {
        $repuesto = $this->db->fetchOne(
            "SELECT * FROM repuestos WHERE id = ?",
            [$id]
        );
        
        if (!$repuesto) {
            http_response_code(404);
            return json_encode([
                'success' => false,
                'message' => 'Repuesto no encontrado'
            ]);
        }
        
        return json_encode([
            'success' => true,
            'data' => $repuesto
        ]);
    }
}
