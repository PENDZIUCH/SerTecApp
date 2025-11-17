<?php
// SerTecApp - Configuracion Controller

class ConfiguracionController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Obtener todas las configuraciones (público)
    public function index() {
        $configs = $this->db->fetchAll(
            "SELECT clave, valor, tipo FROM configuracion_app ORDER BY clave"
        );
        
        // Convertir array a objeto clave-valor
        $result = [];
        foreach ($configs as $config) {
            $result[$config['clave']] = $config['valor'];
        }
        
        return json_encode([
            'success' => true,
            'data' => $result
        ]);
    }
    
    // Obtener una configuración específica
    public function show($clave) {
        $config = $this->db->fetchOne(
            "SELECT * FROM configuracion_app WHERE clave = ?",
            [$clave]
        );
        
        if (!$config) {
            http_response_code(404);
            return json_encode([
                'success' => false,
                'message' => 'Configuración no encontrada'
            ]);
        }
        
        return json_encode([
            'success' => true,
            'data' => $config
        ]);
    }
    
    // Actualizar configuración (solo admin)
    public function update($clave) {
        // TODO: Verificar que el usuario sea admin
        // Por ahora asumimos que está autenticado
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['valor'])) {
            http_response_code(400);
            return json_encode([
                'success' => false,
                'message' => 'El campo valor es requerido'
            ]);
        }
        
        // Verificar que la clave existe
        $config = $this->db->fetchOne(
            "SELECT id FROM configuracion_app WHERE clave = ?",
            [$clave]
        );
        
        if (!$config) {
            http_response_code(404);
            return json_encode([
                'success' => false,
                'message' => 'Configuración no encontrada'
            ]);
        }
        
        // Actualizar
        $this->db->execute(
            "UPDATE configuracion_app SET valor = ?, modificado_por = 1 WHERE clave = ?",
            [$data['valor'], $clave]
        );
        
        return $this->show($clave);
    }
}
