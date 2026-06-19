/**
 * Rutas de nexusMK
 * Módulo de gestión MikroTik desde MySQL
 */
const express = require('express');
const router = express.Router();
const nexusmkController = require('../controllers/nexusmkController');
const { authenticate } = require('../middleware/auth');

// Health check de conexión MySQL
router.get('/health', nexusmkController.health);

// Login (no requiere autenticación previa)
router.post('/login', nexusmkController.login);

// Rutas protegidas (requieren token JWT)
router.get('/stats', authenticate, nexusmkController.getStats);
router.get('/dispositivos', authenticate, nexusmkController.getDispositivos);
router.get('/dbinfo', authenticate, nexusmkController.getDbInfo);

module.exports = router;
