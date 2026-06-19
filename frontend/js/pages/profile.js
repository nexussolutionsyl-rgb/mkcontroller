/**
 * Perfil - Controlador de perfil de usuario
 */
document.addEventListener('DOMContentLoaded', async () => {
  await loadProfile();
});

/**
 * Carga la información del perfil
 */
async function loadProfile() {
  const user = API.getCurrentUser();
  const container = document.getElementById('profile-info');

  if (!user) {
    container.innerHTML = '<p class="empty-state">Error cargando perfil</p>';
    return;
  }

  const roleNames = {
    'superadmin': 'Super Administrador',
    'admin': 'Administrador',
    'user': 'Usuario'
  };

  container.innerHTML = `
    <div style="text-align:center;margin-bottom:20px;">
      <div style="width:64px;height:64px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:24px;font-weight:700;color:white;">
        ${user.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()}
      </div>
      <h2 style="font-size:20px;">${escapeHtml(user.name)}</h2>
      <p style="color:var(--text-secondary);font-size:13px;">${escapeHtml(user.email)}</p>
    </div>
    <div class="info-grid">
      <div class="info-item">
        <div class="info-label">Usuario</div>
        <div class="info-value">${escapeHtml(user.username)}</div>
      </div>
      <div class="info-item">
        <div class="info-label">Rol</div>
        <div class="info-value"><span class="badge badge-info">${roleNames[user.role] || user.role}</span></div>
      </div>
      <div class="info-item">
        <div class="info-label">Email</div>
        <div class="info-value">${escapeHtml(user.email)}</div>
      </div>
      <div class="info-item">
        <div class="info-label">ID</div>
        <div class="info-value" style="font-size:12px;font-family:monospace;">${user.id}</div>
      </div>
    </div>
  `;
}

/**
 * Cambia la contraseña del usuario
 */
async function changePassword() {
  const currentPassword = document.getElementById('pw-current').value;
  const newPassword = document.getElementById('pw-new').value;
  const confirmPassword = document.getElementById('pw-confirm').value;
  const resultDiv = document.getElementById('password-result');

  if (!currentPassword || !newPassword || !confirmPassword) {
    resultDiv.innerHTML = '<div class="alert alert-danger">Complete todos los campos</div>';
    return;
  }

  if (newPassword !== confirmPassword) {
    resultDiv.innerHTML = '<div class="alert alert-danger">Las contraseñas no coinciden</div>';
    return;
  }

  if (newPassword.length < 6) {
    resultDiv.innerHTML = '<div class="alert alert-danger">La contraseña debe tener al menos 6 caracteres</div>';
    return;
  }

  const result = await API.post('/auth/change-password', {
    currentPassword,
    newPassword
  });

  if (result.success) {
    resultDiv.innerHTML = '<div class="alert alert-success">Contraseña actualizada exitosamente</div>';
    document.getElementById('change-password-form').reset();
    App.toast('Contraseña actualizada', 'success');
  } else {
    resultDiv.innerHTML = `<div class="alert alert-danger">${result.message || 'Error cambiando contraseña'}</div>`;
  }
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
