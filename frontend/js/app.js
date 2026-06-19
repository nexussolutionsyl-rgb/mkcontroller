/**
 * MkController - Aplicación Principal
 * Manejo de navegación, autenticación y layout
 */

const App = {
  currentPage: null,
  user: null,

  /**
   * Inicializa la aplicación
   */
  async init() {
    console.log('[App] Inicializando MkController...');

    // Verificar autenticación
    this.user = API.getCurrentUser();

    if (API.isAuthenticated() && this.user) {
      // Verificar token
      const result = await API.get('/auth/verify');
      if (result.success) {
        this.user = result.data;
        localStorage.setItem('mk_user', JSON.stringify(this.user));
        this.showApp();
      } else {
        this.showLogin();
      }
    } else {
      this.showLogin();
    }

    // Escuchar cambios de hash
    window.addEventListener('hashchange', () => this.handleRoute());
  },

  /**
   * Muestra la pantalla de login
   */
  showLogin() {
    document.getElementById('login-page').style.display = 'flex';
    document.getElementById('app-layout').style.display = 'none';
    this.initLoginForm();
  },

  /**
   * Muestra la aplicación principal
   */
  showApp() {
    document.getElementById('login-page').style.display = 'none';
    document.getElementById('app-layout').style.display = 'flex';

    // Actualizar información del usuario en sidebar
    this.updateUserInfo();
    
    // Manejar ruta
    this.handleRoute();
  },

  /**
   * Actualiza la información del usuario en el sidebar
   */
  updateUserInfo() {
    if (!this.user) return;

    const avatar = document.getElementById('sidebar-user-avatar');
    const name = document.getElementById('sidebar-user-name');
    const role = document.getElementById('sidebar-user-role');

    if (avatar) {
      const initials = this.user.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
      avatar.textContent = initials;
    }
    if (name) name.textContent = this.user.name;
    if (role) {
      const roleNames = {
        'superadmin': 'Super Administrador',
        'admin': 'Administrador',
        'user': 'Usuario'
      };
      role.textContent = roleNames[this.user.role] || this.user.role;
    }
  },

  /**
   * Inicializa el formulario de login
   */
  initLoginForm() {
    const form = document.getElementById('login-form');
    const errorDiv = document.getElementById('login-error');

    form.onsubmit = async (e) => {
      e.preventDefault();
      errorDiv.classList.remove('show');

      const username = document.getElementById('login-username').value.trim();
      const password = document.getElementById('login-password').value;
      const btn = form.querySelector('.btn-login');
      btn.disabled = true;
      btn.textContent = 'Iniciando sesión...';

      const result = await API.post('/auth/login', { username, password });

      if (result.success) {
        API.setToken(result.data.token);
        this.user = result.data.user;
        localStorage.setItem('mk_user', JSON.stringify(this.user));
        this.showApp();
        window.location.hash = '#dashboard';
      } else {
        errorDiv.textContent = result.message || 'Error al iniciar sesión';
        errorDiv.classList.add('show');
        btn.disabled = false;
        btn.textContent = 'Iniciar Sesión';
      }
    };
  },

  /**
   * Maneja el enrutamiento
   */
  handleRoute() {
    const hash = window.location.hash.slice(1) || 'dashboard';
    
    if (!API.isAuthenticated()) {
      if (hash !== 'login') {
        window.location.hash = 'login';
      }
      return;
    }

    // Actualizar navegación activa
    document.querySelectorAll('.nav-item').forEach(item => {
      item.classList.toggle('active', item.dataset.page === hash);
    });

    // Cargar página
    this.loadPage(hash);
  },

  /**
   * Carga una página
   */
  async loadPage(page) {
    const content = document.getElementById('page-content');
    const pageTitle = document.getElementById('page-title');

    // Mapeo de páginas
    const pages = {
      'dashboard': { title: 'Dashboard', file: 'dashboard.html', auth: true },
      'routers': { title: 'Routers', file: 'routers.html', auth: true },
      'hotspot': { title: 'Hotspot', file: 'hotspot.html', auth: true },
      'commands': { title: 'Comandos', file: 'commands.html', auth: true },
      'nexusmk': { title: 'nexusMK', file: 'nexusmk.html', auth: true },
      'users': { title: 'Usuarios', file: 'users.html', auth: true },
      'clients': { title: 'Clientes', file: 'clients.html', auth: true },
      'winbox': { title: 'WinBox / WebFig', file: 'winbox.html', auth: true },
      'profile': { title: 'Mi Perfil', file: 'profile.html', auth: true }
    };

    const pageConfig = pages[page];

    if (!pageConfig) {
      window.location.hash = 'dashboard';
      return;
    }

    // Verificar permisos
    if (page === 'clients' && this.user.role !== 'superadmin') {
      window.location.hash = 'dashboard';
      return;
    }

    this.currentPage = page;
    pageTitle.textContent = pageConfig.title;

    // Mostrar loading
    content.innerHTML = '<div class="loading"><div class="spinner"></div></div>';

    try {
      const response = await fetch(`frontend/pages/${pageConfig.file}`);
      if (!response.ok) throw new Error('Página no encontrada');
      const html = await response.text();
      content.innerHTML = html;

      // Ejecutar script de la página si existe
      this.executePageScript(page);
    } catch (error) {
      content.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">📄</div>
          <h3>Página no encontrada</h3>
          <p>La página solicitada no está disponible.</p>
        </div>
      `;
    }
  },

  /**
   * Ejecuta el script específico de una página
   */
  executePageScript(page) {
    // Eliminar script anterior si existe
    const oldScript = document.getElementById('page-script');
    if (oldScript) oldScript.remove();

    const script = document.createElement('script');
    script.id = 'page-script';
    script.src = `js/pages/${page}.js`;
    script.onerror = () => console.log(`[App] No hay script para la página: ${page}`);
    document.body.appendChild(script);
  },

  /**
   * Cierra sesión
   */
  async logout() {
    await API.post('/auth/logout');
    API.logout();
    this.user = null;
    window.location.hash = 'login';
    this.showLogin();
  },

  /**
   * Muestra una notificación toast
   */
  toast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  },

  /**
   * Formatea bytes a tamaño legible
   */
  formatBytes(bytes) {
    if (!bytes || bytes === '0') return '0 B';
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + units[i];
  },

  /**
   * Formatea fecha
   */
  formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-ES', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  },

  /**
   * Formatea tiempo de actividad
   */
  formatUptime(seconds) {
    if (!seconds) return '-';
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    
    if (days > 0) return `${days}d ${hours}h ${minutes}m`;
    if (hours > 0) return `${hours}h ${minutes}m`;
    return `${minutes}m`;
  }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => App.init());

// Manejar sidebar toggle en móvil
document.addEventListener('DOMContentLoaded', () => {
  const menuBtn = document.getElementById('mobile-menu-btn');
  const sidebar = document.getElementById('sidebar');
  
  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });

    // Cerrar sidebar al hacer clic fuera en móvil
    document.addEventListener('click', (e) => {
      if (window.innerWidth <= 768) {
        if (!sidebar.contains(e.target) && !menuBtn.contains(e.target)) {
          sidebar.classList.remove('open');
        }
      }
    });
  }
});
