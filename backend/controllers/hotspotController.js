const { v4: uuidv4 } = require('uuid');
const db = require('../models/database');
const mikrotikService = require('../services/mikrotikService');

/**
 * Controlador de Hotspot
 */
const hotspotController = {
  /**
   * Obtener servidores Hotspot del router
   * GET /api/routers/:routerId/hotspot/servers
   */
  async getServers(req, res) {
    try {
      const router = await db.getById('routers', req.params.routerId);
      if (!router) return res.status(404).json({ success: false, message: 'Router no encontrado' });

      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({ success: false, message: 'Acceso denegado' });
      }

      const servers = await mikrotikService.getHotspotServers({
        host: router.host, port: router.port,
        username: router.username, password: router.password
      });

      res.json({ success: true, data: servers });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * Obtener perfiles Hotspot
   * GET /api/routers/:routerId/hotspot/profiles
   */
  async getProfiles(req, res) {
    try {
      const router = await db.getById('routers', req.params.routerId);
      if (!router) return res.status(404).json({ success: false, message: 'Router no encontrado' });

      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({ success: false, message: 'Acceso denegado' });
      }

      const profiles = await mikrotikService.getHotspotProfiles({
        host: router.host, port: router.port,
        username: router.username, password: router.password
      });

      res.json({ success: true, data: profiles });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * Obtener usuarios activos en Hotspot
   * GET /api/routers/:routerId/hotspot/active
   */
  async getActiveUsers(req, res) {
    try {
      const router = await db.getById('routers', req.params.routerId);
      if (!router) return res.status(404).json({ success: false, message: 'Router no encontrado' });

      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({ success: false, message: 'Acceso denegado' });
      }

      const users = await mikrotikService.getHotspotActiveUsers({
        host: router.host, port: router.port,
        username: router.username, password: router.password
      });

      res.json({ success: true, data: users });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * Generar ticket Hotspot
   * POST /api/routers/:routerId/hotspot/tickets
   */
  async createTicket(req, res) {
    try {
      const { server, profile, limitUptime, limitBytes, comment, username, password } = req.body;

      if (!server || !profile) {
        return res.status(400).json({
          success: false,
          message: 'Servidor y perfil son requeridos'
        });
      }

      const router = await db.getById('routers', req.params.routerId);
      if (!router) return res.status(404).json({ success: false, message: 'Router no encontrado' });

      if (req.user.role !== 'superadmin' && router.clientId !== req.user.clientId) {
        return res.status(403).json({ success: false, message: 'Acceso denegado' });
      }

      // Crear usuario en el router
      const ticketUser = username || `ticket_${Date.now()}`;
      const ticketPass = password || Math.random().toString(36).slice(-8);

      await mikrotikService.createHotspotTicket({
        host: router.host, port: router.port,
        username: router.username, password: router.password
      }, {
        server,
        profile,
        limitUptime: limitUptime || '1d',
        limitBytes: limitBytes || '0',
        comment: comment || `Ticket generado por ${req.user.username}`
      });

      // Guardar ticket en la base de datos
      const ticket = await db.create('hotspotTickets', {
        id: uuidv4(),
        routerId: router.id,
        routerName: router.name,
        clientId: router.clientId,
        server,
        profile,
        username: ticketUser,
        password: ticketPass,
        limitUptime: limitUptime || '1d',
        limitBytes: limitBytes || '0',
        comment: comment || '',
        generatedBy: req.user.id,
        generatedByName: req.user.username,
        status: 'active'
      });

      res.status(201).json({
        success: true,
        message: 'Ticket Hotspot generado exitosamente',
        data: ticket
      });
    } catch (error) {
      console.error('[Hotspot] Error creando ticket:', error);
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * Obtener tickets generados
   * GET /api/hotspot/tickets
   */
  async getTickets(req, res) {
    try {
      let tickets;
      
      if (req.user.role === 'superadmin') {
        tickets = await db.getAll('hotspotTickets');
      } else {
        tickets = await db.findBy('hotspotTickets', 'clientId', req.user.clientId);
      }

      // Ordenar por fecha de creación descendente
      tickets.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));

      res.json({ success: true, data: tickets });
    } catch (error) {
      console.error('[Hotspot] Error obteniendo tickets:', error);
      res.status(500).json({ success: false, message: error.message });
    }
  },

  /**
   * Eliminar un ticket
   * DELETE /api/hotspot/tickets/:id
   */
  async deleteTicket(req, res) {
    try {
      const ticket = await db.getById('hotspotTickets', req.params.id);
      if (!ticket) return res.status(404).json({ success: false, message: 'Ticket no encontrado' });

      if (req.user.role !== 'superadmin' && ticket.clientId !== req.user.clientId) {
        return res.status(403).json({ success: false, message: 'Acceso denegado' });
      }

      await db.delete('hotspotTickets', req.params.id);

      res.json({ success: true, message: 'Ticket eliminado exitosamente' });
    } catch (error) {
      res.status(500).json({ success: false, message: error.message });
    }
  }
};

module.exports = hotspotController;
