/**
 * nexusMK Controller
 * Controlador para el módulo nexusMK - Gestión de MikroTik desde MySQL
 * Se conecta a la base de datos MySQL 'nexusmk' (independiente del JSON DB)
 */
const mysql = require('mysql2/promise');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const config = require('../config/config');

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

      // Intentar con WHERE estado=1, si falla usar COUNT(*) sin filtro
      let total_dispositivos = 0, total_interfaces = 0, total_peers = 0, total_reglas = 0;

      try {
        const [d] = await connection.execute("SELECT COUNT(*) as total FROM `dispositivos_mikrotik` WHERE `estado`=1");
        total_dispositivos = d[0].total;
      } catch (e) {
        const [d] = await connection.execute("SELECT COUNT(*) as total FROM `dispositivos_mikrotik`");
        total_dispositivos = d[0].total;
      }

      try {
        const [i] = await connection.execute("SELECT COUNT(*) as total FROM `interfaces_wireguard`");
        total_interfaces = i[0].total;
      } catch (e) {
        total_interfaces = 0;
      }

      try {
        const [p] = await connection.execute("SELECT COUNT(*) as total FROM `peers_wireguard` WHERE `estado`=1");
        total_peers = p[0].total;
      } catch (e) {
        const [p] = await connection.execute("SELECT COUNT(*) as total FROM `peers_wireguard`");
        total_peers = p[0].total;
      }

      try {
        const [r] = await connection.execute("SELECT COUNT(*) as total FROM `reglas_firewall` WHERE `estado`=1");
        total_reglas = r[0].total;
      } catch (e) {
        const [r] = await connection.execute("SELECT COUNT(*) as total FROM `reglas_firewall`");
        total_reglas = r[0].total;
      }

      res.json({
        success: true,
        data: {
          total_dispositivos,
          total_interfaces,
          total_peers,
          total_reglas
        }
      });
    } catch (error) {
      console.error('[nexusMK] Error en stats:', error);
      res.status(500).json({ success: false, message: 'Error al obtener estadísticas', error: error.message });
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
      
      let rows;
      try {
        [rows] = await connection.execute(
          "SELECT * FROM `dispositivos_mikrotik` WHERE `estado`=1 ORDER BY `id_dispositivo`"
        );
      } catch (e) {
        // Si la columna estado no existe, obtener todos
        [rows] = await connection.execute(
          "SELECT * FROM `dispositivos_mikrotik` ORDER BY `id_dispositivo`"
        );
      }

      res.json({ success: true, data: rows });
    } catch (error) {
      console.error('[nexusMK] Error en dispositivos:', error);
      res.status(500).json({ success: false, message: 'Error al obtener dispositivos', error: error.message });
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
