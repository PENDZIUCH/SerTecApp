-- Tabla de configuración global de la aplicación
CREATE TABLE IF NOT EXISTS configuracion_app (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    descripcion VARCHAR(255),
    tipo ENUM('texto', 'numero', 'booleano', 'imagen', 'json') DEFAULT 'texto',
    modificado_por INT,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (modificado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuraciones por defecto
INSERT INTO configuracion_app (clave, valor, descripcion, tipo) VALUES
('app_nombre', 'SerTecApp', 'Nombre de la aplicación', 'texto'),
('app_logo_url', NULL, 'URL del logo personalizado', 'imagen'),
('app_color_primario', '#3B82F6', 'Color primario de la aplicación', 'texto'),
('empresa_nombre', NULL, 'Nombre de la empresa', 'texto'),
('empresa_telefono', NULL, 'Teléfono de contacto', 'texto'),
('empresa_email', NULL, 'Email de contacto', 'texto'),
('empresa_direccion', NULL, 'Dirección física', 'texto');
