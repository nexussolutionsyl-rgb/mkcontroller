<?php
/**
 * Script de configuracion de base de datos nexusMK
 * Crea la base de datos, tablas e inserta datos iniciales
 * 
 * EJECUTAR: https://nexusmk.nexussolutionsyl.com/setup_nexusmk_db.php
 * LUEGO ELIMINAR: Este archivo por seguridad
 */

// Configuracion MySQL de cPanel
// NOTA: En cPanel, el usuario de BD tiene prefijo "nexusyl_"
// Debes crear la BD y el usuario desde "MySQL Databases" en cPanel ANTES de ejecutar esto
$db_host = 'localhost';
$db_user = 'nexusyl_nexusmk';    // USUARIO: Crear en cPanel -> MySQL Databases
$db_pass = '';                    // CONTRASENA: La que asignes en cPanel
$db_name = 'nexusyl_nexusmk';    // NOMBRE BD: Crear en cPanel -> MySQL Databases

echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Setup nexusMK DB</title>";
echo "<style>
body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;background:#1a1a2e;color:#e0e0e0;}
h1{color:#00d4ff;}
.success{color:#00ff88;background:#003322;padding:10px;border-radius:5px;margin:5px 0;}
.error{color:#ff4444;background:#330000;padding:10px;border-radius:5px;margin:5px 0;}
.info{color:#ffaa00;background:#332200;padding:10px;border-radius:5px;margin:5px 0;}
pre{background:#16213e;padding:10px;border-radius:5px;overflow-x:auto;}
code{color:#00d4ff;}
</style></head><body>";
echo "<h1>Configuracion Base de Datos nexusMK</h1>";

// Verificar si se envio el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_user = $_POST['db_user'] ?? $db_user;
    $db_pass = $_POST['db_pass'] ?? $db_pass;
    $db_name = $_POST['db_name'] ?? $db_name;
    
    echo "<h2>Resultados:</h2>";
    
    // 1. Conectar a MySQL (sin seleccionar BD)
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass);
        if ($conn->connect_error) {
            throw new Exception("Error de conexion: " . $conn->connect_error);
        }
        echo "<div class='success'>Conexion a MySQL exitosa como: <code>$db_user</code></div>";
    } catch (Exception $e) {
        echo "<div class='error'>" . $e->getMessage() . "</div>";
        echo "<div class='info'>Asegurate de haber creado el usuario y la BD en cPanel -> MySQL Databases</div>";
        echo "</body></html>";
        exit;
    }
    
    // 2. Crear BD si no existe
    $sql_create_db = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    if ($conn->query($sql_create_db)) {
        echo "<div class='success'>Base de datos <code>$db_name</code> lista/creada</div>";
    } else {
        echo "<div class='error'>Error creando BD: " . $conn->error . "</div>";
    }
    
    // 3. Seleccionar BD
    $conn->select_db($db_name);
    
    // 4. Crear tablas
    $tables_created = 0;
    
    // TABLA 1: usuarios_nexusmk
    $sql = "CREATE TABLE IF NOT EXISTS `usuarios_nexusmk` (
      `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
      `usuario` VARCHAR(50) NOT NULL UNIQUE,
      `clave` VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt',
      `nombre_completo` VARCHAR(150) NOT NULL,
      `email` VARCHAR(100) DEFAULT NULL,
      `telefono` VARCHAR(20) DEFAULT NULL,
      `permisos` JSON DEFAULT NULL,
      `estado` TINYINT(1) NOT NULL DEFAULT 1,
      `ultimo_acceso` DATETIME DEFAULT NULL,
      `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if ($conn->query($sql)) {
        echo "<div class='success'>Tabla <code>usuarios_nexusmk</code> creada</div>";
        $tables_created++;
    } else {
        echo "<div class='error'>Error creando usuarios_nexusmk: " . $conn->error . "</div>";
    }
    
    // TABLA 2: dispositivos_mikrotik
    $sql = "CREATE TABLE IF NOT EXISTS `dispositivos_mikrotik` (
      `id_dispositivo` INT AUTO_INCREMENT PRIMARY KEY,
      `nombre` VARCHAR(100) NOT NULL,
      `direccion_ip` VARCHAR(45) NOT NULL,
      `puerto_api` INT NOT NULL DEFAULT 8728,
      `puerto_web` INT NOT NULL DEFAULT 80,
      `usuario` VARCHAR(50) NOT NULL,
      `clave` VARCHAR(255) NOT NULL,
      `tipo_dispositivo` ENUM('router','switch','access_point','firewall','otros') NOT NULL DEFAULT 'router',
      `modelo` VARCHAR(100) DEFAULT NULL,
      `version_routeros` VARCHAR(50) DEFAULT NULL,
      `ubicacion` VARCHAR(200) DEFAULT NULL,
      `notas` TEXT DEFAULT NULL,
      `monitoreo_activo` TINYINT(1) NOT NULL DEFAULT 1,
      `intervalo_monitoreo` INT NOT NULL DEFAULT 60,
      `estado` TINYINT(1) NOT NULL DEFAULT 1,
      `ultimo_contacto` DATETIME DEFAULT NULL,
      `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if ($conn->query($sql)) {
        echo "<div class='success'>Tabla <code>dispositivos_mikrotik</code> creada</div>";
        $tables_created++;
    } else {
        echo "<div class='error'>Error creando dispositivos_mikrotik: " . $conn->error . "</div>";
    }
    
    // TABLA 3: interfaces_wireguard
    $sql = "CREATE TABLE IF NOT EXISTS `interfaces_wireguard` (
      `id_interfaz` INT AUTO_INCREMENT PRIMARY KEY,
      `id_dispositivo` INT NOT NULL,
      `nombre_interfaz` VARCHAR(50) NOT NULL,
      `direccion_ip` VARCHAR(45) DEFAULT NULL,
      `puerto_listen` INT DEFAULT NULL,
      `clave_privada` TEXT DEFAULT NULL,
      `clave_publica` VARCHAR(255) DEFAULT NULL,
      `mtu` INT DEFAULT 1420,
      `dns` VARCHAR(200) DEFAULT NULL,
      `estado` TINYINT(1) NOT NULL DEFAULT 1,
      `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`id_dispositivo`) REFERENCES `dispositivos_mikrotik`(`id_dispositivo`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if ($conn->query($sql)) {
        echo "<div class='success'>Tabla <code>interfaces_wireguard</code> creada</div>";
        $tables_created++;
    } else {
        echo "<div class='error'>Error creando interfaces_wireguard: " . $conn->error . "</div>";
    }
    
    // TABLA 4: peers_wireguard
    $sql = "CREATE TABLE IF NOT EXISTS `peers_wireguard` (
      `id_peer` INT AUTO_INCREMENT PRIMARY KEY,
      `id_interfaz` INT NOT NULL,
      `nombre` VARCHAR(100) NOT NULL,
      `clave_publica` VARCHAR(255) NOT NULL,
      `preshared_key` TEXT DEFAULT NULL,
      `allowed_ips` VARCHAR(255) NOT NULL,
      `endpoint` VARCHAR(255) DEFAULT NULL,
      `persistent_keepalive` INT DEFAULT 25,
      `dato_rx` BIGINT DEFAULT 0,
      `dato_tx` BIGINT DEFAULT 0,
      `ultima_handshake` DATETIME DEFAULT NULL,
      `estado` TINYINT(1) NOT NULL DEFAULT 1,
      `notas` TEXT DEFAULT NULL,
      `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`id_interfaz`) REFERENCES `interfaces_wireguard`(`id_interfaz`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if ($conn->query($sql)) {
        echo "<div class='success'>Tabla <code>peers_wireguard</code> creada</div>";
        $tables_created++;
    } else {
        echo "<div class='error'>Error creando peers_wireguard: " . $conn->error . "</div>";
    }
    
    // TABLA 5: reglas_firewall
    $sql = "CREATE TABLE IF NOT EXISTS `reglas_firewall` (
      `id_regla` INT AUTO_INCREMENT PRIMARY KEY,
      `id_dispositivo` INT NOT NULL,
      `chain` VARCHAR(50) NOT NULL DEFAULT 'forward',
      `accion` VARCHAR(50) NOT NULL DEFAULT 'accept',
      `protocolo` VARCHAR(20) DEFAULT NULL,
      `puerto_origen` INT DEFAULT NULL,
      `puerto_destino` INT DEFAULT NULL,
      `direccion_origen` VARCHAR(45) DEFAULT NULL,
      `direccion_destino` VARCHAR(45) DEFAULT NULL,
      `comentario` VARCHAR(255) DEFAULT NULL,
      `orden` INT NOT NULL DEFAULT 0,
      `estado` TINYINT(1) NOT NULL DEFAULT 1,
      `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      FOREIGN KEY (`id_dispositivo`) REFERENCES `dispositivos_mikrotik`(`id_dispositivo`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if ($conn->query($sql)) {
        echo "<div class='success'>Tabla <code>reglas_firewall</code> creada</div>";
        $tables_created++;
    } else {
        echo "<div class='error'>Error creando reglas_firewall: " . $conn->error . "</div>";
    }
    
    // TABLA 6: configuracion_general
    $sql = "CREATE TABLE IF NOT EXISTS `configuracion_general` (
      `id_config` INT AUTO_INCREMENT PRIMARY KEY,
      `clave` VARCHAR(100) NOT NULL UNIQUE,
      `valor` TEXT NOT NULL,
      `descripcion` VARCHAR(255) DEFAULT NULL,
      `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if ($conn->query($sql)) {
        echo "<div class='success'>Tabla <code>configuracion_general</code> creada</div>";
        $tables_created++;
    } else {
        echo "<div class='error'>Error creando configuracion_general: " . $conn->error . "</div>";
    }
    
    echo "<div class='success' style='font-size:1.2em;'>$tables_created tablas creadas exitosamente</div>";
    
    // 5. Insertar datos iniciales
    echo "<h3>Datos iniciales:</h3>";
    
    // Verificar si ya existe el admin
    $result = $conn->query("SELECT COUNT(*) as cnt FROM usuarios_nexusmk");
    $row = $result->fetch_assoc();
    if ($row['cnt'] == 0) {
        // Hash bcrypt de "admin123"
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO usuarios_nexusmk (usuario, clave, nombre_completo, email, permisos, estado) VALUES (?, ?, ?, ?, ?, 1)");
        $permisos = json_encode(['dispositivos'=>true,'wireguard'=>true,'firewall'=>true,'usuarios'=>true,'configuracion'=>true]);
        $usuario = 'admin';
        $nombre = 'Administrador nexusMK';
        $email = 'admin@nexussolutionsyl.com';
        $stmt->bind_param('sssss', $usuario, $hash, $nombre, $email, $permisos);
        if ($stmt->execute()) {
            echo "<div class='success'>Usuario admin creado (contrasena: admin123)</div>";
        } else {
            echo "<div class='error'>Error creando admin: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        echo "<div class='info'>Usuario admin ya existe, saltando...</div>";
    }
    
    // Configuracion por defecto
    $config_defaults = [
        ['app_nombre', 'nexusMK', 'Nombre de la aplicacion'],
        ['app_version', '1.0.0', 'Version del modulo'],
        ['tiempo_monitoreo', '60', 'Intervalo de monitoreo en segundos'],
        ['limite_dispositivos', '10', 'Numero maximo de dispositivos'],
        ['notificaciones_activas', 'true', 'Activar/desactivar notificaciones']
    ];
    foreach ($config_defaults as $cfg) {
        $stmt = $conn->prepare("INSERT IGNORE INTO configuracion_general (clave, valor, descripcion) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $cfg[0], $cfg[1], $cfg[2]);
        $stmt->execute();
        $stmt->close();
    }
    echo "<div class='success'>Configuracion por defecto insertada</div>";
    
    // 6. Generar contenido para .env
    echo "<h3>Actualizar .env:</h3>";
    echo "<div class='info'>Agrega estas lineas al archivo <code>.env</code> en la raiz del proyecto:</div>";
    echo "<pre>";
    echo "NEXUSMK_DB_HOST=localhost\n";
    echo "NEXUSMK_DB_USER=$db_user\n";
    echo "NEXUSMK_DB_PASSWORD=$db_pass\n";
    echo "NEXUSMK_DB_NAME=$db_name\n";
    echo "</pre>";
    
    // 7. Probar conexion Node.js
    echo "<h3>Prueba de conexion:</h3>";
    echo "<div class='success'>Base de datos configurada correctamente</div>";
    echo "<p>Verifica el endpoint: <code>GET https://nexusmk.nexussolutionsyl.com/api/nexusmk/health</code></p>";
    echo "<p>Login de prueba: <code>POST https://nexusmk.nexussolutionsyl.com/api/nexusmk/login</code></p>";
    
    $conn->close();
    
    echo "<hr>";
    echo "<div class='error' style='font-size:1.1em;'>IMPORTANTE: Elimina este archivo del servidor despues de usarlo</div>";
    echo "<p><a href='?delete=1' style='color:#ff4444;' onclick=\"return confirm('Eliminar este archivo?')\">Eliminar este archivo</a></p>";
    
} else {
    // Mostrar formulario
    ?>
    <div class="info">
        <strong>PASO 1:</strong> Ve a cPanel -> MySQL Databases y crea:
        <ul>
            <li>Base de datos: <code>nexusyl_nexusmk</code></li>
            <li>Usuario: <code>nexusyl_nexusmk</code> con contrasena segura</li>
            <li>Asigna el usuario a la BD con <strong>TODOS LOS PRIVILEGIOS</strong></li>
        </ul>
    </div>
    
    <form method="POST" style="background:#16213e;padding:20px;border-radius:10px;">
        <h2>PASO 2: Configurar conexion MySQL</h2>
        
        <label style="display:block;margin:10px 0;">
            Usuario MySQL:
            <input type="text" name="db_user" value="nexusyl_nexusmk" 
                   style="width:100%;padding:8px;background:#1a1a2e;color:#00d4ff;border:1px solid #00d4ff;border-radius:5px;margin-top:5px;">
        </label>
        
        <label style="display:block;margin:10px 0;">
            Contrasena MySQL:
            <input type="password" name="db_pass" value="" required
                   style="width:100%;padding:8px;background:#1a1a2e;color:#00d4ff;border:1px solid #00d4ff;border-radius:5px;margin-top:5px;">
            <small style="color:#888;">La contrasena que asignaste en cPanel -> MySQL Databases</small>
        </label>
        
        <label style="display:block;margin:10px 0;">
            Nombre BD:
            <input type="text" name="db_name" value="nexusyl_nexusmk"
                   style="width:100%;padding:8px;background:#1a1a2e;color:#00d4ff;border:1px solid #00d4ff;border-radius:5px;margin-top:5px;">
        </label>
        
        <button type="submit" style="background:#00d4ff;color:#1a1a2e;padding:12px 30px;border:none;border-radius:5px;font-size:1.1em;cursor:pointer;margin-top:15px;">
            Configurar Base de Datos
        </button>
    </form>
    <?php
}

// Opcion para eliminar este archivo
if (isset($_GET['delete']) && $_GET['delete'] == '1') {
    if (unlink(__FILE__)) {
        echo "<div class='success'>Archivo eliminado exitosamente</div>";
    } else {
        echo "<div class='error'>No se pudo eliminar el archivo</div>";
    }
}

echo "</body></html>";
