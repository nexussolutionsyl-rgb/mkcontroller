const express = require('express');
const router = express.Router();
const hotspotController = require('../controllers/hotspotController');
const { authenticate } = require('../middleware/auth');

// Todas las rutas requieren autenticación
router.use(authenticate);

// GET /api/hotspot/tickets - Obtener tickets generados
router.get('/tickets', hotspotController.getTickets);

// DELETE /api/hotspot/tickets/:id - Eliminar ticket
router.delete('/tickets/:id', hotspotController.deleteTicket);

module.exports = router;
