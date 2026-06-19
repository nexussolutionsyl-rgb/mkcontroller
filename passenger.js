// Entry point para Phusion Passenger (cPanel Node.js Selector)
// Passenger requiere el módulo y se encarga del listen()
require('dotenv').config({ path: './backend/.env' });
const app = require('./backend/app');
module.exports = app;
