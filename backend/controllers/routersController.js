const { v4: uuidv4 } = require('uuid');
const db = require('../models/database');
const mikrotikService = require('../services/mikrotikService');

/**
 * Controlador de Routers MikroTik
 */
const routersController = {
  /**
   * Obtener todos los routers
   * GET /api/routers
   */
  async getAll(req, res) {
    try {
      let routers;
      
      // Filtrar por cliente si el usuario no es superadmin
      if (req.user.role === 'superadmin') {
        routers = await db.getAll('routers');
      } else {
        routers = await db.findBy('routers', 'clientId', req.user.clientId);
      }

      res.json({
        success: true,
        data: routers
      });
    } catch (error) {
      console.error('[Routers] Error obteniendo routers:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Obtener un router por ID
   * GET /api/routers/:id
   */
  async getById(req, res) {
    try {
      const router = await db.getById('routers', req.params.id);
      
      if (!router) {
        return res.status(404).json({
          success: false,
          message: 'Router no encontrado'
        });
      }

      // Verificar permisos
      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({
          success: false,
          message: 'No tienes permiso para acceder a este router'
        });
      }

      res.json({
        success: true,
        data: router
      });
    } catch (error) {
      console.error('[Routers] Error obteniendo router:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Crear un nuevo router
   * POST /api/routers
   */
  async create(req, res) {
    try {
      const { name, host, port, username, password, clientId, comment } = req.body;

      if (!name || !host || !username || !password) {
        return res.status(400).json({
          success: false,
          message: 'Campos requeridos: name, host, username, password'
        });
      }

      // Asignar clientId automáticamente si no es superadmin
      const routerClientId = req.user.role === 'superadmin' ? (clientId || null) : req.user.clientId;

      if (!routerClientId) {
        return res.status(400).json({
          success: false,
          message: 'El router debe estar asociado a un cliente'
        });
      }

      const newRouter = await db.create('routers', {
        id: uuidv4(),
        name,
        host,
        port: port || 8728,
        username,
        password,
        clientId: routerClientId,
        status: 'unknown',
        lastSeen: null,
        comment: comment || ''
      });

      res.status(201).json({
        success: true,
        message: 'Router agregado exitosamente',
        data: newRouter
      });
    } catch (error) {
      console.error('[Routers] Error creando router:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Actualizar un router
   * PUT /api/routers/:id
   */
  async update(req, res) {
    try {
      const routerId = req.params.id;
      const { name, host, port, username, password, clientId, comment } = req.body;

      const router = await db.getById('routers', routerId);
      if (!router) {
        return res.status(404).json({
          success: false,
          message: 'Router no encontrado'
        });
      }

      // Verificar permisos
      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({
          success: false,
          message: 'No tienes permiso para modificar este router'
        });
      }

      const updates = {};
      if (name) updates.name = name;
      if (host) updates.host = host;
      if (port) updates.port = port;
      if (username) updates.username = username;
      if (password) updates.password = password;
      if (clientId && req.user.role === 'superadmin') updates.clientId = clientId;
      if (comment !== undefined) updates.comment = comment;

      const updatedRouter = await db.update('routers', routerId, updates);

      res.json({
        success: true,
        message: 'Router actualizado exitosamente',
        data: updatedRouter
      });
    } catch (error) {
      console.error('[Routers] Error actualizando router:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Eliminar un router
   * DELETE /api/routers/:id
   */
  async delete(req, res) {
    try {
      const routerId = req.params.id;

      const router = await db.getById('routers', routerId);
      if (!router) {
        return res.status(404).json({
          success: false,
          message: 'Router no encontrado'
        });
      }

      // Verificar permisos
      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({
          success: false,
          message: 'No tienes permiso para eliminar este router'
        });
      }

      await db.delete('routers', routerId);

      res.json({
        success: true,
        message: 'Router eliminado exitosamente'
      });
    } catch (error) {
      console.error('[Routers] Error eliminando router:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Probar conexión con un router
   * POST /api/routers/:id/test
   */
  async testConnection(req, res) {
    try {
      const router = await db.getById('routers', req.params.id);
      
      if (!router) {
        return res.status(404).json({
          success: false,
          message: 'Router no encontrado'
        });
      }

      // Verificar permisos
      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({
          success: false,
          message: 'No tienes permiso para acceder a este router'
        });
      }

      const result = await mikrotikService.testConnection({
        host: router.host,
        port: router.port,
        username: router.username,
        password: router.password
      });

      // Actualizar estado del router
      const status = result.connected ? 'online' : 'offline';
      await db.update('routers', router.id, {
        status,
        lastSeen: result.connected ? new Date().toISOString() : router.lastSeen
      });

      res.json({
        success: true,
        data: result
      });
    } catch (error) {
      console.error('[Routers] Error probando conexión:', error);
      res.status(500).json({
        success: false,
        message: 'Error probando conexión: ' + error.message
      });
    }
  },

  /**
   * Obtener información del sistema del router
   * GET /api/routers/:id/system-info
   */
  async getSystemInfo(req, res) {
    try {
      const router = await db.getById('routers', req.params.id);
      
      if (!router) {
        return res.status(404).json({
          success: false,
          message: 'Router no encontrado'
        });
      }

      // Verificar permisos
      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({
          success: false,
          message: 'No tienes permiso para acceder a este router'
        });
      }

      const info = await mikrotikService.getSystemInfo({
        host: router.host,
        port: router.port,
        username: router.username,
        password: router.password
      });

      res.json({
        success: true,
        data: info
      });
    } catch (error) {
      console.error('[Routers] Error obteniendo info del sistema:', error);
      res.status(500).json({
        success: false,
        message: error.message
      });
    }
  },

  /**
   * Obtener interfaces del router
   * GET /api/routers/:id/interfaces
   */
  async getInterfaces(req, res) {
    try {
      const router = await db.getById('routers', req.params.id);
      
      if (!router) {
        return res.status(404).json({
          success: false,
          message: 'Router no encontrado'
        });
      }

      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({
          success: false,
          message: 'No tienes permiso para acceder a este router'
        });
      }

      const interfaces = await mikrotikService.getInterfaces({
        host: router.host,
        port: router.port,
        username: router.username,
        password: router.password
      });

      res.json({
        success: true,
        data: interfaces
      });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * Obtener direcciones IP del router
   * GET /api/routers/:id/ip-addresses
   */
  async getIPAddresses(req, res) {
    try {
      const router = await db.getById('routers', req.params.id);
      
      if (!router) {
        return res.status(404).json({ success: false, message: 'Router no encontrado' });
      }

      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({ success: false, message: 'Acceso denegado' });
      }

      const addresses = await mikrotikService.getIPAddresses({
        host: router.host, port: router.port,
        username: router.username, password: router.password
      });

      res.json({ success: true, data: addresses });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * Obtener clientes DHCP
   * GET /api/routers/:id/dhcp-leases
   */
  async getDHCPLeases(req, res) {
    try {
      const router = await db.getById('routers', req.params.id);
      if (!router) return res.status(404).json({ success: false, message: 'Router no encontrado' });

      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({ success: false, message: 'Acceso denegado' });
      }

      const leases = await mikrotikService.getDHCPLeases({
        host: router.host, port: router.port,
        username: router.username, password: router.password
      });

      res.json({ success: true, data: leases });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * Obtener reglas de firewall
   * GET /api/routers/:id/firewall
   */
  async getFirewallRules(req, res) {
    try {
      const router = await db.getById('routers', req.params.id);
      if (!router) return res.status(404).json({ success: false, message: 'Router no encontrado' });

      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({ success: false, message: 'Acceso denegado' });
      }

      const rules = await mikrotikService.getFirewallRules({
        host: router.host, port: router.port,
        username: router.username, password: router.password
      });

      res.json({ success: true, data: rules });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * Obtener conexiones activas
   * GET /api/routers/:id/connections
   */
  async getActiveConnections(req, res) {
    try {
      const router = await db.getById('routers', req.params.id);
      if (!router) return res.status(404).json({ success: false, message: 'Router no encontrado' });

      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({ success: false, message: 'Acceso denegado' });
      }

      const connections = await mikrotikService.getActiveConnections({
        host: router.host, port: router.port,
        username: router.username, password: router.password
      });

      res.json({ success: true, data: connections });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * Obtener logs del sistema
   * GET /api/routers/:id/logs
   */
  async getLogs(req, res) {
    try {
      const router = await db.getById('routers', req.params.id);
      if (!router) return res.status(404).json({ success: false, message: 'Router no encontrado' });

      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({ success: false, message: 'Acceso denegado' });
      }

      const logs = await mikrotikService.getSystemLogs({
        host: router.host, port: router.port,
        username: router.username, password: router.password
      });

      res.json({ success: true, data: logs });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * Obtener tráfico en tiempo real
   * GET /api/routers/:id/traffic
   */
  async getTraffic(req, res) {
    try {
      const router = await db.getById('routers', req.params.id);
      if (!router) return res.status(404).json({ success: false, message: 'Router no encontrado' });

      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({ success: false, message: 'Acceso denegado' });
      }

      const traffic = await mikrotikService.getTrafficStats({
        host: router.host, port: router.port,
        username: router.username, password: router.password
      });

      res.json({ success: true, data: traffic });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * Obtener usuarios activos en el router
   * GET /api/routers/:id/active-users
   */
  async getActiveUsers(req, res) {
    try {
      const router = await db.getById('routers', req.params.id);
      if (!router) return res.status(404).json({ success: false, message: 'Router no encontrado' });

      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({ success: false, message: 'Acceso denegado' });
      }

      const users = await mikrotikService.getActiveUsers({
        host: router.host, port: router.port,
        username: router.username, password: router.password
      });

      res.json({ success: true, data: users });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * Ejecutar comando personalizado en el router
   * POST /api/routers/:id/command
   */
  async executeCommand(req, res) {
    try {
      const { command, args } = req.body;
      
      if (!command) {
        return res.status(400).json({
          success: false,
          message: 'El comando es requerido'
        });
      }

      const router = await db.getById('routers', req.params.id);
      if (!router) return res.status(404).json({ success: false, message: 'Router no encontrado' });

      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({ success: false, message: 'Acceso denegado' });
      }

      const result = await mikrotikService.executeCustomCommand({
        host: router.host, port: router.port,
        username: router.username, password: router.password
      }, command, args || {});

      // Registrar actividad
      await db.create('activityLog', {
        id: uuidv4(),
        userId: req.user.id,
        username: req.user.username,
        action: 'execute_command',
        details: `Comando ejecutado en ${router.name}: ${command}`,
        ip: req.ip
      });

      res.json({ success: true, data: result });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * Obtener enlace WinBox
   * GET /api/routers/:id/winbox
   */
  getWinBoxLink(req, res) {
    const routerId = req.params.id;
    
    // Buscar router en la base de datos
    db.getById('routers', routerId).then(router => {
      if (!router) {
        return res.status(404).json({ success: false, message: 'Router no encontrado' });
      }

      // Generar enlace WinBox
      const winboxUrl = `winbox://${router.host}:${router.port || 8728}`;
      
      res.json({
        success: true,
        data: {
          url: winboxUrl,
          host: router.host,
          port: router.port || 8728,
          username: router.username
        }
      });
    }).catch(error => {
      res.status(500).json({ success: false, message: error.message });
    });
  },

  /**
   * Obtener enlace WebFig
   * GET /api/routers/:id/webfig
   */
  getWebFigLink(req, res) {
    const routerId = req.params.id;
    
    db.getById('routers', routerId).then(router => {
      if (!router) {
        return res.status(404).json({ success: false, message: 'Router no encontrado' });
      }

      const webfigUrl = `http://${router.host}/webfig/`;
      
      res.json({
        success: true,
        data: {
          url: webfigUrl,
          host: router.host
        }
      });
    }).catch(error => {
      res.status(500).json({ success: false, message: error.message });
    });
  }
};

module.exports = routersController;
