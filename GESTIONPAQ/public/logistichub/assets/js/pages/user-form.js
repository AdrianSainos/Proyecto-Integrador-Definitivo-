window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin'])) {
    return;
  }

  const userId = window.LogisticHubCore.queryParam('id');
  const form = document.querySelector('#userForm');
  const notice = document.querySelector('#formNotice');

  if (userId) {
    const item = await window.LogisticHubCore.apiRequest(`/users/${userId}`);
    form.email.value = item.email || '';
    form.role.value = item.role || 'operator';
    form.active.value = String(Boolean(item.active));
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    if (!userId && !form.password.value.trim()) {
      window.LogisticHubCore.renderNotice(notice, { type: 'error', message: 'Password obligatoria al crear.' });
      return;
    }

    const response = await window.LogisticHubCore.apiRequest(userId ? `/users/${userId}` : '/users', {
      method: userId ? 'PUT' : 'POST',
      data: {
        email: form.email.value.trim(),
        password: form.password.value.trim(),
        role: form.role.value,
        active: form.active.value === 'true',
      },
    });

    window.LogisticHubCore.setNotice('success', response.message || 'Usuario guardado correctamente.');
    window.location.href = '/logistichub/users.html';
  });
});