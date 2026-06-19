module.exports = {
  // Puerto del servidor
  port: process.env.PORT || 3000,

  // Secreta para JWT
  jwtSecret: process.env.JWT_SECRET || 'MkController2024_SuperSecretKey_ChangeInProduction',
  jwtExpiresIn: process.env.JWT_EXPIRES_IN || '24h',

  // Configuración de seguridad
  cors: {
    origin: process.env.CORS_ORIGIN || '*',
    methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
    allowedHeaders: ['Content-Type', 'Authorization']
  },

  // Rate limiting
  rateLimit: {
    windowMs: 15 * 60 * 1000, // 15 minutos
    max: 100 // límite de requests por ventana
  },

  // Rutas de archivos de datos
  dataPaths: {
    users: './backend/data/users.json',
    clients: './backend/data/clients.json',
    routers: './backend/data/routers.json',
    hotspotTickets: './backend/data/hotspot_tickets.json',
    activityLog: './backend/data/activity_log.json'
  },

  // Configuración MySQL para nexusMK (pool de conexiones)
  mysql: {
    host: process.env.MYSQL_HOST || 'localhost',
    user: process.env.MYSQL_USER || 'root',
    password: process.env.MYSQL_PASSWORD || '',
    database: process.env.MYSQL_DATABASE || 'nexusmk'
  },

  // Configuración por defecto para conexión MikroTik
  mikrotik: {
    defaultPort: 8728,
    defaultApiPort: 8728,
    connectionTimeout: 5000,
    commandTimeout: 10000
  }
};
