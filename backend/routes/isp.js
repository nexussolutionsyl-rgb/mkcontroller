/**
 * Rutas del ISP Manager
 * Módulo de gestión centralizada ISP con RouterOS REST API v7
 */
const express = require('express');
const router = express.Router();
const ispController = require('../controllers/ispController');
const { authenticate } = require('../middleware/auth');

// ============================================================
// Health Check (pública)
// ============================================================
router.get('/health', ispController.health);

// ============================================================
// Estadísticas (protegida)
// ============================================================
router.get('/stats', authenticate, ispController.getStats);

// ============================================================
// PPP Profiles (protegidas)
// ============================================================
router.get('/ppp/profiles', authenticate, ispController.getPPPProfiles);
router.post('/ppp/profiles', authenticate, ispController.createPPPProfile);
router.put('/ppp/profiles/:name', authenticate, ispController.updatePPPProfile);
router.delete('/ppp/profiles/:name', authenticate, ispController.deletePPPProfile);
router.post('/ppp/profiles/sync', authenticate, ispController.syncPPPProfiles);

// ============================================================
// Hotspot Profiles (protegidas)
// ============================================================
router.get('/hotspot/profiles', authenticate, ispController.getHotspotProfiles);
router.post('/hotspot/profiles', authenticate, ispController.createHotspotProfile);
router.put('/hotspot/profiles/:name', authenticate, ispController.updateHotspotProfile);
router.delete('/hotspot/profiles/:name', authenticate, ispController.deleteHotspotProfile);
router.post('/hotspot/profiles/sync', authenticate, ispController.syncHotspotProfiles);

// ============================================================
// IP Pools (protegidas)
// ============================================================
router.get('/pools', authenticate, ispController.getIPPools);
router.post('/pools', authenticate, ispController.createIPPool);
router.put('/pools/:name', authenticate, ispController.updateIPPool);
router.delete('/pools/:name', authenticate, ispController.deleteIPPool);
router.post('/pools/sync', authenticate, ispController.syncIPPools);

// ============================================================
// Planes locales (BD) (protegidas)
// ============================================================
router.get('/plans', authenticate, ispController.getPlans);
router.post('/plans', authenticate, ispController.createPlan);
router.put('/plans/:id', authenticate, ispController.updatePlan);
router.delete('/plans/:id', authenticate, ispController.deletePlan);

// ============================================================
// PPP Secrets / Clientes (protegidas)
// ============================================================
router.get('/clients', authenticate, ispController.getPPPSecrets);
router.post('/clients', authenticate, ispController.createPPPSecret);
router.put('/clients/:username', authenticate, ispController.updatePPPSecret);
router.delete('/clients/:username', authenticate, ispController.deletePPPSecret);
router.post('/clients/:username/enable', authenticate, ispController.enablePPPSecret);
router.post('/clients/:username/disable', authenticate, ispController.disablePPPSecret);
router.post('/clients/:username/sync-one', authenticate, ispController.syncOneClient);

// Sesiones activas PPP
router.get('/clients/active', authenticate, ispController.getPPPActive);

// Clientes desde BD local
router.get('/clients/db', authenticate, ispController.getClientsFromDB);

// Sincronización masiva de clientes
router.post('/clients/sync', authenticate, ispController.syncPPPSecrets);

// ============================================================
// Hotspot Users (protegidas)
// ============================================================
router.get('/hotspot/users', authenticate, ispController.getHotspotUsers);
router.post('/hotspot/users', authenticate, ispController.createHotspotUser);
router.put('/hotspot/users/:name', authenticate, ispController.updateHotspotUser);
router.delete('/hotspot/users/:name', authenticate, ispController.deleteHotspotUser);

// Hotspot activos y servidores
router.get('/hotspot/active', authenticate, ispController.getHotspotActive);
router.get('/hotspot/servers', authenticate, ispController.getHotspotServers);

// Sincronización masiva de usuarios Hotspot
router.post('/hotspot/users/sync', authenticate, ispController.syncHotspotUsers);

// ============================================================
// Sistema e Interfaces (protegidas)
// ============================================================
router.get('/system/info', authenticate, ispController.getSystemInfo);
router.get('/system/interfaces', authenticate, ispController.getInterfaces);
router.get('/system/dhcp-leases', authenticate, ispController.getDHCPLeases);
router.get('/system/firewall', authenticate, ispController.getFirewallRules);

// ============================================================
// Webhooks (públicos - el router los llama directamente)
// ============================================================
router.post('/webhook/netwatch', ispController.netwatchWebhook);

// ============================================================
// Alertas (protegidas)
// ============================================================
router.get('/alerts', authenticate, ispController.getAlerts);

// ============================================================
// Gestión de Routers (protegidas)
// ============================================================
router.get('/routers', authenticate, ispController.getRouters);
router.post('/routers', authenticate, ispController.addRouter);
router.put('/routers/:id', authenticate, ispController.updateRouter);
router.delete('/routers/:id', authenticate, ispController.deleteRouter);
router.post('/routers/:id/test', authenticate, ispController.testRouterConnection);
router.post('/routers/:id/activate', authenticate, ispController.setActiveRouter);

// Escaneo de red
router.post('/routers/scan', authenticate, ispController.scanNetwork);
router.post('/routers/scan-host', authenticate, ispController.scanHost);

// Router activo actual
router.get('/routers/active', authenticate, ispController.getActiveRouter);

module.exports = router;
