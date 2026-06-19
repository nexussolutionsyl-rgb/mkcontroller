const bcrypt = require('bcryptjs');
const { v4: uuidv4 } = require('uuid');
const db = require('../models/database');

/**
 * Controlador de Usuarios
 */
const usersController = {
  /**
   * Obtener todos los usuarios (SuperAdmin)
   * GET /api/users
   */
  async getAll(req, res) {
    try {
      const users = await db.getAll('users');
      const filteredUsers = users.map(user => ({
        id: user.id,
        username: user.username,
        name: user.name,
        email: user.email,
        role: user.role,
        clientId: user.clientId || null,
        status: user.status,
        createdAt: user.createdAt,
        updatedAt: user.updatedAt
      }));

      res.json({
        success: true,
        data: filteredUsers
      });
    } catch (error) {
      console.error('[Users] Error obteniendo usuarios:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Obtener usuarios de un cliente específico
   * GET /api/users/client/:clientId
   */
  async getByClient(req, res) {
    try {
      const { clientId } = req.params;
      const users = await db.findBy('users', 'clientId', clientId);
      
      const filteredUsers = users.map(user => ({
        id: user.id,
        username: user.username,
        name: user.name,
        email: user.email,
        role: user.role,
        clientId: user.clientId,
        status: user.status,
        createdAt: user.createdAt,
        updatedAt: user.updatedAt
      }));

      res.json({
        success: true,
        data: filteredUsers
      });
    } catch (error) {
      console.error('[Users] Error obteniendo usuarios del cliente:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Obtener un usuario por ID
   * GET /api/users/:id
   */
  async getById(req, res) {
    try {
      const user = await db.getById('users', req.params.id);
      
      if (!user) {
        return res.status(404).json({
          success: false,
          message: 'Usuario no encontrado'
        });
      }

      res.json({
        success: true,
        data: {
          id: user.id,
          username: user.username,
          name: user.name,
          email: user.email,
          role: user.role,
          clientId: user.clientId || null,
          status: user.status,
          createdAt: user.createdAt,
          updatedAt: user.updatedAt
        }
      });
    } catch (error) {
      console.error('[Users] Error obteniendo usuario:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Crear un nuevo usuario
   * POST /api/users
   */
  async create(req, res) {
    try {
      const { username, password, name, email, role, clientId } = req.body;

      if (!username || !password || !name || !email) {
        return res.status(400).json({
          success: false,
          message: 'Todos los campos son requeridos: username, password, name, email'
        });
      }

      // Verificar si el username ya existe
      const existingUser = await db.findOne('users', 'username', username);
      if (existingUser) {
        return res.status(400).json({
          success: false,
          message: 'El nombre de usuario ya está en uso'
        });
      }

      // Verificar si el email ya existe
      const existingEmail = await db.findOne('users', 'email', email);
      if (existingEmail) {
        return res.status(400).json({
          success: false,
          message: 'El email ya está registrado'
        });
      }

      // Validar rol
      const validRoles = ['superadmin', 'admin', 'user'];
      const userRole = role || 'user';
      if (!validRoles.includes(userRole)) {
        return res.status(400).json({
          success: false,
          message: 'Rol inválido. Roles válidos: superadmin, admin, user'
        });
      }

      // Si es superadmin, no puede tener clientId
      if (userRole === 'superadmin' && clientId) {
        return res.status(400).json({
          success: false,
          message: 'Un SuperAdmin no puede estar asociado a un cliente'
        });
      }

      // Si es admin o user, requiere clientId
      if ((userRole === 'admin' || userRole === 'user') && !clientId) {
        return res.status(400).json({
          success: false,
          message: 'Usuarios admin/user deben estar asociados a un cliente'
        });
      }

      const hashedPassword = await bcrypt.hash(password, 10);

      const newUser = await db.create('users', {
        id: uuidv4(),
        username,
        password: hashedPassword,
        name,
        email,
        role: userRole,
        clientId: clientId || null,
        status: 'active'
      });

      res.status(201).json({
        success: true,
        message: 'Usuario creado exitosamente',
        data: {
          id: newUser.id,
          username: newUser.username,
          name: newUser.name,
          email: newUser.email,
          role: newUser.role,
          clientId: newUser.clientId,
          status: newUser.status
        }
      });
    } catch (error) {
      console.error('[Users] Error creando usuario:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Actualizar un usuario
   * PUT /api/users/:id
   */
  async update(req, res) {
    try {
      const { username, name, email, role, status, clientId, password } = req.body;
      const userId = req.params.id;

      const user = await db.getById('users', userId);
      if (!user) {
        return res.status(404).json({
          success: false,
          message: 'Usuario no encontrado'
        });
      }

      const updates = {};

      if (username) {
        const existingUser = await db.findOne('users', 'username', username);
        if (existingUser && existingUser.id !== userId) {
          return res.status(400).json({
            success: false,
            message: 'El nombre de usuario ya está en uso'
          });
        }
        updates.username = username;
      }

      if (email) {
        const existingEmail = await db.findOne('users', 'email', email);
        if (existingEmail && existingEmail.id !== userId) {
          return res.status(400).json({
            success: false,
            message: 'El email ya está registrado'
          });
        }
        updates.email = email;
      }

      if (name) updates.name = name;
      if (role) updates.role = role;
      if (status) updates.status = status;
      if (clientId !== undefined) updates.clientId = clientId;
      
      if (password) {
        updates.password = await bcrypt.hash(password, 10);
      }

      const updatedUser = await db.update('users', userId, updates);

      res.json({
        success: true,
        message: 'Usuario actualizado exitosamente',
        data: {
          id: updatedUser.id,
          username: updatedUser.username,
          name: updatedUser.name,
          email: updatedUser.email,
          role: updatedUser.role,
          clientId: updatedUser.clientId,
          status: updatedUser.status
        }
      });
    } catch (error) {
      console.error('[Users] Error actualizando usuario:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  },

  /**
   * Eliminar un usuario
   * DELETE /api/users/:id
   */
  async delete(req, res) {
    try {
      const userId = req.params.id;

      // No permitir eliminar el propio usuario
      if (userId === req.user.id) {
        return res.status(400).json({
          success: false,
          message: 'No puedes eliminar tu propio usuario'
        });
      }

      const deleted = await db.delete('users', userId);
      
      if (!deleted) {
        return res.status(404).json({
          success: false,
          message: 'Usuario no encontrado'
        });
      }

      res.json({
        success: true,
        message: 'Usuario eliminado exitosamente'
      });
    } catch (error) {
      console.error('[Users] Error eliminando usuario:', error);
      res.status(500).json({
        success: false,
        message: 'Error interno del servidor'
      });
    }
  }
};

module.exports = usersController;
