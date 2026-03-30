window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'supervisor', 'dispatcher', 'driver'])) {
    return;
  }

  const user = window.LogisticHubCore.getUser();
  const canManage = ['admin', 'supervisor', 'dispatcher'].includes(user.role);
  window.LogisticHubCore.renderNotice('#pageNotice', window.LogisticHubCore.consumeNotice());

  async function loadRoutes() {
    const items = await window.LogisticHubCore.apiRequest('/routes');
    const body = document.querySelector('#routesTableBody');

    body.innerHTML = items.map((item) => `
      <tr>
        <td>${item.id}</td>
        <td>${item.warehouseName}</td>
        <td>${item.distanceKm}</td>
        <td>${item.timeMinutes} min</td>
        <td>${item.assignedWeightKg || 0} kg / ${item.vehicleCapacityKg || 0} kg</td>
        <td>${item.optimizationScore || 0} pts</td>
        <td><span class="${window.LogisticHubCore.badgeClass(item.status)}">${item.status}</span></td>
        <td>${item.vehiclePlate || 'Pendiente'}</td>
        <td>${item.driverName || 'Pendiente'}</td>
        <td><div class="table-actions">${canManage ? `<a class="btn btn-outline btn-sm" href="/logistichub/route-form.html?id=${item.id}">Editar</a><button class="btn btn-danger btn-sm" data-delete-id="${item.id}">Eliminar</button>` : '<span class="text-muted">Solo lectura</span>'}</div></td>
      </tr>
    `).join('');

    window.LogisticHubCore.bindDeleteButtons(body, {
      basePath: '/routes',
      noticeTarget: '#pageNotice',
      confirmMessage: 'Se eliminara la ruta seleccionada y se liberaran sus asignaciones actuales. ¿Deseas continuar?',
      successMessage: 'Ruta eliminada correctamente.',
      onSuccess: loadRoutes,
    });
  }

  loadRoutes();
});