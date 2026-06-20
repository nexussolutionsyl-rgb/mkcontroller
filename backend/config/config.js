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

  // Configuración por defecto para conexión MikroTik (API/Socket)
  mikrotik: {
    defaultPort: 8728,
    defaultApiPort: 8728,
    connectionTimeout: 5000,
    commandTimeout: 10000
  },

  // Configuración ISP Manager (REST API v7)
  isp: {
    mikrotik: {
      host: process.env.ISP_MIKROTIK_HOST || '127.0.0.1',
      port: parseInt(process.env.ISP_MIKROTIK_PORT) || 443,
      username: process.env.ISP_MIKROTIK_USER || 'admin',
      password: process.env.ISP_MIKROTIK_PASSWORD || '',
      ssl: process.env.ISP_MIKROTIK_SSL !== 'false',
      timeout: parseInt(process.env.ISP_MIKROTIK_TIMEOUT) || 10000
    },
    // Prefijo de ruta para la API ISP
    apiPrefix: '/api/isp',
    // Configuración de webhook Telegram
    telegram: {
      botToken: process.env.ISP_TELEGRAM_BOT_TOKEN || '',
      chatId: process.env.ISP_TELEGRAM_CHAT_ID || ''
    }
  }
};
