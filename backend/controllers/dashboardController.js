const db = require('../models/database');

/**
 * Controlador del Dashboard
 */
const dashboardController = {
  /**
   * Obtener estadísticas del dashboard (SuperAdmin)
   * GET /api/dashboard/admin
   */
  async getAdminDashboard(req, res) {
    try {
      const clients = await db.getAll('clients');
      const routers = await db.getAll('routers');
      const users = await db.getAll('users');
      const tickets = await db.getAll('hotspotTickets');
      const logs = await db.getAll('activityLog');

      // Estadísticas de clientes
      const totalClients = clients.length;
      const activeClients = clients.filter(c => c.status === 'active').length;

      // Estadísticas de routers
      const totalRouters = routers.length;
      const onlineRouters = routers.filter(r => r.status === 'online').length;
      const offlineRouters = routers.filter(r => r.status === 'offline').length;

      // Estadísticas de usuarios
      const totalUsers = users.length;
      const adminUsers = users.filter(u => u.role === 'admin').length;
      const normalUsers = users.filter(u => u.role === 'user').length;

      // Estadísticas de tickets
      const totalTickets = tickets.length;
      const activeTickets = tickets.filter(t => t.status === 'active').length;

      // Actividad reciente (últimos 20 registros)
      const recentActivity = logs
        .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
        .slice(0, 20);

      // Distribución de planes
      const plansDistribution = {};
      clients.forEach(c => {
        const plan = c.plan || 'basic';
        plansDistribution[plan] = (plansDistribution[plan] || 0) + 1;
      });

      res.json({
        success: true,
        data: {
          clients: {
            total: totalClients,
            active: activeClients,
            inactive: totalClients - activeClients
          },
          routers: {
            total: totalRouters,
            online: onlineRouters,
            offline: offlineRouters,
            unknown: totalRouters - onlineRouters - offlineRouters
          },
          users: {
            total: totalUsers,
            admins: adminUsers,
            normal: normalUsers,
            superadmins: users.filter(u => u.role === 'superadmin').length
          },
          tickets: {
            total: totalTickets,
            active: activeTickets
          },
          plansDistribution,
          recentActivity
        }
      });
    } catch (error) {
      console.error('[Dashboard] Error:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Obtener dashboard del cliente
   * GET /api/dashboard/client
   */
  async getClientDashboard(req, res) {
    try {
      const clientId = req.user.clientId;
      
      if (!clientId) {
        return res.status(400).json({
          success: false,
          message: 'Usuario no asociado a un cliente'
        });
      }

      const client = await db.getById('clients', clientId);
      if (!client) {
        return res.status(404).json({
          success: false,
          message: 'Cliente no encontrado'
        });
      }

      const routers = await db.findBy('routers', 'clientId', clientId);
      const users = await db.findBy('users', 'clientId', clientId);
      const tickets = await db.findBy('hotspotTickets', 'clientId', clientId);

      // Estadísticas de routers
      const totalRouters = routers.length;
      const onlineRouters = routers.filter(r => r.status === 'online').length;
      const offlineRouters = routers.filter(r => r.status === 'offline').length;

      // Estadísticas de usuarios
      const totalUsers = users.length;
      const activeUsers = users.filter(u => u.status === 'active').length;

      // Tickets recientes
      const recentTickets = tickets
        .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
        .slice(0, 10);

      res.json({
        success: true,
        data: {
          client: {
            id: client.id,
            name: client.name,
            company: client.company,
            plan: client.plan,
            status: client.status
          },
          routers: {
            total: totalRouters,
            online: onlineRouters,
            offline: offlineRouters,
            unknown: totalRouters - onlineRouters - offlineRouters
          },
          users: {
            total: totalUsers,
            active: activeUsers
          },
          tickets: {
            total: tickets.length,
            recentTickets
          }
        }
      });
    } catch (error) {
      console.error('[Dashboard] Error:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  }
};

module.exports = dashboardController;
