window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'operator', 'supervisor', 'dispatcher', 'driver', 'customer'])) {
    return;
  }

  const user = window.LogisticHubCore.getUser();
  const profile = window.LogisticHubCore.getRoleProfile(user.role);
  const rangeSelect = document.querySelector('#dashboardRangeSelect');
  
  // Obtener experiencia del dashboard desde la configuración centralizada
  const roleExperience = window.LogisticHubRoleConfig ? 
    window.LogisticHubRoleConfig.get(user.role).dashboardExperience : 
    null;

  if (!roleExperience) return;

  window.LogisticHubCore.applyShellIntro(roleExperience);

  // Renderizar sección de hero
  const highlightsHTML = roleExperience.highlights
    .map((item) => `<span class="chip"><i class="${profile.icon}"></i>${item}</span>`)
    .join('');

  const actionsHTML = roleExperience.actions
    .map((action) => `<a class="btn btn-${action.tone}" href="${action.href}"><i class="${action.icon}"></i>${action.label}</a>`)
    .join('');

  document.querySelector('#roleExperience').innerHTML = `
    <section class="role-hero slide-in-up">
      <div class="role-hero-copy">
        <div class="eyebrow">${roleExperience.eyebrow}</div>
        <h2 class="role-hero-title">${roleExperience.title}</h2>
        <p class="role-hero-description">${roleExperience.intro}</p>
        <div class="chip-row">${highlightsHTML}</div>
      </div>
      <div class="role-hero-actions">${actionsHTML}</div>
    </section>
  `;

  const pageActions = document.querySelector('.page-actions');
  if (pageActions) {
    pageActions.classList.add('app-hidden');
  }

  // Configuración de gráficos
  const colors = ['#007bff', '#17a2b8', '#4f8f5b', '#f4a300', '#dc3545', '#6f42c1'];
  const charts = {};

  function initializeCharts() {
    const chartSelectors = ['operationsChart', 'statusChart', 'hourlyChart', 'routeStateChart'];
    
    charts.operations = new Chart(document.querySelector('#operationsChart'), {
      type: 'bar',
      data: { labels: [], datasets: [{ label: 'Movimientos', data: [], backgroundColor: colors[0] }] },
    });

    charts.status = new Chart(document.querySelector('#statusChart'), {
      type: 'doughnut',
      data: { labels: [], datasets: [{ data: [], backgroundColor: colors.slice(0, 6) }] },
    });

    charts.hourly = new Chart(document.querySelector('#hourlyChart'), {
      type: 'line',
      data: { labels: [], datasets: [{ label: 'Entregas', data: [], borderColor: colors[4], backgroundColor: 'rgba(40,167,69,0.16)', fill: true }] },
    });

    charts.routeState = new Chart(document.querySelector('#routeStateChart'), {
      type: 'bar',
      data: { labels: [], datasets: [{ label: 'Rutas', data: [], backgroundColor: colors.slice(1, 5) }] },
    });
  }

  function renderChartSummary(target, labels, values, includePercent = true) {
    const element = document.querySelector(target);
    if (!element) return;

    const safeValues = Array.isArray(values) ? values.map((value) => Number(value || 0)) : [];
    const total = safeValues.reduce((sum, value) => sum + value, 0);

    element.innerHTML = (labels || [])
      .map((label, index) => {
        const value = safeValues[index] || 0;
        const percent = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
        const detail = includePercent ? `${value} (${percent}%)` : `${value}`;

        return `<div class="chart-summary-row"><span>${label}</span><strong>${detail}</strong></div>`;
      })
      .join('') || '<div class="text-muted">Sin datos para el rango actual.</div>';
  }

  function buildExceptionGroups(data) {
    const baseGroups = {
      customer: [
        { title: 'Envios por despachar', items: data.exceptions.pendingDeparture.map((item) => item.tracking) },
        { title: 'Rutas vinculadas', items: data.exceptions.activeRoutes.map((item) => item.code) },
        { title: 'Actualizaciones recientes', items: data.leaderboards.customers.map((item) => item.name) },
      ],
      driver: [
        { title: 'Asignaciones activas', items: data.exceptions.activeRoutes.map((item) => item.code) },
        { title: 'Entregas pendientes', items: data.exceptions.pendingDeparture.map((item) => item.tracking) },
        { title: 'Soporte de unidad', items: data.exceptions.maintenanceUnits.map((item) => item.plate) },
      ],
      default: [
        { title: 'Envíos pendientes de salida', items: data.exceptions.pendingDeparture.map((item) => item.tracking) },
        { title: 'Unidades con mantenimiento activo', items: data.exceptions.maintenanceUnits.map((item) => item.plate) },
        { title: 'Conductores fuera de turno', items: data.exceptions.outOfShiftDrivers.map((item) => item.name) },
        { title: 'Rutas en ejecución o preparación', items: data.exceptions.activeRoutes.map((item) => item.code) },
      ],
    };

    return baseGroups[user.role] || baseGroups.default;
  }

  function updateDashboardContent(data) {
    document.querySelector('#dashboardRangeCaption').textContent = `Rango activo: ${data.range.label}`;

    // Render strip
    document.querySelector('#dashboardStrip').innerHTML = data.strip
      .map((item) => `
        <article class="strip-card ${item.className}">
          <div class="strip-label">${item.title}</div>
          <div class="strip-value">${item.value}</div>
          <div class="text-muted">${item.subtitle}</div>
        </article>
      `)
      .join('');

    // Render KPIs
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

    // Render exceptions
    const exceptionGroups = buildExceptionGroups(data);
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

    // Render pulse
    document.querySelector('#pulsePanel').innerHTML = `
      <div class="stack-item"><span>Rutas completadas</span><strong>${data.pulse.completedRoutes}</strong></div>
      <div class="stack-item"><span>Vehiculos en uso</span><strong>${data.pulse.vehiclesInUse}</strong></div>
      <div class="stack-item"><span>Conductores activos</span><strong>${data.pulse.activeDrivers}</strong></div>
    `;

    // Render leaderboards
    document.querySelector('#topDrivers').innerHTML = data.leaderboards.drivers
      .map((driver) => `<div class="stack-item"><div><div class="card-title">${driver.name}</div><div class="text-muted">${driver.shift}</div></div><strong>${driver.deliveriesToday} entregas</strong></div>`)
      .join('');

    document.querySelector('#topCustomers').innerHTML = data.leaderboards.customers
      .map((customer) => `<div class="stack-item"><div><div class="card-title">${customer.name}</div><div class="text-muted">${customer.email}</div></div><strong>${customer.serviceLevel}</strong></div>`)
      .join('');
  }

  function updateCharts(data) {
    // Update operations chart
    charts.operations.data.labels = data.charts.operationalEvolution.labels;
    charts.operations.data.datasets[0].data = data.charts.operationalEvolution.data;
    charts.operations.update();
    renderChartSummary('#operationsChartSummary', data.charts.operationalEvolution.labels, data.charts.operationalEvolution.data);

    // Update status chart
    charts.status.data.labels = data.charts.packageStatus.labels;
    charts.status.data.datasets[0].data = data.charts.packageStatus.data;
    charts.status.update();
    renderChartSummary('#statusChartSummary', data.charts.packageStatus.labels, data.charts.packageStatus.data);

    // Update hourly chart
    charts.hourly.data.labels = data.charts.deliveriesByHour.labels;
    charts.hourly.data.datasets[0].data = data.charts.deliveriesByHour.data;
    charts.hourly.update();
    renderChartSummary('#hourlyChartSummary', data.charts.deliveriesByHour.labels, data.charts.deliveriesByHour.data);

    // Update route state chart
    charts.routeState.data.labels = data.charts.routeState.labels;
    charts.routeState.data.datasets[0].data = data.charts.routeState.data;
    charts.routeState.update();
    renderChartSummary('#routeStateChartSummary', data.charts.routeState.labels, data.charts.routeState.data);
  }

  async function renderDashboard() {
    const selectedRange = rangeSelect ? rangeSelect.value : 'week';
    const data = await window.LogisticHubCore.apiRequest(`/dashboard?range=${encodeURIComponent(selectedRange)}`);
    updateDashboardContent(data);
    updateCharts(data);
  }

  // Inicializar
  initializeCharts();
  if (rangeSelect) {
    rangeSelect.addEventListener('change', renderDashboard);
  }
  renderDashboard();
});
