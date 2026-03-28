window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'supervisor', 'dispatcher'])) {
    return;
  }

  const driverId = window.LogisticHubCore.queryParam('id');
  const form = document.querySelector('#driverForm');
  const options = await window.LogisticHubCore.apiRequest('/drivers/options');

  form.personId.innerHTML = '<option value="">Selecciona persona</option>' + options.people.map((item) => `<option value="${item.id}">${item.name}</option>`).join('');
  form.status.innerHTML = '<option value="">Selecciona estado</option>' + options.statuses.map((item) => `<option value="${item}">${item}</option>`).join('');

  form.personId.addEventListener('change', () => {
    const person = options.people.find((item) => Number(item.id) === Number(form.personId.value));
    if (person && !form.name.value) {
      form.name.value = person.name;
    }
  });

  if (driverId) {
    const item = await window.LogisticHubCore.apiRequest(`/drivers/${driverId}`);
    form.personId.value = item.personId || '';
    form.name.value = item.name || '';
    form.phone.value = item.phone || '';
    form.status.value = item.status || '';
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const response = await window.LogisticHubCore.apiRequest(driverId ? `/drivers/${driverId}` : '/drivers', {
      method: driverId ? 'PUT' : 'POST',
      data: {
        personId: form.personId.value,
        name: form.name.value.trim(),
        phone: form.phone.value.trim(),
        status: form.status.value,
      },
    });

    window.LogisticHubCore.setNotice('success', response.message);
    window.location.href = '/logistichub/drivers.html';
  });
});