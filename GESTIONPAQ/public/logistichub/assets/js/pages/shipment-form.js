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
  fillSelect('#initialStatus', options.editableStatuses || options.statuses, 'Selecciona estado', (item) => ({ value: item, label: item }));

  const statusDescriptions = options.statusDescriptions || {};
  const statusDescriptionEl = document.querySelector('#statusDescription');
  const operationalBadge = document.querySelector('#operationalStatusBadge');
  const currentStatusBadge = document.querySelector('#currentStatusBadge');
  const assignmentSection = document.querySelector('#assignmentInfoSection');
  const editableSet = new Set(options.editableStatuses || options.statuses);

  document.querySelector('#initialStatus').addEventListener('change', () => {
    const val = document.querySelector('#initialStatus').value;
    statusDescriptionEl.textContent = statusDescriptions[val] || 'Selecciona un estado para ver su descripcion.';
  });

  const destinationSelect = document.querySelector('#destinationAddressId');
  const manualDestinationHelp = document.querySelector('#manualDestinationHelp');

  function setDestinationHelp(message) {
    if (manualDestinationHelp) {
      manualDestinationHelp.textContent = message;
    }
  }

  function fillDestinationFields(address) {
    form.destinationAddress.value = address.address || '';
    form.destinationCity.value = address.city || '';
    form.destinationState.value = address.state || '';
    form.destinationPostalCode.value = address.postalCode || '';
  }

  function clearDestinationFields() {
    destinationSelect.value = '';
    fillDestinationFields({ address: '', city: '', state: '', postalCode: '' });
  }

  function validateDistinctParticipants(changedField) {
    if (!form.senderId.value || !form.recipientId.value) {
      return true;
    }

    if (String(form.senderId.value) !== String(form.recipientId.value)) {
      return true;
    }

    if (changedField === 'recipient') {
      form.recipientId.value = '';
      loadRecipientAddresses('');
    } else if (changedField === 'sender') {
      form.senderId.value = '';
    }

    window.LogisticHubCore.renderNotice(noticeTarget, {
      type: 'error',
      message: 'El destinatario debe ser diferente del remitente.',
    });

    return false;
  }

  function loadRecipientAddresses(customerId, selectedAddressId = '') {
    const customer = options.customers.find((item) => Number(item.id) === Number(customerId));
    const addresses = customer ? customer.addresses : [];

    destinationSelect.innerHTML = '<option value="">Selecciona una direccion guardada</option>' + addresses.map((address) => `<option value="${address.id}">${address.label} - ${address.address}, ${address.city}</option>`).join('');

    const hasSelectedAddress = Number(selectedAddressId) > 0 && addresses.some((address) => Number(address.id) === Number(selectedAddressId));

    if (hasSelectedAddress) {
      destinationSelect.value = String(selectedAddressId);
      applyAddress(customerId, selectedAddressId);
      return;
    }

    clearDestinationFields();

    if (!customerId) {
      setDestinationHelp('Selecciona un destinatario para ver sus direcciones guardadas o captura el destino manualmente.');
      return;
    }

    setDestinationHelp('Selecciona una dirección guardada o completa la dirección manualmente.');
  }

  function applyAddress(customerId, addressId) {
    const customer = options.customers.find((item) => Number(item.id) === Number(customerId));
    const address = customer ? customer.addresses.find((item) => Number(item.id) === Number(addressId)) : null;

    if (!address) {
      destinationSelect.value = '';
      setDestinationHelp('Selecciona una dirección guardada o completa la dirección manualmente.');
      return false;
    }

    fillDestinationFields(address);
setDestinationHelp('Dirección guardada cargada automáticamente para este destinatario.');

    return true;
  }

  form.senderId.addEventListener('change', () => {
    validateDistinctParticipants('sender');
  });
  form.recipientId.addEventListener('change', () => {
    if (!validateDistinctParticipants('recipient')) {
      return;
    }

    loadRecipientAddresses(form.recipientId.value);
  });
  destinationSelect.addEventListener('change', () => {
    if (!destinationSelect.value) {
      clearDestinationFields();
      setDestinationHelp('Selecciona una dirección guardada o completa la dirección manualmente.');
      return;
    }

    applyAddress(form.recipientId.value, destinationSelect.value);
  });
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
    loadRecipientAddresses(shipment.recipientId, shipment.destinationAddressId);
    form.originWarehouseId.value = shipment.originWarehouseId || '';
    form.originAddress.value = shipment.originAddress || '';
    form.weightKg.value = shipment.weightKg || '';
    form.quantity.value = shipment.quantity || '';
    form.volumeM3.value = shipment.volumeM3 || '';
    form.scheduledDate.value = shipment.scheduledDate || '';
    form.packageType.value = shipment.packageType || '';
    form.priority.value = shipment.priority || '';
    form.declaredValue.value = shipment.declaredValue || '';
    form.description.value = shipment.description || '';

    const hasSavedDestination = Number(shipment.destinationAddressId) > 0
      && Array.from(destinationSelect.options).some((option) => Number(option.value) === Number(shipment.destinationAddressId));

    if (!hasSavedDestination) {
      fillDestinationFields({
        address: shipment.destinationAddress || '',
        city: shipment.destinationCity || '',
        state: shipment.destinationState || '',
        postalCode: shipment.destinationPostalCode || '',
      });
      setDestinationHelp('Destino actual cargado desde el envío. Puedes cambiarlo por una dirección guardada o editarlo manualmente.');
    }

    const currentStatus = shipment.status || 'Pendiente';

    if (editableSet.has(currentStatus)) {
      form.initialStatus.value = currentStatus;
    } else {
      form.initialStatus.value = '';
      operationalBadge.style.display = '';
      currentStatusBadge.textContent = currentStatus;
      currentStatusBadge.title = statusDescriptions[currentStatus] || '';
    }

    statusDescriptionEl.textContent = statusDescriptions[currentStatus] || '';

    if (currentStatus.toLowerCase() === 'entregado') {
      form.initialStatus.disabled = true;
      statusDescriptionEl.textContent = 'Estado final: el envío ya fue entregado. No se puede cambiar el estado.';
    }

    const hasAssignment = shipment.routeCode && shipment.routeCode !== 'Pendiente';
    if (hasAssignment && assignmentSection) {
      assignmentSection.style.display = '';
      document.querySelector('#assignedRoute').value = shipment.routeCode || 'Sin ruta';
      document.querySelector('#assignedRouteStatus').value = shipment.routeStatus || 'Sin estado';
      document.querySelector('#assignedDriver').value = shipment.driverName || 'Pendiente';
      document.querySelector('#assignedVehicle').value = shipment.vehiclePlate || 'Pendiente';
      document.querySelector('#assignedWarehouse').value = shipment.warehouseName || 'Sin almacen';
      document.querySelector('#assignedPromisedDate').value = shipment.promisedDate || shipment.scheduledDate || 'Sin fecha';
    }
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!form.senderId.value || !form.recipientId.value) {
      window.LogisticHubCore.renderNotice(noticeTarget, { type: 'error', message: 'Debes seleccionar remitente y destinatario.' });
      return;
    }

    if (String(form.senderId.value) === String(form.recipientId.value)) {
      window.LogisticHubCore.renderNotice(noticeTarget, { type: 'error', message: 'El destinatario debe ser diferente del remitente.' });
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
      initialStatus: form.initialStatus.value || '',
      // NOTE: leave initialStatus empty so auto-assignment works
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
    window.location.href = '/logistichub/envios.html';
  });
});

