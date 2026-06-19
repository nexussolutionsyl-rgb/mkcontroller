const express = require('express');
const router = express.Router();
const clientsController = require('../controllers/clientsController');
const { authenticate, requireSuperAdmin, requireAdmin } = require('../middleware/auth');

// Todas las rutas requieren autenticación
router.use(authenticate);

// GET /api/clients/stats - Estadísticas (debe ir antes de /:id)
router.get('/stats', requireSuperAdmin, clientsController.getStats);

// GET /api/clients - Obtener todos los clientes
router.get('/', requireSuperAdmin, clientsController.getAll);

// GET /api/clients/:id - Obtener cliente por ID
router.get('/:id', requireAdmin, clientsController.getById);

// POST /api/clients - Crear cliente
router.post('/', requireSuperAdmin, clientsController.create);

// PUT /api/clients/:id - Actualizar cliente
router.put('/:id', requireSuperAdmin, clientsController.update);

// DELETE /api/clients/:id - Eliminar cliente
router.delete('/:id', requireSuperAdmin, clientsController.delete);

module.exports = router;
