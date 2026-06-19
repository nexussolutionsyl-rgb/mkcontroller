const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { v4: uuidv4 } = require('uuid');
const config = require('../config/config');
const db = require('../models/database');
const mysql = require('mysql2/promise');

// Pool de conexiones MySQL (singleton)
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

/**
 * Controlador de Autenticación
 */
const authController = {
  /**
   * Inicio de sesión
   * POST /api/auth/login
   * Soporta autenticación contra users.json (MkController) y
   * como fallback contra MySQL nexusMK
   */
  async login(req, res) {
    try {
      const { username, password } = req.body;

      if (!username || !password) {
        return res.status(400).json({
          success: false,
          message: 'Usuario y contraseña son requeridos'
        });
      }

      // ============================================
      // PRIMERO: Intentar autenticación contra users.json (MkController local)
      // ============================================
      const users = await db.getAll('users');
      const localUser = users.find(u => u.username === username);

      if (localUser) {
        const isValidPassword = await bcrypt.compare(password, localUser.password);
        if (isValidPassword) {
          if (localUser.status !== 'active') {
            return res.status(403).json({
              success: false,
              message: 'Cuenta desactivada. Contacte al administrador'
            });
          }

          const token = jwt.sign(
            {
              id: localUser.id,
              username: localUser.username,
              role: localUser.role,
              clientId: localUser.clientId || null
            },
            config.jwtSecret,
            { expiresIn: config.jwtExpiresIn }
          );

          await db.create('activityLog', {
            id: uuidv4(),
            userId: localUser.id,
            username: localUser.username,
            action: 'login',
            details: 'Inicio de sesión (MkController)',
            ip: req.ip,
            userAgent: req.headers['user-agent']
          });

          return res.json({
            success: true,
            message: 'Inicio de sesión exitoso',
            data: {
              token,
              user: {
                id: localUser.id,
                username: localUser.username,
                name: localUser.name,
                email: localUser.email,
                role: localUser.role,
                clientId: localUser.clientId || null
              }
            }
          });
        }
      }

      // ============================================
      // SEGUNDO: Fallback - Intentar autenticación contra MySQL nexusMK
      // Usa pool de conexiones con una sola conexión para SELECT + UPDATE
      // ============================================
      let connection = null;
      try {
        const pool = getNexusPool();
        connection = await pool.getConnection();

        const [rows] = await connection.execute(
          'SELECT * FROM usuarios_nexusmk WHERE usuario = ? AND estado = 1 LIMIT 1',
          [username]
        );

        if (rows.length > 0) {
          const nexusUser = rows[0];
          const isValidNexus = bcrypt.compareSync(password, nexusUser.clave);

          if (isValidNexus) {
            // Actualizar último acceso (reusa misma conexión)
            await connection.execute(
              'UPDATE usuarios_nexusmk SET ultimo_acceso = NOW() WHERE id_usuario = ?',
              [nexusUser.id_usuario]
            );

            const token = jwt.sign(
              {
                id: `nexus_${nexusUser.id_usuario}`,
                username: nexusUser.usuario,
                role: 'nexusmk',
                name: nexusUser.nombre_completo,
                permisos: nexusUser.permisos,
                authSource: 'nexusmk'
              },
              config.jwtSecret,
              { expiresIn: config.jwtExpiresIn }
            );

            await db.create('activityLog', {
              id: uuidv4(),
              userId: `nexus_${nexusUser.id_usuario}`,
              username: nexusUser.usuario,
              action: 'login',
              details: 'Inicio de sesión (nexusMK MySQL)',
              ip: req.ip,
              userAgent: req.headers['user-agent']
            });

            return res.json({
              success: true,
              message: 'Inicio de sesión exitoso',
              data: {
                token,
                user: {
                  id: `nexus_${nexusUser.id_usuario}`,
                  username: nexusUser.usuario,
                  name: nexusUser.nombre_completo,
                  email: nexusUser.correo || '',
                  role: 'nexusmk',
                  authSource: 'nexusmk'
                }
              }
            });
          }
        }
      } catch (nexusError) {
        console.warn('[Auth] Fallback nexusMK no disponible:', nexusError.message);
      } finally {
        if (connection) connection.release();
      }

      return res.status(401).json({
        success: false,
        message: 'Credenciales inválidas'
      });

    } catch (error) {
      console.error('[Auth] Error en login:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Verificar token actual
   * GET /api/auth/verify
   */
  async verifyToken(req, res) {
    try {
      const user = await db.getById('users', req.user.id);
      
      if (!user) {
        return res.status(404).json({
          success: false,
          message: 'Usuario no encontrado'
        });
      }

      res.json({
        success: true,
        data: {
          id: user.id,
          username: user.username,
          name: user.name,
          email: user.email,
          role: user.role,
          clientId: user.clientId || null
        }
      });
    } catch (error) {
      console.error('[Auth] Error verificando token:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Cambiar contraseña
   * POST /api/auth/change-password
   */
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

      const isValidPassword = await bcrypt.compare(currentPassword, user.password);
      if (!isValidPassword) {
        return res.status(401).json({
          success: false,
          message: 'Contraseña actual incorrecta'
        });
      }

      const hashedPassword = await bcrypt.hash(newPassword, 10);
      await db.update('users', user.id, { password: hashedPassword });

      res.json({
        success: true,
        message: 'Contraseña actualizada exitosamente'
      });
    } catch (error) {
      console.error('[Auth] Error cambiando contraseña:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Cerrar sesión
   * POST /api/auth/logout
   */
  async logout(req, res) {
    try {
      await db.create('activityLog', {
        id: uuidv4(),
        userId: req.user.id,
        username: req.user.username,
        action: 'logout',
        details: 'Cierre de sesión',
        ip: req.ip,
        userAgent: req.headers['user-agent']
      });

      res.json({
        success: true,
        message: 'Sesión cerrada exitosamente'
      });
    } catch (error) {
      console.error('[Auth] Error en logout:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  }
};

module.exports = authController;
