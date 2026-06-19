const express = require('express');
const router = express.Router();
const routersController = require('../controllers/routersController');
const hotspotController = require('../controllers/hotspotController');
const { authenticate } = require('../middleware/auth');

// Todas las rutas requieren autenticación
router.use(authenticate);

// === Rutas principales de routers ===

// GET /api/routers - Obtener todos los routers
router.get('/', routersController.getAll);

// GET /api/routers/:id - Obtener router por ID
router.get('/:id', routersController.getById);

// POST /api/routers - Crear router
router.post('/', routersController.create);

// PUT /api/routers/:id - Actualizar router
router.put('/:id', routersController.update);

// DELETE /api/routers/:id - Eliminar router
router.delete('/:id', routersController.delete);

// POST /api/routers/:id/test - Probar conexión
router.post('/:id/test', routersController.testConnection);

// === Información del router ===

// GET /api/routers/:id/system-info - Info del sistema
router.get('/:id/system-info', routersController.getSystemInfo);

// GET /api/routers/:id/interfaces - Interfaces
router.get('/:id/interfaces', routersController.getInterfaces);

// GET /api/routers/:id/ip-addresses - Direcciones IP
router.get('/:id/ip-addresses', routersController.getIPAddresses);

// GET /api/routers/:id/dhcp-leases - Leases DHCP
router.get('/:id/dhcp-leases', routersController.getDHCPLeases);

// GET /api/routers/:id/firewall - Reglas de firewall
router.get('/:id/firewall', routersController.getFirewallRules);

// GET /api/routers/:id/connections - Conexiones activas
router.get('/:id/connections', routersController.getActiveConnections);

// GET /api/routers/:id/logs - Logs del sistema
router.get('/:id/logs', routersController.getLogs);

// GET /api/routers/:id/traffic - Tráfico en tiempo real
router.get('/:id/traffic', routersController.getTraffic);

// GET /api/routers/:id/active-users - Usuarios activos
router.get('/:id/active-users', routersController.getActiveUsers);

// POST /api/routers/:id/command - Ejecutar comando
router.post('/:id/command', routersController.executeCommand);

// === Enlaces de acceso ===

// GET /api/routers/:id/winbox - Enlace WinBox
router.get('/:id/winbox', routersController.getWinBoxLink);

// GET /api/routers/:id/webfig - Enlace WebFig
router.get('/:id/webfig', routersController.getWebFigLink);

// === Rutas de Hotspot ===

// GET /api/routers/:routerId/hotspot/servers - Servidores Hotspot
router.get('/:routerId/hotspot/servers', hotspotController.getServers);

// GET /api/routers/:routerId/hotspot/profiles - Perfiles Hotspot
router.get('/:routerId/hotspot/profiles', hotspotController.getProfiles);

// GET /api/routers/:routerId/hotspot/active - Usuarios activos Hotspot
router.get('/:routerId/hotspot/active', hotspotController.getActiveUsers);

// POST /api/routers/:routerId/hotspot/tickets - Crear ticket
router.post('/:routerId/hotspot/tickets', hotspotController.createTicket);

module.exports = router;
