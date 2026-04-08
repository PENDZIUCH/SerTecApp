<?php
// SerTecApp - Reportes Controller
// Sistema de reportes y estadísticas

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Response.php';

class ReportesController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * GET /reportes/dashboard
     * Resumen general para dashboard
     */
    public function dashboard() {
        AuthMiddleware::required();
        
        // Clientes
        $clientes = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN tipo = 'abonado' THEN 1 ELSE 0 END) as abonados,
                SUM(CASE WHEN tipo = 'esporadico' THEN 1 ELSE 0 END) as esporadicos,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado = 'moroso' THEN 1 ELSE 0 END) as morosos
            FROM clientes
        ");
        
        // Órdenes de trabajo
        $ordenes = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'en_progreso' THEN 1 ELSE 0 END) as en_progreso,
                SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN DATE(fecha) = CURDATE() THEN 1 ELSE 0 END) as hoy
            FROM ordenes_trabajo
        ");
        
        // Abonos
        $abonos = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado = 'vencido' THEN 1 ELSE 0 END) as vencidos,
                SUM(CASE WHEN fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as proximos_vencer
            FROM abonos
        ");
        
        // Equipos en taller
        $taller = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'ingresado' THEN 1 ELSE 0 END) as ingresados,
                SUM(CASE WHEN estado = 'en_reparacion' THEN 1 ELSE 0 END) as en_reparacion,
                SUM(CASE WHEN estado = 'listo' THEN 1 ELSE 0 END) as listos,
                SUM(CASE WHEN estado = 'entregado' THEN 1 ELSE 0 END) as entregados
            FROM taller
        ");
        
        // Facturación del mes
        $facturacion = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total_facturas,
                COALESCE(SUM(total), 0) as total_facturado,
                SUM(CASE WHEN enviada = 1 THEN 1 ELSE 0 END) as enviadas,
                SUM(CASE WHEN enviada = 0 THEN 1 ELSE 0 END) as pendientes
            FROM facturas
            WHERE DATE_FORMAT(fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
        ");
        
        // Repuestos con stock bajo
        $repuestos = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN stock_actual <= stock_minimo THEN 1 ELSE 0 END) as stock_bajo,
                SUM(CASE WHEN stock_actual = 0 THEN 1 ELSE 0 END) as sin_stock
            FROM repuestos
        ");
        
        return Response::success([
            'clientes' => $clientes,
            'ordenes' => $ordenes,
            'abonos' => $abonos,
            'taller' => $taller,
            'facturacion' => $facturacion,
            'repuestos' => $repuestos,
            'fecha_reporte' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * GET /reportes/clientes-activos
     * Listado de clientes activos con detalles
     */
    public function clientesActivos() {
        AuthMiddleware::required();
        
        $clientes = $this->db->fetchAll("
            SELECT 
                c.*,
                COUNT(DISTINCT a.id) as abonos_activos,
                COUNT(DISTINCT o.id) as ordenes_mes_actual,
                MAX(o.fecha) as ultima_orden
            FROM clientes c
            LEFT JOIN abonos a ON c.id = a.cliente_id AND a.estado = 'activo'
            LEFT JOIN ordenes_trabajo o ON c.id = o.cliente_id 
                AND DATE_FORMAT(o.fecha, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
            WHERE c.estado = 'activo'
            GROUP BY c.id
            ORDER BY c.nombre ASC
        ");
        
        return Response::success($clientes);
    }
    
    /**
     * GET /reportes/abonos-vencer
     * Abonos próximos a vencer
     */
    public function abonosVencer() {
        AuthMiddleware::required();
        
        $dias = $_GET['dias'] ?? 15;
        
        $abonos = $this->db->fetchAll("
            SELECT 
                a.*,
                c.nombre as cliente_nombre,
                c.telefono as cliente_telefono,
                c.email as cliente_email,
                cf.color as color_frecuencia,
                DATEDIFF(a.fecha_vencimiento, CURDATE()) as dias_restantes
            FROM abonos a
            LEFT JOIN clientes c ON a.cliente_id = c.id
            LEFT JOIN colores_frecuencias cf ON a.frecuencia_visitas = cf.frecuencia_visitas
            WHERE a.estado = 'activo'
            AND a.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY a.fecha_vencimiento ASC
        ", [$dias]);
        
        return Response::success([
            'dias_antelacion' => (int)$dias,
            'total' => count($abonos),
            'abonos' => $abonos
        ]);
    }
    
    /**
     * GET /reportes/taller-por-tecnico
     * Equipos en taller agrupados por técnico
     */
    public function tallerPorTecnico() {
        AuthMiddleware::required();
        
        $tecnicos = $this->db->fetchAll("
            SELECT 
                COALESCE(tec.id, 0) as tecnico_id,
                COALESCE(tec.nombre, 'Sin asignar') as tecnico_nombre,
                COUNT(t.id) as total_equipos,
                SUM(CASE WHEN t.estado = 'ingresado' THEN 1 ELSE 0 END) as ingresados,
                SUM(CASE WHEN t.estado = 'en_reparacion' THEN 1 ELSE 0 END) as en_reparacion,
                SUM(CASE WHEN t.estado = 'esperando_repuesto' THEN 1 ELSE 0 END) as esperando_repuesto,
                SUM(CASE WHEN t.estado = 'listo' THEN 1 ELSE 0 END) as listos,
                AVG(DATEDIFF(COALESCE(t.fecha_salida, CURDATE()), t.fecha_ingreso)) as promedio_dias
            FROM taller t
            LEFT JOIN usuarios tec ON t.tecnico_id = tec.id
            WHERE t.estado IN ('ingresado', 'en_reparacion', 'esperando_repuesto', 'listo')
            GROUP BY tec.id, tec.nombre
            ORDER BY total_equipos DESC
        ");
        
        // Detalle de equipos por técnico
        foreach ($tecnicos as &$tecnico) {
            $equipos = $this->db->fetchAll("
                SELECT 
                    t.id,
                    t.equipo,
                    t.marca,
                    t.modelo,
                    t.estado,
                    t.fecha_ingreso,
                    c.nombre as cliente_nombre,
                    DATEDIFF(CURDATE(), t.fecha_ingreso) as dias_en_taller
                FROM taller t
                LEFT JOIN clientes c ON t.cliente_id = c.id
                WHERE " . ($tecnico['tecnico_id'] == 0 ? "t.tecnico_id IS NULL" : "t.tecnico_id = ?") . "
                AND t.estado IN ('ingresado', 'en_reparacion', 'esperando_repuesto', 'listo')
                ORDER BY t.fecha_ingreso ASC
            ", $tecnico['tecnico_id'] == 0 ? [] : [$tecnico['tecnico_id']]);
            
            $tecnico['equipos'] = $equipos;
        }
        
        return Response::success($tecnicos);
    }
    
    /**
     * GET /reportes/facturacion-mes
     * Reporte de facturación mensual
     */
    public function facturacionMes() {
        AuthMiddleware::required();
        
        $mes = $_GET['mes'] ?? date('Y-m');
        
        // Resumen general
        $resumen = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total_facturas,
                COALESCE(SUM(total), 0) as total_facturado,
                COALESCE(SUM(subtotal), 0) as subtotal,
                COALESCE(SUM(iva), 0) as iva,
                SUM(CASE WHEN tipo = 'A' THEN 1 ELSE 0 END) as facturas_tipo_a,
                SUM(CASE WHEN tipo = 'B' THEN 1 ELSE 0 END) as facturas_tipo_b,
                SUM(CASE WHEN tipo = 'C' THEN 1 ELSE 0 END) as facturas_tipo_c,
                COALESCE(SUM(CASE WHEN tipo = 'A' THEN total ELSE 0 END), 0) as monto_tipo_a,
                COALESCE(SUM(CASE WHEN tipo = 'B' THEN total ELSE 0 END), 0) as monto_tipo_b,
                COALESCE(SUM(CASE WHEN tipo = 'C' THEN total ELSE 0 END), 0) as monto_tipo_c
            FROM facturas
            WHERE DATE_FORMAT(fecha, '%Y-%m') = ?
        ", [$mes]);
        
        // Top 10 clientes por facturación
        $topClientes = $this->db->fetchAll("
            SELECT 
                c.id,
                c.nombre,
                c.razon_social,
                COUNT(f.id) as cantidad_facturas,
                SUM(f.total) as total_facturado
            FROM facturas f
            LEFT JOIN clientes c ON f.cliente_id = c.id
            WHERE DATE_FORMAT(f.fecha, '%Y-%m') = ?
            GROUP BY c.id
            ORDER BY total_facturado DESC
            LIMIT 10
        ", [$mes]);
        
        // Facturación por día del mes
        $facturacionDiaria = $this->db->fetchAll("
            SELECT 
                DATE(fecha) as fecha,
                COUNT(*) as cantidad,
                SUM(total) as total
            FROM facturas
            WHERE DATE_FORMAT(fecha, '%Y-%m') = ?
            GROUP BY DATE(fecha)
            ORDER BY fecha ASC
        ", [$mes]);
        
        return Response::success([
            'mes' => $mes,
            'resumen' => $resumen,
            'top_clientes' => $topClientes,
            'facturacion_diaria' => $facturacionDiaria
        ]);
    }
    
    /**
     * GET /reportes/ordenes-trabajo
     * Reporte de órdenes de trabajo
     */
    public function ordenesTrabaajo() {
        AuthMiddleware::required();
        
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        
        $ordenes = $this->db->fetchAll("
            SELECT 
                o.*,
                c.nombre as cliente_nombre,
                u.nombre as tecnico_nombre,
                COUNT(DISTINCT or_rep.id) as cantidad_repuestos,
                COALESCE(SUM(or_rep.cantidad * r.precio_venta), 0) as costo_repuestos
            FROM ordenes_trabajo o
            LEFT JOIN clientes c ON o.cliente_id = c.id
            LEFT JOIN usuarios u ON o.tecnico_id = u.id
            LEFT JOIN orden_repuestos or_rep ON o.id = or_rep.orden_trabajo_id
            LEFT JOIN repuestos r ON or_rep.repuesto_id = r.id
            WHERE o.fecha BETWEEN ? AND ?
            GROUP BY o.id
            ORDER BY o.fecha DESC
        ", [$desde, $hasta]);
        
        // Estadísticas
        $stats = [
            'total_ordenes' => count($ordenes),
            'pendientes' => 0,
            'en_progreso' => 0,
            'completadas' => 0,
            'costo_total_repuestos' => 0
        ];
        
        foreach ($ordenes as $orden) {
            if ($orden['estado'] == 'pendiente') $stats['pendientes']++;
            if ($orden['estado'] == 'en_progreso') $stats['en_progreso']++;
            if ($orden['estado'] == 'completado') $stats['completadas']++;
            $stats['costo_total_repuestos'] += $orden['costo_repuestos'];
        }
        
        return Response::success([
            'periodo' => [
                'desde' => $desde,
                'hasta' => $hasta
            ],
            'estadisticas' => $stats,
            'ordenes' => $ordenes
        ]);
    }
    
    /**
     * GET /reportes/repuestos-mas-usados
     * Repuestos más utilizados
     */
    public function repuestosMasUsados() {
        AuthMiddleware::required();
        
        $limite = $_GET['limite'] ?? 20;
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        
        $repuestos = $this->db->fetchAll("
            SELECT 
                r.id,
                r.codigo,
                r.descripcion,
                r.marca,
                r.stock_actual,
                COUNT(or_rep.id) as veces_usado,
                SUM(or_rep.cantidad) as cantidad_total_usada,
                SUM(or_rep.cantidad * r.precio_venta) as valor_total
            FROM repuestos r
            INNER JOIN orden_repuestos or_rep ON r.id = or_rep.repuesto_id
            INNER JOIN ordenes_trabajo o ON or_rep.orden_trabajo_id = o.id
            WHERE o.fecha BETWEEN ? AND ?
            GROUP BY r.id
            ORDER BY cantidad_total_usada DESC
            LIMIT ?
        ", [$desde, $hasta, $limite]);
        
        return Response::success([
            'periodo' => [
                'desde' => $desde,
                'hasta' => $hasta
            ],
            'repuestos' => $repuestos
        ]);
    }
}
