window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'operator', 'supervisor', 'dispatcher'])) {
    return;
  }

  const form = document.querySelector('#shipmentForm');
  const noticeTarget = document.querySelector('#formNotice');
  const shipmentId = window.LogisticHubCore.queryParam('id');
  const options = await window.LogisticHubCore.apiRequest('/shipments/options');

  function fillSelect(element, items, placeholder, mapper) {
    const select = document.querySelector(element);
    const render = mapper || ((item) => ({ value: item.id, label: item.name || item.label || item }));
    select.innerHTML = `<option value="">${placeholder}</option>` + items.map((item) => {
      const mapped = render(item);
      return `<option value="${mapped.value}">${mapped.label}</option>`;
    }).join('');
  }

  fillSelect('#senderId', options.customers, 'Selecciona remitente');
  fillSelect('#recipientId', options.customers, 'Selecciona destinatario');
  fillSelect('#originWarehouseId', options.warehouses, 'Asignacion automatica', (item) => ({ value: item.id, label: `${item.name} - ${item.city}` }));
  fillSelect('#packageType', options.packageTypes, 'Selecciona tipo', (item) => ({ value: item, label: item }));
  fillSelect('#priority', options.priorities, 'Selecciona prioridad', (item) => ({ value: item.value, label: item.label }));
  fillSelect('#initialStatus', options.statuses, 'Selecciona estado', (item) => ({ value: item, label: item }));

  const destinationSelect = document.querySelector('#destinationAddressId');
  const coordinatesNotice = document.querySelector('#coordinatesNotice');

  function loadRecipientAddresses(customerId) {
    const customer = options.customers.find((item) => Number(item.id) === Number(customerId));
    const addresses = customer ? customer.addresses : [];

    destinationSelect.innerHTML = '<option value="">Selecciona una direccion guardada</option>' + addresses.map((address) => `<option value="${address.id}">${address.label} - ${address.address}, ${address.city}</option>`).join('');

    if (!addresses.length) {
      coordinatesNotice.value = 'Sin coordenadas guardadas.';
    }
  }

  function applyAddress(customerId, addressId) {
    const customer = options.customers.find((item) => Number(item.id) === Number(customerId));
    const address = customer ? customer.addresses.find((item) => Number(item.id) === Number(addressId)) : null;

    if (!address) {
      coordinatesNotice.value = 'Usa una direccion del desplegable o captura manualmente.';
      return;
    }

    form.destinationAddress.value = address.address || '';
    form.destinationCity.value = address.city || '';
    form.destinationState.value = address.state || '';
    form.destinationPostalCode.value = address.postalCode || '';
    coordinatesNotice.value = address.latitude && address.longitude ? `Coordenadas guardadas: ${address.latitude}, ${address.longitude}` : 'Direccion guardada sin coordenadas.';
  }

  form.recipientId.addEventListener('change', () => loadRecipientAddresses(form.recipientId.value));
  destinationSelect.addEventListener('change', () => applyAddress(form.recipientId.value, destinationSelect.value));
  form.originWarehouseId.addEventListener('change', () => {
    const warehouse = options.warehouses.find((item) => Number(item.id) === Number(form.originWarehouseId.value));
    if (warehouse) {
      form.originAddress.value = `${warehouse.address}, ${warehouse.city}`;
    }
  });

  if (shipmentId) {
    const shipment = await window.LogisticHubCore.apiRequest(`/shipments/${shipmentId}`);
    form.tracking.value = shipment.tracking || '';
    form.senderId.value = shipment.senderId || '';
    form.recipientId.value = shipment.recipientId || '';
    loadRecipientAddresses(shipment.recipientId);
    form.originWarehouseId.value = shipment.originWarehouseId || '';
    form.originAddress.value = shipment.originAddress || '';
    form.weightKg.value = shipment.weightKg || '';
    form.quantity.value = shipment.quantity || '';
    form.volumeM3.value = shipment.volumeM3 || '';
    form.scheduledDate.value = shipment.scheduledDate || '';
    form.packageType.value = shipment.packageType || '';
    form.priority.value = shipment.priority || '';
    form.initialStatus.value = shipment.status || '';
    form.declaredValue.value = shipment.declaredValue || '';
    form.description.value = shipment.description || '';
    destinationSelect.value = shipment.destinationAddressId || '';
    form.destinationAddress.value = shipment.destinationAddress || '';
    form.destinationCity.value = shipment.destinationCity || '';
    form.destinationState.value = shipment.destinationState || '';
    form.destinationPostalCode.value = shipment.destinationPostalCode || '';
    applyAddress(shipment.recipientId, shipment.destinationAddressId);
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!form.senderId.value || !form.recipientId.value) {
      window.LogisticHubCore.renderNotice(noticeTarget, { type: 'error', message: 'Debes seleccionar remitente y destinatario.' });
      return;
    }

    const payload = {
      tracking: form.tracking.value.trim(),
      senderId: form.senderId.value,
      recipientId: form.recipientId.value,
      originWarehouseId: form.originWarehouseId.value,
      originAddress: form.originAddress.value.trim(),
      weightKg: form.weightKg.value,
      quantity: form.quantity.value,
      volumeM3: form.volumeM3.value,
      scheduledDate: form.scheduledDate.value,
      packageType: form.packageType.value,
      priority: form.priority.value,
      initialStatus: form.initialStatus.value,
      declaredValue: form.declaredValue.value,
      description: form.description.value.trim(),
      destinationAddressId: destinationSelect.value,
      destinationAddress: form.destinationAddress.value.trim(),
      destinationCity: form.destinationCity.value.trim(),
      destinationState: form.destinationState.value.trim(),
      destinationPostalCode: form.destinationPostalCode.value.trim(),
    };

    const response = await window.LogisticHubCore.apiRequest(shipmentId ? `/shipments/${shipmentId}` : '/shipments', {
      method: shipmentId ? 'PUT' : 'POST',
      data: payload,
    });

    const recommendation = response.recommendation && response.recommendation.route
      ? ` Ruta sugerida: ${response.recommendation.route.code} (${response.recommendation.score} pts).`
      : '';

    window.LogisticHubCore.setNotice('success', `${response.message || 'Envio guardado correctamente.'}${recommendation}`.trim());
    window.location.href = '/logistichub/shipments.html';
  });
});

