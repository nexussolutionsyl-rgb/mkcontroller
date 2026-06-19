/**
 * Hotspot - Controlador de gestión Hotspot
 */
let hotspotRouters = [];
let ticketsData = [];

document.addEventListener('DOMContentLoaded', async () => {
  await loadHotspotRouters();
  await loadTickets();
});

/**
 * Carga los routers para el selector
 */
async function loadHotspotRouters() {
  const result = await API.get('/routers');
  
  if (!result.success) return;

  hotspotRouters = result.data;
  const select = document.getElementById('hotspot-router');
  const ticketSelect = document.getElementById('ticket-router');
  
  const options = '<option value="">Seleccione un router...</option>' +
    hotspotRouters.map(r => `<option value="${r.id}">${escapeHtml(r.name)} (${r.host})</option>`).join('');
  
  select.innerHTML = options;
  ticketSelect.innerHTML = options;
}

/**
 * Carga datos del hotspot según el router seleccionado
 */
async function loadHotspotData() {
  const routerId = document.getElementById('hotspot-router').value;
  
  if (!routerId) {
    ['servers', 'profiles', 'active'].forEach(tab => {
      document.getElementById(`${tab}-table`).innerHTML = 
        '<tr><td colspan="6" class="empty-state"><p>Seleccione un router</p></td></tr>';
    });
    return;
  }

  await Promise.all([
    loadServers(routerId),
    loadProfiles(routerId),
    loadActiveUsers(routerId)
  ]);
}

/**
 * Carga servidores Hotspot
 */
async function loadServers(routerId) {
  const result = await API.get(`/routers/${routerId}/hotspot/servers`);
  const tbody = document.getElementById('servers-table');

  if (!result.success || !result.data.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="empty-state"><p>No hay servidores Hotspot</p></td></tr>';
    return;
  }

  tbody.innerHTML = result.data.map(s => `
    <tr>
      <td><strong>${escapeHtml(s.name)}</strong></td>
      <td>${escapeHtml(s.interface)}</td>
      <td>${escapeHtml(s.addressPool)}</td>
      <td>${escapeHtml(s.profile)}</td>
      <td><span class="badge ${s.disabled ? 'badge-danger' : 'badge-success'}">${s.disabled ? 'Desactivado' : 'Activo'}</span></td>
    </tr>
  `).join('');
}

/**
 * Carga perfiles Hotspot
 */
async function loadProfiles(routerId) {
  const result = await API.get(`/routers/${routerId}/hotspot/profiles`);
  const tbody = document.getElementById('profiles-table');

  if (!result.success || !result.data.length) {
    tbody.innerHTML = '<tr><td colspan="4" class="empty-state"><p>No hay perfiles Hotspot</p></td></tr>';
    return;
  }

  tbody.innerHTML = result.data.map(p => `
    <tr>
      <td><strong>${escapeHtml(p.name)}</strong></td>
      <td>${p.sharedUsers || 'Ilimitado'}</td>
      <td>${p.rateLimit || 'Sin límite'}</td>
      <td>${p.sessionTimeout || 'Sin timeout'}</td>
    </tr>
  `).join('');
}

/**
 * Carga usuarios activos en Hotspot
 */
async function loadActiveUsers(routerId) {
  const result = await API.get(`/routers/${routerId}/hotspot/active`);
  const tbody = document.getElementById('active-table');

  if (!result.success || !result.data.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="empty-state"><p>No hay usuarios activos</p></td></tr>';
    return;
  }

  tbody.innerHTML = result.data.map(u => `
    <tr>
      <td><strong>${escapeHtml(u.user)}</strong></td>
      <td>${u.address}</td>
      <td>${u.macAddress}</td>
      <td><span class="badge badge-info">${u.loginBy}</span></td>
      <td>${u.uptime}</td>
      <td>${App.formatBytes(parseInt(u.bytesIn) + parseInt(u.bytesOut))}</td>
    </tr>
  `).join('');
}

/**
 * Carga tickets generados
 */
async function loadTickets() {
  const result = await API.get('/hotspot/tickets');
  const tbody = document.getElementById('tickets-table');

  if (!result.success) {
    tbody.innerHTML = '<tr><td colspan="8" class="empty-state"><p>Error cargando tickets</p></td></tr>';
    return;
  }

  ticketsData = result.data;

  if (!ticketsData.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="empty-state"><p>No hay tickets generados</p></td></tr>';
    return;
  }

  tbody.innerHTML = ticketsData.map(t => `
    <tr>
      <td>${escapeHtml(t.routerName)}</td>
      <td>${escapeHtml(t.server)}</td>
      <td>${escapeHtml(t.profile)}</td>
      <td>${t.limitUptime || 'Ilimitado'}</td>
      <td>${escapeHtml(t.generatedByName)}</td>
      <td>${App.formatDate(t.createdAt)}</td>
      <td><span class="badge ${t.status === 'active' ? 'badge-success' : 'badge-secondary'}">${t.status}</span></td>
      <td>
        <button class="btn btn-sm btn-danger" onclick="deleteTicket('${t.id}')">🗑️</button>
      </td>
    </tr>
  `).join('');
}

/**
 * Filtra tickets por búsqueda
 */
function filterTickets() {
  const query = document.getElementById('search-ticket').value.toLowerCase();
  const filtered = ticketsData.filter(t => 
    t.routerName.toLowerCase().includes(query) || 
    t.server.toLowerCase().includes(query) ||
    t.profile.toLowerCase().includes(query)
  );
  
  const tbody = document.getElementById('tickets-table');
  if (!filtered.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="empty-state"><p>Sin resultados</p></td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(t => `
    <tr>
      <td>${escapeHtml(t.routerName)}</td>
      <td>${escapeHtml(t.server)}</td>
      <td>${escapeHtml(t.profile)}</td>
      <td>${t.limitUptime || 'Ilimitado'}</td>
      <td>${escapeHtml(t.generatedByName)}</td>
      <td>${App.formatDate(t.createdAt)}</td>
      <td><span class="badge ${t.status === 'active' ? 'badge-success' : 'badge-secondary'}">${t.status}</span></td>
      <td>
        <button class="btn btn-sm btn-danger" onclick="deleteTicket('${t.id}')">🗑️</button>
      </td>
    </tr>
  `).join('');
}

/**
 * Cambia de tab en hotspot
 */
function switchHotspotTab(tab, btn) {
  document.querySelectorAll('.tabs .tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById(`tab-${tab}`).classList.add('active');
}

/**
 * Muestra modal para generar ticket
 */
async function showGenerateTicketModal() {
  const routerId = document.getElementById('hotspot-router').value;
  
  if (!routerId) {
    App.toast('Seleccione un router primero', 'warning');
    return;
  }

  // Cargar servidores y perfiles del router seleccionado
  const [serversRes, profilesRes] = await Promise.all([
    API.get(`/routers/${routerId}/hotspot/servers`),
    API.get(`/routers/${routerId}/hotspot/profiles`)
  ]);

  const serverSelect = document.getElementById('ticket-server');
  const profileSelect = document.getElementById('ticket-profile');
  const routerSelect = document.getElementById('ticket-router');

  routerSelect.value = routerId;

  if (serversRes.success) {
    serverSelect.innerHTML = '<option value="">Seleccione...</option>' +
      serversRes.data.map(s => `<option value="${s.name}">${escapeHtml(s.name)}</option>`).join('');
  }

  if (profilesRes.success) {
    profileSelect.innerHTML = '<option value="">Seleccione...</option>' +
      profilesRes.data.map(p => `<option value="${p.name}">${escapeHtml(p.name)}</option>`).join('');
  }

  openTicketModal();
}

/**
 * Genera un ticket hotspot
 */
async function generateTicket() {
  const routerId = document.getElementById('ticket-router').value;
  const server = document.getElementById('ticket-server').value;
  const profile = document.getElementById('ticket-profile').value;
  const limitUptime = document.getElementById('ticket-uptime').value;
  const limitBytes = document.getElementById('ticket-bytes').value;
  const comment = document.getElementById('ticket-comment').value;

  if (!routerId || !server || !profile) {
    App.toast('Complete todos los campos requeridos', 'warning');
    return;
  }

  const result = await API.post(`/routers/${routerId}/hotspot/tickets`, {
    server, profile, limitUptime, limitBytes, comment
  });

  if (result.success) {
    App.toast('Ticket generado exitosamente', 'success');
    closeTicketModal();
    await loadTickets();
  } else {
    App.toast(result.message || 'Error generando ticket', 'error');
  }
}

/**
 * Elimina un ticket
 */
async function deleteTicket(id) {
  if (!confirm('¿Eliminar este ticket?')) return;

  const result = await API.delete(`/hotspot/tickets/${id}`);
  
  if (result.success) {
    App.toast('Ticket eliminado', 'success');
    await loadTickets();
  } else {
    App.toast(result.message || 'Error eliminando ticket', 'error');
  }
}

function openTicketModal() {
  document.getElementById('ticket-modal').classList.add('show');
}

function closeTicketModal() {
  document.getElementById('ticket-modal').classList.remove('show');
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
