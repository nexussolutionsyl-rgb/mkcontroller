const bcrypt = require('bcryptjs');
const { v4: uuidv4 } = require('uuid');
const db = require('./models/database');

/**
 * Script de inicialización de datos
 * Crea el usuario SuperAdmin por defecto
 */
async function seed() {
  try {
    await db.initialize();

    // Verificar si ya existe un SuperAdmin
    const users = await db.getAll('users');
    const superAdminExists = users.some(u => u.role === 'superadmin');

    if (!superAdminExists) {
      const hashedPassword = await bcrypt.hash('admin123', 10);

      const superAdmin = await db.create('users', {
        id: uuidv4(),
        username: 'admin',
        password: hashedPassword,
        name: 'Super Administrador',
        email: 'admin@mkcontroller.com',
        role: 'superadmin',
        clientId: null,
        status: 'active'
      });

      console.log('[Seed] SuperAdmin creado:');
      console.log('  Usuario: admin');
      console.log('  Contraseña: admin123');
      console.log('  Email: admin@mkcontroller.com');
    } else {
      console.log('[Seed] SuperAdmin ya existe, omitiendo creación');
    }

    // Crear cliente de ejemplo si no hay clientes
    const clients = await db.getAll('clients');
    if (clients.length === 0) {
      const clientId = uuidv4();
      
      await db.create('clients', {
        id: clientId,
        name: 'Cliente Demo',
        company: 'Empresa Demo S.A.',
        email: 'demo@empresa.com',
        phone: '+58 412-1234567',
        address: 'Av. Principal, Caracas',
        plan: 'professional',
        status: 'active',
        notes: 'Cliente de demostración'
      });

      // Crear usuario admin para el cliente demo
      const hashedPassword = await bcrypt.hash('demo123', 10);
      
      await db.create('users', {
        id: uuidv4(),
        username: 'demo',
        password: hashedPassword,
        name: 'Usuario Demo',
        email: 'demo@mkcontroller.com',
        role: 'admin',
        clientId: clientId,
        status: 'active'
      });

      console.log('[Seed] Cliente demo creado:');
      console.log('  Usuario: demo');
      console.log('  Contraseña: demo123');
    }

    console.log('[Seed] Inicialización completada');
  } catch (error) {
    console.error('[Seed] Error:', error);
  }
}

module.exports = seed;

// Ejecutar directamente si se llama como script
if (require.main === module) {
  seed().then(() => process.exit(0));
}
