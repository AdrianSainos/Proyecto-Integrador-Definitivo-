window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'supervisor', 'dispatcher'])) {
    return;
  }

  const form = document.querySelector('#evidenceFilters');
  const trackingFilter = document.querySelector('#trackingFilter');
  const statusFilter = document.querySelector('#statusFilter');
  trackingFilter.value = window.LogisticHubCore.queryParam('tracking') || '';
  statusFilter.value = window.LogisticHubCore.queryParam('status') || '';
  window.LogisticHubCore.renderNotice('#pageNotice', window.LogisticHubCore.consumeNotice());

  async function loadEvidences() {
    const params = new URLSearchParams();

    if (trackingFilter.value.trim()) {
      params.set('tracking', trackingFilter.value.trim());
    }

    if (statusFilter.value) {
      params.set('status', statusFilter.value);
    }

    const query = params.toString() ? `?${params.toString()}` : '';
    const items = await window.LogisticHubCore.apiRequest(`/evidences${query}`);
    const body = document.querySelector('#evidencesTableBody');

    if (!items.length) {
      window.LogisticHubCore.tableMessage(body, 'No hay evidencias para los filtros actuales.', 7);
      return;
    }

    body.innerHTML = items.map((item) => `
      <tr>
        <td>${item.tracking}</td>
        <td>${item.recipientName}</td>
        <td>${item.driverName || 'Sin conductor'}</td>
        <td>${window.LogisticHubCore.toDate(item.deliveryTimestamp)}</td>
        <td>${item.photoUrl ? `<a href="${item.photoUrl}" target="_blank" rel="noreferrer"><img class="evidence-thumb" src="${item.photoUrl}" alt="Foto de entrega ${item.tracking}" /></a>` : '<span class="text-muted">Sin foto</span>'}</td>
        <td>${item.signatureUrl ? `<a class="btn btn-outline btn-sm" href="${item.signatureUrl}" target="_blank" rel="noreferrer">Abrir</a>` : item.signatureText ? item.signatureText : '<span class="text-muted">Sin firma</span>'}</td>
        <td><a class="btn btn-outline btn-sm" href="/logistichub/rastreo.html?code=${encodeURIComponent(item.tracking)}">Ver tracking</a></td>
      </tr>
    `).join('');
  }

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    loadEvidences();
  });

  loadEvidences();
});