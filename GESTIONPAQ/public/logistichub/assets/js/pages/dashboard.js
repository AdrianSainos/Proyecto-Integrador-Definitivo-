window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'operator', 'supervisor', 'dispatcher', 'driver', 'customer'])) {
    return;
  }

  const user = window.LogisticHubCore.getUser();
  const profile = window.LogisticHubCore.getRoleProfile(user.role);
  const rangeSelect = document.querySelector('#dashboardRangeSelect');
  const roleExperience = {
    admin: {
      eyebrow: 'Direccion ejecutiva',
      title: 'Tablero ejecutivo',
      description: 'Supervision de servicio, capacidad y cumplimiento del periodo.',
      intro: 'Indicadores ejecutivos, cobertura y servicio en una sola vista.',
      actions: [
        { label: 'Nuevo envio', href: '/logistichub/envio-form.html', tone: 'success', icon: 'fa-solid fa-plus' },
        { label: 'Ver rutas', href: '/logistichub/rutas.html', tone: 'outline', icon: 'fa-solid fa-route' },
        { label: 'Reportes', href: '/logistichub/reportes.html', tone: 'primary', icon: 'fa-solid fa-chart-line' },
      ],
      highlights: ['Visibilidad completa', 'Configuracion habilitada', 'KPIs ejecutivos'],
    },
    operator: {
      eyebrow: 'Operacion central',
      title: 'Flujo diario bajo control',
      description: 'Priorizacion de salidas, incidencias y carga pendiente para el turno actual.',
      intro: 'Pendientes, despacho y continuidad del turno actual.',
      actions: [
        { label: 'Ir a operaciones', href: '/logistichub/operaciones.html', tone: 'primary', icon: 'fa-solid fa-wave-square' },
        { label: 'Nuevo envio', href: '/logistichub/envio-form.html', tone: 'success', icon: 'fa-solid fa-plus' },
        { label: 'Rastrear codigo', href: '/logistichub/rastreo.html', tone: 'outline', icon: 'fa-solid fa-location-crosshairs' },
      ],
      highlights: ['Salida prioritaria', 'Monitoreo de cola', 'Incidencias visibles'],
    },
    supervisor: {
      eyebrow: 'Capa de supervision',
      title: 'Rendimiento y excepciones',
      description: 'Lectura de cumplimiento, desbalance operativo y calidad de ejecucion.',
      intro: 'Brechas de SLA, balance operativo y seguimiento de cumplimiento.',
      actions: [
        { label: 'Abrir reportes', href: '/logistichub/reportes.html', tone: 'primary', icon: 'fa-solid fa-chart-line' },
        { label: 'Revisar clientes', href: '/logistichub/clientes.html', tone: 'outline', icon: 'fa-solid fa-users-line' },
        { label: 'Ver flota', href: '/logistichub/vehiculos.html', tone: 'outline', icon: 'fa-solid fa-truck-fast' },
      ],
      highlights: ['SLA y capacidad', 'Alertas laterales', 'Comparativos rapidos'],
    },
    dispatcher: {
      eyebrow: 'Cabina de despacho',
      title: 'Capacidad en movimiento',
      description: 'Asignaciones, rutas activas y cobertura de salida con foco en ejecucion.',
      intro: 'Rutas, unidades y cobertura de salida en tiempo real.',
      actions: [
        { label: 'Gestionar rutas', href: '/logistichub/rutas.html', tone: 'primary', icon: 'fa-solid fa-route' },
        { label: 'Ver flota', href: '/logistichub/vehiculos.html', tone: 'outline', icon: 'fa-solid fa-truck-fast' },
        { label: 'Cola de envios', href: '/logistichub/envios.html', tone: 'success', icon: 'fa-solid fa-boxes-stacked' },
      ],
      highlights: ['Asignacion viva', 'Cobertura de ruta', 'Capacidad util'],
    },
    driver: {
      eyebrow: 'Operacion de ultima milla',
      title: 'Tu jornada en ruta',
      description: 'Entregas asignadas, secuencia de eventos y visibilidad de progreso personal.',
      intro: 'Ruta asignada, progreso y entregas visibles del turno.',
      actions: [
        { label: 'Abrir rutas', href: '/logistichub/rutas.html', tone: 'primary', icon: 'fa-solid fa-route' },
        { label: 'Ver rastreo', href: '/logistichub/rastreo.html', tone: 'outline', icon: 'fa-solid fa-location-crosshairs' },
      ],
      highlights: ['Ruta personal', 'Progreso diario', 'Eventos de entrega'],
    },
    customer: {
      eyebrow: 'Portal de cliente',
      title: 'Seguimiento con contexto',
      description: 'Vista clara del estado de tus envios, hitos y trazabilidad reciente.',
      intro: 'Estado, hitos y trazabilidad reciente de tus envios.',
      actions: [
        { label: 'Rastrear envio', href: '/logistichub/rastreo.html', tone: 'primary', icon: 'fa-solid fa-location-crosshairs' },
        { label: 'Ver tus envios', href: '/logistichub/envios.html', tone: 'outline', icon: 'fa-solid fa-boxes-stacked' },
      ],
      highlights: ['Visibilidad propia', 'Timeline claro', 'Consulta segura'],
    },
  }[user.role];

  window.LogisticHubCore.applyShellIntro(roleExperience);

  document.querySelector('#roleExperience').innerHTML = `
    <section class="role-hero slide-in-up">
      <div class="role-hero-copy">
        <div class="eyebrow">${roleExperience.eyebrow}</div>
        <h2 class="role-hero-title">${roleExperience.title}</h2>
        <p class="role-hero-description">${roleExperience.intro}</p>
        <div class="chip-row">
          ${roleExperience.highlights.map((item) => `<span class="chip"><i class="${profile.icon}"></i>${item}</span>`).join('')}
        </div>
      </div>
      <div class="role-hero-actions">
        ${roleExperience.actions.map((action) => `<a class="btn btn-${action.tone}" href="${action.href}"><i class="${action.icon}"></i>${action.label}</a>`).join('')}
      </div>
    </section>
  `;

  const pageActions = document.querySelector('.page-actions');

  if (pageActions) {
    pageActions.classList.add('app-hidden');
  }
  const colors = ['#007bff', '#17a2b8', '#4f8f5b', '#f4a300', '#dc3545', '#6f42c1'];

  const operationsChart = new Chart(document.querySelector('#operationsChart'), {
    type: 'bar',
    data: { labels: [], datasets: [{ label: 'Movimientos', data: [], backgroundColor: colors[0] }] },
  });

  const statusChart = new Chart(document.querySelector('#statusChart'), {
    type: 'doughnut',
    data: { labels: [], datasets: [{ data: [], backgroundColor: colors.slice(0, 6) }] },
  });

  const hourlyChart = new Chart(document.querySelector('#hourlyChart'), {
    type: 'line',
    data: { labels: [], datasets: [{ label: 'Entregas', data: [], borderColor: colors[4], backgroundColor: 'rgba(40,167,69,0.16)', fill: true }] },
  });

  const routeStateChart = new Chart(document.querySelector('#routeStateChart'), {
    type: 'bar',
    data: { labels: [], datasets: [{ label: 'Rutas', data: [], backgroundColor: colors.slice(1, 5) }] },
  });

  function renderChartSummary(target, labels, values, includePercent = true) {
    const element = document.querySelector(target);

    if (!element) {
      return;
    }

    const safeValues = Array.isArray(values) ? values.map((value) => Number(value || 0)) : [];
    const total = safeValues.reduce((sum, value) => sum + value, 0);

    element.innerHTML = (labels || []).map((label, index) => {
      const value = safeValues[index] || 0;
      const percent = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
      const detail = includePercent ? `${value} (${percent}%)` : `${value}`;

      return `
        <div class="chart-summary-row">
          <span>${label}</span>
          <strong>${detail}</strong>
        </div>
      `;
    }).join('') || '<div class="text-muted">Sin datos para el rango actual.</div>';
  }

  async function renderDashboard() {
    const selectedRange = rangeSelect ? rangeSelect.value : 'week';
    const data = await window.LogisticHubCore.apiRequest(`/dashboard?range=${encodeURIComponent(selectedRange)}`);

    document.querySelector('#dashboardRangeCaption').textContent = `Rango activo: ${data.range.label}`;

    const exceptionGroups = user.role === 'customer'
      ? [
          { title: 'Envios por despachar', items: data.exceptions.pendingDeparture.map((item) => item.tracking) },
          { title: 'Rutas vinculadas', items: data.exceptions.activeRoutes.map((item) => item.code) },
          { title: 'Actualizaciones recientes', items: data.leaderboards.customers.map((item) => item.name) },
        ]
      : user.role === 'driver'
        ? [
            { title: 'Asignaciones activas', items: data.exceptions.activeRoutes.map((item) => item.code) },
            { title: 'Entregas pendientes', items: data.exceptions.pendingDeparture.map((item) => item.tracking) },
            { title: 'Soporte de unidad', items: data.exceptions.maintenanceUnits.map((item) => item.plate) },
          ]
        : [
            { title: 'Envios pendientes de salida', items: data.exceptions.pendingDeparture.map((item) => item.tracking) },
            { title: 'Unidades con mantenimiento activo', items: data.exceptions.maintenanceUnits.map((item) => item.plate) },
            { title: 'Conductores fuera de turno', items: data.exceptions.outOfShiftDrivers.map((item) => item.name) },
            { title: 'Rutas en ejecucion o preparacion', items: data.exceptions.activeRoutes.map((item) => item.code) },
          ];

    document.querySelector('#dashboardStrip').innerHTML = data.strip
      .map((item) => `
        <article class="strip-card ${item.className}">
          <div class="strip-label">${item.title}</div>
          <div class="strip-value">${item.value}</div>
          <div class="text-muted">${item.subtitle}</div>
        </article>
      `)
      .join('');

    document.querySelector('#dashboardKpis').innerHTML = data.kpis
      .map((item, index) => `
        <article class="kpi-card slide-in-up" style="animation-delay:${index * 70}ms;">
          <div class="kpi-top">
            <div>
              <p class="kpi-title">${item.title}</p>
              <div class="kpi-value">${item.value}</div>
            </div>
            <div class="kpi-icon"><i class="fa-solid fa-chart-column"></i></div>
          </div>
          <div class="kpi-note">${item.detail}</div>
        </article>
      `)
      .join('');

    document.querySelector('#exceptionsPanel').innerHTML = exceptionGroups
      .map((group) => `
        <div class="stack-item">
          <div>
            <div class="card-title">${group.title}</div>
            <div class="text-muted">${group.items.length ? group.items.join(', ') : 'Sin incidencias.'}</div>
          </div>
        </div>
      `)
      .join('');

    document.querySelector('#pulsePanel').innerHTML = `
      <div class="stack-item"><span>Rutas completadas</span><strong>${data.pulse.completedRoutes}</strong></div>
      <div class="stack-item"><span>Vehiculos en uso</span><strong>${data.pulse.vehiclesInUse}</strong></div>
      <div class="stack-item"><span>Conductores activos</span><strong>${data.pulse.activeDrivers}</strong></div>
    `;

    document.querySelector('#topDrivers').innerHTML = data.leaderboards.drivers
      .map((driver) => `<div class="stack-item"><div><div class="card-title">${driver.name}</div><div class="text-muted">${driver.shift}</div></div><strong>${driver.deliveriesToday} entregas</strong></div>`)
      .join('');

    document.querySelector('#topCustomers').innerHTML = data.leaderboards.customers
      .map((customer) => `<div class="stack-item"><div><div class="card-title">${customer.name}</div><div class="text-muted">${customer.email}</div></div><strong>${customer.serviceLevel}</strong></div>`)
      .join('');

    operationsChart.data.labels = data.charts.operationalEvolution.labels;
    operationsChart.data.datasets[0].data = data.charts.operationalEvolution.data;
    operationsChart.update();
    renderChartSummary('#operationsChartSummary', data.charts.operationalEvolution.labels, data.charts.operationalEvolution.data);

    statusChart.data.labels = data.charts.packageStatus.labels;
    statusChart.data.datasets[0].data = data.charts.packageStatus.data;
    statusChart.update();
    renderChartSummary('#statusChartSummary', data.charts.packageStatus.labels, data.charts.packageStatus.data);

    hourlyChart.data.labels = data.charts.deliveriesByHour.labels;
    hourlyChart.data.datasets[0].data = data.charts.deliveriesByHour.data;
    hourlyChart.update();
    renderChartSummary('#hourlyChartSummary', data.charts.deliveriesByHour.labels, data.charts.deliveriesByHour.data);

    routeStateChart.data.labels = data.charts.routeState.labels;
    routeStateChart.data.datasets[0].data = data.charts.routeState.data;
    routeStateChart.update();
    renderChartSummary('#routeStateChartSummary', data.charts.routeState.labels, data.charts.routeState.data);
  }

  if (rangeSelect) {
    rangeSelect.addEventListener('change', renderDashboard);
  }

  renderDashboard();
});
