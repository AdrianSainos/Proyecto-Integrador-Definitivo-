window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'supervisor', 'dispatcher'])) {
    return;
  }

  const maintenanceId = window.LogisticHubCore.queryParam('id');
  const form = document.querySelector('#maintenanceForm');
  const options = await window.LogisticHubCore.apiRequest('/maintenance/options');

  function fill(selectId, items, placeholder, mapper) {
    const select = document.querySelector(selectId);
    select.innerHTML = `<option value="">${placeholder}</option>` + items.map((item) => {
      const mapped = mapper(item);
      return `<option value="${mapped.value}">${mapped.label}</option>`;
    }).join('');
  }

  fill('#vehicleId', options.vehicles, 'Selecciona vehiculo', (item) => ({ value: item.id, label: item.plate }));
  fill('#type', options.types, 'Selecciona tipo', (item) => ({ value: item, label: item }));
  fill('#status', options.statuses, 'Selecciona estado', (item) => ({ value: item, label: window.LogisticHubCore.statusLabel(item) }));

  if (maintenanceId) {
    const item = await window.LogisticHubCore.apiRequest(`/maintenance/${maintenanceId}`);
    form.vehicleId.value = item.vehicleId || '';
    form.type.value = item.type || '';
    form.scheduledDate.value = item.scheduledDate ? String(item.scheduledDate).slice(0, 10) : '';
    form.completionDate.value = item.completionDate ? String(item.completionDate).slice(0, 10) : '';
    form.status.value = item.status || '';
    form.cost.value = item.cost || '';
    form.kmAtMaintenance.value = item.kmAtMaintenance || '';
    form.description.value = item.description || '';
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const response = await window.LogisticHubCore.apiRequest(maintenanceId ? `/maintenance/${maintenanceId}` : '/maintenance', {
      method: maintenanceId ? 'PUT' : 'POST',
      data: {
        vehicleId: form.vehicleId.value,
        type: form.type.value,
        scheduledDate: form.scheduledDate.value,
        completionDate: form.completionDate.value || null,
        status: form.status.value,
        cost: form.cost.value,
        kmAtMaintenance: form.kmAtMaintenance.value,
        description: form.description.value.trim(),
      },
    });

    window.LogisticHubCore.setNotice('success', response.message);
    window.location.href = '/logistichub/maintenance.html';
  });
});