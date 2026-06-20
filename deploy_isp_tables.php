<?php
/**
 * deploy_isp_tables.php
 * Despliega las tablas isp_* en la base de datos nexusyl_nexusmk
 * Ejecutar: https://nexusmk.nexussolutionsyl.com/deploy_isp_tables.php
 */

header('Content-Type: text/html; charset=utf-8');
echo "<h1>🚀 Desplegando tablas ISP Manager</h1>";

// Leer variables de entorno
$envPath = __DIR__ . '/.env';
$envVars = [];
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value);
        }
    }
}

$dbHost = $envVars['DB_HOST'] ?? 'localhost';
$dbUser = $envVars['DB_USER'] ?? $envVars['MYSQL_USER'] ?? 'nexusyl_root';
$dbPass = $envVars['DB_PASSWORD'] ?? $envVars['MYSQL_PASSWORD'] ?? '';
$dbName = $envVars['DB_NAME'] ?? $envVars['MYSQL_DATABASE'] ?? 'nexusyl_nexusmk';

echo "<p>📦 Conectando a: <strong>$dbHost</strong> / <strong>$dbName</strong></p>";

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p style='color:green'>✅ Conexión MySQL exitosa</p>";
} catch (PDOException $e) {
    die("<p style='color:red'>❌ Error de conexión: " . $e->getMessage() . "</p>");
}

// SQL de migración
$sql = "
-- ============================================================
-- ISP Manager - Migración de Base de Datos
-- Versión: 1.0
-- ============================================================

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

CREATE TABLE IF NOT EXISTS `isp_ip_pools` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY,
  `name` VARCHAR(64) NOT NULL,
  `ranges` TEXT NOT NULL COMMENT 'Rangos de IP',
  `router_id` VARCHAR(36) DEFAULT NULL,
  `next_ip` VARCHAR(45) DEFAULT NULL,
  `sync_status` ENUM('synced','pending','error') NOT NULL DEFAULT 'pending',
  `comment` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_name` (`name`),
  KEY `idx_router_id` (`router_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `isp_alert_logs` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `event_type` VARCHAR(64) DEFAULT NULL,
  `host` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(32) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `raw_data` JSON DEFAULT NULL,
  `telegram_sent` TINYINT(1) NOT NULL DEFAULT 0,
  `telegram_error` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_event_type` (`event_type`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
";

try {
    $pdo->exec($sql);
    echo "<p style='color:green'>✅ Tablas creadas/verificadas exitosamente</p>";
} catch (PDOException $e) {
    die("<p style='color:red'>❌ Error creando tablas: " . $e->getMessage() . "</p>");
}

// Verificar tablas creadas
$stmt = $pdo->query("SHOW TABLES LIKE 'isp_%'");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>📋 Tablas ISP Manager:</h2><ul>";
foreach ($tables as $table) {
    $countStmt = $pdo->query("SELECT COUNT(*) as c FROM `$table`");
    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['c'];
    echo "<li>✅ <strong>$table</strong> - $count registros</li>";
}
echo "</ul>";

echo "<p style='color:green; font-size:1.2em;'>✅ Migración completada exitosamente</p>";
echo "<p><a href='/api/isp/health' target='_blank'>Verificar Health Check</a></p>";
