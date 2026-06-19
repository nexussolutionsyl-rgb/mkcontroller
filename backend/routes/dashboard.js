const express = require('express');
const router = express.Router();
const dashboardController = require('../controllers/dashboardController');
const { authenticate, requireSuperAdmin } = require('../middleware/auth');

// Todas las rutas requieren autenticación
router.use(authenticate);

// GET /api/dashboard/admin - Dashboard SuperAdmin
router.get('/admin', requireSuperAdmin, dashboardController.getAdminDashboard);

// GET /api/dashboard/client - Dashboard Cliente
router.get('/client', dashboardController.getClientDashboard);

module.exports = router;
