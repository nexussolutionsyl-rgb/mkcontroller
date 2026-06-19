<?php
/**
 * Crea las tablas en la base de datos nexusyl_nexusmk
 * Usa el usuario root de MySQL
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== CREACION DE TABLAS nexusMK ===\n\n";

// Conectar como root
echo "[1] Conectando a MySQL como root...\n";
$conn = @new mysqli('localhost', 'nexusyl_root', 'Casita.2026');
if ($conn->connect_error) {
    echo "ERROR: " . $conn->connect_error . "\n";
    exit(1);
}
echo "OK: Conexion exitosa\n\n";

// Seleccionar base de datos
echo "[2] Seleccionando base de datos nexusyl_nexusmk...\n";
if (!$conn->select_db('nexusyl_nexusmk')) {
    echo "Creando base de datos...\n";
    $conn->query("CREATE DATABASE IF NOT EXISTS nexusyl_nexusmk");
    $conn->select_db('nexusyl_nexusmk');
}
echo "OK\n\n";

// Crear tablas
echo "[3] Creando tablas...\n";

$tables = [
    "CREATE TABLE IF NOT EXISTS usuarios_nexusmk (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        rol ENUM('superadmin', 'admin', 'user') DEFAULT 'user',
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS dispositivos_mikrotik (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        ip VARCHAR(45) NOT NULL,
        puerto INT DEFAULT 8728,
        usuario_routeros VARCHAR(100) NOT NULL,
        password_routeros VARCHAR(255) NOT NULL,
        tipo ENUM('router', 'switch', 'access_point', 'hotspot_server') DEFAULT 'router',
        ubicacion VARCHAR(200),
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS interfaces_wireguard (
        id INT AUTO_INCREMENT PRIMARY KEY,
        dispositivo_id INT NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        ip_local VARCHAR(45),
        puerto INT DEFAULT 51820,
        private_key TEXT,
        public_key VARCHAR(255),
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (dispositivo_id) REFERENCES dispositivos_mikrotik(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS peers_wireguard (
        id INT AUTO_INCREMENT PRIMARY KEY,
        interface_id INT NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        public_key VARCHAR(255) NOT NULL,
        ip_asignada VARCHAR(45),
        allowed_ips VARCHAR(255),
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (interface_id) REFERENCES interfaces_wireguard(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS reglas_firewall (
        id INT AUTO_INCREMENT PRIMARY KEY,
        dispositivo_id INT NOT NULL,
        nombre VARCHAR(100),
        chain ENUM('input', 'output', 'forward') DEFAULT 'forward',
        protocolo VARCHAR(20),
        puerto_destino INT,
        src_address VARCHAR(45),
        dst_address VARCHAR(45),
        accion ENUM('accept', 'drop', 'reject', 'fasttrack') DEFAULT 'accept',
        orden INT DEFAULT 0,
        activo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (dispositivo_id) REFERENCES dispositivos_mikrotik(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS configuracion_general (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(100) NOT NULL UNIQUE,
        valor TEXT,
        descripcion VARCHAR(255),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($tables as $sql) {
    if ($conn->query($sql)) {
        $tableName = '';
        preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $sql, $m);
        $tableName = $m[1] ?? 'desconocida';
        echo "  OK: Tabla $tableName creada/verificada\n";
    } else {
        echo "  ERROR: " . $conn->error . "\n";
    }
}

echo "\n[4] Insertando datos iniciales...\n";

// Insertar admin si no existe
$result = $conn->query("SELECT id FROM usuarios_nexusmk WHERE username = 'admin'");
if ($result && $result->num_rows === 0) {
    $hash = password_hash('superadmin', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO usuarios_nexusmk (username, password, nombre, email, rol) VALUES ('admin', '$hash', 'Administrador', 'admin@nexussolutionsyl.com', 'superadmin')");
    echo "  OK: Usuario admin creado\n";
} else {
    echo "  OK: Usuario admin ya existe\n";
}

// Insertar configuracion inicial
$configs = [
    "INSERT IGNORE INTO configuracion_general (clave, valor, descripcion) VALUES ('app_version', '1.0.0', 'Version de nexusMK')",
    "INSERT IGNORE INTO configuracion_general (clave, valor, descripcion) VALUES ('db_version', '1.0.0', 'Version de la base de datos')",
    "INSERT IGNORE INTO configuracion_general (clave, valor, descripcion) VALUES ('ultima_sincronizacion', NOW(), 'Ultima sincronizacion con dispositivos')"
];
foreach ($configs as $sql) {
    $conn->query($sql);
}
echo "  OK: Configuracion inicial insertada\n";

$conn->close();

echo "\n=== TABLAS CREADAS EXITOSAMENTE ===\n";
echo "Base de datos: nexusyl_nexusmk\n";
echo "Usuario DB: nexusyl_root\n";
echo "Usuario admin: admin / superadmin\n";
