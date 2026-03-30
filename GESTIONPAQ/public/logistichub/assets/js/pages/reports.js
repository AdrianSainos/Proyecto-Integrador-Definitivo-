window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'supervisor'])) {
    return;
  }

  const rangeSelect = document.querySelector('#reportsRangeSelect');
  const notice = document.querySelector('#reportsNotice');

  async function loadReport() {
    const range = rangeSelect ? rangeSelect.value : 'today';
    const data = await window.LogisticHubCore.apiRequest(`/reports?type=summary&range=${encodeURIComponent(range)}`);

    document.querySelector('#reportsRangeCaption').textContent = `Rango actual: ${data.range.label}`;
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

    notice.innerHTML = '';
  }

  async function exportReport(format) {
    const range = rangeSelect ? rangeSelect.value : 'today';

    try {
      await window.LogisticHubCore.downloadFile(`/reports/export/${format}?range=${encodeURIComponent(range)}`, `reporte-${range}.${format}`);
      notice.innerHTML = '<div class="notice notice-success">Reporte descargado correctamente.</div>';
    } catch (error) {
      window.LogisticHubCore.renderNotice(notice, { type: 'error', message: error.message });
    }
  }

  if (rangeSelect) {
    rangeSelect.addEventListener('change', loadReport);
  }

  document.querySelector('#exportCsvButton').addEventListener('click', () => exportReport('csv'));
  document.querySelector('#exportPdfButton').addEventListener('click', () => exportReport('pdf'));

  loadReport();
});