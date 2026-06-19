/**
 * Routers - Controlador de gestión de routers
 */
let routersData = [];
let deleteTargetId = null;

document.addEventListener('DOMContentLoaded', async () => {
  await loadRouters();
  await loadClientsForSelect();
});

/**
 * Carga la lista de routers
 */
async function loadRouters() {
  const result = await API.get('/routers');
  
  if (!result.success) {
    document.getElementById('routers-table-body').innerHTML = `
      <tr><td colspan="7" class="empty-state"><p>Error cargando routers</p></td></tr>
    `;
    return;
  }

  routersData = result.data;
  renderRouters(routersData);
}

/**
 * Renderiza la tabla de routers
 */
function renderRouters(routers) {
  const tbody = document.getElementById('routers-table-body');

  if (routers.length === 0) {
    tbody.innerHTML = `
      <tr><td colspan="7">
        <div class="empty-state">
          <div class="empty-icon">🌐</div>
          <h3>No hay routers</h3>
          <p>Agregue su primer router para comenzar</p>
          <button class="btn btn-primary" onclick="showAddRouterModal()">+ Agregar Router</button>
        </div>
      </td></tr>
    `;
    return;
  }

  tbody.innerHTML = routers.map(router => {
    const statusClass = router.status === 'online' ? 'success' : router.status === 'offline' ? 'danger' : 'secondary';
    const statusDot = router.status === 'online' ? 'online' : router.status === 'offline' ? 'offline' : 'unknown';

    return `
      <tr>
        <td>
          <span class="status-dot ${statusDot}"></span>
          <span class="badge badge-${statusClass}">${router.status}</span>
        </td>
        <td><strong>${escapeHtml(router.name)}</strong></td>
        <td>${escapeHtml(router.host)}</td>
        <td>${router.port || 8728}</td>
        <td>${router.clientId ? router.clientId.slice(0, 8) + '...' : '-'}</td>
        <td>${router.lastSeen ? App.formatDate(router.lastSeen) : 'Nunca'}</td>
        <td>
          <button class="btn btn-sm btn-primary" onclick="viewRouter('${router.id}')" title="Ver detalles">👁️</button>
          <button class="btn btn-sm btn-secondary" onclick="editRouter('${router.id}')" title="Editar">✏️</button>
          <button class="btn btn-sm btn-secondary" onclick="testRouter('${router.id}')" title="Probar conexión">🔌</button>
          <button class="btn btn-sm btn-danger" onclick="showDeleteConfirm('${router.id}', '${escapeHtml(router.name)}')" title="Eliminar">🗑️</button>
        </td>
      </tr>
    `;
  }).join('');
}

/**
 * Filtra routers por búsqueda
 */
function filterRouters() {
  const query = document.getElementById('search-router').value.toLowerCase();
  const filtered = routersData.filter(r => 
    r.name.toLowerCase().includes(query) || 
    r.host.toLowerCase().includes(query)
  );
  renderRouters(filtered);
}

/**
 * Muestra modal para agregar router
 */
function showAddRouterModal() {
  document.getElementById('router-modal-title').textContent = 'Agregar Router';
  document.getElementById('router-form').reset();
  document.getElementById('router-id').value = '';
  document.getElementById('router-password').required = true;
  openModal('router-modal');
}

/**
 * Edita un router
 */
async function editRouter(id) {
  const result = await API.get(`/routers/${id}`);
  if (!result.success) {
    App.toast('Error obteniendo router', 'error');
    return;
  }

  const router = result.data;
  document.getElementById('router-modal-title').textContent = 'Editar Router';
  document.getElementById('router-id').value = router.id;
  document.getElementById('router-name').value = router.name;
  document.getElementById('router-host').value = router.host;
  document.getElementById('router-port').value = router.port || 8728;
  document.getElementById('router-username').value = router.username;
  document.getElementById('router-password').required = false;
  document.getElementById('router-password').value = '';
  document.getElementById('router-password').placeholder = 'Dejar vacío para mantener';
  document.getElementById('router-comment').value = router.comment || '';
  
  if (document.getElementById('router-client')) {
    document.getElementById('router-client').value = router.clientId || '';
  }

  openModal('router-modal');
}

/**
 * Guarda router (crear o actualizar)
 */
async function saveRouter() {
  const id = document.getElementById('router-id').value;
  const data = {
    name: document.getElementById('router-name').value.trim(),
    host: document.getElementById('router-host').value.trim(),
    port: parseInt(document.getElementById('router-port').value) || 8728,
    username: document.getElementById('router-username').value.trim(),
    comment: document.getElementById('router-comment').value.trim()
  };

  const password = document.getElementById('router-password').value;
  if (password) data.password = password;

  const clientSelect = document.getElementById('router-client');
  if (clientSelect) {
    data.clientId = clientSelect.value || null;
  }

  if (!data.name || !data.host || !data.username) {
    App.toast('Complete los campos requeridos', 'warning');
    return;
  }

  let result;
  if (id) {
    result = await API.put(`/routers/${id}`, data);
  } else {
    if (!password) {
      App.toast('La contraseña es requerida', 'warning');
      return;
    }
    result = await API.post('/routers', data);
  }

  if (result.success) {
    App.toast(id ? 'Router actualizado' : 'Router agregado', 'success');
    closeModal('router-modal');
    await loadRouters();
  } else {
    App.toast(result.message || 'Error guardando router', 'error');
  }
}

/**
 * Prueba conexión con un router
 */
async function testRouter(id) {
  App.toast('Probando conexión...', 'info');
  
  const result = await API.post(`/routers/${id}/test`);
  
  if (result.success && result.data.connected) {
    App.toast(`✅ Conexión exitosa con ${result.data.identity}`, 'success');
  } else {
    App.toast(`❌ Error: ${result.data?.error || 'No se pudo conectar'}`, 'error');
  }
  
  await loadRouters();
}

/**
 * Muestra confirmación de eliminación
 */
function showDeleteConfirm(id, name) {
  deleteTargetId = id;
  document.getElementById('confirm-message').textContent = `¿Está seguro de eliminar el router "${name}"?`;
  openModal('confirm-modal');
}

/**
 * Confirma eliminación
 */
async function confirmDelete() {
  if (!deleteTargetId) return;

  const result = await API.delete(`/routers/${deleteTargetId}`);
  
  if (result.success) {
    App.toast('Router eliminado', 'success');
    closeModal('confirm-modal');
    deleteTargetId = null;
    await loadRouters();
  } else {
    App.toast(result.message || 'Error eliminando router', 'error');
  }
}

/**
 * Ver detalles del router
 */
async function viewRouter(id) {
  openModal('detail-modal');
  document.getElementById('detail-title').textContent = 'Cargando...';
  document.getElementById('detail-body').innerHTML = '<div class="loading"><div class="spinner"></div></div>';

  const result = await API.get(`/routers/${id}`);
  
  if (!result.success) {
    document.getElementById('detail-body').innerHTML = '<p class="empty-state">Error cargando detalles</p>';
    return;
  }

  const router = result.data;
  document.getElementById('detail-title').textContent = `Router: ${router.name}`;

  const statusDot = router.status === 'online' ? 'online' : router.status === 'offline' ? 'offline' : 'unknown';
  const statusText = router.status === 'online' ? 'success' : router.status === 'offline' ? 'danger' : 'secondary';

  document.getElementById('detail-body').innerHTML = `
    <div class="info-grid">
      <div class="info-item">
        <div class="info-label">Estado</div>
        <div class="info-value">
          <span class="status-dot ${statusDot}"></span>
          <span class="badge badge-${statusText}">${router.status}</span>
        </div>
      </div>
      <div class="info-item">
        <div class="info-label">Nombre</div>
        <div class="info-value">${escapeHtml(router.name)}</div>
      </div>
      <div class="info-item">
        <div class="info-label">Host</div>
        <div class="info-value">${escapeHtml(router.host)}</div>
      </div>
      <div class="info-item">
        <div class="info-label">Puerto</div>
        <div class="info-value">${router.port || 8728}</div>
      </div>
      <div class="info-item">
        <div class="info-label">Usuario</div>
        <div class="info-value">${escapeHtml(router.username)}</div>
      </div>
      <div class="info-item">
        <div class="info-label">Última Conexión</div>
        <div class="info-value">${router.lastSeen ? App.formatDate(router.lastSeen) : 'Nunca'}</div>
      </div>
      <div class="info-item">
        <div class="info-label">Creado</div>
        <div class="info-value">${App.formatDate(router.createdAt)}</div>
      </div>
      <div class="info-item">
        <div class="info-label">Comentario</div>
        <div class="info-value">${router.comment || '-'}</div>
      </div>
    </div>
    <div style="margin-top:16px;display:flex;gap:8px;">
      <button class="btn btn-sm btn-primary" onclick="testRouter('${router.id}')">🔌 Probar Conexión</button>
      <button class="btn btn-sm btn-secondary" onclick="window.location.hash='winbox'">🔗 WinBox</button>
    </div>
  `;
}

/**
 * Carga clientes para el select
 */
async function loadClientsForSelect() {
  const user = API.getCurrentUser();
  const select = document.getElementById('router-client');
  
  if (!select) return;

  // Si no es superadmin, ocultar el campo de cliente
  if (user.role !== 'superadmin') {
    document.getElementById('router-client-group').style.display = 'none';
    return;
  }

  const result = await API.get('/clients');
  if (result.success) {
    select.innerHTML = `
      <option value="">Seleccione un cliente...</option>
      ${result.data.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('')}
    `;
  }
}

/**
 * Abre un modal
 */
function openModal(id) {
  document.getElementById(id).classList.add('show');
}

/**
 * Cierra un modal
 */
function closeModal(id) {
  document.getElementById(id).classList.remove('show');
}

/**
 * Escapa HTML
 */
function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
