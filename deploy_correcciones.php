<?php
/**
 * Script para desplegar correcciones en nexusmk.nexussolutionsyl.com
 * Se ejecuta via HTTP: https://nexusmk.nexussolutionsyl.com/deploy_correcciones.php
 */

$remoteDir = __DIR__;
$backupDir = $remoteDir . '/backup_' . date('Ymd_His');

echo "=== INICIANDO DESPLIEGUE DE CORRECCIONES ===\n";
echo "Directorio: $remoteDir\n";
echo "Backup: $backupDir\n\n";

$filesToBackup = [
    'backend/controllers/authController.js',
    'backend/config/config.js',
    'backend/services/mikrotikService.js',
    'backend/controllers/nexusmkController.js',
    'start.js'
];

// 1. Crear backup
echo "[1/5] Creando backup...\n";
if (!mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
    echo "  ERROR: No se pudo crear directorio de backup\n";
    exit(1);
}

foreach ($filesToBackup as $file) {
    $src = "$remoteDir/$file";
    $dst = "$backupDir/" . str_replace('/', '_', $file);
    if (file_exists($src)) {
        if (copy($src, $dst)) {
            echo "  Backup: $file -> OK\n";
        } else {
            echo "  Backup: $file -> ERROR\n";
        }
    } else {
        echo "  Backup: $file -> No existe\n";
    }
}

// 2. Escribir authController.js
echo "\n[2/5] Escribiendo authController.js...\n";
$content = <<<'JAVASCRIPT'
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const config = require('../config/config');
const mysql = require('mysql2/promise');
const db = require('../models/database');

let nexusPool = null;

function getNexusPool() {
    if (!nexusPool) {
        nexusPool = mysql.createPool({
            host: config.mysql?.host || 'localhost',
            user: config.mysql?.user || 'root',
            password: config.mysql?.password || '',
            database: 'nexusmk',
            waitForConnections: true,
            connectionLimit: 5,
            queueLimit: 0
        });
    }
    return nexusPool;
}

const authController = {
    async login(req, res) {
        try {
            const { username, password } = req.body;
            if (!username || !password) {
                return res.status(400).json({
                    success: false,
                    message: 'Usuario y contraseña son requeridos'
                });
            }

            // 1. Buscar en users.json (admin/client)
            const user = await db.findOne('users', 'username', username);
            if (user) {
                const isValid = bcrypt.compareSync(password, user.password);
                if (!isValid) {
                    return res.status(401).json({
                        success: false,
                        message: 'Credenciales inválidas'
                    });
                }
                const token = jwt.sign(
                    { id: user.id, username: user.username, role: user.role },
                    config.jwtSecret,
                    { expiresIn: '24h' }
                );
                await db.create('activityLog', {
                    userId: user.id,
                    username: user.username,
                    action: 'login',
                    details: 'Inicio de sesión exitoso',
                    timestamp: new Date().toISOString()
                });
                return res.json({
                    success: true,
                    message: 'Inicio de sesión exitoso',
                    data: {
                        user: { id: user.id, username: user.username, role: user.role, name: user.name },
                        token
                    }
                });
            }

            // 2. Buscar en MySQL nexusmk (operadores)
            let connection;
            try {
                const pool = getNexusPool();
                connection = await pool.getConnection();
                const [rows] = await connection.execute(
                    'SELECT id, usuario, password_hash, nombre, rol FROM usuarios_nexusmk WHERE usuario = ? AND activo = 1 LIMIT 1',
                    [username]
                );
                if (rows.length === 0) {
                    return res.status(401).json({
                        success: false,
                        message: 'Credenciales inválidas'
                    });
                }
                const dbUser = rows[0];
                const isValid = bcrypt.compareSync(password, dbUser.password_hash);
                if (!isValid) {
                    return res.status(401).json({
                        success: false,
                        message: 'Credenciales inválidas'
                    });
                }
                await connection.execute(
                    'UPDATE usuarios_nexusmk SET ultimo_acceso = NOW() WHERE id = ?',
                    [dbUser.id]
                );
                const token = jwt.sign(
                    { id: dbUser.id, username: dbUser.usuario, role: dbUser.rol || 'operator', source: 'nexusmk' },
                    config.jwtSecret,
                    { expiresIn: '24h' }
                );
                return res.json({
                    success: true,
                    message: 'Inicio de sesión exitoso',
                    data: {
                        user: { id: dbUser.id, username: dbUser.usuario, role: dbUser.rol || 'operator', name: dbUser.nombre },
                        token
                    }
                });
            } catch (dbError) {
                console.error('Error en MySQL nexusmk:', dbError.message);
                return res.status(500).json({
                    success: false,
                    message: 'Error de conexión con la base de datos'
                });
            } finally {
                if (connection) connection.release();
            }
        } catch (error) {
            console.error('Error en login:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor'
            });
        }
    },

    async verifyToken(req, res) {
        try {
            const user = req.user;
            if (!user) {
                return res.status(404).json({
                    success: false,
                    message: 'Usuario no encontrado'
                });
            }
            res.json({
                success: true,
                data: {
                    user: {
                        id: user.id,
                        username: user.username,
                        role: user.role,
                        name: user.name || user.username
                    }
                }
            });
        } catch (error) {
            console.error('Error en verifyToken:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor'
            });
        }
    },

    async changePassword(req, res) {
        try {
            const { currentPassword, newPassword } = req.body;
            if (!currentPassword || !newPassword) {
                return res.status(400).json({
                    success: false,
                    message: 'Contraseña actual y nueva son requeridas'
                });
            }
            if (newPassword.length < 6) {
                return res.status(400).json({
                    success: false,
                    message: 'La nueva contraseña debe tener al menos 6 caracteres'
                });
            }
            const user = await db.getById('users', req.user.id);
            if (!user) {
                return res.status(404).json({
                    success: false,
                    message: 'Usuario no encontrado'
                });
            }
            const isValid = bcrypt.compareSync(currentPassword, user.password);
            if (!isValid) {
                return res.status(401).json({
                    success: false,
                    message: 'Contraseña actual incorrecta'
                });
            }
            const salt = bcrypt.genSaltSync(10);
            const hashedPassword = bcrypt.hashSync(newPassword, salt);
            await db.update('users', user.id, { password: hashedPassword });
            res.json({
                success: true,
                message: 'Contraseña cambiada exitosamente'
            });
        } catch (error) {
            console.error('Error en changePassword:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor'
            });
        }
    },

    async logout(req, res) {
        try {
            if (req.user) {
                await db.create('activityLog', {
                    userId: req.user.id,
                    username: req.user.username,
                    action: 'logout',
                    details: 'Cierre de sesión',
                    timestamp: new Date().toISOString()
                });
            }
            res.json({
                success: true,
                message: 'Sesión cerrada exitosamente'
            });
        } catch (error) {
            console.error('Error en logout:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor'
            });
        }
    }
};

module.exports = authController;
JAVASCRIPT;

if (file_put_contents("$remoteDir/backend/controllers/authController.js", $content) !== false) {
    echo "  authController.js -> OK\n";
} else {
    echo "  authController.js -> ERROR\n";
}

// 3. Escribir config.js
echo "\n[3/5] Escribiendo config.js...\n";
$content = <<<'JAVASCRIPT'
module.exports = {
    port: process.env.PORT || 3000,
    jwtSecret: process.env.JWT_SECRET || 'mkcontroller_v3_secret_key_2024',
    cors: {
        origin: process.env.CORS_ORIGIN || '*',
        credentials: true
    },
    rateLimit: {
        windowMs: 15 * 60 * 1000,
        max: 100
    },
    dataPaths: {
        users: './backend/data/users.json',
        routers: './backend/data/routers.json',
        clients: './backend/data/clients.json',
        activityLog: './backend/data/activity_log.json',
        hotspotTickets: './backend/data/hotspot_tickets.json'
    },
    mysql: {
        host: process.env.MYSQL_HOST || 'localhost',
        user: process.env.MYSQL_USER || 'root',
        password: process.env.MYSQL_PASSWORD || '',
        database: process.env.MYSQL_DATABASE || 'nexusmk'
    },
    mikrotik: {
        connectionTimeout: parseInt(process.env.MIKROTIK_TIMEOUT || '5000'),
        cleanupInterval: parseInt(process.env.MIKROTIK_CLEANUP || '300000'),
        staleThreshold: parseInt(process.env.MIKROTIK_STALE || '600000')
    }
};
JAVASCRIPT;

if (file_put_contents("$remoteDir/backend/config/config.js", $content) !== false) {
    echo "  config.js -> OK\n";
} else {
    echo "  config.js -> ERROR\n";
}

// 4. Escribir mikrotikService.js
echo "\n[4/5] Escribiendo mikrotikService.js...\n";
$content = <<<'JAVASCRIPT'
const { RouterOSAPI } = require('node-routeros');

class MikroTikService {
    constructor() {
        this.connections = new Map();
        this._startCleanupTimer();
    }

    _startCleanupTimer() {
        const CLEANUP_INTERVAL = 5 * 60 * 1000;
        setInterval(() => this._cleanupStaleConnections(), CLEANUP_INTERVAL);
    }

    _cleanupStaleConnections() {
        const STALE_THRESHOLD = 10 * 60 * 1000;
        const now = Date.now();
        for (const [key, connData] of this.connections.entries()) {
            if (connData.connected && (now - connData._lastUsed) > STALE_THRESHOLD) {
                console.log(`Limpiando conexión stale: ${key}`);
                try { connData.close(); } catch (e) { /* ignore */ }
                this.connections.delete(key);
            }
        }
    }

    async _isConnected(conn) {
        try {
            await conn.write('/system/identity/print');
            return true;
        } catch (e) {
            return false;
        }
    }

    _getConnectionKey(host, port) {
        return `${host}:${port || 8728}`;
    }

    async connect(routerConfig) {
        const { host, port = 8728, username, password } = routerConfig;
        const key = this._getConnectionKey(host, port);

        if (this.connections.has(key)) {
            const existing = this.connections.get(key);
            if (existing.connected) {
                const isAlive = await this._isConnected(existing);
                if (isAlive) {
                    existing._lastUsed = Date.now();
                    return existing;
                }
            }
            this.connections.delete(key);
        }

        const conn = new RouterOSAPI({
            host,
            port,
            username,
            password,
            timeout: 5000
        });

        try {
            await conn.connect();
            conn._lastUsed = Date.now();
            this.connections.set(key, conn);
            console.log(`Conectado a MikroTik: ${key}`);
            return conn;
        } catch (error) {
            console.error(`Error conectando a MikroTik ${key}:`, error.message);
            throw error;
        }
    }

    async disconnect(host, port) {
        const key = this._getConnectionKey(host, port);
        if (this.connections.has(key)) {
            try {
                await this.connections.get(key).close();
            } catch (e) { /* ignore */ }
            this.connections.delete(key);
            console.log(`Desconectado de MikroTik: ${key}`);
        }
    }

    async executeCommand(routerConfig, command, args = {}) {
        const conn = await this.connect(routerConfig);
        try {
            const result = await conn.write(command, args);
            conn._lastUsed = Date.now();
            return result;
        } catch (error) {
            console.error(`Error ejecutando comando ${command}:`, error.message);
            throw error;
        }
    }

    async getSystemInfo(routerConfig) {
        try {
            const [identity, resources, uptime] = await Promise.all([
                this.executeCommand(routerConfig, '/system/identity/print'),
                this.executeCommand(routerConfig, '/system/resource/print'),
                this.executeCommand(routerConfig, '/system/uptime/print')
            ]);
            return {
                identity: identity[0]?.name || 'Unknown',
                version: resources[0]?.version || 'Unknown',
                cpu: resources[0]?.cpu || 'Unknown',
                cpuLoad: resources[0]?.['cpu-load'] || '0',
                uptime: uptime[0]?.uptime || resources[0]?.uptime || 'Unknown',
                totalMemory: resources[0]?.['total-memory'] || 0,
                freeMemory: resources[0]?.['free-memory'] || 0,
                totalHdd: resources[0]?.['total-hdd-space'] || 0,
                freeHdd: resources[0]?.['free-hdd-space'] || 0,
                architecture: resources[0]?.architecture || 'Unknown',
                board: resources[0]?.['board-name'] || 'Unknown'
            };
        } catch (error) {
            console.error('Error obteniendo info del sistema:', error.message);
            throw error;
        }
    }

    async getInterfaces(routerConfig) {
        try {
            const interfaces = await this.executeCommand(routerConfig, '/interface/print');
            return interfaces.map(iface => ({
                name: iface.name,
                type: iface.type,
                macAddress: iface['mac-address'],
                running: iface.running === 'true' || iface.running === true,
                enabled: iface.disabled === 'false' || iface.disabled === false,
                comment: iface.comment || '',
                lastLinkDown: iface['last-link-down-time'] || '',
                lastLinkUp: iface['last-link-up-time'] || ''
            }));
        } catch (error) {
            console.error('Error obteniendo interfaces:', error.message);
            throw error;
        }
    }

    async getIPAddresses(routerConfig) {
        try {
            const addresses = await this.executeCommand(routerConfig, '/ip/address/print');
            return addresses.map(addr => ({
                address: addr.address,
                network: addr.network,
                interface: addr.interface,
                disabled: addr.disabled === 'true'
            }));
        } catch (error) {
            console.error('Error obteniendo direcciones IP:', error.message);
            throw error;
        }
    }

    async getDHCPLeases(routerConfig) {
        try {
            const leases = await this.executeCommand(routerConfig, '/ip/dhcp-server/lease/print');
            return leases.map(lease => ({
                address: lease['address'],
                macAddress: lease['mac-address'],
                hostName: lease['host-name'] || '',
                status: lease['status'],
                expires: lease['expires-after'] || '',
                server: lease['server'],
                comment: lease.comment || ''
            }));
        } catch (error) {
            console.error('Error obteniendo leases DHCP:', error.message);
            throw error;
        }
    }

    async getActiveConnections(routerConfig) {
        try {
            const connections = await this.executeCommand(routerConfig, '/ip/firewall/connection/print');
            return connections.map(conn => ({
                srcAddress: conn['src-address'],
                dstAddress: conn['dst-address'],
                protocol: conn.protocol,
                srcPort: conn['src-port'],
                dstPort: conn['dst-port'],
                bytes: conn.bytes || 0,
                timeout: conn.timeout || ''
            }));
        } catch (error) {
            console.error('Error obteniendo conexiones activas:', error.message);
            throw error;
        }
    }

    async getAddressLists(routerConfig) {
        try {
            const lists = await this.executeCommand(routerConfig, '/ip/firewall/address-list/print');
            return lists.map(list => ({
                list: list.list,
                address: list.address,
                timeout: list.timeout || '',
                dynamic: list.dynamic === 'true',
                comment: list.comment || ''
            }));
        } catch (error) {
            console.error('Error obteniendo address lists:', error.message);
            throw error;
        }
    }

    async getFirewallRules(routerConfig) {
        try {
            const rules = await this.executeCommand(routerConfig, '/ip/firewall/filter/print');
            return rules.map(rule => ({
                chain: rule.chain,
                action: rule.action,
                protocol: rule.protocol || '',
                srcAddress: rule['src-address'] || '',
                dstAddress: rule['dst-address'] || '',
                srcPort: rule['src-port'] || '',
                dstPort: rule['dst-port'] || '',
                disabled: rule.disabled === 'true',
                comment: rule.comment || '',
                bytes: rule.bytes || 0,
                packets: rule.packets || 0
            }));
        } catch (error) {
            console.error('Error obteniendo reglas de firewall:', error.message);
            throw error;
        }
    }

    async getActiveUsers(routerConfig) {
        try {
            const users = await this.executeCommand(routerConfig, '/user/active/print');
            return users.map(user => ({
                name: user.name,
                address: user.address,
                via: user.via || '',
                group: user.group,
                loggedIn: user['logged-in'] || '',
                duration: user.duration || ''
            }));
        } catch (error) {
            console.error('Error obteniendo usuarios activos:', error.message);
            throw error;
        }
    }

    async createHotspotTicket(routerConfig, ticketData) {
        try {
            const user = await this.executeCommand(routerConfig, '/ip/hotspot/user/add', {
                name: ticketData.username,
                password: ticketData.password,
                server: ticketData.server || 'all',
                limit_uptime: ticketData.timeLimit || '',
                limit_bytes_total: ticketData.dataLimit || ''
            });
            return user;
        } catch (error) {
            console.error('Error creando ticket hotspot:', error.message);
            throw error;
        }
    }

    async getHotspotProfiles(routerConfig) {
        try {
            const profiles = await this.executeCommand(routerConfig, '/ip/hotspot/user/profile/print');
            return profiles.map(profile => ({
                name: profile.name,
                sharedUsers: profile['shared-users'] || 0,
                rateLimit: profile['rate-limit'] || '',
                sessionTimeout: profile['session-timeout'] || '',
                idleTimeout: profile['idle-timeout'] || '',
                keepaliveTimeout: profile['keepalive-timeout'] || ''
            }));
        } catch (error) {
            console.error('Error obteniendo perfiles hotspot:', error.message);
            throw error;
        }
    }

    async getHotspotServers(routerConfig) {
        try {
            const servers = await this.executeCommand(routerConfig, '/ip/hotspot/print');
            return servers.map(server => ({
                name: server.name,
                interface: server.interface,
                addressPool: server['address-pool'] || '',
                profile: server.profile || '',
                disabled: server.disabled === 'true',
                uptime: server.uptime || ''
            }));
        } catch (error) {
            console.error('Error obteniendo servidores hotspot:', error.message);
            throw error;
        }
    }

    async getHotspotActiveUsers(routerConfig) {
        try {
            const users = await this.executeCommand(routerConfig, '/ip/hotspot/active/print');
            return users.map(user => ({
                user: user.user,
                address: user.address,
                macAddress: user['mac-address'],
                loginBy: user['login-by'] || '',
                uptime: user.uptime || '',
                idleTime: user['idle-time'] || '',
                keepaliveTimeout: user['keepalive-timeout'] || '',
                bytesIn: user.bytes || 0,
                bytesOut: user['bytes-out'] || 0,
                packetsIn: user.packets || 0,
                packetsOut: user['packets-out'] || 0
            }));
        } catch (error) {
            console.error('Error obteniendo usuarios hotspot activos:', error.message);
            throw error;
        }
    }

    async getSystemLogs(routerConfig, topics = []) {
        try {
            const params = topics.length > 0 ? { topics: topics.join(',') } : {};
            const logs = await this.executeCommand(routerConfig, '/log/print', params);
            return logs.slice(0, 100).map(log => ({
                time: log.time,
                topics: log.topics,
                message: log.message
            }));
        } catch (error) {
            console.error('Error obteniendo logs del sistema:', error.message);
            throw error;
        }
    }

    async getTrafficStats(routerConfig) {
        try {
            const interfaces = await this.executeCommand(routerConfig, '/interface/monitor-traffic', {
                interface: 'all',
                once: ''
            });
            return interfaces.map(iface => ({
                name: iface.name,
                rxBitsPerSecond: parseInt(iface['rx-bits-per-second']) || 0,
                txBitsPerSecond: parseInt(iface['tx-bits-per-second']) || 0,
                rxPacketsPerSecond: parseInt(iface['rx-packets-per-second']) || 0,
                txPacketsPerSecond: parseInt(iface['tx-packets-per-second']) || 0
            }));
        } catch (error) {
            console.error('Error obteniendo estadísticas de tráfico:', error.message);
            throw error;
        }
    }

    async testConnection(routerConfig) {
        try {
            const identity = await this.executeCommand(routerConfig, '/system/identity/print');
            return {
                success: true,
                identity: identity[0]?.name || 'Unknown',
                message: 'Conexión exitosa'
            };
        } catch (error) {
            return {
                success: false,
                message: error.message
            };
        }
    }

    async executeCustomCommand(routerConfig, commandPath, args = {}) {
        try {
            const result = await this.executeCommand(routerConfig, commandPath, args);
            return result;
        } catch (error) {
            console.error(`Error ejecutando comando personalizado ${commandPath}:`, error.message);
            throw error;
        }
    }
}

module.exports = new MikroTikService();
JAVASCRIPT;

if (file_put_contents("$remoteDir/backend/services/mikrotikService.js", $content) !== false) {
    echo "  mikrotikService.js -> OK\n";
} else {
    echo "  mikrotikService.js -> ERROR\n";
}

// 5. Escribir nexusmkController.js
echo "\n[5/5] Escribiendo nexusmkController.js...\n";
$content = <<<'JAVASCRIPT'
const mysql = require('mysql2/promise');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const config = require('../config/config');

const DB_CONFIG = {
    host: process.env.NEXUSMK_DB_HOST || 'localhost',
    user: process.env.NEXUSMK_DB_USER || 'root',
    password: process.env.NEXUSMK_DB_PASSWORD || '',
    database: 'nexusmk',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
};

let pool = null;

function getPool() {
    if (!pool) {
        pool = mysql.createPool(DB_CONFIG);
    }
    return pool;
}

const TABLAS_PERMITIDAS = [
    'dispositivos_mikrotik', 'clientes', 'usuarios_nexusmk',
    'planes', 'facturas', 'pagos', 'tickets_soporte',
    'configuracion', 'logs_sistema'
];

const nexusmkController = {
    async login(req, res) {
        let connection;
        try {
            const { username, password } = req.body;
            if (!username || !password) {
                return res.status(400).json({
                    success: false,
                    message: 'Usuario y contraseña son requeridos'
                });
            }
            const pool = getPool();
            connection = await pool.getConnection();
            const [rows] = await connection.execute(
                'SELECT id, usuario, password_hash, nombre, rol FROM usuarios_nexusmk WHERE usuario = ? AND activo = 1 LIMIT 1',
                [username]
            );
            if (rows.length === 0) {
                return res.status(401).json({
                    success: false,
                    message: 'Credenciales inválidas'
                });
            }
            const user = rows[0];
            const isValid = bcrypt.compareSync(password, user.password_hash);
            if (!isValid) {
                return res.status(401).json({
                    success: false,
                    message: 'Credenciales inválidas'
                });
            }
            await connection.execute(
                'UPDATE usuarios_nexusmk SET ultimo_acceso = NOW() WHERE id = ?',
                [user.id]
            );
            const token = jwt.sign(
                { id: user.id, username: user.usuario, role: user.rol || 'operator', source: 'nexusmk' },
                config.jwtSecret,
                { expiresIn: '24h' }
            );
            res.json({
                success: true,
                message: 'Inicio de sesión exitoso',
                data: {
                    user: { id: user.id, username: user.usuario, role: user.rol || 'operator', name: user.nombre },
                    token
                }
            });
        } catch (error) {
            console.error('Error en login nexusmk:', error);
            res.status(500).json({
                success: false,
                message: 'Error interno del servidor'
            });
        } finally {
            if (connection) connection.release();
        }
    },

    async getStats(req, res) {
        let connection;
        try {
            const pool = getPool();
            connection = await pool.getConnection();
            const [dispositivos] = await connection.execute('SELECT COUNT(*) as total FROM dispositivos_mikrotik');
            const [clientes] = await connection.execute('SELECT COUNT(*) as total FROM clientes');
            const [activos] = await connection.execute("SELECT COUNT(*) as total FROM dispositivos_mikrotik WHERE estado = 'activo'");
            res.json({
                success: true,
                data: {
                    dispositivos: dispositivos[0].total,
                    clientes: clientes[0].total,
                    activos: activos[0].total
                }
            });
        } catch (error) {
            console.error('Error en getStats:', error);
            res.status(500).json({
                success: false,
                message: 'Error al obtener estadísticas'
            });
        } finally {
            if (connection) connection.release();
        }
    },

    async getDispositivos(req, res) {
        let connection;
        try {
            const pool = getPool();
            connection = await pool.getConnection();
            const [rows] = await connection.execute('SELECT * FROM dispositivos_mikrotik ORDER BY nombre ASC');
            res.json({
                success: true,
                data: rows
            });
        } catch (error) {
            console.error('Error en getDispositivos:', error);
            res.status(500).json({
                success: false,
                message: 'Error al obtener dispositivos'
            });
        } finally {
            if (connection) connection.release();
        }
    },

    async getDbInfo(req, res) {
        let connection;
        try {
            const { tabla } = req.query;
            if (!tabla || !TABLAS_PERMITIDAS.includes(tabla)) {
                return res.status(400).json({
                    success: false,
                    message: 'Tabla no válida'
                });
            }
            const pool = getPool();
            connection = await pool.getConnection();
            const [rows] = await connection.execute(
                `SELECT * FROM \`${tabla}\` LIMIT 100`
            );
            const [countResult] = await connection.execute(
                `SELECT COUNT(*) as total FROM \`${tabla}\``
            );
            res.json({
                success: true,
                data: {
                    registros: rows,
                    total: countResult[0].total,
                    tabla
                }
            });
        } catch (error) {
            console.error('Error en getDbInfo:', error);
            res.status(500).json({
                success: false,
                message: 'Error al obtener información de la base de datos'
            });
        } finally {
            if (connection) connection.release();
        }
    },

    async health(req, res) {
        let connection;
        try {
            const pool = getPool();
            connection = await pool.getConnection();
            await connection.execute('SELECT 1');
            res.json({
                success: true,
                message: 'Conexión a MySQL establecida',
                timestamp: new Date().toISOString()
            });
        } catch (error) {
            res.status(500).json({
                success: false,
                message: 'Error de conexión a MySQL',
                error: error.message
            });
        } finally {
            if (connection) connection.release();
        }
    }
};

module.exports = nexusmkController;
JAVASCRIPT;

if (file_put_contents("$remoteDir/backend/controllers/nexusmkController.js", $content) !== false) {
    echo "  nexusmkController.js -> OK\n";
} else {
    echo "  nexusmkController.js -> ERROR\n";
}

// 6. Escribir start.js
echo "\n[6/5] Escribiendo start.js...\n";
$content = <<<'JAVASCRIPT'
const app = require('./backend/app');
const config = require('./backend/config/config');

const PORT = config.port || 3000;

async function initialize() {
    try {
        const db = require('./backend/models/database');
        await db.initialize();
        console.log('Base de datos inicializada correctamente');
    } catch (error) {
        console.error('Error al inicializar la base de datos:', error.message);
    }
}

initialize();

app.listen(PORT, '0.0.0.0', () => {
    console.log(`MkController v3.0 iniciado en puerto ${PORT}`);
    console.log(`Modo: ${process.env.NODE_ENV || 'development'}`);
    console.log(`Directorio: ${__dirname}`);
});

module.exports = app;
JAVASCRIPT;

if (file_put_contents("$remoteDir/start.js", $content) !== false) {
    echo "  start.js -> OK\n";
} else {
    echo "  start.js -> ERROR\n";
}

// 7. Crear .env si no existe
echo "\n[7/5] Verificando .env...\n";
$envPath = "$remoteDir/.env";
if (!file_exists($envPath)) {
    $envContent = <<<'ENV'
PORT=3000
NODE_ENV=production
JWT_SECRET=mkcontroller_v3_secret_key_2024
MYSQL_HOST=localhost
MYSQL_USER=root
MYSQL_PASSWORD=
MYSQL_DATABASE=nexusmk
NEXUSMK_DB_HOST=localhost
NEXUSMK_DB_USER=root
NEXUSMK_DB_PASSWORD=
CORS_ORIGIN=*
MIKROTIK_TIMEOUT=5000
MIKROTIK_CLEANUP=300000
MIKROTIK_STALE=600000
ENV;
    if (file_put_contents($envPath, $envContent) !== false) {
        echo "  .env creado -> OK\n";
    } else {
        echo "  .env -> ERROR\n";
    }
} else {
    echo "  .env ya existe -> OK\n";
}

echo "\n=== DESPLIEGUE COMPLETADO ===\n";
echo "Backup creado en: $backupDir\n";
echo "\nIMPORTANTE: Debes reiniciar la aplicación Node.js desde cPanel\n";
echo "O ejecutar: touch $remoteDir/tmp/restart.txt\n";
