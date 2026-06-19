/**
 * Clientes - Controlador de gestión de clientes (SuperAdmin)
 */
let clientsData = [];

document.addEventListener('DOMContentLoaded', async () => {
  await loadClients();
});

/**
 * Carga la lista de clientes
 */
async function loadClients() {
  const result = await API.get('/clients');
  
  if (!result.success) {
    document.getElementById('clients-table-body').innerHTML = 
      '<tr><td colspan="8" class="empty-state"><p>Error cargando clientes</p></td></tr>';
    return;
  }

  clientsData = result.data;
  renderClients(clientsData);
}

/**
 * Renderiza la tabla de clientes
 */
function renderClients(clients) {
  const tbody = document.getElementById('clients-table-body');

  if (clients.length === 0) {
    tbody.innerHTML = `
      <tr><td colspan="8">
        <div class="empty-state">
          <div class="empty-icon">🏢</div>
          <h3>No hay clientes</h3>
          <p>Cree su primer cliente para comenzar</p>
          <button class="btn btn-primary" onclick="showAddClientModal()">+ Nuevo Cliente</button>
        </div>
      </td></tr>
    `;
    return;
  }

  tbody.innerHTML = clients.map(c => {
    const statusBadge = c.status === 'active' ? 'badge-success' : 'badge-danger';
    const statusText = c.status === 'active' ? 'Activo' : 'Inactivo';
    const planNames = { basic: 'Básico', professional: 'Profesional', enterprise: 'Empresarial' };

    return `
      <tr>
        <td><strong>${escapeHtml(c.name)}</strong></td>
        <td>${escapeHtml(c.company || '-')}</td>
        <td>${escapeHtml(c.email)}</td>
        <td><span class="badge badge-info">${planNames[c.plan] || c.plan}</span></td>
        <td>-</td>
        <td><span class="badge ${statusBadge}">${statusText}</span></td>
        <td>${App.formatDate(c.createdAt)}</td>
        <td>
          <button class="btn btn-sm btn-secondary" onclick="editClient('${c.id}')">✏️</button>
          <button class="btn btn-sm btn-danger" onclick="deleteClient('${c.id}', '${escapeHtml(c.name)}')">🗑️</button>
        </td>
      </tr>
    `;
  }).join('');
}

/**
 * Filtra clientes por búsqueda
 */
function filterClients() {
  const query = document.getElementById('search-client').value.toLowerCase();
  const filtered = clientsData.filter(c => 
    c.name.toLowerCase().includes(query) || 
    c.company.toLowerCase().includes(query) ||
    c.email.toLowerCase().includes(query)
  );
  renderClients(filtered);
}

/**
 * Muestra modal para agregar cliente
 */
function showAddClientModal() {
  document.getElementById('client-modal-title').textContent = 'Nuevo Cliente';
  document.getElementById('client-form').reset();
  document.getElementById('client-id').value = '';
  openModal('client-modal');
}

/**
 * Edita un cliente
 */
async function editClient(id) {
  const result = await API.get(`/clients/${id}`);
  if (!result.success) {
    App.toast('Error obteniendo cliente', 'error');
    return;
  }

  const c = result.data;
  document.getElementById('client-modal-title').textContent = 'Editar Cliente';
  document.getElementById('client-id').value = c.id;
  document.getElementById('client-name').value = c.name;
  document.getElementById('client-company').value = c.company || '';
  document.getElementById('client-email').value = c.email;
  document.getElementById('client-phone').value = c.phone || '';
  document.getElementById('client-address').value = c.address || '';
  document.getElementById('client-plan').value = c.plan || 'basic';
  document.getElementById('client-status').value = c.status || 'active';
  document.getElementById('client-notes').value = c.notes || '';

  openModal('client-modal');
}

/**
 * Guarda cliente (crear o actualizar)
 */
async function saveClient() {
  const id = document.getElementById('client-id').value;
  const data = {
    name: document.getElementById('client-name').value.trim(),
    company: document.getElementById('client-company').value.trim(),
    email: document.getElementById('client-email').value.trim(),
    phone: document.getElementById('client-phone').value.trim(),
    address: document.getElementById('client-address').value.trim(),
    plan: document.getElementById('client-plan').value,
    status: document.getElementById('client-status').value,
    notes: document.getElementById('client-notes').value.trim()
  };

  if (!data.name || !data.email) {
    App.toast('Nombre y email son requeridos', 'warning');
    return;
  }

  let result;
  if (id) {
    result = await API.put(`/clients/${id}`, data);
  } else {
    result = await API.post('/clients', data);
  }

  if (result.success) {
    App.toast(id ? 'Cliente actualizado' : 'Cliente creado', 'success');
    closeModal('client-modal');
    await loadClients();
  } else {
    App.toast(result.message || 'Error guardando cliente', 'error');
  }
}

/**
 * Elimina un cliente
 */
async function deleteClient(id, name) {
  if (!confirm(`¿Eliminar al cliente "${name}"?`)) return;

  const result = await API.delete(`/clients/${id}`);
  
  if (result.success) {
    App.toast('Cliente eliminado', 'success');
    await loadClients();
  } else {
    App.toast(result.message || 'Error eliminando cliente', 'error');
  }
}

function openModal(id) {
  document.getElementById(id).classList.add('show');
}

function closeModal(id) {
  document.getElementById(id).classList.remove('show');
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
