window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin'])) {
    return;
  }

  const userId = window.LogisticHubCore.queryParam('id');
  const form = document.querySelector('#userForm');
  const notice = document.querySelector('#formNotice');
  const driverStatusField = document.querySelector('#driverStatusField');
  const driverOptions = await window.LogisticHubCore.apiRequest('/drivers/options');

  const DEFAULT_PROFILES = {
    admin: { jobTitle: 'Administrador de plataforma', scheduleLabel: 'Jornada administrativa', workDays: 'Lun-Vie', shiftStart: '08:00', shiftEnd: '17:00', createDriver: 'false' },
    operator: { jobTitle: 'Operador logistico', scheduleLabel: 'Mesa operativa', workDays: 'Lun-Sab', shiftStart: '07:00', shiftEnd: '16:00', createDriver: 'false' },
    supervisor: { jobTitle: 'Supervisor logistico', scheduleLabel: 'Supervision regional', workDays: 'Lun-Sab', shiftStart: '09:00', shiftEnd: '18:00', createDriver: 'false' },
    dispatcher: { jobTitle: 'Despachador operativo', scheduleLabel: 'Despacho AM', workDays: 'Lun-Sab', shiftStart: '06:00', shiftEnd: '15:00', createDriver: 'false' },
    driver: { jobTitle: 'Conductor de reparto', scheduleLabel: 'Primera salida', workDays: 'Lun-Sab', shiftStart: '07:30', shiftEnd: '18:00', createDriver: 'true' },
    customer: { jobTitle: 'Contacto cliente', scheduleLabel: 'Portal cliente', workDays: 'Lun-Dom', shiftStart: '00:00', shiftEnd: '23:59', createDriver: 'false' },
  };

  form.driverStatus.innerHTML = '<option value="">Selecciona estado</option>' + driverOptions.statuses.map((item) => `<option value="${item}">${item}</option>`).join('');

  function applyDefaults(force = false) {
    const profile = DEFAULT_PROFILES[form.role.value] || DEFAULT_PROFILES.operator;

    ['jobTitle', 'scheduleLabel', 'workDays', 'shiftStart', 'shiftEnd'].forEach((fieldName) => {
      if (force || !form[fieldName].value) {
        form[fieldName].value = profile[fieldName] || '';
      }
    });

    if (form.role.value === 'driver') {
      form.createDriver.value = 'true';
      if (!form.driverStatus.value) {
        form.driverStatus.value = 'Disponible';
      }
    } else if (force && !userId) {
      form.createDriver.value = profile.createDriver;
    }
  }

  function syncDriverFields() {
    const shouldCreateDriver = form.role.value === 'driver' || form.createDriver.value === 'true';

    if (form.role.value === 'driver') {
      form.createDriver.value = 'true';
    }

    driverStatusField.classList.toggle('app-hidden', !shouldCreateDriver);
  }

  function updateUsernameSuggestion() {
    if (userId || form.username.dataset.touched === 'true' || !form.firstName.value.trim()) {
      return;
    }

    const parts = [form.firstName.value, form.lastName.value].map((value) => value.trim().toLowerCase()).filter(Boolean);
    form.username.value = parts.join('.').replace(/[^a-z0-9._-]+/g, '.').replace(/[.]{2,}/g, '.').replace(/^[._-]+|[._-]+$/g, '');
  }

  form.username.addEventListener('input', () => {
    form.username.dataset.touched = 'true';
  });

  form.firstName.addEventListener('input', updateUsernameSuggestion);
  form.lastName.addEventListener('input', updateUsernameSuggestion);
  form.role.addEventListener('change', () => {
    applyDefaults();
    syncDriverFields();
  });
  form.createDriver.addEventListener('change', syncDriverFields);

  if (userId) {
    const item = await window.LogisticHubCore.apiRequest(`/users/${userId}`);
    form.username.value = item.username || '';
    form.email.value = item.email || '';
    form.role.value = item.role || 'operator';
    form.active.value = String(Boolean(item.active));
    form.employeeCode.value = item.employeeCode || '';
    form.firstName.value = item.firstName || '';
    form.lastName.value = item.lastName || '';
    form.secondLastName.value = item.secondLastName || '';
    form.phone.value = item.phone || '';
    form.document.value = item.document || '';
    form.jobTitle.value = item.jobTitle || '';
    form.scheduleLabel.value = item.scheduleLabel || '';
    form.workDays.value = item.workDays || '';
    form.shiftStart.value = item.shiftStart || '';
    form.shiftEnd.value = item.shiftEnd || '';
    form.createDriver.value = String(Boolean(item.isDriver || item.role === 'driver'));
    form.driverStatus.value = item.driverStatus || '';
  } else {
    applyDefaults(true);
  }

  syncDriverFields();

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!userId && !form.password.value.trim()) {
      window.LogisticHubCore.renderNotice(notice, { type: 'error', message: 'Contraseña obligatoria al crear.' });
      return;
    }

    const response = await window.LogisticHubCore.apiRequest(userId ? `/users/${userId}` : '/users', {
      method: userId ? 'PUT' : 'POST',
      data: {
        username: form.username.value.trim(),
        email: form.email.value.trim(),
        password: form.password.value.trim(),
        role: form.role.value,
        active: form.active.value === 'true',
        employeeCode: form.employeeCode.value.trim(),
        firstName: form.firstName.value.trim(),
        lastName: form.lastName.value.trim(),
        secondLastName: form.secondLastName.value.trim(),
        phone: form.phone.value.trim(),
        document: form.document.value.trim(),
        jobTitle: form.jobTitle.value.trim(),
        scheduleLabel: form.scheduleLabel.value.trim(),
        workDays: form.workDays.value.trim(),
        shiftStart: form.shiftStart.value,
        shiftEnd: form.shiftEnd.value,
        createDriver: form.role.value === 'driver' || form.createDriver.value === 'true',
        driverStatus: form.driverStatus.value,
      },
    });

    window.LogisticHubCore.setNotice('success', response.message || 'Usuario guardado correctamente.');
    window.location.href = '/logistichub/usuarios.html';
  });
});