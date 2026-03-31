window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'supervisor', 'dispatcher'])) {
    return;
  }

  const vehicleId = window.LogisticHubCore.queryParam('vehicleId');
  const query = vehicleId ? `?vehicleId=${encodeURIComponent(vehicleId)}` : '';
  window.LogisticHubCore.renderNotice('#pageNotice', window.LogisticHubCore.consumeNotice());

  async function loadMaintenance() {
    const items = await window.LogisticHubCore.apiRequest(`/maintenance${query}`);
    const body = document.querySelector('#maintenanceTableBody');

    if (!items.length) {
      window.LogisticHubCore.tableMessage(body, 'No hay eventos de mantenimiento registrados.', 6);
      return;
    }

    body.innerHTML = items.map((item) => `
      <tr>
        <td>${item.vehiclePlate || 'Sin placa'}</td>
        <td>${item.type}</td>
        <td>${window.LogisticHubCore.toDate(item.scheduledDate)}</td>
        <td><span class="${window.LogisticHubCore.badgeClass(item.status)}">${item.statusLabel || item.status}</span></td>
        <td>${window.LogisticHubCore.toCurrency(item.cost)}</td>
        <td>
          <div class="table-actions">
            <a class="btn btn-outline btn-sm" href="/logistichub/mantenimiento-form.html?id=${item.id}">Editar</a>
            <button class="btn btn-danger btn-sm" data-delete-id="${item.id}">Eliminar</button>
          </div>
        </td>
      </tr>
    `).join('');

    window.LogisticHubCore.bindDeleteButtons(body, {
      basePath: '/maintenance',
      noticeTarget: '#pageNotice',
      confirmMessage: 'Se eliminara el evento de mantenimiento seleccionado. ¿Deseas continuar?',
      successMessage: 'Evento de mantenimiento eliminado correctamente.',
      onSuccess: loadMaintenance,
    });
  }

  loadMaintenance();
});