const { RouterOSAPI } = require('node-routeros');
const config = require('../config/config');

/**
 * Servicio para interactuar con routers MikroTik vía API
 */
class MikroTikService {
  constructor() {
    this.connections = new Map(); // Cache de conexiones activas
    this.CLEANUP_INTERVAL = 5 * 60 * 1000; // Limpiar conexiones stale cada 5 min
    this.MAX_IDLE_TIME = 10 * 60 * 1000; // 10 min sin uso = stale

    // Iniciar limpieza periódica de conexiones stale
    this._cleanupTimer = setInterval(() => this._cleanupStaleConnections(), this.CLEANUP_INTERVAL);
    this._cleanupTimer.unref(); // No evitar que Node.js termine
  }

  /**
   * Limpia conexiones que han excedido el tiempo máximo de inactividad
   */
  _cleanupStaleConnections() {
    const now = Date.now();
    for (const [key, conn] of this.connections.entries()) {
      const idle = now - (conn._lastUsed || now);
      if (idle > this.MAX_IDLE_TIME) {
        console.log(`[MikroTik] Limpiando conexión stale: ${key} (inactiva ${Math.round(idle/1000)}s)`);
        this.disconnect(key.split(':')[0], parseInt(key.split(':')[1])).catch(() => {});
      }
    }
  }

  /**
   * Verifica si una conexión sigue activa
   */
  async _isConnected(conn) {
    try {
      // Intentar un comando simple para verificar conectividad
      await conn.write('/system/identity/print');
      return true;
    } catch {
      return false;
    }
  }

  /**
   * Conecta a un router MikroTik
   * @param {Object} routerConfig - { host, port, username, password }
   * @returns {Object} conexión API
   */
  async connect(routerConfig) {
    const { host, port, username, password } = routerConfig;
    const connKey = `${host}:${port}`;

    // Verificar si ya hay una conexión activa
    if (this.connections.has(connKey)) {
      const existing = this.connections.get(connKey);
      const isAlive = await this._isConnected(existing);
      if (isAlive) {
        existing._lastUsed = Date.now();
        return existing;
      }
      // Conexión muerta, eliminarla
      console.log(`[MikroTik] Conexión stale detectada para ${host}:${port}, reconectando...`);
      this.connections.delete(connKey);
    }

    const conn = new RouterOSAPI({
      host,
      port: port || config.mikrotik.defaultApiPort,
      user: username,
      password,
      timeout: config.mikrotik.connectionTimeout
    });

    try {
      await conn.connect();
      await conn.login();
      
      conn._lastUsed = Date.now();
      this.connections.set(connKey, conn);
      
      console.log(`[MikroTik] Conectado a ${host}:${port}`);
      return conn;
    } catch (error) {
      console.error(`[MikroTik] Error conectando a ${host}:${port}:`, error.message);
      throw new Error(`No se pudo conectar al router ${host}: ${error.message}`);
    }
  }

  /**
   * Desconecta de un router
   */
  async disconnect(host, port) {
    const connKey = `${host}:${port}`;
    const conn = this.connections.get(connKey);
    
    if (conn) {
      try {
        await conn.close();
      } catch (e) {
        // Ignorar errores al cerrar
      }
      this.connections.delete(connKey);
      console.log(`[MikroTik] Desconectado de ${host}:${port}`);
    }
  }

  /**
   * Ejecuta un comando en el router
   */
  async executeCommand(routerConfig, command, args = {}) {
    const conn = await this.connect(routerConfig);
    
    try {
      const result = await conn.write(command, args);
      conn._lastUsed = Date.now(); // Actualizar timestamp de uso
      return result;
    } catch (error) {
      console.error(`[MikroTik] Error ejecutando comando ${command}:`, error.message);
      throw new Error(`Error ejecutando comando: ${error.message}`);
    }
  }

  /**
   * Obtiene información del sistema
   */
  async getSystemInfo(routerConfig) {
    try {
      const [identity, resources, uptime] = await Promise.all([
        this.executeCommand(routerConfig, '/system/identity/print'),
        this.executeCommand(routerConfig, '/system/resource/print'),
        this.executeCommand(routerConfig, '/system/uptime/print')
      ]);

      return {
        identity: identity[0]?.name || 'Unknown',
        board: resources[0]?.board || 'Unknown',
        version: resources[0]?.version || 'Unknown',
        cpu: resources[0]?.cpu || 'Unknown',
        cpuLoad: resources[0]?.['cpu-load'] || '0',
        totalMemory: resources[0]?.['total-memory'] || '0',
        freeMemory: resources[0]?.['free-memory'] || '0',
        totalHdd: resources[0]?.['total-hdd-space'] || '0',
        freeHdd: resources[0]?.['free-hdd-space'] || '0',
        uptime: uptime[0]?.time || 'Unknown'
      };
    } catch (error) {
      throw new Error(`Error obteniendo información del sistema: ${error.message}`);
    }
  }

  /**
   * Obtiene interfaces del router
   */
  async getInterfaces(routerConfig) {
    try {
      const interfaces = await this.executeCommand(routerConfig, '/interface/print');
      return interfaces.map(iface => ({
        name: iface.name,
        type: iface.type,
        macAddress: iface['mac-address'],
        running: iface.running === 'true',
        disabled: iface.disabled === 'true',
        comment: iface.comment,
        lastLinkDown: iface['last-link-down-time'],
        lastLinkUp: iface['last-link-up-time']
      }));
    } catch (error) {
      throw new Error(`Error obteniendo interfaces: ${error.message}`);
    }
  }

  /**
   * Obtiene direcciones IP
   */
  async getIPAddresses(routerConfig) {
    try {
      const addresses = await this.executeCommand(routerConfig, '/ip/address/print');
      return addresses.map(addr => ({
        address: addr.address,
        network: addr.network,
        interface: addr.interface,
        disabled: addr.disabled === 'true',
        dynamic: addr.dynamic === 'true'
      }));
    } catch (error) {
      throw new Error(`Error obteniendo direcciones IP: ${error.message}`);
    }
  }

  /**
   * Obtiene clientes DHCP activos
   */
  async getDHCPLeases(routerConfig) {
    try {
      const leases = await this.executeCommand(routerConfig, '/ip/dhcp-server/lease/print');
      return leases.map(lease => ({
        address: lease['address'],
        macAddress: lease['mac-address'],
        hostName: lease['host-name'],
        server: lease.server,
        status: lease.status,
        expiresAfter: lease['expires-after'],
        comment: lease.comment
      }));
    } catch (error) {
      throw new Error(`Error obteniendo leases DHCP: ${error.message}`);
    }
  }

  /**
   * Obtiene conexiones activas
   */
  async getActiveConnections(routerConfig) {
    try {
      const connections = await this.executeCommand(routerConfig, '/ip/firewall/connection/print');
      return connections.map(conn => ({
        srcAddress: conn['src-address'],
        dstAddress: conn['dst-address'],
        protocol: conn.protocol,
        srcPort: conn['src-port'],
        dstPort: conn['dst-port'],
        bytes: conn.bytes,
        timeout: conn.timeout
      }));
    } catch (error) {
      throw new Error(`Error obteniendo conexiones activas: ${error.message}`);
    }
  }

  /**
   * Obtiene listas de direcciones (firewall)
   */
  async getAddressLists(routerConfig) {
    try {
      const lists = await this.executeCommand(routerConfig, '/ip/firewall/address-list/print');
      return lists.map(list => ({
        list: list.list,
        address: list.address,
        disabled: list.disabled === 'true',
        dynamic: list.dynamic === 'true',
        comment: list.comment
      }));
    } catch (error) {
      throw new Error(`Error obteniendo address lists: ${error.message}`);
    }
  }

  /**
   * Obtiene reglas de firewall
   */
  async getFirewallRules(routerConfig) {
    try {
      const rules = await this.executeCommand(routerConfig, '/ip/firewall/filter/print');
      return rules.map(rule => ({
        chain: rule.chain,
        action: rule.action,
        protocol: rule.protocol,
        srcAddress: rule['src-address'],
        dstAddress: rule['dst-address'],
        srcPort: rule['src-port'],
        dstPort: rule['dst-port'],
        disabled: rule.disabled === 'true',
        comment: rule.comment
      }));
    } catch (error) {
      throw new Error(`Error obteniendo reglas de firewall: ${error.message}`);
    }
  }

  /**
   * Obtiene usuarios activos en el router
   */
  async getActiveUsers(routerConfig) {
    try {
      const users = await this.executeCommand(routerConfig, '/user/active/print');
      return users.map(user => ({
        name: user.name,
        address: user.address,
        via: user.via,
        group: user.group,
        when: user.when,
        duration: user.duration
      }));
    } catch (error) {
      throw new Error(`Error obteniendo usuarios activos: ${error.message}`);
    }
  }

  /**
   * Genera un ticket Hotspot
   */
  async createHotspotTicket(routerConfig, ticketData) {
    try {
      const { server, profile, limitUptime, limitBytes, comment } = ticketData;
      
      // Crear usuario hotspot
      const user = await this.executeCommand(routerConfig, '/ip/hotspot/user/add', {
        server,
        profile,
        'limit-uptime': limitUptime,
        'limit-bytes': limitBytes,
        comment: comment || `Ticket generado por MkController`
      });

      return user;
    } catch (error) {
      throw new Error(`Error creando ticket hotspot: ${error.message}`);
    }
  }

  /**
   * Obtiene perfiles Hotspot
   */
  async getHotspotProfiles(routerConfig) {
    try {
      const profiles = await this.executeCommand(routerConfig, '/ip/hotspot/user/profile/print');
      return profiles.map(profile => ({
        name: profile.name,
        sharedUsers: profile['shared-users'],
        rateLimit: profile['rate-limit'],
        sessionTimeout: profile['session-timeout'],
        idleTimeout: profile['idle-timeout'],
        keepaliveTimeout: profile['keepalive-timeout']
      }));
    } catch (error) {
      throw new Error(`Error obteniendo perfiles hotspot: ${error.message}`);
    }
  }

  /**
   * Obtiene servidores Hotspot
   */
  async getHotspotServers(routerConfig) {
    try {
      const servers = await this.executeCommand(routerConfig, '/ip/hotspot/print');
      return servers.map(server => ({
        name: server.name,
        interface: server.interface,
        addressPool: server['address-pool'],
        profile: server.profile,
        disabled: server.disabled === 'true'
      }));
    } catch (error) {
      throw new Error(`Error obteniendo servidores hotspot: ${error.message}`);
    }
  }

  /**
   * Obtiene usuarios Hotspot activos
   */
  async getHotspotActiveUsers(routerConfig) {
    try {
      const users = await this.executeCommand(routerConfig, '/ip/hotspot/active/print');
      return users.map(user => ({
        user: user.user,
        address: user.address,
        macAddress: user['mac-address'],
        loginBy: user['login-by'],
        uptime: user.uptime,
        bytesIn: user.bytes,
        bytesOut: user['bytes-out'],
        packetsIn: user.packets,
        packetsOut: user['packets-out'],
        server: user.server
      }));
    } catch (error) {
      throw new Error(`Error obteniendo usuarios hotspot activos: ${error.message}`);
    }
  }

  /**
   * Obtiene logs del sistema
   */
  async getSystemLogs(routerConfig, topics = []) {
    try {
      let command = '/log/print';
      let args = {};
      
      if (topics.length > 0) {
        args['?topics'] = topics.join(',');
      }
      
      const logs = await this.executeCommand(routerConfig, command, args);
      return logs.slice(0, 100).map(log => ({
        time: log.time,
        topics: log.topics,
        message: log.message
      }));
    } catch (error) {
      throw new Error(`Error obteniendo logs: ${error.message}`);
    }
  }

  /**
   * Obtiene estadísticas de tráfico por interfaz
   */
  async getTrafficStats(routerConfig) {
    try {
      const interfaces = await this.executeCommand(routerConfig, '/interface/monitor-traffic', {
        interface: 'all',
        once: true
      });
      
      return interfaces.map(iface => ({
        name: iface.name,
        rxBitsPerSecond: iface['rx-bits-per-second'],
        txBitsPerSecond: iface['tx-bits-per-second'],
        rxPacketsPerSecond: iface['rx-packets-per-second'],
        txPacketsPerSecond: iface['tx-packets-per-second']
      }));
    } catch (error) {
      throw new Error(`Error obteniendo estadísticas de tráfico: ${error.message}`);
    }
  }

  /**
   * Prueba de conectividad con el router
   */
  async testConnection(routerConfig) {
    try {
      const conn = await this.connect(routerConfig);
      const identity = await this.executeCommand(routerConfig, '/system/identity/print');
      
      return {
        connected: true,
        identity: identity[0]?.name || 'Unknown',
        host: routerConfig.host
      };
    } catch (error) {
      return {
        connected: false,
        error: error.message,
        host: routerConfig.host
      };
    }
  }

  /**
   * Ejecuta un comando personalizado en el router
   */
  async executeCustomCommand(routerConfig, commandPath, args = {}) {
    try {
      const result = await this.executeCommand(routerConfig, commandPath, args);
      return result;
    } catch (error) {
      throw new Error(`Error ejecutando comando personalizado: ${error.message}`);
    }
  }
}

module.exports = new MikroTikService();
