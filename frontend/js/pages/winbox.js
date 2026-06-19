/**
 * WinBox/WebFig - Controlador de acceso remoto
 */
let winboxRouters = [];

document.addEventListener('DOMContentLoaded', async () => {
  await loadRoutersForWinbox();
});

/**
 * Carga routers para el selector
 */
async function loadRoutersForWinbox() {
  const result = await API.get('/routers');
  
  if (!result.success) return;

  winboxRouters = result.data;
  const select = document.getElementById('winbox-router');
  select.innerHTML = '<option value="">Seleccione un router...</option>' +
    result.data.map(r => `<option value="${r.id}">${escapeHtml(r.name)} (${r.host})</option>`).join('');
}

/**
 * Actualiza los enlaces de acceso según selección
 */
async function updateAccessLinks() {
  const routerId = document.getElementById('winbox-router').value;
  const method = document.getElementById('winbox-method').value;

  if (!routerId) {
    document.getElementById('winbox-info').innerHTML = `
      <div class="empty-state" style="padding:20px;">
        <div class="empty-icon">🔗</div>
        <h3>Seleccione un router</h3>
        <p>Elija un router y método de acceso para ver los detalles de conexión</p>
      </div>
    `;
    document.getElementById('winbox-actions').innerHTML = `
      <div class="empty-state" style="padding:20px;">
        <p>Seleccione un router para habilitar los accesos directos</p>
      </div>
    `;
    return;
  }

  const router = winboxRouters.find(r => r.id === routerId);
  if (!router) return;

  // Mostrar información
  document.getElementById('winbox-info').innerHTML = `
    <div class="info-grid">
      <div class="info-item">
        <div class="info-label">Router</div>
        <div class="info-value">${escapeHtml(router.name)}</div>
      </div>
      <div class="info-item">
        <div class="info-label">Dirección IP</div>
        <div class="info-value">${router.host}</div>
      </div>
      <div class="info-item">
        <div class="info-label">Puerto API</div>
        <div class="info-value">${router.port || 8728}</div>
      </div>
      <div class="info-item">
        <div class="info-label">Usuario</div>
        <div class="info-value">${escapeHtml(router.username)}</div>
      </div>
      <div class="info-item">
        <div class="info-label">Método</div>
        <div class="info-value">${method === 'winbox' ? 'WinBox' : 'WebFig'}</div>
      </div>
      <div class="info-item">
        <div class="info-label">Estado</div>
        <div class="info-value">
          <span class="status-dot ${router.status === 'online' ? 'online' : 'offline'}"></span>
          <span class="badge ${router.status === 'online' ? 'badge-success' : 'badge-danger'}">${router.status}</span>
        </div>
      </div>
    </div>
  `;

  // Mostrar acciones
  const winboxUrl = `winbox://${router.host}:${router.port || 8728}`;
  const webfigUrl = `http://${router.host}/webfig/`;
  const sshCommand = `ssh ${router.username}@${router.host}`;

  document.getElementById('winbox-actions').innerHTML = `
    <div style="display:flex;flex-direction:column;gap:12px;">
      <div style="display:flex;flex-wrap:wrap;gap:8px;">
        <a href="${winboxUrl}" target="_blank" class="btn btn-primary">
          🖥️ Abrir en WinBox
        </a>
        <a href="${webfigUrl}" target="_blank" class="btn btn-secondary">
          🌐 Abrir WebFig
        </a>
      </div>
      
      <div style="margin-top:8px;">
        <p style="font-size:13px;color:var(--text-secondary);margin-bottom:8px;">
          <strong>WinBox URL:</strong>
        </p>
        <div style="display:flex;align-items:center;gap:8px;background:var(--bg-input);padding:8px 12px;border-radius:6px;">
          <code style="flex:1;font-size:13px;">${winboxUrl}</code>
          <button class="copy-btn" onclick="copyToClipboard('${winboxUrl}')" title="Copiar">📋</button>
        </div>
      </div>

      <div>
        <p style="font-size:13px;color:var(--text-secondary);margin-bottom:8px;">
          <strong>WebFig URL:</strong>
        </p>
        <div style="display:flex;align-items:center;gap:8px;background:var(--bg-input);padding:8px 12px;border-radius:6px;">
          <code style="flex:1;font-size:13px;">${webfigUrl}</code>
          <button class="copy-btn" onclick="copyToClipboard('${webfigUrl}')" title="Copiar">📋</button>
        </div>
      </div>

      <div>
        <p style="font-size:13px;color:var(--text-secondary);margin-bottom:8px;">
          <strong>Conexión SSH:</strong>
        </p>
        <div style="display:flex;align-items:center;gap:8px;background:var(--bg-input);padding:8px 12px;border-radius:6px;">
          <code style="flex:1;font-size:13px;">${sshCommand}</code>
          <button class="copy-btn" onclick="copyToClipboard('${sshCommand}')" title="Copiar">📋</button>
        </div>
      </div>
    </div>
  `;
}

/**
 * Copia texto al portapapeles
 */
function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    App.toast('Copiado al portapapeles', 'success');
  }).catch(() => {
    App.toast('Error al copiar', 'error');
  });
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
