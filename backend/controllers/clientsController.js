const { v4: uuidv4 } = require('uuid');
const db = require('../models/database');

/**
 * Controlador de Clientes
 */
const clientsController = {
  /**
   * Obtener todos los clientes
   * GET /api/clients
   */
  async getAll(req, res) {
    try {
      const clients = await db.getAll('clients');
      res.json({
        success: true,
        data: clients
      });
    } catch (error) {
      console.error('[Clients] Error obteniendo clientes:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Obtener un cliente por ID
   * GET /api/clients/:id
   */
  async getById(req, res) {
    try {
      const client = await db.getById('clients', req.params.id);
      
      if (!client) {
        return res.status(404).json({
          success: false,
          message: 'Cliente no encontrado'
        });
      }

      // Obtener routers asociados
      const routers = await db.findBy('routers', 'clientId', client.id);
      
      // Obtener usuarios asociados
      const users = await db.findBy('users', 'clientId', client.id);
      const filteredUsers = users.map(u => ({
        id: u.id,
        username: u.username,
        name: u.name,
        email: u.email,
        role: u.role,
        status: u.status
      }));

      res.json({
        success: true,
        data: {
          ...client,
          routers: routers.length,
          users: filteredUsers
        }
      });
    } catch (error) {
      console.error('[Clients] Error obteniendo cliente:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Crear un nuevo cliente
   * POST /api/clients
   */
  async create(req, res) {
    try {
      const { name, company, email, phone, address, plan, notes } = req.body;

      if (!name || !email) {
        return res.status(400).json({
          success: false,
          message: 'Nombre y email son requeridos'
        });
      }

      const newClient = await db.create('clients', {
        id: uuidv4(),
        name,
        company: company || '',
        email,
        phone: phone || '',
        address: address || '',
        plan: plan || 'basic',
        status: 'active',
        notes: notes || ''
      });

      res.status(201).json({
        success: true,
        message: 'Cliente creado exitosamente',
        data: newClient
      });
    } catch (error) {
      console.error('[Clients] Error creando cliente:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Actualizar un cliente
   * PUT /api/clients/:id
   */
  async update(req, res) {
    try {
      const { name, company, email, phone, address, plan, status, notes } = req.body;
      const clientId = req.params.id;

      const client = await db.getById('clients', clientId);
      if (!client) {
        return res.status(404).json({
          success: false,
          message: 'Cliente no encontrado'
        });
      }

      const updates = {};
      if (name) updates.name = name;
      if (company !== undefined) updates.company = company;
      if (email) updates.email = email;
      if (phone !== undefined) updates.phone = phone;
      if (address !== undefined) updates.address = address;
      if (plan) updates.plan = plan;
      if (status) updates.status = status;
      if (notes !== undefined) updates.notes = notes;

      const updatedClient = await db.update('clients', clientId, updates);

      res.json({
        success: true,
        message: 'Cliente actualizado exitosamente',
        data: updatedClient
      });
    } catch (error) {
      console.error('[Clients] Error actualizando cliente:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Eliminar un cliente
   * DELETE /api/clients/:id
   */
  async delete(req, res) {
    try {
      const clientId = req.params.id;

      // Verificar si tiene routers asociados
      const routers = await db.findBy('routers', 'clientId', clientId);
      if (routers.length > 0) {
        return res.status(400).json({
          success: false,
          message: `No se puede eliminar el cliente porque tiene ${routers.length} router(es) asociado(s). Elimine los routers primero.`
        });
      }

      const deleted = await db.delete('clients', clientId);
      
      if (!deleted) {
        return res.status(404).json({
          success: false,
          message: 'Cliente no encontrado'
        });
      }

      res.json({
        success: true,
        message: 'Cliente eliminado exitosamente'
      });
    } catch (error) {
      console.error('[Clients] Error eliminando cliente:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Obtener estadísticas de clientes
   * GET /api/clients/stats
   */
  async getStats(req, res) {
    try {
      const clients = await db.getAll('clients');
      const routers = await db.getAll('routers');
      const users = await db.getAll('users');

      const stats = {
        totalClients: clients.length,
        activeClients: clients.filter(c => c.status === 'active').length,
        totalRouters: routers.length,
        onlineRouters: routers.filter(r => r.status === 'online').length,
        totalUsers: users.filter(u => u.role !== 'superadmin').length,
        plansDistribution: {}
      };

      clients.forEach(client => {
        const plan = client.plan || 'basic';
        stats.plansDistribution[plan] = (stats.plansDistribution[plan] || 0) + 1;
      });

      res.json({
        success: true,
        data: stats
      });
    } catch (error) {
      console.error('[Clients] Error obteniendo estadísticas:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  }
};

module.exports = clientsController;
