window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin'])) {
    return;
  }

  const form = document.querySelector('#settingsForm');
  const response = await window.LogisticHubCore.apiRequest('/settings');

  form.companyName.value = response.companyName || '';
  form.supportEmail.value = response.supportEmail || '';
  form.supportPhone.value = response.supportPhone || '';
  form.dispatchStartTime.value = response.dispatchStartTime || '';
  form.defaultLeadDays.value = response.defaultLeadDays || 0;
  form.maxDeliveryAttempts.value = response.maxDeliveryAttempts || 0;
  form.requirePhoto.checked = Boolean(response.requirePhoto);
  form.requireSignature.checked = Boolean(response.requireSignature);

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const saved = await window.LogisticHubCore.apiRequest('/settings', {
      method: 'PUT',
      data: {
        companyName: form.companyName.value.trim(),
        supportEmail: form.supportEmail.value.trim(),
        supportPhone: form.supportPhone.value.trim(),
        dispatchStartTime: form.dispatchStartTime.value,
        defaultLeadDays: Number(form.defaultLeadDays.value || 0),
        maxDeliveryAttempts: Number(form.maxDeliveryAttempts.value || 0),
        requirePhoto: form.requirePhoto.checked,
        requireSignature: form.requireSignature.checked,
      },
    });

    window.LogisticHubCore.renderNotice('#formNotice', { type: 'success', message: saved.message });
  });
});