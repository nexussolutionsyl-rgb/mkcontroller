const fs = require('fs');
const path = require('path');
const config = require('../config/config');

/**
 * Base de datos JSON - Sistema de almacenamiento basado en archivos
 * Fase inicial, migrable a MongoDB/PostgreSQL
 */
class Database {
  constructor() {
    this.data = {};
    this.initialized = false;
  }

  /**
   * Inicializa la base de datos cargando todos los archivos JSON
   */
  async initialize() {
    if (this.initialized) return;
    
    try {
      for (const [key, filePath] of Object.entries(config.dataPaths)) {
        await this._loadCollection(key, filePath);
      }
      this.initialized = true;
      console.log('[DB] Base de datos JSON inicializada correctamente');
    } catch (error) {
      console.error('[DB] Error inicializando base de datos:', error.message);
      throw error;
    }
  }

  /**
   * Carga una colección desde un archivo JSON
   */
  async _loadCollection(key, filePath) {
    const fullPath = path.resolve(filePath);
    
    try {
      if (fs.existsSync(fullPath)) {
        const raw = fs.readFileSync(fullPath, 'utf8');
        this.data[key] = JSON.parse(raw);
        console.log(`[DB] Colección "${key}" cargada: ${this.data[key].length} registros`);
      } else {
        // Crear archivo vacío si no existe
        this.data[key] = [];
        await this._saveCollection(key);
        console.log(`[DB] Colección "${key}" creada (vacía)`);
      }
    } catch (error) {
      console.error(`[DB] Error cargando colección "${key}":`, error.message);
      this.data[key] = [];
    }
  }

  /**
   * Guarda una colección en su archivo JSON
   */
  async _saveCollection(key) {
    const filePath = config.dataPaths[key];
    if (!filePath) {
      throw new Error(`Ruta no definida para la colección: ${key}`);
    }
    
    const fullPath = path.resolve(filePath);
    const dir = path.dirname(fullPath);
    
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
    }
    
    fs.writeFileSync(fullPath, JSON.stringify(this.data[key], null, 2), 'utf8');
  }

  /**
   * Obtiene todos los registros de una colección
   */
  async getAll(collection) {
    this._validateCollection(collection);
    return [...this.data[collection]];
  }

  /**
   * Obtiene un registro por ID
   */
  async getById(collection, id) {
    this._validateCollection(collection);
    return this.data[collection].find(item => item.id === id) || null;
  }

  /**
   * Busca registros por un campo específico
   */
  async findBy(collection, field, value) {
    this._validateCollection(collection);
    return this.data[collection].filter(item => item[field] === value);
  }

  /**
   * Busca un solo registro por un campo específico
   */
  async findOne(collection, field, value) {
    this._validateCollection(collection);
    return this.data[collection].find(item => item[field] === value) || null;
  }

  /**
   * Crea un nuevo registro
   */
  async create(collection, record) {
    this._validateCollection(collection);
    
    const newRecord = {
      ...record,
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };
    
    this.data[collection].push(newRecord);
    await this._saveCollection(collection);
    return newRecord;
  }

  /**
   * Actualiza un registro por ID
   */
  async update(collection, id, updates) {
    this._validateCollection(collection);
    
    const index = this.data[collection].findIndex(item => item.id === id);
    if (index === -1) return null;
    
    this.data[collection][index] = {
      ...this.data[collection][index],
      ...updates,
      id: this.data[collection][index].id, // No permitir cambiar ID
      createdAt: this.data[collection][index].createdAt,
      updatedAt: new Date().toISOString()
    };
    
    await this._saveCollection(collection);
    return this.data[collection][index];
  }

  /**
   * Elimina un registro por ID
   */
  async delete(collection, id) {
    this._validateCollection(collection);
    
    const index = this.data[collection].findIndex(item => item.id === id);
    if (index === -1) return false;
    
    this.data[collection].splice(index, 1);
    await this._saveCollection(collection);
    return true;
  }

  /**
   * Cuenta registros en una colección
   */
  async count(collection, filter = {}) {
    this._validateCollection(collection);
    
    if (Object.keys(filter).length === 0) {
      return this.data[collection].length;
    }
    
    return this.data[collection].filter(item => {
      return Object.entries(filter).every(([key, value]) => item[key] === value);
    }).length;
  }

  /**
   * Valida que la colección exista
   */
  _validateCollection(collection) {
    if (!this.data[collection]) {
      throw new Error(`Colección no encontrada: ${collection}`);
    }
  }

  /**
   * Obtiene estadísticas de la base de datos
   */
  async getStats() {
    const stats = {};
    for (const [key] of Object.entries(config.dataPaths)) {
      stats[key] = this.data[key] ? this.data[key].length : 0;
    }
    return stats;
  }
}

// Singleton
const database = new Database();

module.exports = database;
