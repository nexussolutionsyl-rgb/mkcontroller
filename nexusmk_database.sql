-- ============================================================
-- SCRIPT DE CREACION DE BASE DE DATOS NEXUSMK
-- MkController v3.0 - Modulo de Gestion MikroTik desde MySQL
-- ============================================================
-- EJECUTAR EN phpMyAdmin:
-- 1. Abrir https://server166.web-hosting.com:2083
-- 2. Ir a "phpMyAdmin"
-- 3. Crear BD: CREATE DATABASE nexusyl_nexusmk;
-- 4. Seleccionar la BD
-- 5. Ir a pestaña "SQL" y pegar TODO este script
-- 6. Ejecutar
-- ============================================================

-- ============================================================
-- TABLA 1: usuarios_nexusmk
-- Usuarios del modulo nexusMK con acceso al dashboard
-- ============================================================
CREATE TABLE IF NOT EXISTS `usuarios_nexusmk` (
  `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario` VARCHAR(50) NOT NULL UNIQUE,
  `clave` VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt (compatible con PHP password_hash)',
  `nombre_completo` VARCHAR(150) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  `permisos` JSON DEFAULT NULL COMMENT 'Permisos en formato JSON',
  `estado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  `ultimo_acceso` DATETIME DEFAULT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA 2: dispositivos_mikrotik
-- Dispositivos MikroTik gestionados desde el modulo
-- ============================================================
CREATE TABLE IF NOT EXISTS `dispositivos_mikrotik` (
  `id_dispositivo` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `direccion_ip` VARCHAR(45) NOT NULL COMMENT 'IPv4 o IPv6',
  `puerto_api` INT NOT NULL DEFAULT 8728,
  `puerto_web` INT NOT NULL DEFAULT 80 COMMENT 'Puerto para WebFig/WinBox',
  `usuario` VARCHAR(50) NOT NULL COMMENT 'Usuario de conexion RouterOS',
  `clave` VARCHAR(255) NOT NULL COMMENT 'Contrasena RouterOS (encriptada)',
  `tipo_dispositivo` ENUM('router','switch','access_point','firewall','otros') NOT NULL DEFAULT 'router',
  `modelo` VARCHAR(100) DEFAULT NULL,
  `version_routeros` VARCHAR(50) DEFAULT NULL,
  `ubicacion` VARCHAR(200) DEFAULT NULL,
  `notas` TEXT DEFAULT NULL,
  `monitoreo_activo` TINYINT(1) NOT NULL DEFAULT 1,
  `intervalo_monitoreo` INT NOT NULL DEFAULT 60 COMMENT 'Intervalo en segundos',
  `estado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  `ultimo_contacto` DATETIME DEFAULT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA 3: interfaces_wireguard
-- Interfaces WireGuard configuradas en los dispositivos
-- ============================================================
CREATE TABLE IF NOT EXISTS `interfaces_wireguard` (
  `id_interfaz` INT AUTO_INCREMENT PRIMARY KEY,
  `id_dispositivo` INT NOT NULL,
  `nombre_interfaz` VARCHAR(50) NOT NULL COMMENT 'Ej: wg0, wg1',
  `direccion_ip` VARCHAR(45) DEFAULT NULL COMMENT 'IP de la interfaz',
  `puerto_listen` INT DEFAULT NULL COMMENT 'Puerto de escucha UDP',
  `clave_privada` TEXT DEFAULT NULL COMMENT 'Clave privada (encriptada)',
  `clave_publica` VARCHAR(255) DEFAULT NULL COMMENT 'Clave publica',
  `mtu` INT DEFAULT 1420,
  `dns` VARCHAR(200) DEFAULT NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Activa, 0=Inactiva',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_dispositivo`) REFERENCES `dispositivos_mikrotik`(`id_dispositivo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA 4: peers_wireguard
-- Peers WireGuard asociados a las interfaces
-- ============================================================
CREATE TABLE IF NOT EXISTS `peers_wireguard` (
  `id_peer` INT AUTO_INCREMENT PRIMARY KEY,
  `id_interfaz` INT NOT NULL,
  `nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre descriptivo del peer',
  `clave_publica` VARCHAR(255) NOT NULL,
  `preshared_key` TEXT DEFAULT NULL COMMENT 'Clave pre-compartida (opcional)',
  `allowed_ips` VARCHAR(255) NOT NULL COMMENT 'Ej: 10.0.0.2/32, 192.168.1.0/24',
  `endpoint` VARCHAR(255) DEFAULT NULL COMMENT 'Endpoint: puerto (ej: 203.0.113.5:51820)',
  `persistent_keepalive` INT DEFAULT 25 COMMENT 'Keepalive en segundos',
  `dato_rx` BIGINT DEFAULT 0 COMMENT 'Bytes recibidos',
  `dato_tx` BIGINT DEFAULT 0 COMMENT 'Bytes enviados',
  `ultima_handshake` DATETIME DEFAULT NULL,
  `estado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  `notas` TEXT DEFAULT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_interfaz`) REFERENCES `interfaces_wireguard`(`id_interfaz`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA 5: reglas_firewall
-- Reglas de firewall para los dispositivos MikroTik
-- ============================================================
CREATE TABLE IF NOT EXISTS `reglas_firewall` (
  `id_regla` INT AUTO_INCREMENT PRIMARY KEY,
  `id_dispositivo` INT NOT NULL,
  `chain` VARCHAR(50) NOT NULL DEFAULT 'forward' COMMENT 'input, output, forward',
  `accion` VARCHAR(50) NOT NULL DEFAULT 'accept' COMMENT 'accept, drop, reject, masquerade',
  `protocolo` VARCHAR(20) DEFAULT NULL COMMENT 'tcp, udp, icmp, any',
  `puerto_origen` INT DEFAULT NULL,
  `puerto_destino` INT DEFAULT NULL,
  `direccion_origen` VARCHAR(45) DEFAULT NULL,
  `direccion_destino` VARCHAR(45) DEFAULT NULL,
  `comentario` VARCHAR(255) DEFAULT NULL,
  `orden` INT NOT NULL DEFAULT 0,
  `estado` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=Activa, 0=Inactiva',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_dispositivo`) REFERENCES `dispositivos_mikrotik`(`id_dispositivo`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA 6: configuracion_general
-- Configuracion general del modulo nexusMK
-- ============================================================
CREATE TABLE IF NOT EXISTS `configuracion_general` (
  `id_config` INT AUTO_INCREMENT PRIMARY KEY,
  `clave` VARCHAR(100) NOT NULL UNIQUE,
  `valor` TEXT NOT NULL,
  `descripcion` VARCHAR(255) DEFAULT NULL,
  `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATOS INICIALES
-- ============================================================

-- Usuario administrador por defecto
-- Usuario: admin
-- Contrasena: admin123 (hash bcrypt)
INSERT INTO `usuarios_nexusmk` (`usuario`, `clave`, `nombre_completo`, `email`, `permisos`, `estado`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador nexusMK', 'admin@nexussolutionsyl.com', '{\"dispositivos\": true, \"wireguard\": true, \"firewall\": true, \"usuarios\": true, \"configuracion\": true}', 1);

-- Configuracion por defecto
INSERT INTO `configuracion_general` (`clave`, `valor`, `descripcion`) VALUES
('app_nombre', 'nexusMK', 'Nombre de la aplicacion'),
('app_version', '1.0.0', 'Version del modulo'),
('tiempo_monitoreo', '60', 'Intervalo de monitoreo en segundos'),
('limite_dispositivos', '10', 'Numero maximo de dispositivos permitidos'),
('notificaciones_activas', 'true', 'Activar/desactivar notificaciones');

-- ============================================================
-- NOTA: En cPanel, el prefijo de la base de datos es "nexusyl_"
-- Si phpMyAdmin no permite crear la BD con el nombre exacto,
-- usa: CREATE DATABASE nexusyl_nexusmk;
-- Y luego actualiza el DB_CONFIG en el controlador.
-- ============================================================
