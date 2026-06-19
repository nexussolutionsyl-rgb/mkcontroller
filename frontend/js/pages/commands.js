/**
 * Comandos - Controlador de ejecución remota
 */
document.addEventListener('DOMContentLoaded', async () => {
  await loadRoutersForCommands();
});

/**
 * Carga routers para el selector
 */
async function loadRoutersForCommands() {
  const result = await API.get('/routers');
  
  if (!result.success) return;

  const select = document.getElementById('cmd-router');
  select.innerHTML = '<option value="">Seleccione un router...</option>' +
    result.data.map(r => `<option value="${r.id}">${escapeHtml(r.name)} (${r.host})</option>`).join('');
}

/**
 * Ejecuta un comando en el router
 */
async function executeCommand() {
  const routerId = document.getElementById('cmd-router').value;
  const command = document.getElementById('cmd-command').value.trim();
  const argsStr = document.getElementById('cmd-args').value.trim();

  if (!routerId) {
    App.toast('Seleccione un router', 'warning');
    return;
  }

  if (!command) {
    App.toast('Ingrese un comando', 'warning');
    return;
  }

  let args = {};
  if (argsStr) {
    try {
      args = JSON.parse(argsStr);
    } catch (e) {
      App.toast('Argumentos JSON inválidos', 'error');
      return;
    }
  }

  const output = document.getElementById('cmd-output');
  const status = document.getElementById('cmd-status');
  
  output.textContent = 'Ejecutando...';
  status.textContent = '⌛ Ejecutando comando...';

  const result = await API.post(`/routers/${routerId}/command`, { command, args });

  if (result.success) {
    output.textContent = JSON.stringify(result.data, null, 2);
    status.textContent = `✅ Completado (${new Date().toLocaleTimeString()})`;
    App.toast('Comando ejecutado exitosamente', 'success');
  } else {
    output.textContent = `Error: ${result.message}`;
    status.textContent = '❌ Error';
    App.toast(result.message || 'Error ejecutando comando', 'error');
  }
}

/**
 * Comando rápido desde los botones
 */
function quickCommand(cmd) {
  document.getElementById('cmd-command').value = cmd;
  document.getElementById('cmd-args').value = '';
  executeCommand();
}

/**
 * Limpia la salida
 */
function clearOutput() {
  document.getElementById('cmd-output').textContent = 'Salida limpiada.';
  document.getElementById('cmd-status').textContent = '';
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
