window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'operator', 'supervisor', 'dispatcher', 'customer'])) {
    return;
  }

  const user = window.LogisticHubCore.getUser();
  const profile = window.LogisticHubCore.getRoleProfile(user.role);
  const form = document.querySelector('#trackingForm');
  const input = document.querySelector('#trackingCode');
  const preset = window.LogisticHubCore.queryParam('code');

  window.LogisticHubCore.applyShellIntro(
    user.role === 'customer'
      ? {
          eyebrow: 'Portal de cliente',
          title: 'Seguimiento de tus envios',
          description: 'Consulta de tracking y linea de tiempo limitada a la visibilidad de tu cuenta.',
        }
      : {
          eyebrow: 'Rastreo',
          title: 'Seguimiento operativo',
          description: 'Consulta de tracking, timeline de eventos y lectura rapida de contexto logistico.',
        }
  );

  document.querySelector('#trackingPersona').innerHTML = `
    <article class="persona-panel">
      <div>
        <div class="small-label">${profile.label}</div>
        <h2 class="card-title">Rastreo contextual</h2>
        <p class="card-subtitle">${user.role === 'customer' ? 'Puedes consultar solo envios vinculados a tu cuenta. El timeline conserva el mismo detalle visual del resto del producto.' : 'El timeline conserva hitos, ubicacion y estado para soporte operativo o consulta interna.'}</p>
      </div>
      <span class="role-pill"><i class="${profile.icon}"></i>${profile.mode}</span>
    </article>
  `;

  async function search(code) {
    try {
      const response = await window.LogisticHubCore.apiRequest(`/tracking/${encodeURIComponent(code)}`);

      document.querySelector('#trackingSummary').innerHTML = `
        <div class="stack-item"><span>Tracking</span><strong>${response.shipment.tracking}</strong></div>
        <div class="stack-item"><span>Cliente</span><strong>${response.shipment.customerName}</strong></div>
        <div class="stack-item"><span>Estado</span><strong>${response.shipment.status}</strong></div>
        <div class="stack-item"><span>Ruta</span><strong>${response.shipment.routeCode}</strong></div>
        <div class="stack-item"><span>Destino</span><strong>${response.shipment.destinationAddress}, ${response.shipment.destinationCity}</strong></div>
      `;

      document.querySelector('#trackingTimeline').innerHTML = response.events.map((event) => `
        <div class="timeline-item">
          <div class="timeline-dot"></div>
          <div>
            <div class="card-title">${event.type}</div>
            <div>${event.description}</div>
            <div class="text-muted">${event.location} - ${window.LogisticHubCore.toDate(event.timestamp)}</div>
          </div>
        </div>
      `).join('');

      document.querySelector('#trackingEvidence').innerHTML = response.evidences && response.evidences.length
        ? response.evidences.map((item) => `
          <article class="evidence-entry">
            <div class="stack-item"><span>Receptor</span><strong>${item.recipientName}</strong></div>
            <div class="stack-item"><span>Entrega</span><strong>${window.LogisticHubCore.toDate(item.deliveryTimestamp)}</strong></div>
            <div class="stack-item"><span>Conductor</span><strong>${item.driverName || 'Sin conductor'}</strong></div>
            <div class="stack-item"><span>Estado</span><strong>${item.status}</strong></div>
            ${item.photoUrl ? `<a class="btn btn-outline btn-sm" href="${item.photoUrl}" target="_blank" rel="noreferrer">Abrir foto</a>` : '<div class="text-muted">Sin foto registrada</div>'}
            ${item.signatureUrl ? `<a class="btn btn-outline btn-sm" href="${item.signatureUrl}" target="_blank" rel="noreferrer">Abrir firma</a>` : item.signatureText ? `<div class="text-muted">Firma: ${item.signatureText}</div>` : '<div class="text-muted">Sin firma registrada</div>'}
            ${item.notes ? `<div class="text-muted">${item.notes}</div>` : ''}
          </article>
        `).join('')
        : '<div class="stack-item"><span>Evidencia</span><strong>Sin pruebas registradas</strong></div>';

      document.querySelector('#trackingNotice').innerHTML = '';
    } catch (error) {
      window.LogisticHubCore.renderNotice('#trackingNotice', { type: 'error', message: error.message });
    }
  }

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    if (input.value.trim()) {
      search(input.value.trim());
    }
  });

  if (preset) {
    input.value = preset;
    search(preset);
  }
});
