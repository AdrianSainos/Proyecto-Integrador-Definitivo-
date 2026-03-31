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
        <td>
          <strong>${item.name}</strong>
          <div class="text-muted">${item.phone || item.jobTitle || '--'}</div>
        </td>
        <td>
          <strong>${item.username ? `@${item.username}` : '--'}</strong>
          <div class="text-muted">${item.email || '--'}</div>
        </td>
        <td><span class="${window.LogisticHubCore.badgeClass(item.status)}">${window.LogisticHubCore.statusLabel(item.status)}</span></td>
        <td>
          <strong>${item.shift}</strong>
          <div class="text-muted">${item.baseSchedule || '--'}</div>
        </td>
        <td>${item.routeCount} total / ${item.activeRouteCount} activas</td>
        <td><div class="table-actions">${canManage ? `<a class="btn btn-outline btn-sm" href="/logistichub/driver-form.html?id=${item.id}">Editar</a><button class="btn btn-danger btn-sm" data-delete-id="${item.id}">Eliminar</button>` : '<span class="text-muted">Solo lectura</span>'}</div></td>
      </tr>
    `).join('');

    window.LogisticHubCore.bindDeleteButtons(body, {
      basePath: '/drivers',
      noticeTarget: '#pageNotice',
      confirmMessage: 'Se eliminará el conductor seleccionado. Si aún tiene rutas o asignaciones, la operación será rechazada. ¿Deseas continuar?',
      successMessage: 'Conductor eliminado correctamente.',
      onSuccess: loadDrivers,
    });
  }

  loadDrivers();
});