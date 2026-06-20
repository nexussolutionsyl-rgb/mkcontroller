/**
 * ISP Manager - Controlador de página
 * Panel de gestión centralizada ISP con RouterOS REST API v7
 */
const ISP = {
  currentTab: 'ppp-profiles',

  /**
   * Inicializa la página
   */
  async init() {
    console.log('[ISP] Inicializando...');
    await this.loadStats();
    await this.loadPPPProfiles();
    await this.loadHotspotProfiles();
    await this.loadIPPools();
    await this.loadPlans();
    await this.loadSystemInfo();
    await this.loadInterfaces();
  },

  /**
   * Refresca todos los datos
   */
  async refresh() {
    document.getElementById('isp-stats').classList.add('loading');
    await this.loadStats();
    await this.loadPPPProfiles();
    await this.loadHotspotProfiles();
    await this.loadIPPools();
    await this.loadPlans();
    await this.loadSystemInfo();
    await this.loadInterfaces();
    document.getElementById('isp-stats').classList.remove('loading');
    App.toast('Datos actualizados', 'success');
  },

  /**
   * Cambia de tab
   */
  switchTab(tabId) {
    this.currentTab = tabId;
    document.querySelectorAll('.tab-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.tab === tabId);
    });
    document.querySelectorAll('.tab-content').forEach(content => {
      content.classList.toggle('active', content.id === `tab-${tabId}`);
    });
  },

  // ==========================================================
  // STATS
  // ==========================================================

  async loadStats() {
    try {
      const result = await API.get('/isp/stats');
      if (result.success) {
        const d = result.data;
        document.getElementById('stat-clientes').textContent = d.database?.clients?.total || 0;
        document.getElementById('stat-planes').textContent = d.database?.plans?.total || 0;
        document.getElementById('stat-ppp-active').textContent = d.router?.pppActive || 0;
        document.getElementById('stat-hotspot-active').textContent = d.router?.hotspotActive || 0;
      }
    } catch (error) {
      console.error('[ISP] Error cargando stats:', error);
    }
  },

  // ==========================================================
  // PPP PROFILES
  // ==========================================================

  async loadPPPProfiles() {
    try {
      const result = await API.get('/isp/ppp/profiles');
      const tbody = document.getElementById('isp-ppp-profiles-body');
      if (result.success && result.data) {
        tbody.innerHTML = result.data.map(p => `
          <tr>
            <td><strong>${p.name || 'N/A'}</strong></td>
            <td>${p['rate-limit'] || '-'}</td>
            <td>${p['local-address'] || '-'}</td>
            <td>${p['remote-address'] || '-'}</td>
            <td>${p['only-one'] === 'yes' ? '✅ Sí' : '❌ No'}</td>
            <td>${p.comment || '-'}</td>
            <td class="actions-cell">
              <button class="btn btn-sm btn-danger" onclick="ISP.confirmDelete('ppp/profile/${encodeURIComponent(p.name)}', '${p.name}')">🗑️</button>
            </td>
          </tr>
        `).join('');
      } else {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Sin perfiles PPP</td></tr>';
      }
    } catch (error) {
      document.getElementById('isp-ppp-profiles-body').innerHTML = 
        `<tr><td colspan="7" class="text-center error">Error: ${error.message}</td></tr>`;
    }
  },

  // ==========================================================
  // HOTSPOT PROFILES
  // ==========================================================

  async loadHotspotProfiles() {
    try {
      const result = await API.get('/isp/hotspot/profiles');
      const tbody = document.getElementById('isp-hotspot-profiles-body');
      if (result.success && result.data) {
        tbody.innerHTML = result.data.map(p => `
          <tr>
            <td><strong>${p.name || 'N/A'}</strong></td>
            <td>${p['rate-limit'] || '-'}</td>
            <td>${p['shared-users'] || '1'}</td>
            <td>${p['session-timeout'] || '-'}</td>
            <td>${p['idle-timeout'] || '-'}</td>
            <td>${p.comment || '-'}</td>
            <td class="actions-cell">
              <button class="btn btn-sm btn-danger" onclick="ISP.confirmDelete('hotspot/profile/${encodeURIComponent(p.name)}', '${p.name}')">🗑️</button>
            </td>
          </tr>
        `).join('');
      } else {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Sin perfiles Hotspot</td></tr>';
      }
    } catch (error) {
      document.getElementById('isp-hotspot-profiles-body').innerHTML = 
        `<tr><td colspan="7" class="text-center error">Error: ${error.message}</td></tr>`;
    }
  },

  // ==========================================================
  // IP POOLS
  // ==========================================================

  async loadIPPools() {
    try {
      const result = await API.get('/isp/pools');
      const tbody = document.getElementById('isp-pools-body');
      if (result.success && result.data) {
        tbody.innerHTML = result.data.map(p => `
          <tr>
            <td><strong>${p.name || 'N/A'}</strong></td>
            <td>${p.ranges || '-'}</td>
            <td>${p.comment || '-'}</td>
            <td class="actions-cell">
              <button class="btn btn-sm btn-danger" onclick="ISP.confirmDelete('pool/${encodeURIComponent(p.name)}', '${p.name}')">🗑️</button>
            </td>
          </tr>
        `).join('');
      } else {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">Sin IP Pools</td></tr>';
      }
    } catch (error) {
      document.getElementById('isp-pools-body').innerHTML = 
        `<tr><td colspan="4" class="text-center error">Error: ${error.message}</td></tr>`;
    }
  },

  // ==========================================================
  // PLANS (BD)
  // ==========================================================

  async loadPlans() {
    try {
      const result = await API.get('/isp/plans');
      const tbody = document.getElementById('isp-plans-body');
      if (result.success && result.data) {
        tbody.innerHTML = result.data.map(p => `
          <tr>
            <td><strong>${p.name}</strong></td>
            <td><span class="badge badge-${p.service}">${p.service}</span></td>
            <td>${p.speed_limit || '-'}</td>
            <td>${p.session_timeout || '-'}</td>
            <td>${p.shared_users}</td>
            <td>${p.price ? '$' + parseFloat(p.price).toFixed(2) : '-'}</td>
            <td><span class="badge badge-${p.sync_status}">${p.sync_status}</span></td>
            <td class="actions-cell">
              <button class="btn btn-sm btn-danger" onclick="ISP.confirmDeletePlan('${p.id}', '${p.name}')">🗑️</button>
            </td>
          </tr>
        `).join('');
      } else {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">Sin planes registrados</td></tr>';
      }
    } catch (error) {
      document.getElementById('isp-plans-body').innerHTML = 
        `<tr><td colspan="8" class="text-center error">Error: ${error.message}</td></tr>`;
    }
  },

  // ==========================================================
  // SYSTEM INFO
  // ==========================================================

  async loadSystemInfo() {
    try {
      const result = await API.get('/isp/system/info');
      if (result.success && result.data) {
        const d = result.data;
        document.getElementById('sys-identity').textContent = d.identity || '-';
        document.getElementById('sys-version').textContent = d.version || '-';
        document.getElementById('sys-cpu').textContent = d.cpuLoad ? `${d.cpuLoad}%` : '-';
        document.getElementById('sys-uptime').textContent = d.uptime || '-';
      }
    } catch (error) {
      console.error('[ISP] Error cargando system info:', error);
    }
  },

  async loadInterfaces() {
    try {
      const result = await API.get('/isp/system/interfaces');
      const tbody = document.getElementById('isp-interfaces-body');
      if (result.success && result.data) {
        tbody.innerHTML = result.data.map(iface => `
          <tr>
            <td><strong>${iface.name || 'N/A'}</strong></td>
            <td>${iface.type || '-'}</td>
            <td>${iface['mac-address'] || '-'}</td>
            <td>
              <span class="status-indicator ${iface.running === 'true' ? 'online' : 'offline'}">
                ${iface.running === 'true' ? '🟢 Activo' : '🔴 Inactivo'}
              </span>
            </td>
            <td>${iface.comment || '-'}</td>
          </tr>
        `).join('');
      } else {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">Sin interfaces</td></tr>';
      }
    } catch (error) {
      document.getElementById('isp-interfaces-body').innerHTML = 
        `<tr><td colspan="5" class="text-center error">Error: ${error.message}</td></tr>`;
    }
  },

  // ==========================================================
  // SINCRONIZACIÓN
  // ==========================================================

  async syncProfiles(type) {
    const endpoints = {
      ppp: '/isp/ppp/profiles/sync',
      hotspot: '/isp/hotspot/profiles/sync',
      pools: '/isp/pools/sync'
    };

    const endpoint = endpoints[type];
    if (!endpoint) return;

    try {
      App.toast('Sincronizando...', 'info');
      const result = await API.post(endpoint);
      if (result.success) {
        App.toast(result.message, 'success');
        // Recargar según tipo
        if (type === 'ppp') await this.loadPPPProfiles();
        else if (type === 'hotspot') await this.loadHotspotProfiles();
        else if (type === 'pools') await this.loadIPPools();
        await this.loadPlans();
      } else {
        App.toast(result.message || 'Error en sincronización', 'error');
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  // ==========================================================
  // MODALES - Crear Perfil
  // ==========================================================

  showCreateProfileModal(type) {
    document.getElementById('profile-type').value = type;
    document.getElementById('isp-profile-modal-title').textContent = 
      type === 'ppp' ? 'Nuevo Perfil PPP' : 'Nuevo Perfil Hotspot';
    
    // Mostrar/ocultar campos específicos
    document.getElementById('ppp-fields').style.display = type === 'ppp' ? 'block' : 'none';
    document.getElementById('hotspot-fields').style.display = type === 'hotspot' ? 'block' : 'none';
    
    // Limpiar formulario
    document.getElementById('isp-profile-form').reset();
    
    document.getElementById('isp-profile-modal').style.display = 'flex';
  },

  async saveProfile(event) {
    event.preventDefault();
    const type = document.getElementById('profile-type').value;
    const name = document.getElementById('profile-name').value;
    const rateLimit = document.getElementById('profile-rate-limit').value;
    const comment = document.getElementById('profile-comment').value;

    let endpoint, data;

    if (type === 'ppp') {
      endpoint = '/isp/ppp/profiles';
      data = {
        name,
        rateLimit: rateLimit || undefined,
        localAddress: document.getElementById('profile-local-address').value || undefined,
        remoteAddress: document.getElementById('profile-remote-address').value || undefined,
        onlyOne: document.getElementById('profile-only-one').checked,
        comment: comment || undefined
      };
    } else {
      endpoint = '/isp/hotspot/profiles';
      data = {
        name,
        rateLimit: rateLimit || undefined,
        sharedUsers: parseInt(document.getElementById('profile-shared-users').value) || 1,
        sessionTimeout: document.getElementById('profile-session-timeout').value || undefined,
        idleTimeout: document.getElementById('profile-idle-timeout').value || undefined,
        comment: comment || undefined
      };
    }

    try {
      const result = await API.post(endpoint, data);
      if (result.success) {
        App.toast(`Perfil ${name} creado`, 'success');
        this.closeModal('isp-profile-modal');
        if (type === 'ppp') await this.loadPPPProfiles();
        else await this.loadHotspotProfiles();
        await this.loadPlans();
      } else {
        App.toast(result.message || 'Error al crear perfil', 'error');
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  // ==========================================================
  // MODALES - Crear Pool
  // ==========================================================

  showCreatePoolModal() {
    document.getElementById('isp-pool-form').reset();
    document.getElementById('isp-pool-modal').style.display = 'flex';
  },

  async savePool(event) {
    event.preventDefault();
    const name = document.getElementById('pool-name').value;
    const ranges = document.getElementById('pool-ranges').value;
    const comment = document.getElementById('pool-comment').value;

    try {
      const result = await API.post('/isp/pools', { name, ranges, comment: comment || undefined });
      if (result.success) {
        App.toast(`Pool ${name} creado`, 'success');
        this.closeModal('isp-pool-modal');
        await this.loadIPPools();
        await this.loadPlans();
      } else {
        App.toast(result.message || 'Error al crear pool', 'error');
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  // ==========================================================
  // MODALES - Crear Plan
  // ==========================================================

  showCreatePlanModal() {
    document.getElementById('isp-plan-form').reset();
    document.getElementById('isp-plan-modal').style.display = 'flex';
  },

  async savePlan(event) {
    event.preventDefault();
    const data = {
      name: document.getElementById('plan-name').value,
      service: document.getElementById('plan-service').value,
      speedLimit: document.getElementById('plan-speed-limit').value || undefined,
      sessionTimeout: document.getElementById('plan-session-timeout').value || undefined,
      sharedUsers: parseInt(document.getElementById('plan-shared-users').value) || 1,
      price: document.getElementById('plan-price').value ? parseFloat(document.getElementById('plan-price').value) : undefined,
      comment: document.getElementById('plan-comment').value || undefined
    };

    try {
      const result = await API.post('/isp/plans', data);
      if (result.success) {
        App.toast(`Plan ${data.name} creado`, 'success');
        this.closeModal('isp-plan-modal');
        await this.loadPlans();
      } else {
        App.toast(result.message || 'Error al crear plan', 'error');
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  // ==========================================================
  // ELIMINAR
  // ==========================================================

  async confirmDelete(endpoint, name) {
    if (!confirm(`¿Eliminar "${name}"? Esta acción no se puede deshacer.`)) return;
    
    try {
      const result = await API.delete(`/isp/${endpoint}`);
      if (result.success) {
        App.toast(`"${name}" eliminado`, 'success');
        await this.refresh();
      } else {
        App.toast(result.message || 'Error al eliminar', 'error');
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  async confirmDeletePlan(id, name) {
    if (!confirm(`¿Eliminar el plan "${name}"?`)) return;
    
    try {
      const result = await API.delete(`/isp/plans/${id}`);
      if (result.success) {
        App.toast(`Plan "${name}" eliminado`, 'success');
        await this.loadPlans();
      } else {
        App.toast(result.message || 'Error al eliminar', 'error');
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  // ==========================================================
  // UTILIDADES
  // ==========================================================

  closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
  }
};
