const express = require('express');
const router = express.Router();
const authController = require('../controllers/authController');
const { authenticate } = require('../middleware/auth');

// POST /api/auth/login - Inicio de sesión
router.post('/login', authController.login);

// POST /api/auth/logout - Cerrar sesión
router.post('/logout', authenticate, authController.logout);

// GET /api/auth/verify - Verificar token
router.get('/verify', authenticate, authController.verifyToken);

// POST /api/auth/change-password - Cambiar contraseña
router.post('/change-password', authenticate, authController.changePassword);

module.exports = router;
