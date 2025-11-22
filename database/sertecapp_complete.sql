-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: sertecapp
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `abonos`
--

DROP TABLE IF EXISTS `abonos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `abonos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `frecuencia_visitas` tinyint NOT NULL COMMENT '1, 2 o 3 visitas mensuales',
  `monto_mensual` decimal(10,2) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('activo','suspendido','finalizado') COLLATE utf8mb4_unicode_ci DEFAULT 'activo',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_inicio` (`fecha_inicio`),
  CONSTRAINT `abonos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `abonos`
--

LOCK TABLES `abonos` WRITE;
/*!40000 ALTER TABLE `abonos` DISABLE KEYS */;
/*!40000 ALTER TABLE `abonos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clientes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `razon_social` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cuit` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('abonado','esporadico') COLLATE utf8mb4_unicode_ci DEFAULT 'esporadico',
  `frecuencia_visitas` tinyint DEFAULT '0' COMMENT '0=esporadico, 1,2,3=visitas mensuales',
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `localidad` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provincia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_postal` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacto_nombre` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacto_telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','inactivo','moroso') COLLATE utf8mb4_unicode_ci DEFAULT 'activo',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_frecuencia` (`frecuencia_visitas`),
  KEY `idx_estado` (`estado`),
  KEY `idx_nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'Club Ateneo Gym','Club Deportivo Ateneo S.A.','30-12345678-9','abonado',3,'Av. Principal 1234','Don Torcuato','Buenos Aires',NULL,'011-4444-5555','contacto@ateneoclub.com',NULL,NULL,'activo',NULL,'2025-11-14 22:14:04','2025-11-15 04:30:37'),(2,'Club Ateneo Gym','Club Deportivo Ateneo S.A.','30-12345678-9','abonado',2,'Av. Principal 1234','Don Torcuato','Buenos Aires',NULL,'011-4444-5555','contacto@ateneoclub.com',NULL,NULL,'activo',NULL,'2025-11-14 23:13:35','2025-11-16 02:49:49'),(3,'Test Gym 1763162269874','Club Deportivo Test S.A.','30-1763162269874-9','abonado',2,'Av. Test 1234','Don Torcuato','Buenos Aires',NULL,'011-4444-5555','test1763162269874@test.com',NULL,NULL,'activo',NULL,'2025-11-14 23:17:49','2025-11-14 23:17:49'),(4,'Test Gym 1763162310949','Club Deportivo Test S.A.','30-1763162310949-9','abonado',2,'Av. Test 1234','Don Torcuato','Buenos Aires',NULL,'011-4444-5555','test1763162310949@test.com',NULL,NULL,'activo',NULL,'2025-11-14 23:18:30','2025-11-14 23:18:30'),(5,'Test Gym 1763162360670','Club Deportivo Test S.A.','30-1763162360670-9','abonado',2,'Av. Test 1234','Don Torcuato','Buenos Aires',NULL,'011-4444-5555','test1763162360670@test.com',NULL,NULL,'activo',NULL,'2025-11-14 23:19:20','2025-11-15 04:34:02'),(6,'Gym Fitness Center','','','abonado',0,'','','',NULL,'011-5555-6666','info@gymfitness.com',NULL,NULL,'activo',NULL,'2025-11-15 04:26:17','2025-11-15 04:30:56'),(7,'Hugo Pendziuch','PENDZIUCH.com','','esporadico',0,'','','',NULL,'','',NULL,NULL,'activo',NULL,'2025-11-15 04:31:28','2025-11-16 02:49:59'),(8,'Test Gym 1763181464061','Club Deportivo Test S.A.','30-1763181464061-9','abonado',2,'Av. Test 1234','Don Torcuato','Buenos Aires',NULL,'011-4444-5555','test1763181464061@test.com',NULL,NULL,'activo',NULL,'2025-11-15 04:37:44','2025-11-15 04:37:44');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config_frecuencias`
--

DROP TABLE IF EXISTS `config_frecuencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_frecuencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `frecuencia_visitas` tinyint NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color_hex` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ejemplo: #00FF00',
  `color_nombre` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `orden` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `frecuencia_visitas` (`frecuencia_visitas`),
  KEY `idx_frecuencia` (`frecuencia_visitas`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config_frecuencias`
--

LOCK TABLES `config_frecuencias` WRITE;
/*!40000 ALTER TABLE `config_frecuencias` DISABLE KEYS */;
INSERT INTO `config_frecuencias` VALUES (1,1,'1 Visita Mensual','#22C55E','Verde',1,1,'2025-11-14 20:17:34','2025-11-14 20:17:34'),(2,2,'2 Visitas Mensuales','#EAB308','Amarillo',1,2,'2025-11-14 20:17:34','2025-11-14 20:17:34'),(3,3,'3 Visitas Mensuales','#EF4444','Rojo',1,3,'2025-11-14 20:17:34','2025-11-14 20:17:34'),(4,4,'4 Visitas Mensuales','#3B82F6','Azul',1,4,'2025-11-14 20:17:34','2025-11-14 20:17:34'),(5,5,'5+ Visitas Mensuales','#8B5CF6','Morado',1,5,'2025-11-14 20:17:34','2025-11-14 20:17:34');
/*!40000 ALTER TABLE `config_frecuencias` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion_app`
--

DROP TABLE IF EXISTS `configuracion_app`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuracion_app` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('texto','numero','booleano','imagen','json') COLLATE utf8mb4_unicode_ci DEFAULT 'texto',
  `modificado_por` int DEFAULT NULL,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`),
  KEY `modificado_por` (`modificado_por`),
  CONSTRAINT `configuracion_app_ibfk_1` FOREIGN KEY (`modificado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion_app`
--

LOCK TABLES `configuracion_app` WRITE;
/*!40000 ALTER TABLE `configuracion_app` DISABLE KEYS */;
INSERT INTO `configuracion_app` VALUES (1,'app_nombre','SerTecApp','Nombre de la aplicaci├│n','texto',NULL,'2025-11-15 16:30:43'),(2,'app_logo_url',NULL,'URL del logo personalizado','imagen',NULL,'2025-11-15 16:30:43'),(3,'app_color_primario','#3B82F6','Color primario de la aplicaci├│n','texto',NULL,'2025-11-15 16:30:43'),(4,'empresa_nombre',NULL,'Nombre de la empresa','texto',NULL,'2025-11-15 16:30:43'),(5,'empresa_telefono',NULL,'Tel├®fono de contacto','texto',NULL,'2025-11-15 16:30:43'),(6,'empresa_email',NULL,'Email de contacto','texto',NULL,'2025-11-15 16:30:43'),(7,'empresa_direccion',NULL,'Direcci├│n f├¡sica','texto',NULL,'2025-11-15 16:30:43');
/*!40000 ALTER TABLE `configuracion_app` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `factura_items`
--

DROP TABLE IF EXISTS `factura_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `factura_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `factura_id` int NOT NULL,
  `descripcion` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_factura` (`factura_id`),
  CONSTRAINT `factura_items_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `factura_items`
--

LOCK TABLES `factura_items` WRITE;
/*!40000 ALTER TABLE `factura_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `factura_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facturas`
--

DROP TABLE IF EXISTS `facturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facturas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int NOT NULL,
  `numero_factura` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('A','B','C') COLLATE utf8mb4_unicode_ci DEFAULT 'B',
  `fecha_emision` date NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado` enum('pendiente','enviada_tango','aprobada','error') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `tango_response` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON response de Tango API',
  `orden_trabajo_id` int DEFAULT NULL COMMENT 'Si la factura es de una orden espec├¡fica',
  `abono_id` int DEFAULT NULL COMMENT 'Si la factura es de un abono',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_factura` (`numero_factura`),
  KEY `orden_trabajo_id` (`orden_trabajo_id`),
  KEY `abono_id` (`abono_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha` (`fecha_emision`),
  CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `facturas_ibfk_2` FOREIGN KEY (`orden_trabajo_id`) REFERENCES `ordenes_trabajo` (`id`),
  CONSTRAINT `facturas_ibfk_3` FOREIGN KEY (`abono_id`) REFERENCES `abonos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facturas`
--

LOCK TABLES `facturas` WRITE;
/*!40000 ALTER TABLE `facturas` DISABLE KEYS */;
/*!40000 ALTER TABLE `facturas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orden_repuestos`
--

DROP TABLE IF EXISTS `orden_repuestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orden_repuestos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `orden_trabajo_id` int NOT NULL,
  `repuesto_id` int NOT NULL,
  `cantidad` int NOT NULL DEFAULT '1',
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orden` (`orden_trabajo_id`),
  KEY `idx_repuesto` (`repuesto_id`),
  CONSTRAINT `orden_repuestos_ibfk_1` FOREIGN KEY (`orden_trabajo_id`) REFERENCES `ordenes_trabajo` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orden_repuestos_ibfk_2` FOREIGN KEY (`repuesto_id`) REFERENCES `repuestos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orden_repuestos`
--

LOCK TABLES `orden_repuestos` WRITE;
/*!40000 ALTER TABLE `orden_repuestos` DISABLE KEYS */;
INSERT INTO `orden_repuestos` VALUES (3,7,1,1,66500.00,66500.00,'2025-11-14 23:19:24'),(4,8,1,1,66500.00,66500.00,'2025-11-14 23:21:45'),(5,9,1,1,66500.00,66500.00,'2025-11-15 04:37:47'),(6,11,2,1,58800.00,58800.00,'2025-11-16 02:51:43');
/*!40000 ALTER TABLE `orden_repuestos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ordenes_trabajo`
--

DROP TABLE IF EXISTS `ordenes_trabajo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ordenes_trabajo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero_parte` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_id` int NOT NULL,
  `tecnico_id` int NOT NULL,
  `fecha_trabajo` date NOT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL,
  `equipo_marca` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `equipo_modelo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `equipo_serie` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion_trabajo` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('pendiente','en_progreso','completado','cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `firma_cliente` text COLLATE utf8mb4_unicode_ci COMMENT 'Base64 de firma digital',
  `total` decimal(10,2) DEFAULT '0.00',
  `sincronizado` tinyint(1) DEFAULT '0' COMMENT 'Para control offline/online',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_parte` (`numero_parte`),
  KEY `idx_numero_parte` (`numero_parte`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_tecnico` (`tecnico_id`),
  KEY `idx_fecha` (`fecha_trabajo`),
  KEY `idx_estado` (`estado`),
  KEY `idx_sincronizado` (`sincronizado`),
  CONSTRAINT `ordenes_trabajo_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ordenes_trabajo_ibfk_2` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ordenes_trabajo`
--

LOCK TABLES `ordenes_trabajo` WRITE;
/*!40000 ALTER TABLE `ordenes_trabajo` DISABLE KEYS */;
INSERT INTO `ordenes_trabajo` VALUES (1,'OT-2025-001',1,1,'2025-11-14','10:00:00',NULL,'Body Fitness','PT300',NULL,'Cambio de banda en cinta',NULL,'completado',NULL,0.00,1,'2025-11-14 22:14:16','2025-11-14 22:14:16'),(6,'OT-2025-1763162303643',4,1,'2025-11-14','10:00:00',NULL,'Body Fitness','PT300',NULL,'Cambio de banda en cinta',NULL,'completado',NULL,0.00,1,'2025-11-14 23:18:36','2025-11-14 23:18:36'),(7,'OT-2025-1763162356690',5,1,'2025-11-14','10:00:00',NULL,'Body Fitness','PT300',NULL,'Cambio de banda en cinta',NULL,'completado',NULL,66500.00,1,'2025-11-14 23:19:24','2025-11-14 23:19:24'),(8,'OT-2025-1763162356691',5,1,'2025-11-14','10:00:00',NULL,'Body Fitness','PT300',NULL,'Cambio de banda en cinta',NULL,'completado',NULL,66500.00,1,'2025-11-14 23:21:45','2025-11-14 23:21:45'),(9,'OT-2025-1763180160016',8,1,'2025-11-14','10:00:00',NULL,'Body Fitness','PT300',NULL,'Cambio de banda en cinta',NULL,'completado',NULL,66500.00,1,'2025-11-15 04:37:47','2025-11-15 04:37:47'),(10,'OT-TEST-001',1,1,'2025-11-15','10:00:00',NULL,'Test','Test',NULL,'Prueba',NULL,'pendiente',NULL,0.00,1,'2025-11-15 11:47:19','2025-11-15 11:47:19'),(11,'OT-1763261452433',7,1,'2025-11-16','09:00:00',NULL,NULL,NULL,NULL,'probando sistema 001','espero que ande muy bien esto','pendiente',NULL,58800.00,1,'2025-11-16 02:51:43','2025-11-16 02:51:43');
/*!40000 ALTER TABLE `ordenes_trabajo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repuestos`
--

DROP TABLE IF EXISTS `repuestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `repuestos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marca` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock_actual` int DEFAULT '0',
  `stock_minimo` int DEFAULT '0',
  `precio_unitario` decimal(10,2) NOT NULL,
  `proveedor` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ubicacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ubicaci├│n f├¡sica en dep├│sito',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `idx_codigo` (`codigo`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_stock` (`stock_actual`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repuestos`
--

LOCK TABLES `repuestos` WRITE;
/*!40000 ALTER TABLE `repuestos` DISABLE KEYS */;
INSERT INTO `repuestos` VALUES (1,'BANDA-BF-PT300','Banda para cinta Body Fitness PT300',NULL,NULL,5,2,66500.00,NULL,NULL,1,'2025-11-14 23:19:02','2025-11-14 23:19:02'),(2,'TABLA-BF-PT300','Tabla para cinta Body Fitness PT300',NULL,NULL,3,1,58800.00,NULL,NULL,1,'2025-11-14 23:19:02','2025-11-14 23:19:02'),(3,'SILICONA-CINTA','Silicona lubricante para cintas',NULL,NULL,10,5,4580.00,NULL,NULL,1,'2025-11-14 23:19:02','2025-11-14 23:19:02');
/*!40000 ALTER TABLE `repuestos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sync_log`
--

DROP TABLE IF EXISTS `sync_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sync_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tabla` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registro_id` int NOT NULL,
  `accion` enum('create','update','delete') COLLATE utf8mb4_unicode_ci NOT NULL,
  `datos_json` text COLLATE utf8mb4_unicode_ci,
  `sincronizado` tinyint(1) DEFAULT '0',
  `fecha_cambio` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_sincronizacion` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sincronizado` (`sincronizado`),
  KEY `idx_tabla` (`tabla`),
  KEY `idx_fecha_cambio` (`fecha_cambio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sync_log`
--

LOCK TABLES `sync_log` WRITE;
/*!40000 ALTER TABLE `sync_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `sync_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `taller_equipos`
--

DROP TABLE IF EXISTS `taller_equipos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `taller_equipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cliente_id` int DEFAULT NULL,
  `origen` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'De d├│nde viene el equipo',
  `equipo_marca` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `equipo_modelo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `equipo_serie` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_ingreso` date NOT NULL,
  `fecha_salida` date DEFAULT NULL,
  `estado` enum('en_taller','esperando_repuesto','reparado','entregado') COLLATE utf8mb4_unicode_ci DEFAULT 'en_taller',
  `diagnostico` text COLLATE utf8mb4_unicode_ci,
  `reparacion_realizada` text COLLATE utf8mb4_unicode_ci,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `tecnico_responsable_id` int DEFAULT NULL,
  `costo_reparacion` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tecnico_responsable_id` (`tecnico_responsable_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_ingreso` (`fecha_ingreso`),
  KEY `idx_cliente` (`cliente_id`),
  CONSTRAINT `taller_equipos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `taller_equipos_ibfk_2` FOREIGN KEY (`tecnico_responsable_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taller_equipos`
--

LOCK TABLES `taller_equipos` WRITE;
/*!40000 ALTER TABLE `taller_equipos` DISABLE KEYS */;
/*!40000 ALTER TABLE `taller_equipos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin','tecnico','supervisor') COLLATE utf8mb4_unicode_ci DEFAULT 'tecnico',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_rol` (`rol`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Admin SerTecApp','admin@sertecapp.com','$2y$10$t6YtDiXKFwe8rUjCD9jIiehd//.bCISSGQSL5jJsayAhxSlAf7a.q','admin',1,'2025-11-14 20:17:35','2025-11-15 04:15:35');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `v_clientes_abonados`
--

DROP TABLE IF EXISTS `v_clientes_abonados`;
/*!50001 DROP VIEW IF EXISTS `v_clientes_abonados`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_clientes_abonados` AS SELECT 
 1 AS `id`,
 1 AS `nombre`,
 1 AS `tipo`,
 1 AS `frecuencia_visitas`,
 1 AS `color_hex`,
 1 AS `color_nombre`,
 1 AS `abono_id`,
 1 AS `monto_mensual`,
 1 AS `fecha_inicio`,
 1 AS `abono_estado`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_stock_bajo`
--

DROP TABLE IF EXISTS `v_stock_bajo`;
/*!50001 DROP VIEW IF EXISTS `v_stock_bajo`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_stock_bajo` AS SELECT 
 1 AS `codigo`,
 1 AS `descripcion`,
 1 AS `stock_actual`,
 1 AS `stock_minimo`,
 1 AS `faltante`,
 1 AS `proveedor`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `visitas_abono`
--

DROP TABLE IF EXISTS `visitas_abono`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitas_abono` (
  `id` int NOT NULL AUTO_INCREMENT,
  `abono_id` int NOT NULL,
  `orden_trabajo_id` int NOT NULL,
  `mes` int NOT NULL COMMENT '1-12',
  `anio` int NOT NULL,
  `numero_visita` tinyint NOT NULL COMMENT 'Qu├® visita del mes es (1, 2 o 3)',
  `fecha_visita` date NOT NULL,
  `completada` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_visita` (`abono_id`,`mes`,`anio`,`numero_visita`),
  KEY `orden_trabajo_id` (`orden_trabajo_id`),
  KEY `idx_abono` (`abono_id`),
  KEY `idx_mes_anio` (`mes`,`anio`),
  CONSTRAINT `visitas_abono_ibfk_1` FOREIGN KEY (`abono_id`) REFERENCES `abonos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visitas_abono_ibfk_2` FOREIGN KEY (`orden_trabajo_id`) REFERENCES `ordenes_trabajo` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitas_abono`
--

LOCK TABLES `visitas_abono` WRITE;
/*!40000 ALTER TABLE `visitas_abono` DISABLE KEYS */;
/*!40000 ALTER TABLE `visitas_abono` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `v_clientes_abonados`
--

/*!50001 DROP VIEW IF EXISTS `v_clientes_abonados`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_clientes_abonados` AS select `c`.`id` AS `id`,`c`.`nombre` AS `nombre`,`c`.`tipo` AS `tipo`,`c`.`frecuencia_visitas` AS `frecuencia_visitas`,`cf`.`color_hex` AS `color_hex`,`cf`.`color_nombre` AS `color_nombre`,`a`.`id` AS `abono_id`,`a`.`monto_mensual` AS `monto_mensual`,`a`.`fecha_inicio` AS `fecha_inicio`,`a`.`estado` AS `abono_estado` from ((`clientes` `c` left join `abonos` `a` on(((`c`.`id` = `a`.`cliente_id`) and (`a`.`estado` = 'activo')))) left join `config_frecuencias` `cf` on((`c`.`frecuencia_visitas` = `cf`.`frecuencia_visitas`))) where ((`c`.`tipo` = 'abonado') and (`c`.`estado` = 'activo')) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_stock_bajo`
--

/*!50001 DROP VIEW IF EXISTS `v_stock_bajo`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_stock_bajo` AS select `repuestos`.`codigo` AS `codigo`,`repuestos`.`descripcion` AS `descripcion`,`repuestos`.`stock_actual` AS `stock_actual`,`repuestos`.`stock_minimo` AS `stock_minimo`,(`repuestos`.`stock_minimo` - `repuestos`.`stock_actual`) AS `faltante`,`repuestos`.`proveedor` AS `proveedor` from `repuestos` where ((`repuestos`.`stock_actual` <= `repuestos`.`stock_minimo`) and (`repuestos`.`activo` = true)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-21 10:58:32
