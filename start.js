// Entry point for MkController v3.0
// Compatible with Phusion Passenger (cPanel Node.js Selector)
// And direct execution (node start.js)

require('dotenv').config({ path: './backend/.env' });
const app = require('./backend/app');
const db = require('./backend/models/database');
const seed = require('./backend/seed');
const config = require('./backend/config/config');

/**
 * Initialize database and run seed
 */
async function initialize() {
  try {
    console.log('=== MkController v3.0.0 ===');
    console.log('Administracion de Routers MikroTik');
    console.log('');

    // Initialize database
    console.log('[Init] Initializing database...');
    await db.initialize();

    // Run seed (create initial data if not exists)
    console.log('[Init] Checking initial data...');
    await seed();

    console.log('[Init] Initialization completed successfully');
    console.log('[Init] MkController ready to serve requests');
  } catch (error) {
    console.error('[Init] Fatal error:', error);
    process.exit(1);
  }
}

// Initialize
initialize();

// If executed directly (not by Passenger), start HTTP server
if (!process.env.PASSENGER_APP) {
  const PORT = process.env.PORT || config.port || 3000;
  app.listen(PORT, '127.0.0.1', () => {
    console.log('[Server] Server started on port ' + PORT);
  });
}

// Export the Express app for Passenger
module.exports = app;
