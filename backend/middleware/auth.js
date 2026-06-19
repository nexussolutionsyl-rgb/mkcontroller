const jwt = require('jsonwebtoken');
const config = require('../config/config');
const db = require('../models/database');

/**
 * Middleware de autenticación JWT
 * Verifica el token y adjunta el usuario a la request
 */
function authenticate(req, res, next) {
  const authHeader = req.headers.authorization;

  if (!authHeader) {
    return res.status(401).json({
      success: false,
      message: 'Token de autenticación requerido'
    });
  }

  const token = authHeader.split(' ')[1]; // Bearer <token>

  if (!token) {
    return res.status(401).json({
      success: false,
      message: 'Formato de token inválido'
    });
  }

  try {
    const decoded = jwt.verify(token, config.jwtSecret);
    req.user = decoded;
    next();
  } catch (error) {
    if (error.name === 'TokenExpiredError') {
      return res.status(401).json({
        success: false,
        message: 'Token expirado',
        code: 'TOKEN_EXPIRED'
      });
    }

    return res.status(401).json({
      success: false,
      message: 'Token inválido',
      code: 'INVALID_TOKEN'
    });
  }
}

/**
 * Middleware para verificar rol de SuperAdmin
 */
function requireSuperAdmin(req, res, next) {
  if (!req.user || req.user.role !== 'superadmin') {
    return res.status(403).json({
      success: false,
      message: 'Acceso denegado. Se requieren permisos de SuperAdmin'
    });
  }
  next();
}

/**
 * Middleware para verificar rol de Admin o superior
 */
function requireAdmin(req, res, next) {
  if (!req.user || (req.user.role !== 'superadmin' && req.user.role !== 'admin')) {
    return res.status(403).json({
      success: false,
      message: 'Acceso denegado. Se requieren permisos de administrador'
    });
  }
  next();
}

/**
 * Middleware opcional - adjunta usuario si hay token pero no bloquea
 */
function optionalAuth(req, res, next) {
  const authHeader = req.headers.authorization;

  if (!authHeader) {
    req.user = null;
    return next();
  }

  const token = authHeader.split(' ')[1];

  if (!token) {
    req.user = null;
    return next();
  }

  try {
    const decoded = jwt.verify(token, config.jwtSecret);
    req.user = decoded;
  } catch (error) {
    req.user = null;
  }

  next();
}

/**
 * Middleware para verificar que el usuario accede solo a sus recursos
 * o es SuperAdmin
 */
function requireOwnResource(req, res, next) {
  if (!req.user) {
    return res.status(401).json({
      success: false,
      message: 'Autenticación requerida'
    });
  }

  // SuperAdmin puede acceder a todo
  if (req.user.role === 'superadmin') {
    return next();
  }

  // Verificar que el ID del recurso coincida con el usuario
  const resourceId = req.params.id || req.params.userId;
  if (resourceId && resourceId !== req.user.id) {
    return res.status(403).json({
      success: false,
      message: 'No tienes permiso para acceder a este recurso'
    });
  }

  next();
}

module.exports = {
  authenticate,
  requireSuperAdmin,
  requireAdmin,
  optionalAuth,
  requireOwnResource
};
