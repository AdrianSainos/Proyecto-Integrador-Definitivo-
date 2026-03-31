window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'supervisor', 'dispatcher'])) {
    return;
  }

  const routeId = window.LogisticHubCore.queryParam('id');
  const form = document.querySelector('#routeForm');
  const options = await window.LogisticHubCore.apiRequest('/routes/options');

  function fill(selectId, items, placeholder, mapper) {
    const select = document.querySelector(selectId);
    select.innerHTML = `<option value="">${placeholder}</option>` + items.map((item) => {
      const mapped = mapper(item);
      return `<option value="${mapped.value}">${mapped.label}</option>`;
    }).join('');
  }

  fill('#warehouseId', options.warehouses, 'Selecciona almacen', (item) => ({ value: item.id, label: item.name }));
  fill('#status', options.statuses, 'Selecciona estado', (item) => ({ value: item, label: item }));
  fill('#vehicleId', options.vehicles, 'Selecciona vehiculo', (item) => ({ value: item.id, label: item.plate }));
  fill('#driverId', options.drivers, 'Selecciona conductor', (item) => ({ value: item.id, label: item.name }));

  if (routeId) {
    const item = await window.LogisticHubCore.apiRequest(`/routes/${routeId}`);
    form.warehouseId.value = item.warehouseId || '';
    form.distanceKm.value = item.distanceKm || '';
    form.timeMinutes.value = item.timeMinutes || '';
    form.status.value = item.status || '';
    form.vehicleId.value = item.vehicleId || '';
    form.driverId.value = item.driverId || '';
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const response = await window.LogisticHubCore.apiRequest(routeId ? `/routes/${routeId}` : '/routes', {
      method: routeId ? 'PUT' : 'POST',
      data: {
        warehouseId: form.warehouseId.value,
        distanceKm: form.distanceKm.value,
        timeMinutes: form.timeMinutes.value,
        status: form.status.value,
        vehicleId: form.vehicleId.value,
        driverId: form.driverId.value,
      },
    });

    window.LogisticHubCore.setNotice('success', response.message);
    window.location.href = '/logistichub/rutas.html';
  });
});