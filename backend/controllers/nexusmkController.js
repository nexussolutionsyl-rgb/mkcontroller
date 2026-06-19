/**
 * nexusMK Controller
 * Controlador para el módulo nexusMK - Gestión de MikroTik desde MySQL
 * Se conecta a la base de datos MySQL 'nexusmk' (independiente del JSON DB)
 */
const mysql = require('mysql2/promise');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const config = require('../config/config');
const { execSync } = require('child_process');

// Cargar configuracion desde variables de entorno
// Configurar en .env:
//   NEXUSMK_DB_HOST=localhost
//   NEXUSMK_DB_USER=nexusyl_nexusmk
//   NEXUSMK_DB_PASSWORD=tu_password
//   NEXUSMK_DB_NAME=nexusyl_nexusmk
const DB_CONFIG = {
  host: process.env.NEXUSMK_DB_HOST || 'localhost',
  user: process.env.NEXUSMK_DB_USER || 'nexusyl_nexusmk',
  password: process.env.NEXUSMK_DB_PASSWORD || '',
  database: process.env.NEXUSMK_DB_NAME || 'nexusyl_nexusmk',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
};

// Pool de conexiones MySQL para reutilización eficiente
let pool = null;

/**
 * Obtiene el pool de conexiones MySQL (singleton)
 */
function getPool() {
  if (!pool) {
    pool = mysql.createPool(DB_CONFIG);
  }
  return pool;
}

/**
 * Lista blanca de tablas permitidas para evitar inyección SQL
 */
const TABLAS_PERMITIDAS = [
  'usuarios_nexusmk',
  'dispositivos_mikrotik',
  'interfaces_wireguard',
  'peers_wireguard',
  'reglas_firewall',
  'configuracion_general'
];

const nexusmkController = {
  /**
   * Endpoint de diagnóstico - muestra versión del código y config
   * GET /api/nexusmk/debug
   */
  async debug(req, res) {
    try {
      let commitHash = 'unknown';
      try {
        commitHash = execSync('git rev-parse HEAD', { encoding: 'utf8', timeout: 5000 }).trim();
      } catch (e) {
        commitHash = 'no-git';
      }
      
      // Probar conexión MySQL
      let dbStatus = 'unknown';
      let dbError = null;
      let connection = null;
      try {
        const poolConn = getPool();
        connection = await poolConn.getConnection();
        await connection.ping();
        dbStatus = 'connected';
        
        // Probar query simple
        try {
          const [r] = await connection.execute('SELECT 1 as test');
          dbStatus = 'query-ok';
        } catch (e) {
          dbStatus = 'query-failed';
          dbError = e.message;
        }
      } catch (e) {
        dbStatus = 'connection-failed';
        dbError = e.message;
      } finally {
        if (connection) connection.release();
      }

      res.json({
        success: true,
        data: {
          commit: commitHash,
          node_version: process.version,
          db_config: {
            host: DB_CONFIG.host,
            user: DB_CONFIG.user,
            database: DB_CONFIG.database,
            password_set: !!DB_CONFIG.password
          },
          db_status: dbStatus,
          db_error: dbError,
          env_vars: {
            NEXUSMK_DB_HOST: process.env.NEXUSMK_DB_HOST ? 'set' : 'not-set',
            NEXUSMK_DB_USER: process.env.NEXUSMK_DB_USER ? 'set' : 'not-set',
            NEXUSMK_DB_PASSWORD: process.env.NEXUSMK_DB_PASSWORD ? 'set' : 'not-set',
            NEXUSMK_DB_NAME: process.env.NEXUSMK_DB_NAME ? 'set' : 'not-set'
          }
        }
      });
    } catch (error) {
      res.status(500).json({ success: false, message: 'Error en debug', error: error.message });
    }
  },

  /**
   * Inicio de sesión en nexusMK
   * POST /api/nexusmk/login
   */
  async login(req, res) {
    let connection = null;
    try {
      const { username, password } = req.body;

      if (!username || !password) {
        return res.status(400).json({
          success: false,
          message: 'Usuario y contraseña son requeridos'
        });
      }

      const poolConn = getPool();
      connection = await poolConn.getConnection();

      const [rows] = await connection.execute(
        'SELECT * FROM usuarios_nexusmk WHERE usuario = ? AND estado = 1 LIMIT 1',
        [username]
      );

      if (rows.length === 0) {
        return res.status(401).json({
          success: false,
          message: 'Credenciales inválidas'
        });
      }

      const user = rows[0];

      // Verificar contraseña con bcrypt (compatible con PHP password_hash)
      const isValid = bcrypt.compareSync(password, user.clave);

      if (!isValid) {
        return res.status(401).json({
          success: false,
          message: 'Credenciales inválidas'
        });
      }

      // Actualizar último acceso (reusa la misma conexión)
      await connection.execute(
        'UPDATE usuarios_nexusmk SET ultimo_acceso = NOW() WHERE id_usuario = ?',
        [user.id_usuario]
      );

      // Generar token JWT
      const token = jwt.sign(
        {
          id: user.id_usuario,
          username: user.usuario,
          nombre: user.nombre_completo,
          role: 'nexusmk',
          permisos: user.permisos
        },
        config.jwtSecret,
        { expiresIn: '24h' }
      );

      res.json({
        success: true,
        message: 'Inicio de sesión exitoso',
        data: {
          token,
          user: {
            id: user.id_usuario,
            username: user.usuario,
            nombre: user.nombre_completo,
            permisos: user.permisos
          }
        }
      });
    } catch (error) {
      console.error('[nexusMK] Error en login:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    } finally {
      if (connection) connection.release();
    }
  },

  /**
   * Obtener estadísticas del dashboard
   * GET /api/nexusmk/stats
   */
  async getStats(req, res) {
    let connection = null;
    try {
      const poolConn = getPool();
      connection = await poolConn.getConnection();

      // Usar queries sin WHERE para evitar cualquier problema de columna
      const [d] = await connection.execute("SELECT COUNT(*) as total FROM `dispositivos_mikrotik`");
      const [i] = await connection.execute("SELECT COUNT(*) as total FROM `interfaces_wireguard`");
      const [p] = await connection.execute("SELECT COUNT(*) as total FROM `peers_wireguard`");
      const [r] = await connection.execute("SELECT COUNT(*) as total FROM `reglas_firewall`");

      res.json({
        success: true,
        data: {
          total_dispositivos: d[0].total,
          total_interfaces: i[0].total,
          total_peers: p[0].total,
          total_reglas: r[0].total
        }
      });
    } catch (error) {
      console.error('[nexusMK] Error en stats:', error);
      res.status(500).json({ success: false, message: 'Error al obtener estadísticas', error: error.message, stack: error.stack });
    } finally {
      if (connection) connection.release();
    }
  },

  /**
   * Obtener lista de dispositivos
   * GET /api/nexusmk/dispositivos
   */
  async getDispositivos(req, res) {
    let connection = null;
    try {
      const poolConn = getPool();
      connection = await poolConn.getConnection();
      
      const [rows] = await connection.execute(
        "SELECT * FROM `dispositivos_mikrotik` ORDER BY `id_dispositivo`"
      );

      res.json({ success: true, data: rows });
    } catch (error) {
      console.error('[nexusMK] Error en dispositivos:', error);
      res.status(500).json({ success: false, message: 'Error al obtener dispositivos', error: error.message, stack: error.stack });
    } finally {
      if (connection) connection.release();
    }
  },

  /**
   * Obtener información de tablas de la BD
   * GET /api/nexusmk/dbinfo
   */
  async getDbInfo(req, res) {
    let connection = null;
    try {
      const poolConn = getPool();
      connection = await poolConn.getConnection();

      const info = [];

      for (const table of TABLAS_PERMITIDAS) {
        // El nombre de la tabla viene de la lista blanca, es seguro usarlo con backticks
        const [rows] = await connection.execute(
          `SELECT COUNT(*) as total FROM \`${table}\``
        );
        info.push({ tabla: table, registros: rows[0].total });
      }

      res.json({ success: true, data: info });
    } catch (error) {
      console.error('[nexusMK] Error en dbinfo:', error);
      res.status(500).json({ success: false, message: 'Error al obtener info de BD' });
    } finally {
      if (connection) connection.release();
    }
  },

  /**
   * Verificar estado de la conexión MySQL
   * GET /api/nexusmk/health
   */
  async health(req, res) {
    let connection = null;
    try {
      const poolConn = getPool();
      connection = await poolConn.getConnection();
      await connection.ping();

      res.json({
        success: true,
        message: 'Conexión a MySQL establecida',
        database: 'nexusmk'
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
