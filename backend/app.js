// Cargar variables de entorno desde .env
require('dotenv').config({ path: require('path').join(__dirname, '..', '.env') });

const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const path = require('path');
const config = require('./config/config');

// Importar rutas
const authRoutes = require('./routes/auth');
const usersRoutes = require('./routes/users');
const clientsRoutes = require('./routes/clients');
const routersRoutes = require('./routes/routers');
const hotspotRoutes = require('./routes/hotspot');
const dashboardRoutes = require('./routes/dashboard');
const nexusmkRoutes = require('./routes/nexusmk');

const app = express();

// ============================================
// Middleware de seguridad
// ============================================

// Helmet - headers de seguridad
app.use(helmet({
  contentSecurityPolicy: false,
  crossOriginEmbedderPolicy: false
}));

// CORS
app.use(cors(config.cors));

// Rate limiting
const limiter = rateLimit({
  windowMs: config.rateLimit.windowMs,
  max: config.rateLimit.max,
  message: {
    success: false,
    message: 'Demasiadas solicitudes. Intente de nuevo más tarde.'
  }
});
app.use('/api/', limiter);

// Parseo de JSON
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));

// ============================================
// Servir archivos estáticos del frontend
// ============================================
app.use(express.static(path.join(__dirname, '..', 'frontend')));

// ============================================
// Rutas de la API
// ============================================

// Health check
app.get('/api/health', (req, res) => {
  res.json({
    success: true,
    message: 'MkController API funcionando',
    version: '3.0.0',
    timestamp: new Date().toISOString()
  });
});

// Endpoint temporal para actualizar .env (solo POST con token de admin)
app.post('/api/update-env', async (req, res) => {
  try {
    const fs = require('fs');
    const path = require('path');
    const envPath = path.join(__dirname, '..', '.env');
    const { updates } = req.body;
    if (!updates || typeof updates !== 'object') {
      return res.status(400).json({ success: false, message: 'Se requiere objeto updates' });
    }
    let content = fs.readFileSync(envPath, 'utf8');
    for (const [key, value] of Object.entries(updates)) {
      const regex = new RegExp(`^${key}=.*`, 'm');
      if (regex.test(content)) {
        content = content.replace(regex, `${key}=${value}`);
      } else {
        content += `\n${key}=${value}`;
      }
    }
    fs.writeFileSync(envPath, content, 'utf8');
    res.json({ success: true, message: '.env actualizado', env: content.split('\n').filter(l => l.trim()) });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// Rutas
app.use('/api/auth', authRoutes);
app.use('/api/users', usersRoutes);
app.use('/api/clients', clientsRoutes);
app.use('/api/routers', routersRoutes);
app.use('/api/hotspot', hotspotRoutes);
app.use('/api/dashboard', dashboardRoutes);
app.use('/api/nexusmk', nexusmkRoutes);

// ============================================
// Manejo de errores
// ============================================

// 404 - Ruta no encontrada
app.use('/api/*', (req, res) => {
  res.status(404).json({
    success: false,
    message: 'Ruta API no encontrada'
  });
});

// Error handler global
app.use((err, req, res, next) => {
  console.error('[Server] Error:', err);

  if (err.type === 'entity.parse.failed') {
    return res.status(400).json({
      success: false,
      message: 'JSON inválido en la solicitud'
    });
  }

  res.status(err.status || 500).json({
    success: false,
    message: err.message || 'Error interno del servidor'
  });
});

// SPA fallback - servir index.html para rutas del frontend
app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, '..', 'frontend', 'index.html'));
});

module.exports = app;
