const express = require('express');
const router = express.Router();
const usersController = require('../controllers/usersController');
const { authenticate, requireSuperAdmin, requireAdmin } = require('../middleware/auth');

// Todas las rutas requieren autenticación
router.use(authenticate);

// GET /api/users - Obtener todos los usuarios (SuperAdmin)
router.get('/', requireSuperAdmin, usersController.getAll);

// GET /api/users/client/:clientId - Usuarios por cliente
router.get('/client/:clientId', requireAdmin, usersController.getByClient);

// GET /api/users/:id - Obtener usuario por ID
router.get('/:id', usersController.getById);

// POST /api/users - Crear usuario
router.post('/', requireAdmin, usersController.create);

// PUT /api/users/:id - Actualizar usuario
router.put('/:id', requireAdmin, usersController.update);

// DELETE /api/users/:id - Eliminar usuario
router.delete('/:id', requireSuperAdmin, usersController.delete);

module.exports = router;
