-- SerTecApp - Esquema de Base de Datos MySQL
-- Versión: 1.0.0
-- Fecha: Noviembre 2025

-- ============================================
-- TABLAS PRINCIPALES
-- ============================================

-- Usuarios del sistema
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'tecnico', 'supervisor') DEFAULT 'tecnico',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clientes
CREATE TABLE clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(200) NOT NULL,
    razon_social VARCHAR(250),
    cuit VARCHAR(20),
    tipo ENUM('abonado', 'esporadico') DEFAULT 'esporadico',
    frecuencia_visitas TINYINT DEFAULT 0 COMMENT '0=esporadico, 1,2,3=visitas mensuales',
    direccion TEXT,
    localidad VARCHAR(100),
    provincia VARCHAR(100),
    codigo_postal VARCHAR(10),
    telefono VARCHAR(50),
    email VARCHAR(150),
    contacto_nombre VARCHAR(150),
    contacto_telefono VARCHAR(50),
    estado ENUM('activo', 'inactivo', 'moroso') DEFAULT 'activo',
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_frecuencia (frecuencia_visitas),
    INDEX idx_estado (estado),
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Abonos
CREATE TABLE abonos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    frecuencia_visitas TINYINT NOT NULL COMMENT '1, 2 o 3 visitas mensuales',
    monto_mensual DECIMAL(10,2) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    estado ENUM('activo', 'suspendido', 'finalizado') DEFAULT 'activo',
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    INDEX idx_cliente (cliente_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha_inicio (fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuración de frecuencias y colores (SISTEMA CONFIGURABLE)
CREATE TABLE config_frecuencias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    frecuencia_visitas TINYINT NOT NULL UNIQUE,
    nombre VARCHAR(50) NOT NULL,
    color_hex VARCHAR(7) NOT NULL COMMENT 'Ejemplo: #00FF00',
    color_nombre VARCHAR(50),
    activo BOOLEAN DEFAULT TRUE,
    orden INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_frecuencia (frecuencia_visitas),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Datos iniciales de frecuencias
INSERT INTO config_frecuencias (frecuencia_visitas, nombre, color_hex, color_nombre, orden) VALUES
(1, '1 Visita Mensual', '#22C55E', 'Verde', 1),
(2, '2 Visitas Mensuales', '#EAB308', 'Amarillo', 2),
(3, '3 Visitas Mensuales', '#EF4444', 'Rojo', 3),
(4, '4 Visitas Mensuales', '#3B82F6', 'Azul', 4),
(5, '5+ Visitas Mensuales', '#8B5CF6', 'Morado', 5);

-- Órdenes de Trabajo (Partes)
CREATE TABLE ordenes_trabajo (
    id INT PRIMARY KEY AUTO_INCREMENT,
    numero_parte VARCHAR(50) UNIQUE NOT NULL,
    cliente_id INT NOT NULL,
    tecnico_id INT NOT NULL,
    fecha_trabajo DATE NOT NULL,
    hora_inicio TIME,
    hora_fin TIME,
    equipo_marca VARCHAR(100),
    equipo_modelo VARCHAR(100),
    equipo_serie VARCHAR(100),
    descripcion_trabajo TEXT NOT NULL,
    observaciones TEXT,
    estado ENUM('pendiente', 'en_progreso', 'completado', 'cancelado') DEFAULT 'pendiente',
    firma_cliente TEXT COMMENT 'Base64 de firma digital',
    total DECIMAL(10,2) DEFAULT 0,
    sincronizado BOOLEAN DEFAULT FALSE COMMENT 'Para control offline/online',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id),
    INDEX idx_numero_parte (numero_parte),
    INDEX idx_cliente (cliente_id),
    INDEX idx_tecnico (tecnico_id),
    INDEX idx_fecha (fecha_trabajo),
    INDEX idx_estado (estado),
    INDEX idx_sincronizado (sincronizado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Repuestos/Productos
CREATE TABLE repuestos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    descripcion VARCHAR(250) NOT NULL,
    categoria VARCHAR(100),
    marca VARCHAR(100),
    stock_actual INT DEFAULT 0,
    stock_minimo INT DEFAULT 0,
    precio_unitario DECIMAL(10,2) NOT NULL,
    proveedor VARCHAR(150),
    ubicacion VARCHAR(100) COMMENT 'Ubicación física en depósito',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_categoria (categoria),
    INDEX idx_stock (stock_actual),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Repuestos utilizados en órdenes de trabajo
CREATE TABLE orden_repuestos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    orden_trabajo_id INT NOT NULL,
    repuesto_id INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (orden_trabajo_id) REFERENCES ordenes_trabajo(id) ON DELETE CASCADE,
    FOREIGN KEY (repuesto_id) REFERENCES repuestos(id),
    INDEX idx_orden (orden_trabajo_id),
    INDEX idx_repuesto (repuesto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Equipos en Taller
CREATE TABLE taller_equipos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT,
    origen VARCHAR(150) COMMENT 'De dónde viene el equipo',
    equipo_marca VARCHAR(100),
    equipo_modelo VARCHAR(100),
    equipo_serie VARCHAR(100),
    fecha_ingreso DATE NOT NULL,
    fecha_salida DATE,
    estado ENUM('en_taller', 'esperando_repuesto', 'reparado', 'entregado') DEFAULT 'en_taller',
    diagnostico TEXT,
    reparacion_realizada TEXT,
    observaciones TEXT,
    tecnico_responsable_id INT,
    costo_reparacion DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (tecnico_responsable_id) REFERENCES usuarios(id),
    INDEX idx_estado (estado),
    INDEX idx_fecha_ingreso (fecha_ingreso),
    INDEX idx_cliente (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Control de visitas de abonos
CREATE TABLE visitas_abono (
    id INT PRIMARY KEY AUTO_INCREMENT,
    abono_id INT NOT NULL,
    orden_trabajo_id INT NOT NULL,
    mes INT NOT NULL COMMENT '1-12',
    anio INT NOT NULL,
    numero_visita TINYINT NOT NULL COMMENT 'Qué visita del mes es (1, 2 o 3)',
    fecha_visita DATE NOT NULL,
    completada BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (abono_id) REFERENCES abonos(id) ON DELETE CASCADE,
    FOREIGN KEY (orden_trabajo_id) REFERENCES ordenes_trabajo(id),
    INDEX idx_abono (abono_id),
    INDEX idx_mes_anio (mes, anio),
    UNIQUE KEY unique_visita (abono_id, mes, anio, numero_visita)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Facturas (integración Tango)
CREATE TABLE facturas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    numero_factura VARCHAR(50) UNIQUE,
    tipo ENUM('A', 'B', 'C') DEFAULT 'B',
    fecha_emision DATE NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'enviada_tango', 'aprobada', 'error') DEFAULT 'pendiente',
    tango_response TEXT COMMENT 'JSON response de Tango API',
    orden_trabajo_id INT COMMENT 'Si la factura es de una orden específica',
    abono_id INT COMMENT 'Si la factura es de un abono',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (orden_trabajo_id) REFERENCES ordenes_trabajo(id),
    FOREIGN KEY (abono_id) REFERENCES abonos(id),
    INDEX idx_cliente (cliente_id),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha_emision)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Items de facturas
CREATE TABLE factura_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    factura_id INT NOT NULL,
    descripcion VARCHAR(250) NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE,
    INDEX idx_factura (factura_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log de sincronización (para PWA offline)
CREATE TABLE sync_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tabla VARCHAR(100) NOT NULL,
    registro_id INT NOT NULL,
    accion ENUM('create', 'update', 'delete') NOT NULL,
    datos_json TEXT,
    sincronizado BOOLEAN DEFAULT FALSE,
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_sincronizacion TIMESTAMP NULL,
    INDEX idx_sincronizado (sincronizado),
    INDEX idx_tabla (tabla),
    INDEX idx_fecha_cambio (fecha_cambio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario administrador por defecto (password: admin123)
INSERT INTO usuarios (nombre, email, password_hash, rol) VALUES
('Admin SerTecApp', 'admin@sertecapp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Vistas útiles
CREATE OR REPLACE VIEW v_clientes_abonados AS
SELECT c.id, c.nombre, c.tipo, c.frecuencia_visitas, cf.color_hex, cf.color_nombre,
       a.id as abono_id, a.monto_mensual, a.fecha_inicio, a.estado as abono_estado
FROM clientes c
LEFT JOIN abonos a ON c.id = a.cliente_id AND a.estado = 'activo'
LEFT JOIN config_frecuencias cf ON c.frecuencia_visitas = cf.frecuencia_visitas
WHERE c.tipo = 'abonado' AND c.estado = 'activo';

CREATE OR REPLACE VIEW v_stock_bajo AS
SELECT codigo, descripcion, stock_actual, stock_minimo, 
       (stock_minimo - stock_actual) as faltante, proveedor
FROM repuestos WHERE stock_actual <= stock_minimo AND activo = TRUE;
