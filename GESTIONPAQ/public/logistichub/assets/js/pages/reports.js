window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'supervisor'])) {
    return;
  }

  const data = await window.LogisticHubCore.apiRequest('/reports?type=daily&range=today');

  document.querySelector('#reportsCards').innerHTML = data.cards.map((item) => `
    <article class="report-card">
      <div class="small-label">${item.title}</div>
      <div class="metric-value">${item.value}</div>
      <div class="text-muted">${item.detail}</div>
    </article>
  `).join('');

  document.querySelector('#reportsTableBody').innerHTML = data.rows.map((item) => `
    <tr>
      <td>${item.metric}</td>
      <td>${item.value}</td>
      <td>${item.variation}</td>
    </tr>
  `).join('');

  function exportNotice(type) {
    window.LogisticHubCore.setNotice('info', `La exportacion ${type} queda preparada para el backend.`);
    window.LogisticHubCore.renderNotice('#globalNotice', window.LogisticHubCore.consumeNotice());
  }

  document.querySelector('#exportCsvButton').addEventListener('click', () => exportNotice('CSV'));
  document.querySelector('#exportPdfButton').addEventListener('click', () => exportNotice('PDF'));
});