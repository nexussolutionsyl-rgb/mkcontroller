/**
 * ISP Manager - Controlador de página
 * Panel de gestión centralizada ISP con RouterOS REST API v7
 */
const ISP = {
  currentTab: 'ppp-profiles',
  clientsPage: 0,
  clientsPageSize: 50,
  clientsTotal: 0,
  clientsData: [],

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
    await this.loadRouters();
    await this.loadSystemInfo();
    await this.loadInterfaces();
    await this.loadClients();
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
    await this.loadClients();
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
  // CLIENTES
  // ==========================================================

  async loadClients() {
    try {
      const service = document.getElementById('client-service-filter')?.value || 'all';
      const status = document.getElementById('client-status-filter')?.value || 'all';
      const search = document.getElementById('client-search-input')?.value || '';

      let endpoint = '/isp/clients/db?';
      const params = [];
      if (service !== 'all') params.push(`service=${service}`);
      if (status === 'active') params.push('disabled=0');
      else if (status === 'disabled') params.push('disabled=1');
      if (search) params.push(`search=${encodeURIComponent(search)}`);
      params.push(`limit=${this.clientsPageSize}`);
      params.push(`offset=${this.clientsPage * this.clientsPageSize}`);
      endpoint += params.join('&');

      const result = await API.get(endpoint);
      const tbody = document.getElementById('isp-clients-body');
      const pagination = document.getElementById('isp-clients-pagination');
      const pageInfo = document.getElementById('isp-clients-page-info');

      if (result.success && result.data) {
        this.clientsData = result.data;
        this.clientsTotal = result.pagination?.total || result.data.length;

        if (result.data.length === 0) {
          tbody.innerHTML = '<tr><td colspan="9" class="text-center">Sin clientes registrados</td></tr>';
          pagination.style.display = 'none';
          return;
        }

        tbody.innerHTML = result.data.map(c => `
          <tr>
            <td><strong>${c.username || 'N/A'}</strong></td>
            <td><span class="badge badge-${c.service}">${c.service || '-'}</span></td>
            <td>${c.profile || '-'}</td>
            <td>${c.ip_address || '-'}</td>
            <td>${c.plan_id ? c.plan_id.substring(0, 8) + '...' : '-'}</td>
            <td>
              <span class="status-indicator ${c.disabled == 0 ? 'online' : 'offline'}">
                ${c.disabled == 0 ? '🟢 Activo' : '🔴 Deshabilitado'}
              </span>
            </td>
            <td>
              <span class="badge badge-${c.sync_status}">${c.sync_status}</span>
            </td>
            <td>${c.comment || '-'}</td>
            <td class="actions-cell">
              <button class="btn btn-sm btn-outline" onclick="ISP.editClient('${c.username}', '${c.service}')" title="Editar">✏️</button>
              ${c.disabled == 0
                ? `<button class="btn btn-sm btn-warning" onclick="ISP.toggleClientStatus('${c.username}', '${c.service}', 'disable')" title="Deshabilitar">🔌</button>`
                : `<button class="btn btn-sm btn-success" onclick="ISP.toggleClientStatus('${c.username}', '${c.service}', 'enable')" title="Habilitar">✅</button>`
              }
              <button class="btn btn-sm btn-outline" onclick="ISP.syncOneClient('${c.username}', '${c.service}')" title="Sincronizar">🔄</button>
              <button class="btn btn-sm btn-danger" onclick="ISP.confirmDeleteClient('${c.username}', '${c.service}')" title="Eliminar">🗑️</button>
            </td>
          </tr>
        `).join('');

        // Paginación
        const totalPages = Math.ceil(this.clientsTotal / this.clientsPageSize);
        if (totalPages > 1) {
          pagination.style.display = 'flex';
          pageInfo.textContent = `Página ${this.clientsPage + 1} de ${totalPages} (${this.clientsTotal} total)`;
        } else {
          pagination.style.display = 'none';
        }
      } else {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center error">Error al cargar clientes</td></tr>';
      }
    } catch (error) {
      document.getElementById('isp-clients-body').innerHTML =
        `<tr><td colspan="9" class="text-center error">Error: ${error.message}</td></tr>`;
    }
  },

  searchClients(event) {
    if (event.key === 'Enter') {
      this.clientsPage = 0;
      this.loadClients();
    }
  },

  prevPage() {
    if (this.clientsPage > 0) {
      this.clientsPage--;
      this.loadClients();
    }
  },

  nextPage() {
    const totalPages = Math.ceil(this.clientsTotal / this.clientsPageSize);
    if (this.clientsPage < totalPages - 1) {
      this.clientsPage++;
      this.loadClients();
    }
  },

  async syncClients() {
    const service = document.getElementById('client-service-filter')?.value || 'all';
    try {
      App.toast('Sincronizando clientes...', 'info');
      let result;
      if (service === 'hotspot') {
        result = await API.post('/isp/hotspot/users/sync');
      } else {
        result = await API.post('/isp/clients/sync');
      }
      if (result.success) {
        App.toast(result.message, 'success');
        await this.loadClients();
        await this.loadStats();
      } else {
        App.toast(result.message || 'Error en sincronización', 'error');
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  async syncOneClient(username, service) {
    try {
      App.toast(`Sincronizando ${username}...`, 'info');
      const result = await API.post(`/isp/clients/${encodeURIComponent(username)}/sync-one`, { service });
      if (result.success) {
        App.toast(`Cliente ${username} sincronizado`, 'success');
        await this.loadClients();
      } else {
        App.toast(result.message || 'Error', 'error');
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  showCreateClientModal() {
    document.getElementById('isp-client-modal-title').textContent = 'Nuevo Cliente';
    document.getElementById('client-edit-mode').value = '0';
    document.getElementById('client-original-username').value = '';
    document.getElementById('isp-client-form').reset();
    document.getElementById('client-password').required = true;
    this.loadProfilesForSelect();
    this.toggleClientFields();
    document.getElementById('isp-client-modal').style.display = 'flex';
  },

  async editClient(username, service) {
    document.getElementById('isp-client-modal-title').textContent = `Editar: ${username}`;
    document.getElementById('client-edit-mode').value = '1';
    document.getElementById('client-original-username').value = username;
    document.getElementById('client-username').value = username;
    document.getElementById('client-username').readOnly = true;
    document.getElementById('client-password').required = false;

    // Cargar datos del cliente desde BD
    try {
      const result = await API.get(`/isp/clients/db?search=${encodeURIComponent(username)}&limit=1`);
      if (result.success && result.data && result.data.length > 0) {
        const c = result.data[0];
        document.getElementById('client-service').value = c.service || 'pppoe';
        document.getElementById('client-ip-address').value = c.ip_address || '';
        document.getElementById('client-comment').value = c.comment || '';
        document.getElementById('client-password').value = c.password || '';
      }
    } catch (e) {
      console.error('Error cargando datos del cliente:', e);
    }

    this.loadProfilesForSelect(service);
    this.toggleClientFields();
    document.getElementById('isp-client-modal').style.display = 'flex';
  },

  async loadProfilesForSelect(serviceType) {
    const select = document.getElementById('client-profile');
    const service = serviceType || document.getElementById('client-service').value;
    select.innerHTML = '<option value="">Seleccionar perfil...</option>';

    try {
      const result = await API.get(`/isp/plans?service=${service}`);
      if (result.success && result.data) {
        result.data.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.routeros_name || p.name;
          opt.textContent = `${p.name}${p.speed_limit ? ' (' + p.speed_limit + ')' : ''}`;
          select.appendChild(opt);
        });
      }
    } catch (e) {
      console.error('Error cargando perfiles:', e);
    }
  },

  toggleClientFields() {
    const service = document.getElementById('client-service').value;
    document.getElementById('client-pppoe-fields').style.display = service === 'pppoe' ? 'block' : 'none';
    document.getElementById('client-hotspot-fields').style.display = service === 'hotspot' ? 'block' : 'none';
    this.loadProfilesForSelect(service);
  },

  async saveClient(event) {
    event.preventDefault();
    const isEdit = document.getElementById('client-edit-mode').value === '1';
    const username = document.getElementById('client-username').value;
    const password = document.getElementById('client-password').value;
    const service = document.getElementById('client-service').value;
    const profile = document.getElementById('client-profile').value;
    const ipAddress = document.getElementById('client-ip-address').value;
    const comment = document.getElementById('client-comment').value;

    try {
      let result;
      if (isEdit) {
        const originalUsername = document.getElementById('client-original-username').value;
        const data = { service, profile: profile || undefined, comment: comment || undefined };
        if (password) data.password = password;
        if (ipAddress) data.ipAddress = ipAddress;

        if (service === 'hotspot') {
          data.server = document.getElementById('client-hotspot-server').value || undefined;
          data.limitUptime = document.getElementById('client-limit-uptime').value || undefined;
          data.limitBytes = document.getElementById('client-limit-bytes').value || undefined;
          result = await API.put(`/isp/hotspot/users/${encodeURIComponent(originalUsername)}`, data);
        } else {
          result = await API.put(`/isp/clients/${encodeURIComponent(originalUsername)}`, data);
        }
      } else {
        const data = { username, password, service, profile: profile || undefined, comment: comment || undefined };
        if (ipAddress) data.ipAddress = ipAddress;

        if (service === 'hotspot') {
          data.server = document.getElementById('client-hotspot-server').value || undefined;
          data.limitUptime = document.getElementById('client-limit-uptime').value || undefined;
          data.limitBytes = document.getElementById('client-limit-bytes').value || undefined;
          result = await API.post('/isp/hotspot/users', data);
        } else {
          result = await API.post('/isp/clients', data);
        }
      }

      if (result.success) {
        App.toast(isEdit ? `Cliente ${username} actualizado` : `Cliente ${username} creado`, 'success');
        this.closeModal('isp-client-modal');
        await this.loadClients();
        await this.loadStats();
      } else {
        App.toast(result.message || 'Error al guardar cliente', 'error');
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  async toggleClientStatus(username, service, action) {
    const actionText = action === 'enable' ? 'habilitar' : 'deshabilitar';
    if (!confirm(`¿${actionText} al cliente "${username}"?`)) return;

    try {
      const endpoint = action === 'enable' ? 'enable' : 'disable';
      let result;
      if (service === 'hotspot') {
        // Hotspot enable/disable via update
        result = await API.put(`/isp/hotspot/users/${encodeURIComponent(username)}`, {
          disabled: action === 'disable'
        });
      } else {
        result = await API.post(`/isp/clients/${encodeURIComponent(username)}/${endpoint}`);
      }

      if (result.success) {
        App.toast(`Cliente ${username} ${actionText}do`, 'success');
        await this.loadClients();
      } else {
        App.toast(result.message || 'Error', 'error');
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  async confirmDeleteClient(username, service) {
    if (!confirm(`¿Eliminar al cliente "${username}"? Esta acción no se puede deshacer.`)) return;

    try {
      let result;
      if (service === 'hotspot') {
        result = await API.delete(`/isp/hotspot/users/${encodeURIComponent(username)}`);
      } else {
        result = await API.delete(`/isp/clients/${encodeURIComponent(username)}`);
      }

      if (result.success) {
        App.toast(`Cliente ${username} eliminado`, 'success');
        await this.loadClients();
        await this.loadStats();
      } else {
        App.toast(result.message || 'Error al eliminar', 'error');
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
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
  // GESTIÓN DE ROUTERS
  // ==========================================================

  /**
   * Cargar lista de routers registrados
   */
  async loadRouters() {
    try {
      const res = await API.get('/isp/routers');
      const tbody = document.getElementById('isp-routers-table-body');
      if (!res.success) {
        tbody.innerHTML = `<tr><td colspan="10" class="text-center">Error: ${res.message}</td></tr>`;
        return;
      }
      if (!res.data || res.data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="10" class="text-center">No hay routers registrados. Agrega uno o escanea la red.</td></tr>`;
        return;
      }
      tbody.innerHTML = res.data.map(r => `
        <tr>
          <td>${r.is_online ? '<span class="badge badge-success">🟢 Online</span>' : '<span class="badge badge-danger">🔴 Offline</span>'}</td>
          <td><strong>${r.name}</strong></td>
          <td>${r.host}</td>
          <td>${r.port}</td>
          <td>${r.identity || '-'}</td>
          <td>${r.version || '-'}</td>
          <td><span class="badge badge-info">${r.discovery_method}</span></td>
          <td>${r.is_active ? '<span class="badge badge-primary">✅ Activo</span>' : '<button class="btn btn-sm btn-outline" onclick="ISP.activateRouter(\'' + r.id + '\')">Activar</button>'}</td>
          <td>${r.last_connected_at ? new Date(r.last_connected_at).toLocaleString('es-ES') : 'Nunca'}</td>
          <td class="actions-cell">
            <button class="btn btn-sm btn-outline" onclick="ISP.testRouter('${r.id}')" title="Probar conexión">🔌</button>
            <button class="btn btn-sm btn-outline" onclick="ISP.editRouter('${r.id}')" title="Editar">✏️</button>
            <button class="btn btn-sm btn-danger" onclick="ISP.confirmDeleteRouter('${r.id}','${r.name}')" title="Eliminar">🗑️</button>
          </td>
        </tr>
      `).join('');
    } catch (error) {
      console.error('[ISP] Error cargando routers:', error);
    }
  },

  /**
   * Mostrar modal para agregar router
   */
  showAddRouterModal() {
    document.getElementById('router-modal-title').textContent = 'Agregar Router';
    document.getElementById('router-id').value = '';
    document.getElementById('isp-router-form').reset();
    document.getElementById('router-port').value = '443';
    document.getElementById('router-username').value = 'admin';
    document.getElementById('router-ssl').checked = true;
    document.getElementById('isp-router-modal').style.display = 'flex';
  },

  /**
   * Guardar router (crear o actualizar)
   */
  async saveRouter(event) {
    event.preventDefault();
    const id = document.getElementById('router-id').value;
    const data = {
      name: document.getElementById('router-name').value,
      host: document.getElementById('router-host').value,
      port: parseInt(document.getElementById('router-port').value) || 443,
      username: document.getElementById('router-username').value || 'admin',
      password: document.getElementById('router-password').value,
      ssl: document.getElementById('router-ssl').checked,
      api_port: parseInt(document.getElementById('router-api-port').value) || null,
      comment: document.getElementById('router-comment').value
    };

    try {
      const endpoint = id ? `/isp/routers/${id}` : '/isp/routers';
      const method = id ? 'put' : 'post';
      const res = await API[method](endpoint, data);
      if (res.success) {
        App.toast(res.message, 'success');
        this.closeModal('isp-router-modal');
        await this.loadRouters();
      } else {
        App.toast(res.message, 'error');
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  /**
   * Editar router
   */
  async editRouter(id) {
    try {
      const res = await API.get('/isp/routers');
      if (!res.success) return;
      const router = res.data.find(r => r.id === id);
      if (!router) return;

      document.getElementById('router-modal-title').textContent = 'Editar Router';
      document.getElementById('router-id').value = router.id;
      document.getElementById('router-name').value = router.name;
      document.getElementById('router-host').value = router.host;
      document.getElementById('router-port').value = router.port;
      document.getElementById('router-api-port').value = router.api_port || '';
      document.getElementById('router-username').value = router.username;
      document.getElementById('router-password').value = '';
      document.getElementById('router-ssl').checked = !!router.ssl;
      document.getElementById('router-comment').value = router.comment || '';
      document.getElementById('isp-router-modal').style.display = 'flex';
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  /**
   * Probar conexión con un router
   */
  async testRouter(id) {
    try {
      const res = await API.post(`/isp/routers/${id}/test`);
      if (res.success && res.data) {
        if (res.data.connected) {
          App.toast(`✅ Conectado a ${res.data.identity} (v${res.data.version})`, 'success');
        } else {
          App.toast(`❌ Error: ${res.data.error}`, 'error');
        }
        await this.loadRouters();
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  /**
   * Activar router como principal
   */
  async activateRouter(id) {
    try {
      const res = await API.post(`/isp/routers/${id}/activate`);
      if (res.success) {
        App.toast(res.message, 'success');
        await this.loadRouters();
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  /**
   * Confirmar y eliminar router
   */
  async confirmDeleteRouter(id, name) {
    if (!confirm(`¿Eliminar router "${name}"?`)) return;
    try {
      const res = await API.delete(`/isp/routers/${id}`);
      if (res.success) {
        App.toast(res.message, 'success');
        await this.loadRouters();
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    }
  },

  /**
   * Mostrar modal de escaneo de red
   */
  showScanModal() {
    document.getElementById('isp-scan-form').reset();
    document.getElementById('scan-subnet').value = '192.168.88.0/24';
    document.getElementById('scan-username').value = 'admin';
    document.getElementById('scan-timeout').value = '3000';
    document.getElementById('scan-results').style.display = 'none';
    document.getElementById('scan-btn').disabled = false;
    document.getElementById('scan-btn').textContent = '🔍 Iniciar Escaneo';
    document.getElementById('isp-scan-modal').style.display = 'flex';
  },

  /**
   * Iniciar escaneo de red
   */
  async scanNetwork(event) {
    event.preventDefault();
    const btn = document.getElementById('scan-btn');
    btn.disabled = true;
    btn.textContent = '⏳ Escaneando...';

    try {
      const res = await API.post('/isp/routers/scan', {
        subnet: document.getElementById('scan-subnet').value,
        username: document.getElementById('scan-username').value || 'admin',
        password: document.getElementById('scan-password').value,
        timeout: parseInt(document.getElementById('scan-timeout').value) || 3000
      });

      if (res.success) {
        App.toast('Escaneo iniciado. Los routers detectados se agregarán automáticamente.', 'success');
        // Esperar un momento y recargar
        setTimeout(() => {
          this.loadRouters();
          this.closeModal('isp-scan-modal');
        }, 2000);
      } else {
        App.toast(res.message, 'error');
      }
    } catch (error) {
      App.toast(`Error: ${error.message}`, 'error');
    } finally {
      btn.disabled = false;
      btn.textContent = '🔍 Iniciar Escaneo';
    }
  },

  // ==========================================================
  // UTILIDADES
  // ==========================================================

  closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
  }
};
