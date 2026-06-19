/**
 * Dashboard - Controlador de la página principal
 */
document.addEventListener('DOMContentLoaded', async () => {
  await loadDashboard();
});

async function loadDashboard() {
  const user = API.getCurrentUser();
  
  if (user.role === 'superadmin') {
    await loadAdminDashboard();
  } else {
    await loadClientDashboard();
  }
}

/**
 * Carga el dashboard de SuperAdmin
 */
async function loadAdminDashboard() {
  const result = await API.get('/dashboard/admin');
  
  if (!result.success) {
    App.toast('Error cargando dashboard', 'error');
    return;
  }

  const data = result.data;

  // Mostrar sección de clientes
  document.getElementById('stat-card-clients').style.display = 'flex';
  document.getElementById('plans-card').style.display = 'block';

  // Stats
  document.getElementById('stat-routers').textContent = data.routers.total;
  document.getElementById('stat-routers-online').textContent = data.routers.online;
  document.getElementById('stat-routers-offline').textContent = data.routers.offline;
  document.getElementById('stat-clients').textContent = data.clients.total;
  document.getElementById('stat-clients-active').textContent = data.clients.active;
  document.getElementById('stat-users').textContent = data.users.total;
  document.getElementById('stat-users-admins').textContent = data.users.admins;
  document.getElementById('stat-tickets').textContent = data.tickets.total;
  document.getElementById('stat-tickets-active').textContent = data.tickets.active;

  // Actividad reciente
  renderActivity(data.recentActivity);

  // Distribución de planes
  renderPlansDistribution(data.plansDistribution);
}

/**
 * Carga el dashboard de Cliente
 */
async function loadClientDashboard() {
  const result = await API.get('/dashboard/client');
  
  if (!result.success) {
    App.toast('Error cargando dashboard', 'error');
    return;
  }

  const data = result.data;

  // Ocultar secciones de SuperAdmin
  document.getElementById('stat-card-clients').style.display = 'none';
  document.getElementById('plans-card').style.display = 'none';

  // Mostrar info del cliente
  const clientCard = document.getElementById('client-info-card');
  clientCard.style.display = 'block';
  document.getElementById('client-name').textContent = data.client.name;
  document.getElementById('client-company').textContent = data.client.company || '-';
  document.getElementById('client-plan').textContent = data.client.plan || 'basic';
  document.getElementById('client-status').innerHTML = `<span class="badge badge-success">Activo</span>`;

  // Stats
  document.getElementById('stat-routers').textContent = data.routers.total;
  document.getElementById('stat-routers-online').textContent = data.routers.online;
  document.getElementById('stat-routers-offline').textContent = data.routers.offline;
  document.getElementById('stat-users').textContent = data.users.total;
  document.getElementById('stat-users-admins').textContent = data.users.active;
  document.getElementById('stat-tickets').textContent = data.tickets.total;
  document.getElementById('stat-tickets-active').textContent = data.tickets.recentTickets.length;

  // Actividad reciente (vacía para clientes por ahora)
  document.getElementById('activity-list').innerHTML = `
    <li class="empty-state" style="padding:20px;">
      <p>Panel de cliente - Actividad próximamente</p>
    </li>
  `;
}

/**
 * Renderiza la lista de actividad reciente
 */
function renderActivity(activities) {
  const container = document.getElementById('activity-list');
  
  if (!activities || activities.length === 0) {
    container.innerHTML = `
      <li class="empty-state" style="padding:20px;">
        <p>No hay actividad reciente</p>
      </li>
    `;
    return;
  }

  container.innerHTML = activities.map(activity => `
    <li class="activity-item">
      <span class="activity-dot" style="background:var(--primary);"></span>
      <div class="activity-content">
        <div class="activity-text">
          <strong>${escapeHtml(activity.username)}</strong> ${escapeHtml(activity.details)}
        </div>
        <div class="activity-time">${App.formatDate(activity.createdAt)}</div>
      </div>
    </li>
  `).join('');
}

/**
 * Renderiza la distribución de planes
 */
function renderPlansDistribution(plans) {
  const container = document.getElementById('plans-distribution');
  
  if (!plans || Object.keys(plans).length === 0) {
    container.innerHTML = '<p class="empty-state" style="padding:10px;">Sin datos de planes</p>';
    return;
  }

  const total = Object.values(plans).reduce((a, b) => a + b, 0);
  
  container.innerHTML = Object.entries(plans).map(([plan, count]) => {
    const percentage = ((count / total) * 100).toFixed(1);
    const colors = {
      'basic': 'var(--secondary)',
      'professional': 'var(--primary)',
      'enterprise': 'var(--success)'
    };
    const color = colors[plan] || 'var(--secondary)';
    
    return `
      <div style="margin-bottom:12px;">
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
          <span style="text-transform:capitalize;">${plan}</span>
          <span>${count} (${percentage}%)</span>
        </div>
        <div style="height:8px;background:var(--bg-input);border-radius:4px;overflow:hidden;">
          <div style="height:100%;width:${percentage}%;background:${color};border-radius:4px;transition:width 0.5s;"></div>
        </div>
      </div>
    `;
  }).join('');
}

/**
 * Escapa HTML para prevenir XSS
 */
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
