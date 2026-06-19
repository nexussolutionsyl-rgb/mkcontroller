/**
 * Usuarios - Controlador de gestión de usuarios
 */
let usersData = [];

document.addEventListener('DOMContentLoaded', async () => {
  await loadUsers();
  await loadClientsForUserSelect();
});

/**
 * Carga la lista de usuarios
 */
async function loadUsers() {
  const result = await API.get('/users');
  
  if (!result.success) {
    document.getElementById('users-table-body').innerHTML = 
      '<tr><td colspan="7" class="empty-state"><p>Error cargando usuarios</p></td></tr>';
    return;
  }

  usersData = result.data;
  renderUsers(usersData);
}

/**
 * Renderiza la tabla de usuarios
 */
function renderUsers(users) {
  const tbody = document.getElementById('users-table-body');

  if (users.length === 0) {
    tbody.innerHTML = `
      <tr><td colspan="7">
        <div class="empty-state">
          <div class="empty-icon">👥</div>
          <h3>No hay usuarios</h3>
          <p>Cree el primer usuario del sistema</p>
        </div>
      </td></tr>
    `;
    return;
  }

  tbody.innerHTML = users.map(u => {
    const roleNames = { superadmin: 'SuperAdmin', admin: 'Admin', user: 'Usuario' };
    const statusBadge = u.status === 'active' ? 'badge-success' : 'badge-danger';
    const statusText = u.status === 'active' ? 'Activo' : 'Inactivo';

    return `
      <tr>
        <td><strong>${escapeHtml(u.username)}</strong></td>
        <td>${escapeHtml(u.name)}</td>
        <td>${escapeHtml(u.email)}</td>
        <td><span class="badge badge-info">${roleNames[u.role] || u.role}</span></td>
        <td><span class="badge ${statusBadge}">${statusText}</span></td>
        <td>${App.formatDate(u.createdAt)}</td>
        <td>
          <button class="btn btn-sm btn-secondary" onclick="editUser('${u.id}')">✏️</button>
          <button class="btn btn-sm btn-danger" onclick="deleteUser('${u.id}', '${escapeHtml(u.username)}')">🗑️</button>
        </td>
      </tr>
    `;
  }).join('');
}

/**
 * Filtra usuarios por búsqueda
 */
function filterUsers() {
  const query = document.getElementById('search-user').value.toLowerCase();
  const filtered = usersData.filter(u => 
    u.username.toLowerCase().includes(query) || 
    u.name.toLowerCase().includes(query) ||
    u.email.toLowerCase().includes(query)
  );
  renderUsers(filtered);
}

/**
 * Muestra modal para agregar usuario
 */
function showAddUserModal() {
  document.getElementById('user-modal-title').textContent = 'Nuevo Usuario';
  document.getElementById('user-form').reset();
  document.getElementById('user-id').value = '';
  document.getElementById('user-password').required = true;
  document.getElementById('user-password').placeholder = 'Mínimo 6 caracteres';
  openModal('user-modal');
}

/**
 * Edita un usuario
 */
async function editUser(id) {
  const result = await API.get(`/users/${id}`);
  if (!result.success) {
    App.toast('Error obteniendo usuario', 'error');
    return;
  }

  const u = result.data;
  document.getElementById('user-modal-title').textContent = 'Editar Usuario';
  document.getElementById('user-id').value = u.id;
  document.getElementById('user-username').value = u.username;
  document.getElementById('user-name').value = u.name;
  document.getElementById('user-email').value = u.email;
  document.getElementById('user-role').value = u.role;
  document.getElementById('user-status').value = u.status;
  document.getElementById('user-password').required = false;
  document.getElementById('user-password').placeholder = 'Dejar vacío para mantener';
  document.getElementById('user-password').value = '';
  
  const clientSelect = document.getElementById('user-client');
  if (clientSelect) clientSelect.value = u.clientId || '';

  openModal('user-modal');
}

/**
 * Guarda usuario (crear o actualizar)
 */
async function saveUser() {
  const id = document.getElementById('user-id').value;
  const data = {
    username: document.getElementById('user-username').value.trim(),
    name: document.getElementById('user-name').value.trim(),
    email: document.getElementById('user-email').value.trim(),
    role: document.getElementById('user-role').value,
    status: document.getElementById('user-status').value
  };

  const password = document.getElementById('user-password').value;
  if (password) data.password = password;

  const clientSelect = document.getElementById('user-client');
  if (clientSelect) {
    data.clientId = clientSelect.value || null;
  }

  if (!data.username || !data.name || !data.email) {
    App.toast('Complete los campos requeridos', 'warning');
    return;
  }

  let result;
  if (id) {
    result = await API.put(`/users/${id}`, data);
  } else {
    if (!password) {
      App.toast('La contraseña es requerida', 'warning');
      return;
    }
    result = await API.post('/users', data);
  }

  if (result.success) {
    App.toast(id ? 'Usuario actualizado' : 'Usuario creado', 'success');
    closeModal('user-modal');
    await loadUsers();
  } else {
    App.toast(result.message || 'Error guardando usuario', 'error');
  }
}

/**
 * Elimina un usuario
 */
async function deleteUser(id, username) {
  if (!confirm(`¿Eliminar al usuario "${username}"?`)) return;

  const result = await API.delete(`/users/${id}`);
  
  if (result.success) {
    App.toast('Usuario eliminado', 'success');
    await loadUsers();
  } else {
    App.toast(result.message || 'Error eliminando usuario', 'error');
  }
}

/**
 * Carga clientes para el select
 */
async function loadClientsForUserSelect() {
  const user = API.getCurrentUser();
  const select = document.getElementById('user-client');
  
  if (!select) return;

  // Solo SuperAdmin puede asignar clientes
  if (user.role !== 'superadmin') {
    document.getElementById('user-client-group').style.display = 'none';
    return;
  }

  const result = await API.get('/clients');
  if (result.success) {
    select.innerHTML = '<option value="">Sin cliente (SuperAdmin)</option>' +
      result.data.map(c => `<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
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
