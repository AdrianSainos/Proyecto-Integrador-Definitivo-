window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'supervisor', 'dispatcher'])) {
    return;
  }

  window.LogisticHubCore.renderNotice('#pageNotice', window.LogisticHubCore.consumeNotice());

  async function loadVehicles() {
    const items = await window.LogisticHubCore.apiRequest('/vehicles');
    const body = document.querySelector('#vehiclesTableBody');

    body.innerHTML = items.map((item) => `
      <tr>
        <td>${item.id}</td>
        <td>${item.plate}</td>
        <td>${item.type}</td>
        <td><span class="${window.LogisticHubCore.badgeClass(item.status)}">${item.status}</span></td>
        <td>${item.capacity}</td>
        <td>${item.maintenance ? '<span class="role-pill">Activo</span>' : '<span class="status-pill">Sin eventos</span>'}</td>
        <td>
          <div class="table-actions">
            <a class="btn btn-outline btn-sm" href="/logistichub/vehicle-form.html?id=${item.id}">Editar</a>
            <a class="btn btn-outline btn-sm" href="/logistichub/maintenance.html?vehicleId=${item.id}">Mantenimiento</a>
            <button class="btn btn-danger btn-sm" data-delete-id="${item.id}">Eliminar</button>
          </div>
        </td>
      </tr>
    `).join('');

    body.querySelectorAll('[data-delete-id]').forEach((button) => {
      button.addEventListener('click', async () => {
        await window.LogisticHubCore.apiRequest(`/vehicles/${button.dataset.deleteId}`, { method: 'DELETE' });
        window.LogisticHubCore.setNotice('success', 'Vehiculo eliminado correctamente.');
        loadVehicles();
      });
    });
  }

  loadVehicles();
});