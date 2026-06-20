/**
 * MikrotikEngine v1.0
 * Motor centralizado de comunicación con RouterOS REST API v7
 * 
 * Este motor es la ÚNICA fuente de comunicación con la REST API del MikroTik.
 * NO se deben hacer peticiones HTTP dispersas por el código.
 * 
 * RouterOS REST API: https://help.mikrotik.com/docs/display/ROS/REST+API
 * Endpoint base: https://{host}:{port}/rest/{path}
 * Autenticación: Basic Auth
 */

const https = require('https');
const http = require('http');
const config = require('../config/config');

class MikrotikEngine {
  /**
   * @param {Object} routerConfig - Configuración del router
   * @param {string} routerConfig.host - IP o hostname del router
   * @param {number} [routerConfig.port=443] - Puerto REST API (443 por defecto)
   * @param {string} routerConfig.username - Usuario RouterOS
   * @param {string} routerConfig.password - Contraseña RouterOS
   * @param {boolean} [routerConfig.ssl=true] - Usar HTTPS
   * @param {number} [routerConfig.timeout=10000] - Timeout en ms
   */
  constructor(routerConfig = {}) {
    this.host = routerConfig.host || config.isp?.mikrotik?.host || process.env.ISP_MIKROTIK_HOST || '127.0.0.1';
    this.port = routerConfig.port || config.isp?.mikrotik?.port || process.env.ISP_MIKROTIK_PORT || 443;
    this.username = routerConfig.username || config.isp?.mikrotik?.username || process.env.ISP_MIKROTIK_USER || 'admin';
    this.password = routerConfig.password || config.isp?.mikrotik?.password || process.env.ISP_MIKROTIK_PASSWORD || '';
    this.ssl = routerConfig.ssl !== undefined ? routerConfig.ssl : true;
    this.timeout = routerConfig.timeout || config.isp?.mikrotik?.timeout || 10000;

    // Cache de conexión (token Basic Auth pre-calculado)
    this._authHeader = 'Basic ' + Buffer.from(`${this.username}:${this.password}`).toString('base64');
    this._baseUrl = `${this.ssl ? 'https' : 'http'}://${this.host}:${this.port}/rest`;
  }

  /**
   * Actualiza la configuración del router en caliente
   * @param {Object} routerConfig 
   */
  configure(routerConfig) {
    if (routerConfig.host) this.host = routerConfig.host;
    if (routerConfig.port) this.port = routerConfig.port;
    if (routerConfig.username) this.username = routerConfig.username;
    if (routerConfig.password) this.password = routerConfig.password;
    if (routerConfig.ssl !== undefined) this.ssl = routerConfig.ssl;
    if (routerConfig.timeout) this.timeout = routerConfig.timeout;

    this._authHeader = 'Basic ' + Buffer.from(`${this.username}:${this.password}`).toString('base64');
    this._baseUrl = `${this.ssl ? 'https' : 'http'}://${this.host}:${this.port}/rest`;
  }

  /**
   * Realiza una petición HTTP a la REST API del MikroTik
   * @param {string} method - Método HTTP (GET, PUT, PATCH, DELETE)
   * @param {string} path - Ruta REST (ej: '/ppp/secret', '/ip/hotspot/user')
   * @param {Object} [body=null] - Cuerpo de la petición (para PUT/PATCH)
   * @returns {Promise<Array|Object>} Respuesta del router
   */
  _request(method, path, body = null) {
    return new Promise((resolve, reject) => {
      const url = new URL(`${this._baseUrl}${path}`);
      
      const options = {
        hostname: url.hostname,
        port: url.port,
        path: url.pathname + url.search,
        method: method,
        headers: {
          'Authorization': this._authHeader,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        timeout: this.timeout,
        rejectUnauthorized: false // Permitir certificados auto-firmados en MikroTik
      };

      if (body && (method === 'PUT' || method === 'PATCH')) {
        options.headers['Content-Type'] = 'application/json';
      }

      const transport = this.ssl ? https : http;
      const req = transport.request(options, (res) => {
        let data = '';

        res.on('data', (chunk) => {
          data += chunk;
        });

        res.on('end', () => {
          try {
            // RouterOS REST API puede devolver array o objeto
            const parsed = data ? JSON.parse(data) : null;
            
            if (res.statusCode >= 200 && res.statusCode < 300) {
              resolve(parsed);
            } else {
              const errorMsg = parsed?.detail || parsed?.error || parsed?.message || `HTTP ${res.statusCode}`;
              reject(new Error(`[MikrotikEngine] ${method} ${path}: ${errorMsg}`));
            }
          } catch (e) {
            reject(new Error(`[MikrotikEngine] Error parseando respuesta: ${e.message}. Raw: ${data.substring(0, 200)}`));
          }
        });
      });

      req.on('error', (e) => {
        reject(new Error(`[MikrotikEngine] Error de conexión a ${this.host}:${this.port} - ${e.message}`));
      });

      req.on('timeout', () => {
        req.destroy();
        reject(new Error(`[MikrotikEngine] Timeout después de ${this.timeout}ms conectando a ${this.host}:${this.port}`));
      });

      if (body && (method === 'PUT' || method === 'PATCH')) {
        req.write(JSON.stringify(body));
      }

      req.end();
    });
  }

  // ============================================================
  // MÉTODOS CORE
  // ============================================================

  /**
   * GET - Obtener recursos
   * @param {string} path - Ruta REST (ej: '/ppp/secret')
   * @returns {Promise<Array>} Lista de recursos
   */
  async get(path) {
    return this._request('GET', path);
  }

  /**
   * PUT - Crear o actualizar recurso (RouterOS usa PUT para crear)
   * @param {string} path - Ruta REST (ej: '/ppp/secret')
   * @param {Object} data - Datos del recurso
   * @returns {Promise<Object>} Recurso creado/actualizado
   */
  async put(path, data) {
    return this._request('PUT', path, data);
  }

  /**
   * PATCH - Actualizar parcialmente un recurso
   * @param {string} path - Ruta REST con ID (ej: '/ppp/secret/123')
   * @param {Object} data - Datos a actualizar
   * @returns {Promise<Object>} Recurso actualizado
   */
  async patch(path, data) {
    return this._request('PATCH', path, data);
  }

  /**
   * DELETE - Eliminar un recurso
   * @param {string} path - Ruta REST con ID (ej: '/ppp/secret/123')
   * @returns {Promise<Object>} Confirmación
   */
  async delete(path) {
    return this._request('DELETE', path);
  }

  /**
   * POST - Comando especial (enable, disable, reset, etc.)
   * @param {string} path - Ruta REST (ej: '/ppp/secret/123/disable')
   * @returns {Promise<Object>}
   */
  async post(path) {
    return this._request('POST', path);
  }

  // ============================================================
  // MÉTODOS DE ALTO NIVEL - PPP
  // ============================================================

  /**
   * Obtener todos los PPP secrets (clientes PPPoE/PPTP/L2TP)
   * @returns {Promise<Array>}
   */
  async getPPPSecrets() {
    return this.get('/ppp/secret');
  }

  /**
   * Obtener un PPP secret por nombre
   * @param {string} name - Nombre del usuario PPP
   * @returns {Promise<Object|null>}
   */
  async getPPPSecret(name) {
    const secrets = await this.get(`/ppp/secret?name=${encodeURIComponent(name)}`);
    return Array.isArray(secrets) && secrets.length > 0 ? secrets[0] : null;
  }

  /**
   * Crear un PPP secret
   * @param {Object} data - { name, password, service, profile, remoteAddress, comment }
   * @returns {Promise<Object>}
   */
  async createPPPSecret(data) {
    return this.put('/ppp/secret', {
      name: data.name,
      password: data.password,
      service: data.service || 'pppoe',
      profile: data.profile || 'default',
      ...(data.remoteAddress && { 'remote-address': data.remoteAddress }),
      ...(data.comment && { comment: data.comment }),
      disabled: data.disabled !== undefined ? (data.disabled ? 'yes' : 'no') : 'no'
    });
  }

  /**
   * Actualizar un PPP secret
   * @param {string} name - Nombre del usuario
   * @param {Object} data - Datos a actualizar
   * @returns {Promise<Object>}
   */
  async updatePPPSecret(name, data) {
    const patchData = {};
    if (data.password) patchData.password = data.password;
    if (data.service) patchData.service = data.service;
    if (data.profile) patchData.profile = data.profile;
    if (data.remoteAddress) patchData['remote-address'] = data.remoteAddress;
    if (data.comment !== undefined) patchData.comment = data.comment;
    if (data.disabled !== undefined) patchData.disabled = data.disabled ? 'yes' : 'no';
    
    return this.patch(`/ppp/secret/${encodeURIComponent(name)}`, patchData);
  }

  /**
   * Eliminar un PPP secret
   * @param {string} name - Nombre del usuario
   * @returns {Promise<Object>}
   */
  async deletePPPSecret(name) {
    return this.delete(`/ppp/secret/${encodeURIComponent(name)}`);
  }

  /**
   * Habilitar un PPP secret
   * @param {string} name
   * @returns {Promise<Object>}
   */
  async enablePPPSecret(name) {
    return this.post(`/ppp/secret/${encodeURIComponent(name)}/enable`);
  }

  /**
   * Deshabilitar un PPP secret
   * @param {string} name
   * @returns {Promise<Object>}
   */
  async disablePPPSecret(name) {
    return this.post(`/ppp/secret/${encodeURIComponent(name)}/disable`);
  }

  /**
   * Obtener sesiones PPP activas
   * @returns {Promise<Array>}
   */
  async getPPPActive() {
    return this.get('/ppp/active');
  }

  // ============================================================
  // MÉTODOS DE ALTO NIVEL - PPP Profiles
  // ============================================================

  /**
   * Obtener todos los perfiles PPP
   * @returns {Promise<Array>}
   */
  async getPPPProfiles() {
    return this.get('/ppp/profile');
  }

  /**
   * Crear un perfil PPP
   * @param {Object} data - { name, localAddress, remoteAddress, rateLimit, onlyOne, comment }
   * @returns {Promise<Object>}
   */
  async createPPPProfile(data) {
    return this.put('/ppp/profile', {
      name: data.name,
      ...(data.localAddress && { 'local-address': data.localAddress }),
      ...(data.remoteAddress && { 'remote-address': data.remoteAddress }),
      ...(data.rateLimit && { 'rate-limit': data.rateLimit }),
      ...(data.onlyOne !== undefined && { 'only-one': data.onlyOne ? 'yes' : 'no' }),
      ...(data.comment && { comment: data.comment })
    });
  }

  /**
   * Actualizar un perfil PPP
   * @param {string} name
   * @param {Object} data
   * @returns {Promise<Object>}
   */
  async updatePPPProfile(name, data) {
    const patchData = {};
    if (data.localAddress) patchData['local-address'] = data.localAddress;
    if (data.remoteAddress) patchData['remote-address'] = data.remoteAddress;
    if (data.rateLimit) patchData['rate-limit'] = data.rateLimit;
    if (data.onlyOne !== undefined) patchData['only-one'] = data.onlyOne ? 'yes' : 'no';
    if (data.comment !== undefined) patchData.comment = data.comment;
    
    return this.patch(`/ppp/profile/${encodeURIComponent(name)}`, patchData);
  }

  /**
   * Eliminar un perfil PPP
   * @param {string} name
   * @returns {Promise<Object>}
   */
  async deletePPPProfile(name) {
    return this.delete(`/ppp/profile/${encodeURIComponent(name)}`);
  }

  // ============================================================
  // MÉTODOS DE ALTO NIVEL - Hotspot
  // ============================================================

  /**
   * Obtener usuarios Hotspot
   * @returns {Promise<Array>}
   */
  async getHotspotUsers() {
    return this.get('/ip/hotspot/user');
  }

  /**
   * Obtener un usuario Hotspot por nombre
   * @param {string} name
   * @returns {Promise<Object|null>}
   */
  async getHotspotUser(name) {
    const users = await this.get(`/ip/hotspot/user?name=${encodeURIComponent(name)}`);
    return Array.isArray(users) && users.length > 0 ? users[0] : null;
  }

  /**
   * Crear un usuario Hotspot
   * @param {Object} data - { name, password, profile, server, limitUptime, limitBytes, comment }
   * @returns {Promise<Object>}
   */
  async createHotspotUser(data) {
    return this.put('/ip/hotspot/user', {
      name: data.name,
      password: data.password || data.name,
      ...(data.profile && { profile: data.profile }),
      ...(data.server && { server: data.server }),
      ...(data.limitUptime && { 'limit-uptime': data.limitUptime }),
      ...(data.limitBytes && { 'limit-bytes': data.limitBytes }),
      ...(data.comment && { comment: data.comment }),
      disabled: data.disabled !== undefined ? (data.disabled ? 'yes' : 'no') : 'no'
    });
  }

  /**
   * Actualizar un usuario Hotspot
   * @param {string} name
   * @param {Object} data
   * @returns {Promise<Object>}
   */
  async updateHotspotUser(name, data) {
    const patchData = {};
    if (data.password) patchData.password = data.password;
    if (data.profile) patchData.profile = data.profile;
    if (data.server) patchData.server = data.server;
    if (data.limitUptime) patchData['limit-uptime'] = data.limitUptime;
    if (data.limitBytes) patchData['limit-bytes'] = data.limitBytes;
    if (data.comment !== undefined) patchData.comment = data.comment;
    if (data.disabled !== undefined) patchData.disabled = data.disabled ? 'yes' : 'no';
    
    return this.patch(`/ip/hotspot/user/${encodeURIComponent(name)}`, patchData);
  }

  /**
   * Eliminar un usuario Hotspot
   * @param {string} name
   * @returns {Promise<Object>}
   */
  async deleteHotspotUser(name) {
    return this.delete(`/ip/hotspot/user/${encodeURIComponent(name)}`);
  }

  /**
   * Habilitar un usuario Hotspot
   * @param {string} name
   * @returns {Promise<Object>}
   */
  async enableHotspotUser(name) {
    return this.post(`/ip/hotspot/user/${encodeURIComponent(name)}/enable`);
  }

  /**
   * Deshabilitar un usuario Hotspot
   * @param {string} name
   * @returns {Promise<Object>}
   */
  async disableHotspotUser(name) {
    return this.post(`/ip/hotspot/user/${encodeURIComponent(name)}/disable`);
  }

  /**
   * Obtener perfiles Hotspot
   * @returns {Promise<Array>}
   */
  async getHotspotProfiles() {
    return this.get('/ip/hotspot/user/profile');
  }

  /**
   * Crear un perfil Hotspot
   * @param {Object} data - { name, sharedUsers, rateLimit, sessionTimeout, idleTimeout, keepaliveTimeout, comment }
   * @returns {Promise<Object>}
   */
  async createHotspotProfile(data) {
    return this.put('/ip/hotspot/user/profile', {
      name: data.name,
      ...(data.sharedUsers && { 'shared-users': String(data.sharedUsers) }),
      ...(data.rateLimit && { 'rate-limit': data.rateLimit }),
      ...(data.sessionTimeout && { 'session-timeout': data.sessionTimeout }),
      ...(data.idleTimeout && { 'idle-timeout': data.idleTimeout }),
      ...(data.keepaliveTimeout && { 'keepalive-timeout': data.keepaliveTimeout }),
      ...(data.comment && { comment: data.comment })
    });
  }

  /**
   * Actualizar un perfil Hotspot
   * @param {string} name
   * @param {Object} data
   * @returns {Promise<Object>}
   */
  async updateHotspotProfile(name, data) {
    const patchData = {};
    if (data.sharedUsers) patchData['shared-users'] = String(data.sharedUsers);
    if (data.rateLimit) patchData['rate-limit'] = data.rateLimit;
    if (data.sessionTimeout) patchData['session-timeout'] = data.sessionTimeout;
    if (data.idleTimeout) patchData['idle-timeout'] = data.idleTimeout;
    if (data.keepaliveTimeout) patchData['keepalive-timeout'] = data.keepaliveTimeout;
    if (data.comment !== undefined) patchData.comment = data.comment;
    
    return this.patch(`/ip/hotspot/user/profile/${encodeURIComponent(name)}`, patchData);
  }

  /**
   * Eliminar un perfil Hotspot
   * @param {string} name
   * @returns {Promise<Object>}
   */
  async deleteHotspotProfile(name) {
    return this.delete(`/ip/hotspot/user/profile/${encodeURIComponent(name)}`);
  }

  /**
   * Obtener usuarios Hotspot activos
   * @returns {Promise<Array>}
   */
  async getHotspotActive() {
    return this.get('/ip/hotspot/active');
  }

  /**
   * Obtener servidores Hotspot
   * @returns {Promise<Array>}
   */
  async getHotspotServers() {
    return this.get('/ip/hotspot');
  }

  // ============================================================
  // MÉTODOS DE ALTO NIVEL - IP Pools
  // ============================================================

  /**
   * Obtener IP Pools
   * @returns {Promise<Array>}
   */
  async getIPPools() {
    return this.get('/ip/pool');
  }

  /**
   * Crear un IP Pool
   * @param {Object} data - { name, ranges, comment }
   * @returns {Promise<Object>}
   */
  async createIPPool(data) {
    return this.put('/ip/pool', {
      name: data.name,
      ranges: data.ranges,
      ...(data.comment && { comment: data.comment })
    });
  }

  /**
   * Actualizar un IP Pool
   * @param {string} name
   * @param {Object} data
   * @returns {Promise<Object>}
   */
  async updateIPPool(name, data) {
    const patchData = {};
    if (data.ranges) patchData.ranges = data.ranges;
    if (data.comment !== undefined) patchData.comment = data.comment;
    
    return this.patch(`/ip/pool/${encodeURIComponent(name)}`, patchData);
  }

  /**
   * Eliminar un IP Pool
   * @param {string} name
   * @returns {Promise<Object>}
   */
  async deleteIPPool(name) {
    return this.delete(`/ip/pool/${encodeURIComponent(name)}`);
  }

  // ============================================================
  // MÉTODOS DE ALTO NIVEL - Sistema e Interfaces
  // ============================================================

  /**
   * Obtener información del sistema
   * @returns {Promise<Object>}
   */
  async getSystemResource() {
    const res = await this.get('/system/resource');
    return Array.isArray(res) ? res[0] : res;
  }

  /**
   * Obtener identidad del router
   * @returns {Promise<string>}
   */
  async getIdentity() {
    const res = await this.get('/system/identity');
    return Array.isArray(res) ? res[0]?.name || 'Unknown' : res?.name || 'Unknown';
  }

  /**
   * Obtener interfaces
   * @returns {Promise<Array>}
   */
  async getInterfaces() {
    return this.get('/interface');
  }

  /**
   * Obtener una interfaz por nombre
   * @param {string} name
   * @returns {Promise<Object|null>}
   */
  async getInterface(name) {
    const ifaces = await this.get(`/interface?name=${encodeURIComponent(name)}`);
    return Array.isArray(ifaces) && ifaces.length > 0 ? ifaces[0] : null;
  }

  /**
   * Obtener direcciones IP
   * @returns {Promise<Array>}
   */
  async getIPAddresses() {
    return this.get('/ip/address');
  }

  /**
   * Obtener leases DHCP
   * @returns {Promise<Array>}
   */
  async getDHCPLeases() {
    return this.get('/ip/dhcp-server/lease');
  }

  /**
   * Obtener reglas de firewall
   * @returns {Promise<Array>}
   */
  async getFirewallRules() {
    return this.get('/ip/firewall/filter');
  }

  /**
   * Obtener NAT rules
   * @returns {Promise<Array>}
   */
  async getNATRules() {
    return this.get('/ip/firewall/nat');
  }

  /**
   * Obtener conexiones activas
   * @returns {Promise<Array>}
   */
  async getConnections() {
    return this.get('/ip/firewall/connection');
  }

  /**
   * Obtener listas de direcciones
   * @returns {Promise<Array>}
   */
  async getAddressLists() {
    return this.get('/ip/firewall/address-list');
  }

  /**
   * Obtener logs del sistema
   * @param {string} [topics] - Filtrar por tópico (ej: 'pppoe,info')
   * @returns {Promise<Array>}
   */
  async getLogs(topics = '') {
    const path = topics ? `/log?topics=${encodeURIComponent(topics)}` : '/log';
    return this.get(path);
  }

  // ============================================================
  // MÉTODOS DE ALTO NIVEL - VPN
  // ============================================================

  /**
   * Obtener interfaces WireGuard
   * @returns {Promise<Array>}
   */
  async getWireGuardInterfaces() {
    return this.get('/interface/wireguard');
  }

  /**
   * Obtener peers WireGuard
   * @returns {Promise<Array>}
   */
  async getWireGuardPeers() {
    return this.get('/interface/wireguard/peers');
  }

  /**
   * Obtener usuarios L2TP
   * @returns {Promise<Array>}
   */
  async getL2TPSecrets() {
    return this.get('/ppp/l2tp/secret');
  }

  /**
   * Obtener usuarios SSTP
   * @returns {Promise<Array>}
   */
  async getSSTPSecrets() {
    return this.get('/ppp/sstp/secret');
  }

  /**
   * Obtener usuarios OVPN
   * @returns {Promise<Array>}
   */
  async getOVPNSecrets() {
    return this.get('/ppp/ovpn/secret');
  }

  // ============================================================
  // UTILIDADES
  // ============================================================

  /**
   * Probar conectividad con el router vía REST API
   * @returns {Promise<Object>} { connected, identity, host }
   */
  async testConnection() {
    try {
      const identity = await this.getIdentity();
      const resource = await this.getSystemResource();
      return {
        connected: true,
        identity,
        version: resource?.version || 'Unknown',
        host: this.host,
        port: this.port
      };
    } catch (error) {
      return {
        connected: false,
        error: error.message,
        host: this.host,
        port: this.port
      };
    }
  }

  /**
   * Prueba de conexión estática (sin modificar la instancia actual)
   * @param {Object} opts - { host, port, username, password, ssl, timeout }
   * @returns {Promise<Object>} Resultado de la prueba
   */
  static async testConnectionStatic(opts = {}) {
    const engine = new MikrotikEngine(opts);
    return engine.testConnection();
  }

  /**
   * Escanea un host específico para detectar si es un MikroTik
   * Prueba REST API (443), API/Socket (8728), y HTTP (80)
   * @param {string} host - IP o dominio a escanear
   * @param {Object} [credentials] - { username, password } opcional
   * @param {number} [timeout=5000] - Timeout por intento
   * @returns {Promise<Object>} Resultado del escaneo
   */
  static async scanHost(host, credentials = {}, timeout = 5000) {
    const username = credentials.username || 'admin';
    const password = credentials.password || '';
    const results = { host, services: [], detected: false };

    // Puertos a probar: REST API SSL (443), REST API HTTP (80), API/Socket (8728)
    const portsToTry = [
      { port: 443, ssl: true, service: 'rest-api-ssl' },
      { port: 80, ssl: false, service: 'rest-api-http' },
      { port: 8728, ssl: false, service: 'api-socket' }
    ];

    for (const { port, ssl, service } of portsToTry) {
      try {
        const engine = new MikrotikEngine({
          host, port, ssl,
          username, password,
          timeout
        });
        const result = await engine.testConnection();
        results.services.push({
          service,
          port,
          ssl,
          connected: result.connected,
          identity: result.identity || null,
          version: result.version || null,
          error: result.error || null
        });
        if (result.connected) {
          results.detected = true;
          results.identity = result.identity;
          results.version = result.version;
        }
      } catch (err) {
        results.services.push({
          service,
          port,
          ssl,
          connected: false,
          error: err.message
        });
      }
    }

    return results;
  }

  /**
   * Escanea un rango de red para detectar routers MikroTik
   * @param {string} subnet - Subred en formato CIDR (ej: '192.168.88.0/24')
   * @param {Object} [credentials] - { username, password } opcional
   * @param {number} [timeout=3000] - Timeout por host
   * @param {number} [concurrency=10] - Hosts simultáneos
   * @returns {Promise<Array>} Lista de routers detectados
   */
  static async scanNetwork(subnet, credentials = {}, timeout = 3000, concurrency = 10) {
    const detected = [];
    const ips = MikrotikEngine._expandCIDR(subnet);

    // Procesar en lotes para controlar concurrencia
    for (let i = 0; i < ips.length; i += concurrency) {
      const batch = ips.slice(i, i + concurrency);
      const results = await Promise.allSettled(
        batch.map(ip => MikrotikEngine.scanHost(ip, credentials, timeout))
      );

      for (const result of results) {
        if (result.status === 'fulfilled' && result.value.detected) {
          detected.push(result.value);
        }
      }
    }

    return detected;
  }

  /**
   * Expande una notación CIDR a lista de IPs
   * @param {string} cidr - Ej: '192.168.88.0/24'
   * @returns {string[]} Lista de IPs
   */
  static _expandCIDR(cidr) {
    const [base, bits] = cidr.split('/');
    const mask = parseInt(bits) || 24;
    const octets = base.split('.').map(Number);
    
    if (octets.length !== 4 || octets.some(isNaN)) {
      return [base]; // Si no es CIDR válido, devolver la IP tal cual
    }

    const ipInt = (octets[0] << 24) | (octets[1] << 16) | (octets[2] << 8) | octets[3];
    const hostBits = 32 - mask;
    const totalHosts = Math.pow(2, hostBits);

    // Limitar a /24 (256 hosts) o menos para no saturar
    if (totalHosts > 256) {
      // Solo escanear los primeros 256 hosts
      const limited = Math.min(256, totalHosts);
      const ips = [];
      for (let i = 1; i < limited; i++) {
        const addr = ipInt + i;
        ips.push([
          (addr >>> 24) & 255,
          (addr >>> 16) & 255,
          (addr >>> 8) & 255,
          addr & 255
        ].join('.'));
      }
      return ips;
    }

    const ips = [];
    for (let i = 1; i < totalHosts - 1; i++) {
      const addr = ipInt + i;
      ips.push([
        (addr >>> 24) & 255,
        (addr >>> 16) & 255,
        (addr >>> 8) & 255,
        addr & 255
      ].join('.'));
    }
    return ips;
  }

  /**
   * Obtener estadísticas consolidadas del router
   * @returns {Promise<Object>}
   */
  async getStats() {
    const [resource, identity, interfaces, pppActive, hotspotActive, pools] = await Promise.all([
      this.getSystemResource().catch(() => null),
      this.getIdentity().catch(() => 'Unknown'),
      this.getInterfaces().catch(() => []),
      this.getPPPActive().catch(() => []),
      this.getHotspotActive().catch(() => []),
      this.getIPPools().catch(() => [])
    ]);

    return {
      identity,
      uptime: resource?.uptime || 'N/A',
      version: resource?.version || 'N/A',
      cpuLoad: resource?.['cpu-load'] || '0',
      totalMemory: resource?.['total-memory'] || '0',
      freeMemory: resource?.['free-memory'] || '0',
      totalHdd: resource?.['total-hdd-space'] || '0',
      freeHdd: resource?.['free-hdd-space'] || '0',
      interfaces: interfaces.length,
      interfacesRunning: interfaces.filter(i => i.running === 'true').length,
      pppActive: pppActive.length,
      hotspotActive: hotspotActive.length,
      pools: pools.length
    };
  }

  /**
   * Auto-detecta routers MikroTik en la red local
   * Escanea subredes comunes (/24) y prueba credenciales por defecto
   * Funciona como WinBox: descubre routers en la misma red
   * @param {Object} [options] - Opciones de escaneo
   * @param {string[]} [options.subnets] - Subredes a escanear (auto-detectadas si no se especifican)
   * @param {string} [options.username='admin'] - Usuario por defecto para probar
   * @param {string} [options.password=''] - Contraseña por defecto para probar
   * @param {number} [options.timeout=3000] - Timeout por host
   * @param {number} [options.concurrency=20] - Hosts simultáneos
   * @returns {Promise<Array>} Lista de routers detectados con { host, port, ssl, identity, version }
   */
  static async autoDetect(options = {}) {
    const {
      subnets,
      username = 'admin',
      password = '',
      timeout = 3000,
      concurrency = 20
    } = options;

    // Si no se especificaron subredes, detectar automáticamente
    let subnetsToScan = subnets;
    if (!subnetsToScan || subnetsToScan.length === 0) {
      subnetsToScan = await MikrotikEngine._detectLocalSubnets();
    }

    console.log(`[MikrotikEngine] Auto-detect: escaneando ${subnetsToScan.length} subred(es): ${subnetsToScan.join(', ')}`);

    const allDetected = [];
    const seen = new Set();

    for (const subnet of subnetsToScan) {
      try {
        const detected = await MikrotikEngine.scanNetwork(subnet, { username, password }, timeout, concurrency);
        for (const router of detected) {
          if (!seen.has(router.host)) {
            seen.add(router.host);
            allDetected.push(router);
          }
        }
      } catch (err) {
        console.error(`[MikrotikEngine] Error escaneando subred ${subnet}: ${err.message}`);
      }
    }

    console.log(`[MikrotikEngine] Auto-detect: ${allDetected.length} router(es) encontrado(s)`);
    return allDetected;
  }

  /**
   * Detecta las subredes locales del servidor
   * Obtiene las IPs de las interfaces de red y genera subredes /24
   * @returns {Promise<string[]>} Lista de subredes en formato CIDR
   */
  static async _detectLocalSubnets() {
    const subnets = [];
    const os = require('os');
    const interfaces = os.networkInterfaces();

    for (const [name, addrs] of Object.entries(interfaces)) {
      if (!addrs) continue;
      for (const addr of addrs) {
        // Solo IPv4, no loopback, no internas de docker
        if (addr.family === 'IPv4' && !addr.internal && !name.startsWith('docker') && !name.startsWith('veth')) {
          const parts = addr.address.split('.');
          if (parts.length === 4) {
            // Generar /24 a partir de la IP
            const subnet = `${parts[0]}.${parts[1]}.${parts[2]}.0/24`;
            if (!subnets.includes(subnet)) {
              subnets.push(subnet);
            }
            // También agregar /24 de la gateway (asumiendo .1)
            const gatewaySubnet = `${parts[0]}.${parts[1]}.${parts[2]}.0/24`;
            if (!subnets.includes(gatewaySubnet)) {
              subnets.push(gatewaySubnet);
            }
          }
        }
      }
    }

    // Si no se detectaron subredes, usar subredes comunes
    if (subnets.length === 0) {
      subnets.push('192.168.88.0/24', '192.168.1.0/24', '192.168.0.0/24', '10.0.0.0/24');
    }

    return subnets;
  }
}

module.exports = MikrotikEngine;
