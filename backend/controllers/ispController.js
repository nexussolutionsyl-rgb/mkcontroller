/**
 * ISP Manager Controller v1.0
 * Controlador centralizado para la gestión ISP
 * Comunicación con RouterOS vía REST API (MikrotikEngine)
 * Almacenamiento en MySQL (tablas isp_*)
 */
const { v4: uuidv4 } = require('uuid');
const mysql = require('mysql2/promise');
const config = require('../config/config');
const MikrotikEngine = require('../services/mikrotikEngine');

// ============================================================
// Pool de conexiones MySQL para ISP Manager
// ============================================================
let ispPool = null;

function getIspPool() {
  if (!ispPool) {
    ispPool = mysql.createPool({
      host: process.env.ISP_DB_HOST || config.mysql?.host || 'localhost',
      user: process.env.ISP_DB_USER || config.mysql?.user || 'nexusyl_root',
      password: process.env.ISP_DB_PASSWORD || config.mysql?.password || '',
      database: process.env.ISP_DB_NAME || config.mysql?.database || 'nexusyl_nexusmk',
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0
    });
  }
  return ispPool;
}

// ============================================================
// Instancia del motor MikroTik (REST API v7)
// ============================================================
const engine = new MikrotikEngine(config.isp?.mikrotik);

// ============================================================
// Controlador
// ============================================================
const ispController = {

  // ==========================================================
  // HEALTH CHECK
  // ==========================================================
  /**
   * GET /api/isp/health
   * Verifica el estado del módulo ISP Manager
   */
  async health(req, res) {
    try {
      const pool = getIspPool();
      // Verificar conexión MySQL
      const conn = await pool.getConnection();
      await conn.ping();
      conn.release();

      // Verificar conexión RouterOS (REST API)
      const testResult = await engine.testConnection();

      res.json({
        success: true,
        message: 'ISP Manager funcionando',
        data: {
          mysql: { connected: true },
          routeros: testResult,
          timestamp: new Date().toISOString()
        }
      });
    } catch (error) {
      res.status(500).json({
        success: false,
        message: 'Error en health check ISP Manager',
        error: error.message
      });
    }
  },

  // ==========================================================
  // PPP PROFILES (Perfiles)
  // ==========================================================

  /**
   * GET /api/isp/ppp/profiles
   * Obtener todos los perfiles PPP desde RouterOS
   */
  async getPPPProfiles(req, res) {
    try {
      const profiles = await engine.getPPPProfiles();
      res.json({ success: true, data: profiles });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * POST /api/isp/ppp/profiles
   * Crear un perfil PPP en RouterOS y guardarlo en BD
   */
  async createPPPProfile(req, res) {
    try {
      const { name, localAddress, remoteAddress, rateLimit, onlyOne, comment } = req.body;
      if (!name) {
        return res.status(400).json({ success: false, message: 'El nombre del perfil es requerido' });
      }

      // Crear en RouterOS
      const result = await engine.createPPPProfile({
        name, localAddress, remoteAddress, rateLimit, onlyOne, comment
      });

      // Guardar en BD
      const pool = getIspPool();
      const id = uuidv4();
      await pool.execute(
        `INSERT INTO isp_plans (id, name, service, speed_limit, routeros_name, sync_status, comment)
         VALUES (?, ?, 'pppoe', ?, ?, 'synced', ?)`,
        [id, name, rateLimit || null, name, comment || null]
      );

      res.status(201).json({ success: true, data: { id, name, routeros: result } });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * PUT /api/isp/ppp/profiles/:name
   * Actualizar un perfil PPP
   */
  async updatePPPProfile(req, res) {
    try {
      const { name } = req.params;
      const { localAddress, remoteAddress, rateLimit, onlyOne, comment } = req.body;

      const result = await engine.updatePPPProfile(name, {
        localAddress, remoteAddress, rateLimit, onlyOne, comment
      });

      // Actualizar en BD
      const pool = getIspPool();
      await pool.execute(
        `UPDATE isp_plans SET speed_limit = ?, comment = ?, sync_status = 'synced' WHERE routeros_name = ? AND service = 'pppoe'`,
        [rateLimit || null, comment || null, name]
      );

      res.json({ success: true, data: result });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * DELETE /api/isp/ppp/profiles/:name
   * Eliminar un perfil PPP
   */
  async deletePPPProfile(req, res) {
    try {
      const { name } = req.params;
      await engine.deletePPPProfile(name);

      // Eliminar de BD
      const pool = getIspPool();
      await pool.execute(`DELETE FROM isp_plans WHERE routeros_name = ? AND service = 'pppoe'`, [name]);

      res.json({ success: true, message: `Perfil ${name} eliminado` });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  // ==========================================================
  // HOTSPOT PROFILES
  // ==========================================================

  /**
   * GET /api/isp/hotspot/profiles
   * Obtener perfiles Hotspot desde RouterOS
   */
  async getHotspotProfiles(req, res) {
    try {
      const profiles = await engine.getHotspotProfiles();
      res.json({ success: true, data: profiles });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * POST /api/isp/hotspot/profiles
   * Crear un perfil Hotspot
   */
  async createHotspotProfile(req, res) {
    try {
      const { name, sharedUsers, rateLimit, sessionTimeout, idleTimeout, keepaliveTimeout, comment } = req.body;
      if (!name) {
        return res.status(400).json({ success: false, message: 'El nombre del perfil es requerido' });
      }

      const result = await engine.createHotspotProfile({
        name, sharedUsers, rateLimit, sessionTimeout, idleTimeout, keepaliveTimeout, comment
      });

      // Guardar en BD
      const pool = getIspPool();
      const id = uuidv4();
      await pool.execute(
        `INSERT INTO isp_plans (id, name, service, speed_limit, shared_users, routeros_name, sync_status, comment)
         VALUES (?, ?, 'hotspot', ?, ?, ?, 'synced', ?)`,
        [id, name, rateLimit || null, sharedUsers || 1, name, comment || null]
      );

      res.status(201).json({ success: true, data: { id, name, routeros: result } });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * DELETE /api/isp/hotspot/profiles/:name
   * Eliminar un perfil Hotspot
   */
  async deleteHotspotProfile(req, res) {
    try {
      const { name } = req.params;
      await engine.deleteHotspotProfile(name);

      const pool = getIspPool();
      await pool.execute(`DELETE FROM isp_plans WHERE routeros_name = ? AND service = 'hotspot'`, [name]);

      res.json({ success: true, message: `Perfil ${name} eliminado` });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * PUT /api/isp/hotspot/profiles/:name
   * Actualizar un perfil Hotspot
   */
  async updateHotspotProfile(req, res) {
    try {
      const { name } = req.params;
      const { sharedUsers, rateLimit, sessionTimeout, idleTimeout, keepaliveTimeout, comment } = req.body;

      const result = await engine.updateHotspotProfile(name, {
        sharedUsers, rateLimit, sessionTimeout, idleTimeout, keepaliveTimeout, comment
      });

      // Actualizar en BD
      const pool = getIspPool();
      await pool.execute(
        `UPDATE isp_plans SET speed_limit = ?, shared_users = ?, comment = ?, sync_status = 'synced' WHERE routeros_name = ? AND service = 'hotspot'`,
        [rateLimit || null, sharedUsers || 1, comment || null, name]
      );

      res.json({ success: true, data: result });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  // ==========================================================
  // IP POOLS
  // ==========================================================

  /**
   * GET /api/isp/pools
   * Obtener IP Pools desde RouterOS
   */
  async getIPPools(req, res) {
    try {
      const pools = await engine.getIPPools();
      res.json({ success: true, data: pools });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * POST /api/isp/pools
   * Crear un IP Pool
   */
  async createIPPool(req, res) {
    try {
      const { name, ranges, comment } = req.body;
      if (!name || !ranges) {
        return res.status(400).json({ success: false, message: 'Nombre y rangos son requeridos' });
      }

      const result = await engine.createIPPool({ name, ranges, comment });

      // Guardar en BD
      const pool = getIspPool();
      const id = uuidv4();
      await pool.execute(
        `INSERT INTO isp_ip_pools (id, name, ranges, sync_status, comment)
         VALUES (?, ?, ?, 'synced', ?)`,
        [id, name, ranges, comment || null]
      );

      res.status(201).json({ success: true, data: { id, name, routeros: result } });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * DELETE /api/isp/pools/:name
   * Eliminar un IP Pool
   */
  async deleteIPPool(req, res) {
    try {
      const { name } = req.params;
      await engine.deleteIPPool(name);

      const pool = getIspPool();
      await pool.execute(`DELETE FROM isp_ip_pools WHERE name = ?`, [name]);

      res.json({ success: true, message: `Pool ${name} eliminado` });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * PUT /api/isp/pools/:name
   * Actualizar un IP Pool
   */
  async updateIPPool(req, res) {
    try {
      const { name } = req.params;
      const { ranges, comment } = req.body;
      if (!ranges) {
        return res.status(400).json({ success: false, message: 'Los rangos son requeridos' });
      }

      const result = await engine.updateIPPool(name, { ranges, comment });

      // Actualizar en BD
      const pool = getIspPool();
      await pool.execute(
        `UPDATE isp_ip_pools SET ranges = ?, comment = ?, sync_status = 'synced' WHERE name = ?`,
        [ranges, comment || null, name]
      );

      res.json({ success: true, data: result });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  // ==========================================================
  // PPP SECRETS (Clientes PPPoE/VPN)
  // ==========================================================

  /**
   * GET /api/isp/clients
   * Obtener clientes PPP desde RouterOS o desde BD local
   * Query params: ?source=routeros (default) | ?source=db | ?search=text
   */
  async getPPPSecrets(req, res) {
    try {
      const source = req.query.source || 'routeros';
      const search = req.query.search || null;

      if (source === 'db') {
        return await this.getClientsFromDB(req, res);
      }

      const secrets = await engine.getPPPSecrets();

      // Si hay búsqueda, filtrar del lado servidor
      if (search) {
        const term = search.toLowerCase();
        const filtered = secrets.filter(s =>
          (s.name && s.name.toLowerCase().includes(term)) ||
          (s.comment && s.comment.toLowerCase().includes(term)) ||
          (s.profile && s.profile.toLowerCase().includes(term)) ||
          (s['remote-address'] && s['remote-address'].includes(term))
        );
        return res.json({ success: true, data: filtered, source: 'routeros', filtered: true });
      }

      res.json({ success: true, data: secrets, source: 'routeros' });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * GET /api/isp/clients/db
   * Obtener clientes desde la BD local con filtros
   */
  async getClientsFromDB(req, res) {
    try {
      const pool = getIspPool();
      const { service, disabled, search, plan_id, limit, offset } = req.query;

      let query = 'SELECT * FROM isp_clients WHERE 1=1';
      const params = [];

      if (service) {
        query += ' AND service = ?';
        params.push(service);
      }
      if (disabled !== undefined) {
        query += ' AND disabled = ?';
        params.push(disabled === '1' ? 1 : 0);
      }
      if (plan_id) {
        query += ' AND plan_id = ?';
        params.push(plan_id);
      }
      if (search) {
        query += ' AND (username LIKE ? OR comment LIKE ? OR profile LIKE ? OR ip_address LIKE ?)';
        const term = `%${search}%`;
        params.push(term, term, term, term);
      }

      // Total count
      const [countResult] = await pool.execute(
        query.replace('SELECT *', 'SELECT COUNT(*) as total'),
        params
      );

      query += ' ORDER BY created_at DESC';
      const lim = parseInt(limit) || 100;
      const off = parseInt(offset) || 0;
      query += ' LIMIT ? OFFSET ?';
      params.push(lim, off);

      const [rows] = await pool.execute(query, params);

      res.json({
        success: true,
        data: rows,
        source: 'db',
        pagination: {
          total: countResult[0].total,
          limit: lim,
          offset: off
        }
      });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * POST /api/isp/clients
   * Crear un cliente PPP (PPP secret + BD)
   */
  async createPPPSecret(req, res) {
    try {
      const { username, password, service, profile, ipAddress, comment } = req.body;
      if (!username || !password) {
        return res.status(400).json({ success: false, message: 'Usuario y contraseña son requeridos' });
      }

      // Crear en RouterOS
      const result = await engine.createPPPSecret({
        name: username,
        password,
        service: service || 'pppoe',
        profile: profile || 'default',
        remoteAddress: ipAddress,
        comment
      });

      // Guardar en BD
      const pool = getIspPool();
      const id = uuidv4();
      await pool.execute(
        `INSERT INTO isp_clients (id, username, password, service, profile, ip_address, sync_status, comment)
         VALUES (?, ?, ?, ?, ?, ?, 'synced', ?)`,
        [id, username, password, service || 'pppoe', profile || 'default', ipAddress || null, comment || null]
      );

      res.status(201).json({ success: true, data: { id, username, routeros: result } });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * PUT /api/isp/clients/:username
   * Actualizar un cliente PPP
   */
  async updatePPPSecret(req, res) {
    try {
      const { username } = req.params;
      const { password, service, profile, ipAddress, comment, disabled } = req.body;

      const result = await engine.updatePPPSecret(username, {
        password, service, profile, remoteAddress: ipAddress, comment, disabled
      });

      // Actualizar en BD
      const pool = getIspPool();
      const updates = [];
      const params = [];
      if (password) { updates.push('password = ?'); params.push(password); }
      if (profile) { updates.push('profile = ?'); params.push(profile); }
      if (ipAddress) { updates.push('ip_address = ?'); params.push(ipAddress); }
      if (comment !== undefined) { updates.push('comment = ?'); params.push(comment); }
      if (disabled !== undefined) { updates.push('disabled = ?'); params.push(disabled ? 1 : 0); }
      updates.push("sync_status = 'synced'");

      if (updates.length > 1) {
        params.push(username);
        await pool.execute(
          `UPDATE isp_clients SET ${updates.join(', ')} WHERE username = ?`,
          params
        );
      }

      res.json({ success: true, data: result });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * DELETE /api/isp/clients/:username
   * Eliminar un cliente PPP
   */
  async deletePPPSecret(req, res) {
    try {
      const { username } = req.params;
      await engine.deletePPPSecret(username);

      const pool = getIspPool();
      await pool.execute(`DELETE FROM isp_clients WHERE username = ?`, [username]);

      res.json({ success: true, message: `Cliente ${username} eliminado` });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * POST /api/isp/clients/:username/enable
   * Habilitar un cliente PPP
   */
  async enablePPPSecret(req, res) {
    try {
      const { username } = req.params;
      await engine.enablePPPSecret(username);

      const pool = getIspPool();
      await pool.execute(`UPDATE isp_clients SET disabled = 0 WHERE username = ?`, [username]);

      res.json({ success: true, message: `Cliente ${username} habilitado` });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * POST /api/isp/clients/:username/disable
   * Deshabilitar un cliente PPP
   */
  async disablePPPSecret(req, res) {
    try {
      const { username } = req.params;
      await engine.disablePPPSecret(username);

      const pool = getIspPool();
      await pool.execute(`UPDATE isp_clients SET disabled = 1 WHERE username = ?`, [username]);

      res.json({ success: true, message: `Cliente ${username} deshabilitado` });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * GET /api/isp/clients/active
   * Obtener sesiones PPP activas
   */
  async getPPPActive(req, res) {
    try {
      const active = await engine.getPPPActive();
      res.json({ success: true, data: active });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  // ==========================================================
  // HOTSPOT USERS
  // ==========================================================

  /**
   * GET /api/isp/hotspot/users
   * Obtener usuarios Hotspot desde RouterOS o BD
   * Query params: ?source=routeros (default) | ?source=db | ?search=text
   */
  async getHotspotUsers(req, res) {
    try {
      const source = req.query.source || 'routeros';
      const search = req.query.search || null;

      if (source === 'db') {
        return await this.getClientsFromDB(req, res);
      }

      const users = await engine.getHotspotUsers();

      if (search) {
        const term = search.toLowerCase();
        const filtered = users.filter(u =>
          (u.name && u.name.toLowerCase().includes(term)) ||
          (u.comment && u.comment.toLowerCase().includes(term)) ||
          (u.profile && u.profile.toLowerCase().includes(term)) ||
          (u.server && u.server.toLowerCase().includes(term))
        );
        return res.json({ success: true, data: filtered, source: 'routeros', filtered: true });
      }

      res.json({ success: true, data: users, source: 'routeros' });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * POST /api/isp/hotspot/users
   * Crear un usuario Hotspot
   */
  async createHotspotUser(req, res) {
    try {
      const { name, password, profile, server, limitUptime, limitBytes, comment } = req.body;
      if (!name) {
        return res.status(400).json({ success: false, message: 'Nombre de usuario requerido' });
      }

      const result = await engine.createHotspotUser({
        name, password: password || name, profile, server, limitUptime, limitBytes, comment
      });

      // Guardar en BD
      const pool = getIspPool();
      const id = uuidv4();
      await pool.execute(
        `INSERT INTO isp_clients (id, username, password, service, profile, sync_status, comment)
         VALUES (?, ?, ?, 'hotspot', ?, 'synced', ?)`,
        [id, name, password || name, profile || null, comment || null]
      );

      res.status(201).json({ success: true, data: { id, username: name, routeros: result } });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * DELETE /api/isp/hotspot/users/:name
   * Eliminar un usuario Hotspot
   */
  async deleteHotspotUser(req, res) {
    try {
      const { name } = req.params;
      await engine.deleteHotspotUser(name);

      const pool = getIspPool();
      await pool.execute(`DELETE FROM isp_clients WHERE username = ? AND service = 'hotspot'`, [name]);

      res.json({ success: true, message: `Usuario ${name} eliminado` });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * PUT /api/isp/hotspot/users/:name
   * Actualizar un usuario Hotspot
   */
  async updateHotspotUser(req, res) {
    try {
      const { name } = req.params;
      const { password, profile, server, limitUptime, limitBytes, comment } = req.body;

      const result = await engine.updateHotspotUser(name, {
        password, profile, server, limitUptime, limitBytes, comment
      });

      // Actualizar en BD
      const pool = getIspPool();
      await pool.execute(
        `UPDATE isp_clients SET profile = ?, comment = ?, sync_status = 'synced' WHERE username = ? AND service = 'hotspot'`,
        [profile || null, comment || null, name]
      );

      res.json({ success: true, data: result });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * GET /api/isp/hotspot/active
   * Obtener usuarios Hotspot activos
   */
  async getHotspotActive(req, res) {
    try {
      const active = await engine.getHotspotActive();
      res.json({ success: true, data: active });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * GET /api/isp/hotspot/servers
   * Obtener servidores Hotspot
   */
  async getHotspotServers(req, res) {
    try {
      const servers = await engine.getHotspotServers();
      res.json({ success: true, data: servers });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  // ==========================================================
  // SISTEMA E INTERFACES
  // ==========================================================

  /**
   * GET /api/isp/system/info
   * Obtener información del sistema del router
   */
  async getSystemInfo(req, res) {
    try {
      const [resource, identity, interfaces] = await Promise.all([
        engine.getSystemResource(),
        engine.getIdentity(),
        engine.getInterfaces()
      ]);

      res.json({
        success: true,
        data: {
          identity,
          version: resource?.version || 'N/A',
          board: resource?.board || 'N/A',
          cpu: resource?.cpu || 'N/A',
          cpuLoad: resource?.['cpu-load'] || '0',
          totalMemory: resource?.['total-memory'] || '0',
          freeMemory: resource?.['free-memory'] || '0',
          totalHdd: resource?.['total-hdd-space'] || '0',
          freeHdd: resource?.['free-hdd-space'] || '0',
          uptime: resource?.uptime || 'N/A',
          interfaces: interfaces.length
        }
      });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * GET /api/isp/system/interfaces
   * Obtener interfaces del router
   */
  async getInterfaces(req, res) {
    try {
      const interfaces = await engine.getInterfaces();
      res.json({ success: true, data: interfaces });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * GET /api/isp/system/dhcp-leases
   * Obtener leases DHCP
   */
  async getDHCPLeases(req, res) {
    try {
      const leases = await engine.getDHCPLeases();
      res.json({ success: true, data: leases });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * GET /api/isp/system/firewall
   * Obtener reglas de firewall
   */
  async getFirewallRules(req, res) {
    try {
      const rules = await engine.getFirewallRules();
      res.json({ success: true, data: rules });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  // ==========================================================
  // ESTADÍSTICAS
  // ==========================================================

  /**
   * GET /api/isp/stats
   * Obtener estadísticas consolidadas
   */
  async getStats(req, res) {
    try {
      const [routerStats, pool] = await Promise.all([
        engine.getStats(),
        getIspPool()
      ]);

      // Obtener conteos de BD
      const [clientCount] = await pool.execute(
        `SELECT 
          COUNT(*) as total,
          SUM(CASE WHEN disabled = 0 THEN 1 ELSE 0 END) as active,
          SUM(CASE WHEN service = 'pppoe' THEN 1 ELSE 0 END) as pppoe,
          SUM(CASE WHEN service = 'hotspot' THEN 1 ELSE 0 END) as hotspot,
          SUM(CASE WHEN service = 'vpn' THEN 1 ELSE 0 END) as vpn
         FROM isp_clients`
      );

      const [planCount] = await pool.execute(
        `SELECT 
          COUNT(*) as total,
          SUM(CASE WHEN service = 'pppoe' THEN 1 ELSE 0 END) as pppoe,
          SUM(CASE WHEN service = 'hotspot' THEN 1 ELSE 0 END) as hotspot
         FROM isp_plans`
      );

      res.json({
        success: true,
        data: {
          router: routerStats,
          database: {
            clients: clientCount[0],
            plans: planCount[0]
          }
        }
      });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  // ==========================================================
  // WEBHOOK - NETWATCH
  // ==========================================================

  /**
   * POST /api/isp/webhook/netwatch
   * Endpoint para recibir alertas de Netwatch desde el MikroTik
   * RouterOS envía: { host, status, message, event_type }
   */
  async netwatchWebhook(req, res) {
    try {
      const { host, status, message, event_type } = req.body;
      
      if (!host || !status) {
        return res.status(400).json({ success: false, message: 'host y status son requeridos' });
      }

      // Guardar en BD
      const pool = getIspPool();
      const [result] = await pool.execute(
        `INSERT INTO isp_alert_logs (event_type, host, status, message, raw_data)
         VALUES (?, ?, ?, ?, ?)`,
        [
          event_type || 'netwatch',
          host,
          status,
          message || `Host ${host} ${status}`,
          JSON.stringify(req.body)
        ]
      );

      // Enviar a Telegram si está configurado
      let telegramResult = null;
      if (config.isp?.telegram?.botToken && config.isp?.telegram?.chatId) {
        telegramResult = await this._sendTelegramAlert({
          event_type: event_type || 'netwatch',
          host,
          status,
          message: message || `Host ${host} ${status}`
        });

        if (telegramResult.sent) {
          await pool.execute(
            `UPDATE isp_alert_logs SET telegram_sent = 1 WHERE id = ?`,
            [result.insertId]
          );
        } else {
          await pool.execute(
            `UPDATE isp_alert_logs SET telegram_error = ? WHERE id = ?`,
            [telegramResult.error, result.insertId]
          );
        }
      }

      res.status(201).json({
        success: true,
        message: 'Alerta registrada',
        data: { id: result.insertId, telegram: telegramResult }
      });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * GET /api/isp/alerts
   * Obtener historial de alertas
   */
  async getAlerts(req, res) {
    try {
      const pool = getIspPool();
      const limit = parseInt(req.query.limit) || 50;
      const offset = parseInt(req.query.offset) || 0;
      const eventType = req.query.type || null;

      let query = 'SELECT * FROM isp_alert_logs';
      const params = [];

      if (eventType) {
        query += ' WHERE event_type = ?';
        params.push(eventType);
      }

      query += ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
      params.push(limit, offset);

      const [rows] = await pool.execute(query, params);

      // Total
      let countQuery = 'SELECT COUNT(*) as total FROM isp_alert_logs';
      const countParams = [];
      if (eventType) {
        countQuery += ' WHERE event_type = ?';
        countParams.push(eventType);
      }
      const [countResult] = await pool.execute(countQuery, countParams);

      res.json({
        success: true,
        data: rows,
        pagination: {
          total: countResult[0].total,
          limit,
          offset
        }
      });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  // ==========================================================
  // TELEGRAM (interno)
  // ==========================================================

  /**
   * Envía una alerta a Telegram
   * @private
   */
  async _sendTelegramAlert(alert) {
    try {
      const { botToken, chatId } = config.isp.telegram;
      if (!botToken || !chatId) {
        return { sent: false, error: 'Telegram no configurado' };
      }

      const https = require('https');
      const message = `⚠️ *Alerta ISP Manager*\n\n` +
        `*Tipo:* ${alert.event_type}\n` +
        `*Host:* ${alert.host}\n` +
        `*Estado:* ${alert.status}\n` +
        `*Mensaje:* ${alert.message}\n` +
        `*Hora:* ${new Date().toLocaleString('es-VE', { timeZone: 'America/Caracas' })}`;

      const url = `https://api.telegram.org/bot${botToken}/sendMessage`;
      const data = JSON.stringify({
        chat_id: chatId,
        text: message,
        parse_mode: 'Markdown'
      });

      return new Promise((resolve) => {
        const req = https.request(url, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Content-Length': data.length }
        }, (res) => {
          let body = '';
          res.on('data', chunk => body += chunk);
          res.on('end', () => {
            try {
              const parsed = JSON.parse(body);
              resolve(parsed.ok ? { sent: true } : { sent: false, error: parsed.description });
            } catch {
              resolve({ sent: false, error: 'Error parseando respuesta Telegram' });
            }
          });
        });

        req.on('error', (e) => resolve({ sent: false, error: e.message }));
        req.write(data);
        req.end();
      });
    } catch (error) {
      return { sent: false, error: error.message };
    }
  },

  // ==========================================================
  // SINCRONIZACIÓN - Importar perfiles desde RouterOS a BD
  // ==========================================================

  /**
   * POST /api/isp/ppp/profiles/sync
   * Importar todos los perfiles PPP desde RouterOS a la BD local
   */
  async syncPPPProfiles(req, res) {
    try {
      const profiles = await engine.getPPPProfiles();
      const pool = getIspPool();
      let imported = 0;
      let updated = 0;

      for (const profile of profiles) {
        const name = profile.name;
        if (!name || name === 'default' || name === 'default-encryption') continue;

        // Verificar si ya existe en BD
        const [existing] = await pool.execute(
          `SELECT id FROM isp_plans WHERE routeros_name = ? AND service = 'pppoe'`,
          [name]
        );

        const speedLimit = profile['rate-limit'] || null;
        const onlyOne = profile['only-one'] || null;
        const comment = profile.comment || null;

        if (existing.length > 0) {
          // Actualizar existente
          await pool.execute(
            `UPDATE isp_plans SET speed_limit = ?, comment = ?, sync_status = 'synced' WHERE routeros_name = ? AND service = 'pppoe'`,
            [speedLimit, comment, name]
          );
          updated++;
        } else {
          // Crear nuevo
          const id = uuidv4();
          await pool.execute(
            `INSERT INTO isp_plans (id, name, service, speed_limit, routeros_name, sync_status, comment)
             VALUES (?, ?, 'pppoe', ?, ?, 'synced', ?)`,
            [id, name, speedLimit, name, comment]
          );
          imported++;
        }
      }

      res.json({
        success: true,
        message: `Sincronización completada: ${imported} importados, ${updated} actualizados`,
        data: { imported, updated, total: profiles.length }
      });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * POST /api/isp/hotspot/profiles/sync
   * Importar perfiles Hotspot desde RouterOS a la BD
   */
  async syncHotspotProfiles(req, res) {
    try {
      const profiles = await engine.getHotspotProfiles();
      const pool = getIspPool();
      let imported = 0;
      let updated = 0;

      for (const profile of profiles) {
        const name = profile.name;
        if (!name || name === 'default') continue;

        const [existing] = await pool.execute(
          `SELECT id FROM isp_plans WHERE routeros_name = ? AND service = 'hotspot'`,
          [name]
        );

        const speedLimit = profile['rate-limit'] || null;
        const sharedUsers = parseInt(profile['shared-users']) || 1;
        const comment = profile.comment || null;

        if (existing.length > 0) {
          await pool.execute(
            `UPDATE isp_plans SET speed_limit = ?, shared_users = ?, comment = ?, sync_status = 'synced' WHERE routeros_name = ? AND service = 'hotspot'`,
            [speedLimit, sharedUsers, comment, name]
          );
          updated++;
        } else {
          const id = uuidv4();
          await pool.execute(
            `INSERT INTO isp_plans (id, name, service, speed_limit, shared_users, routeros_name, sync_status, comment)
             VALUES (?, ?, 'hotspot', ?, ?, ?, 'synced', ?)`,
            [id, name, speedLimit, sharedUsers, name, comment]
          );
          imported++;
        }
      }

      res.json({
        success: true,
        message: `Sincronización completada: ${imported} importados, ${updated} actualizados`,
        data: { imported, updated, total: profiles.length }
      });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * POST /api/isp/pools/sync
   * Importar IP Pools desde RouterOS a la BD
   */
  async syncIPPools(req, res) {
    try {
      const pools = await engine.getIPPools();
      const pool = getIspPool();
      let imported = 0;
      let updated = 0;

      for (const p of pools) {
        const name = p.name;
        if (!name) continue;

        const [existing] = await pool.execute(
          `SELECT id FROM isp_ip_pools WHERE name = ?`,
          [name]
        );

        const ranges = p.ranges || null;
        const comment = p.comment || null;

        if (existing.length > 0) {
          await pool.execute(
            `UPDATE isp_ip_pools SET ranges = ?, comment = ?, sync_status = 'synced' WHERE name = ?`,
            [ranges, comment, name]
          );
          updated++;
        } else {
          const id = uuidv4();
          await pool.execute(
            `INSERT INTO isp_ip_pools (id, name, ranges, sync_status, comment)
             VALUES (?, ?, ?, 'synced', ?)`,
            [id, name, ranges, comment]
          );
          imported++;
        }
      }

      res.json({
        success: true,
        message: `Sincronización completada: ${imported} importados, ${updated} actualizados`,
        data: { imported, updated, total: pools.length }
      });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  // ==========================================================
  // PLANES - CRUD desde BD local
  // ==========================================================

  /**
   * GET /api/isp/plans
   * Obtener planes desde la BD local
   */
  async getPlans(req, res) {
    try {
      const pool = getIspPool();
      const service = req.query.service || null;
      let query = 'SELECT * FROM isp_plans';
      const params = [];

      if (service) {
        query += ' WHERE service = ?';
        params.push(service);
      }

      query += ' ORDER BY service, name';
      const [rows] = await pool.execute(query, params);

      res.json({ success: true, data: rows });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * POST /api/isp/plans
   * Crear un plan en BD (sin RouterOS)
   */
  async createPlan(req, res) {
    try {
      const { name, service, speedLimit, sessionTimeout, sharedUsers, price, comment } = req.body;
      if (!name || !service) {
        return res.status(400).json({ success: false, message: 'Nombre y servicio son requeridos' });
      }

      const pool = getIspPool();
      const id = uuidv4();
      await pool.execute(
        `INSERT INTO isp_plans (id, name, service, speed_limit, session_timeout, shared_users, price, comment, sync_status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')`,
        [id, name, service, speedLimit || null, sessionTimeout || null, sharedUsers || 1, price || null, comment || null]
      );

      res.status(201).json({ success: true, data: { id, name, service } });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * PUT /api/isp/plans/:id
   * Actualizar un plan en BD
   */
  async updatePlan(req, res) {
    try {
      const { id } = req.params;
      const { name, service, speedLimit, sessionTimeout, sharedUsers, price, comment } = req.body;

      const pool = getIspPool();
      const updates = [];
      const params = [];

      if (name) { updates.push('name = ?'); params.push(name); }
      if (service) { updates.push('service = ?'); params.push(service); }
      if (speedLimit !== undefined) { updates.push('speed_limit = ?'); params.push(speedLimit); }
      if (sessionTimeout !== undefined) { updates.push('session_timeout = ?'); params.push(sessionTimeout); }
      if (sharedUsers) { updates.push('shared_users = ?'); params.push(sharedUsers); }
      if (price !== undefined) { updates.push('price = ?'); params.push(price); }
      if (comment !== undefined) { updates.push('comment = ?'); params.push(comment); }
      updates.push("sync_status = 'pending'");

      params.push(id);
      await pool.execute(`UPDATE isp_plans SET ${updates.join(', ')} WHERE id = ?`, params);

      res.json({ success: true, message: 'Plan actualizado' });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * DELETE /api/isp/plans/:id
   * Eliminar un plan de BD
   */
  async deletePlan(req, res) {
    try {
      const { id } = req.params;
      const pool = getIspPool();
      await pool.execute(`DELETE FROM isp_plans WHERE id = ?`, [id]);
      res.json({ success: true, message: 'Plan eliminado' });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  // ==========================================================
  // SINCRONIZACIÓN DE CLIENTES
  // ==========================================================

  /**
   * POST /api/isp/clients/sync
   * Importar todos los PPP secrets desde RouterOS a la BD local
   */
  async syncPPPSecrets(req, res) {
    try {
      const secrets = await engine.getPPPSecrets();
      const pool = getIspPool();
      let imported = 0;
      let updated = 0;

      for (const secret of secrets) {
        const username = secret.name;
        if (!username) continue;

        const [existing] = await pool.execute(
          `SELECT id FROM isp_clients WHERE username = ? AND service = 'pppoe'`,
          [username]
        );

        const password = secret.password || '';
        const profile = secret.profile || 'default';
        const service = secret.service || 'pppoe';
        const ipAddress = secret['remote-address'] || null;
        const disabled = secret.disabled === 'true' ? 1 : 0;
        const comment = secret.comment || null;

        if (existing.length > 0) {
          await pool.execute(
            `UPDATE isp_clients SET password = ?, profile = ?, ip_address = ?, disabled = ?, comment = ?, sync_status = 'synced', last_sync_at = NOW() WHERE username = ? AND service = 'pppoe'`,
            [password, profile, ipAddress, disabled, comment, username]
          );
          updated++;
        } else {
          const id = uuidv4();
          await pool.execute(
            `INSERT INTO isp_clients (id, username, password, service, profile, ip_address, disabled, sync_status, comment, last_sync_at)
             VALUES (?, ?, ?, 'pppoe', ?, ?, ?, 'synced', ?, NOW())`,
            [id, username, password, profile, ipAddress, disabled, comment]
          );
          imported++;
        }
      }

      res.json({
        success: true,
        message: `Sincronización PPP completada: ${imported} importados, ${updated} actualizados`,
        data: { imported, updated, total: secrets.length }
      });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * POST /api/isp/hotspot/users/sync
   * Importar usuarios Hotspot desde RouterOS a la BD local
   */
  async syncHotspotUsers(req, res) {
    try {
      const users = await engine.getHotspotUsers();
      const pool = getIspPool();
      let imported = 0;
      let updated = 0;

      for (const user of users) {
        const username = user.name;
        if (!username) continue;

        const [existing] = await pool.execute(
          `SELECT id FROM isp_clients WHERE username = ? AND service = 'hotspot'`,
          [username]
        );

        const password = user.password || username;
        const profile = user.profile || null;
        const disabled = user.disabled === 'true' ? 1 : 0;
        const comment = user.comment || null;

        if (existing.length > 0) {
          await pool.execute(
            `UPDATE isp_clients SET password = ?, profile = ?, disabled = ?, comment = ?, sync_status = 'synced', last_sync_at = NOW() WHERE username = ? AND service = 'hotspot'`,
            [password, profile, disabled, comment, username]
          );
          updated++;
        } else {
          const id = uuidv4();
          await pool.execute(
            `INSERT INTO isp_clients (id, username, password, service, profile, disabled, sync_status, comment, last_sync_at)
             VALUES (?, ?, ?, 'hotspot', ?, ?, 'synced', ?, NOW())`,
            [id, username, password, profile, disabled, comment]
          );
          imported++;
        }
      }

      res.json({
        success: true,
        message: `Sincronización Hotspot completada: ${imported} importados, ${updated} actualizados`,
        data: { imported, updated, total: users.length }
      });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * POST /api/isp/clients/:username/sync-one
   * Sincronizar un cliente específico desde RouterOS a BD
   */
  async syncOneClient(req, res) {
    try {
      const { username } = req.params;
      const { service } = req.body || 'pppoe';
      const pool = getIspPool();

      let clientData;
      if (service === 'hotspot') {
        const users = await engine.getHotspotUsers();
        clientData = users.find(u => u.name === username);
        if (!clientData) {
          return res.status(404).json({ success: false, message: `Usuario Hotspot ${username} no encontrado en RouterOS` });
        }
        await pool.execute(
          `UPDATE isp_clients SET password = ?, profile = ?, disabled = ?, comment = ?, sync_status = 'synced', last_sync_at = NOW() WHERE username = ? AND service = 'hotspot'`,
          [clientData.password || username, clientData.profile || null, clientData.disabled === 'true' ? 1 : 0, clientData.comment || null, username]
        );
      } else {
        const secrets = await engine.getPPPSecrets();
        clientData = secrets.find(s => s.name === username);
        if (!clientData) {
          return res.status(404).json({ success: false, message: `Cliente PPP ${username} no encontrado en RouterOS` });
        }
        await pool.execute(
          `UPDATE isp_clients SET password = ?, profile = ?, ip_address = ?, disabled = ?, comment = ?, sync_status = 'synced', last_sync_at = NOW() WHERE username = ? AND service = 'pppoe'`,
          [clientData.password || '', clientData.profile || 'default', clientData['remote-address'] || null, clientData.disabled === 'true' ? 1 : 0, clientData.comment || null, username]
        );
      }

      res.json({ success: true, message: `Cliente ${username} sincronizado` });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  }
};

module.exports = ispController;
