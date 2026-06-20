-- ============================================================
-- ISP Manager - Migración de Base de Datos
-- Motor: MySQL (cPanel - nexusyl_nexusmk)
-- Versión: 1.0
-- ============================================================

-- Tabla: isp_clients
-- Clientes ISP sincronizados con RouterOS (/ppp secret, /ip/hotspot/user)
CREATE TABLE IF NOT EXISTS `isp_clients` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `username` VARCHAR(64) NOT NULL,
  `password` VARCHAR(128) NOT NULL,
  `service` ENUM('pppoe','hotspot','vpn') NOT NULL DEFAULT 'pppoe',
  `plan_id` VARCHAR(36) DEFAULT NULL,
  `router_id` VARCHAR(36) DEFAULT NULL,
  `profile` VARCHAR(64) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `mac_address` VARCHAR(17) DEFAULT NULL,
  `disabled` TINYINT(1) NOT NULL DEFAULT 0,
  `sync_status` ENUM('synced','pending','error') NOT NULL DEFAULT 'pending',
  `sync_message` TEXT DEFAULT NULL,
  `last_sync_at` TIMESTAMP NULL DEFAULT NULL,
  `comment` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_username_service` (`username`, `service`),
  KEY `idx_plan_id` (`plan_id`),
  KEY `idx_router_id` (`router_id`),
  KEY `idx_disabled` (`disabled`),
  KEY `idx_sync_status` (`sync_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: isp_plans
-- Planes/Perfiles ISP (PPP profiles, Hotspot profiles)
CREATE TABLE IF NOT EXISTS `isp_plans` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `name` VARCHAR(64) NOT NULL,
  `service` ENUM('pppoe','hotspot','vpn') NOT NULL DEFAULT 'pppoe',
  `speed_limit` VARCHAR(32) DEFAULT NULL,
  `session_timeout` VARCHAR(32) DEFAULT NULL,
  `shared_users` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(10,2) DEFAULT NULL,
  `routeros_name` VARCHAR(64) DEFAULT NULL COMMENT 'Nombre del perfil en RouterOS',
  `sync_status` ENUM('synced','pending','error') NOT NULL DEFAULT 'pending',
  `comment` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_name_service` (`name`, `service`),
  KEY `idx_service` (`service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: isp_ip_pools
-- IP Pools sincronizados con RouterOS (/ip/pool)
CREATE TABLE IF NOT EXISTS `isp_ip_pools` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `name` VARCHAR(64) NOT NULL,
  `ranges` TEXT NOT NULL COMMENT 'Rangos de IP (ej: 192.168.88.100-192.168.88.200)',
  `router_id` VARCHAR(36) DEFAULT NULL,
  `next_ip` VARCHAR(45) DEFAULT NULL COMMENT 'Próxima IP disponible',
  `sync_status` ENUM('synced','pending','error') NOT NULL DEFAULT 'pending',
  `comment` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_router_id` (`router_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: isp_alert_logs
-- Log de alertas recibidas via Netwatch webhook
CREATE TABLE IF NOT EXISTS `isp_alert_logs` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `event_type` VARCHAR(64) DEFAULT NULL COMMENT 'Tipo de evento (netwatch, interface, system)',
  `host` VARCHAR(255) DEFAULT NULL COMMENT 'Host/IP del router que envió la alerta',
  `status` VARCHAR(32) DEFAULT NULL COMMENT 'Estado (up, down, error)',
  `message` TEXT DEFAULT NULL,
  `raw_data` JSON DEFAULT NULL COMMENT 'Datos completos del webhook',
  `telegram_sent` TINYINT(1) NOT NULL DEFAULT 0,
  `telegram_error` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_event_type` (`event_type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: isp_sessions
-- Registro de sesiones de clientes (histórico)
CREATE TABLE IF NOT EXISTS `isp_sessions` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `client_id` VARCHAR(36) DEFAULT NULL,
  `username` VARCHAR(64) NOT NULL,
  `service` ENUM('pppoe','hotspot','vpn') NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `mac_address` VARCHAR(17) DEFAULT NULL,
  `bytes_in` BIGINT NOT NULL DEFAULT 0,
  `bytes_out` BIGINT NOT NULL DEFAULT 0,
  `session_start` TIMESTAMP NULL DEFAULT NULL,
  `session_end` TIMESTAMP NULL DEFAULT NULL,
  `duration` VARCHAR(32) DEFAULT NULL,
  `terminated_by` VARCHAR(64) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_client_id` (`client_id`),
  KEY `idx_username` (`username`),
  KEY `idx_session_start` (`session_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
