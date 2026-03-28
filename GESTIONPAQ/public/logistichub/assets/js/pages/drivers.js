window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'supervisor', 'dispatcher'])) {
    return;
  }

  window.LogisticHubCore.renderNotice('#pageNotice', window.LogisticHubCore.consumeNotice());

  async function loadDrivers() {
    const user = window.LogisticHubCore.getUser();
    const canManage = ['admin', 'supervisor', 'dispatcher'].includes(user.role);
    const items = await window.LogisticHubCore.apiRequest('/drivers');
    const body = document.querySelector('#driversTableBody');

    body.innerHTML = items.map((item) => `
      <tr>
        <td>${item.id}</td>
        <td>${item.name}</td>
        <td>${item.phone || '--'}</td>
        <td><span class="${window.LogisticHubCore.badgeClass(item.status)}">${item.status}</span></td>
        <td><div class="table-actions">${canManage ? `<a class="btn btn-outline btn-sm" href="/logistichub/driver-form.html?id=${item.id}">Editar</a><button class="btn btn-danger btn-sm" data-delete-id="${item.id}">Eliminar</button>` : '<span class="text-muted">Solo lectura</span>'}</div></td>
      </tr>
    `).join('');

    body.querySelectorAll('[data-delete-id]').forEach((button) => {
      button.addEventListener('click', async () => {
        await window.LogisticHubCore.apiRequest(`/drivers/${button.dataset.deleteId}`, { method: 'DELETE' });
        window.LogisticHubCore.setNotice('success', 'Conductor eliminado correctamente.');
        loadDrivers();
      });
    });
  }

  loadDrivers();
});