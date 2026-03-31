window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'supervisor', 'dispatcher'])) {
    return;
  }

  const vehicleId = window.LogisticHubCore.queryParam('id');
  const form = document.querySelector('#vehicleForm');
  const options = await window.LogisticHubCore.apiRequest('/vehicles/options');

  form.type.innerHTML = '<option value="">Selecciona tipo</option>' + options.types.map((item) => `<option value="${item}">${item}</option>`).join('');
  form.status.innerHTML = '<option value="">Selecciona estado</option>' + options.statuses.map((item) => `<option value="${item}">${item}</option>`).join('');

  if (vehicleId) {
    const item = await window.LogisticHubCore.apiRequest(`/vehicles/${vehicleId}`);
    form.plate.value = item.plate || '';
    form.type.value = item.type || '';
    form.status.value = item.status || '';
    form.capacity.value = item.capacity || '';
    form.fuelConsumptionKm.value = item.fuelConsumptionKm || '';
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const response = await window.LogisticHubCore.apiRequest(vehicleId ? `/vehicles/${vehicleId}` : '/vehicles', {
      method: vehicleId ? 'PUT' : 'POST',
      data: {
        plate: form.plate.value.trim(),
        type: form.type.value,
        status: form.status.value,
        capacity: form.capacity.value.trim(),
        fuelConsumptionKm: form.fuelConsumptionKm.value,
      },
    });

    window.LogisticHubCore.setNotice('success', response.message);
    window.location.href = '/logistichub/vehiculos.html';
  });
});