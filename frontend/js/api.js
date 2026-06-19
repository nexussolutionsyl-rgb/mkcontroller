/**
 * MkController - API Helper
 * Cliente para comunicación con el backend
 */

const API = {
  baseURL: '/api',
  token: null,

  /**
   * Inicializa el API helper
   */
  init() {
    this.token = localStorage.getItem('mk_token');
  },

  /**
   * Establece el token de autenticación
   */
  setToken(token) {
    this.token = token;
    if (token) {
      localStorage.setItem('mk_token', token);
    } else {
      localStorage.removeItem('mk_token');
    }
  },

  /**
   * Obtiene headers para las peticiones
   */
  getHeaders() {
    const headers = {
      'Content-Type': 'application/json'
    };
    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }
    return headers;
  },

  /**
   * Petición GET
   */
  async get(endpoint) {
    try {
      const response = await fetch(`${this.baseURL}${endpoint}`, {
        method: 'GET',
        headers: this.getHeaders()
      });
      return this.handleResponse(response);
    } catch (error) {
      return { success: false, message: 'Error de conexión con el servidor' };
    }
  },

  /**
   * Petición POST
   */
  async post(endpoint, data = {}) {
    try {
      const response = await fetch(`${this.baseURL}${endpoint}`, {
        method: 'POST',
        headers: this.getHeaders(),
        body: JSON.stringify(data)
      });
      return this.handleResponse(response);
    } catch (error) {
      return { success: false, message: 'Error de conexión con el servidor' };
    }
  },

  /**
   * Petición PUT
   */
  async put(endpoint, data = {}) {
    try {
      const response = await fetch(`${this.baseURL}${endpoint}`, {
        method: 'PUT',
        headers: this.getHeaders(),
        body: JSON.stringify(data)
      });
      return this.handleResponse(response);
    } catch (error) {
      return { success: false, message: 'Error de conexión con el servidor' };
    }
  },

  /**
   * Petición DELETE
   */
  async delete(endpoint) {
    try {
      const response = await fetch(`${this.baseURL}${endpoint}`, {
        method: 'DELETE',
        headers: this.getHeaders()
      });
      return this.handleResponse(response);
    } catch (error) {
      return { success: false, message: 'Error de conexión con el servidor' };
    }
  },

  /**
   * Maneja la respuesta del servidor
   */
  async handleResponse(response) {
    const data = await response.json();

    if (response.status === 401 && data.code === 'TOKEN_EXPIRED') {
      this.logout();
      window.location.hash = '#login';
      return { success: false, message: 'Sesión expirada. Inicie sesión nuevamente.' };
    }

    return data;
  },

  /**
   * Cierra sesión
   */
  logout() {
    this.setToken(null);
    localStorage.removeItem('mk_user');
  },

  /**
   * Verifica si el usuario está autenticado
   */
  isAuthenticated() {
    return !!this.token;
  },

  /**
   * Obtiene el usuario actual del localStorage
   */
  getCurrentUser() {
    const userStr = localStorage.getItem('mk_user');
    return userStr ? JSON.parse(userStr) : null;
  }
};

// Inicializar
API.init();
