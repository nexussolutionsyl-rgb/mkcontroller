/**
 * nexusMK - Controlador de página
 * Dashboard de gestión MikroTik desde MySQL
 */
const NexusMK = {
  /**
   * Inicializa la página
   */
  async init() {
    console.log('[nexusMK] Inicializando...');
    await this.loadStats();
    await this.loadDispositivos();
    await this.loadDbInfo();
    await this.checkHealth();
  },

  /**
   * Refresca todos los datos
   */
  async refresh() {
    document.getElementById('nexusmk-stats').classList.add('loading');
    document.getElementById('nexusmk-dispositivos').innerHTML = '<div class="loading-state">Actualizando...</div>';
    await this.loadStats();
    await this.loadDispositivos();
    await this.loadDbInfo();
    await this.checkHealth();
    document.getElementById('nexusmk-stats').classList.remove('loading');
    App.showToast('Datos actualizados', 'success');
  },

  /**
   * Carga las estadísticas
   */
  async loadStats() {
    try {
      const result = await API.get('/nexusmk/stats');
      if (result.success) {
        document.getElementById('stat-dispositivos').textContent = result.data.total_dispositivos;
        document.getElementById('stat-interfaces').textContent = result.data.total_interfaces;
        document.getElementById('stat-peers').textContent = result.data.total_peers;
        document.getElementById('stat-reglas').textContent = result.data.total_reglas;
      }
    } catch (error) {
      console.error('[nexusMK] Error cargando stats:', error);
    }
  },

  /**
   * Carga la lista de dispositivos
   */
  async loadDispositivos() {
    try {
      const result = await API.get('/nexusmk/dispositivos');
      const container = document.getElementById('nexusmk-dispositivos');

      if (!result.success || !result.data || result.data.length === 0) {
        container.innerHTML = '<div class="empty-state">No hay dispositivos registrados</div>';
        return;
      }

      container.innerHTML = result.data.map(dev => {
        const tipoClass = {
          'chr': 'badge-chr',
          'routerboard': 'badge-rb',
          'switch': 'badge-switch',
          'otros': 'badge-otros'
        }[dev.tipo] || 'badge-otros';

        const tipoLabel = {
          'chr': 'CHR',
          'routerboard': 'RouterBoard',
          'switch': 'Switch',
          'otros': 'Otros'
        }[dev.tipo] || dev.tipo;

        return `
          <div class="device-card">
            <div class="device-header">
              <span class="device-type-badge ${tipoClass}">${tipoLabel}</span>
              <span class="device-status ${dev.estado == 1 ? 'active' : 'inactive'}">
                ${dev.estado == 1 ? 'Activo' : 'Inactivo'}
              </span>
            </div>
            <div class="device-name">${dev.nombre}</div>
            <div class="device-detail">
              <span class="detail-label">IP:</span>
              <span class="detail-value">${dev.ip_wan || '-'}</span>
            </div>
            <div class="device-detail">
              <span class="detail-label">Modelo:</span>
              <span class="detail-value">${dev.modelo || '-'}</span>
            </div>
            <div class="device-detail">
              <span class="detail-label">RouterOS:</span>
              <span class="detail-value">${dev.version_routeros || '-'}</span>
            </div>
            <div class="device-detail">
              <span class="detail-label">Licencia:</span>
              <span class="detail-value">${dev.licencia || '-'}</span>
            </div>
          </div>
        `;
      }).join('');
    } catch (error) {
      console.error('[nexusMK] Error cargando dispositivos:', error);
      document.getElementById('nexusmk-dispositivos').innerHTML =
        '<div class="error-state">Error al cargar dispositivos</div>';
    }
  },

  /**
   * Carga información de las tablas de la BD
   */
  async loadDbInfo() {
    try {
      const result = await API.get('/nexusmk/dbinfo');
      const tbody = document.getElementById('nexusmk-dbinfo');

      if (!result.success || !result.data) {
        tbody.innerHTML = '<tr><td colspan="2" class="text-center error-text">Error al cargar</td></tr>';
        return;
      }

      tbody.innerHTML = result.data.map(row => `
        <tr>
          <td><code>${row.tabla}</code></td>
          <td><strong>${row.registros}</strong> registros</td>
        </tr>
      `).join('');
    } catch (error) {
      console.error('[nexusMK] Error cargando dbinfo:', error);
    }
  },

  /**
   * Verifica el estado de la conexión MySQL
   */
  async checkHealth() {
    try {
      const result = await API.get('/nexusmk/health');
      const statusDiv = document.getElementById('nexusmk-status');

      if (result.success) {
        statusDiv.innerHTML = `
          <div class="status-indicator connected">
            <span class="status-dot green"></span>
            MySQL conectado - Base de datos: <strong>nexusmk</strong>
          </div>
        `;
      } else {
        statusDiv.innerHTML = `
          <div class="status-indicator error">
            <span class="status-dot red"></span>
            Error: ${result.message || 'No se puede conectar a MySQL'}
          </div>
        `;
      }
    } catch (error) {
      document.getElementById('nexusmk-status').innerHTML = `
        <div class="status-indicator error">
          <span class="status-dot red"></span>
          Error de conexión: ${error.message}
        </div>
      `;
    }
  }
};
