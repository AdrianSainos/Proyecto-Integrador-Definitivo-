window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'operator', 'supervisor', 'dispatcher', 'driver', 'customer'])) {
    return;
  }

  const user = window.LogisticHubCore.getUser();
  const profile = window.LogisticHubCore.getRoleProfile(user.role);
  const roleExperience = {
    admin: {
      eyebrow: 'Direccion ejecutiva',
      title: 'Plataforma operativa integral',
      description: 'Supervision de servicio, capacidad y configuracion desde una sola cabina.',
      intro: 'Tu vista conserva amplitud total y concentra los indicadores mas utiles para toma de decision ejecutiva.',
      actions: [
        { label: 'Nuevo envio', href: '/logistichub/shipment-form.html', tone: 'success', icon: 'fa-solid fa-plus' },
        { label: 'Ver rutas', href: '/logistichub/routes.html', tone: 'outline', icon: 'fa-solid fa-route' },
        { label: 'Reportes', href: '/logistichub/reports.html', tone: 'primary', icon: 'fa-solid fa-chart-line' },
      ],
      highlights: ['Visibilidad completa', 'Configuracion habilitada', 'KPIs ejecutivos'],
    },
    operator: {
      eyebrow: 'Operacion central',
      title: 'Flujo diario bajo control',
      description: 'Priorizacion de salidas, incidencias y carga pendiente para el turno actual.',
      intro: 'La experiencia del operador enfatiza continuidad de despacho, seguimiento y volumen pendiente.',
      actions: [
        { label: 'Ir a operaciones', href: '/logistichub/operations.html', tone: 'primary', icon: 'fa-solid fa-wave-square' },
        { label: 'Nuevo envio', href: '/logistichub/shipment-form.html', tone: 'success', icon: 'fa-solid fa-plus' },
        { label: 'Rastrear codigo', href: '/logistichub/tracking.html', tone: 'outline', icon: 'fa-solid fa-location-crosshairs' },
      ],
      highlights: ['Salida prioritaria', 'Monitoreo de cola', 'Incidencias visibles'],
    },
    supervisor: {
      eyebrow: 'Capa de supervision',
      title: 'Rendimiento y excepciones',
      description: 'Lectura de cumplimiento, desbalance operativo y calidad de ejecucion.',
      intro: 'La interfaz del supervisor prioriza brechas de SLA, desbalance entre equipos y trazabilidad de cumplimiento.',
      actions: [
        { label: 'Abrir reportes', href: '/logistichub/reports.html', tone: 'primary', icon: 'fa-solid fa-chart-line' },
        { label: 'Revisar clientes', href: '/logistichub/customers.html', tone: 'outline', icon: 'fa-solid fa-users-line' },
        { label: 'Ver flota', href: '/logistichub/vehicles.html', tone: 'outline', icon: 'fa-solid fa-truck-fast' },
      ],
      highlights: ['SLA y capacidad', 'Alertas laterales', 'Comparativos rapidos'],
    },
    dispatcher: {
      eyebrow: 'Cabina de despacho',
      title: 'Capacidad en movimiento',
      description: 'Asignaciones, rutas activas y cobertura de salida con foco en ejecucion.',
      intro: 'La vista del despachador mantiene el acabado premium, pero la jerarquia ahora favorece rutas, unidades y conductores.',
      actions: [
        { label: 'Gestionar rutas', href: '/logistichub/routes.html', tone: 'primary', icon: 'fa-solid fa-route' },
        { label: 'Ver flota', href: '/logistichub/vehicles.html', tone: 'outline', icon: 'fa-solid fa-truck-fast' },
        { label: 'Cola de envios', href: '/logistichub/shipments.html', tone: 'success', icon: 'fa-solid fa-boxes-stacked' },
      ],
      highlights: ['Asignacion viva', 'Cobertura de ruta', 'Capacidad util'],
    },
    driver: {
      eyebrow: 'Operacion de ultima milla',
      title: 'Tu jornada en ruta',
      description: 'Entregas asignadas, secuencia de eventos y visibilidad de progreso personal.',
      intro: 'En conductor, el tablero se compacta hacia lo que realmente mueve tu jornada: ruta asignada, progreso y entregas visibles.',
      actions: [
        { label: 'Abrir rutas', href: '/logistichub/routes.html', tone: 'primary', icon: 'fa-solid fa-route' },
        { label: 'Ver rastreo', href: '/logistichub/tracking.html', tone: 'outline', icon: 'fa-solid fa-location-crosshairs' },
      ],
      highlights: ['Ruta personal', 'Progreso diario', 'Eventos de entrega'],
    },
    customer: {
      eyebrow: 'Portal de cliente',
      title: 'Seguimiento con contexto',
      description: 'Vista clara del estado de tus envios, hitos y trazabilidad reciente.',
      intro: 'No es un admin recortado: la experiencia cliente ahora enfoca seguimiento, confianza y lectura clara del estado de tus envios.',
      actions: [
        { label: 'Rastrear envio', href: '/logistichub/tracking.html', tone: 'primary', icon: 'fa-solid fa-location-crosshairs' },
        { label: 'Ver tus envios', href: '/logistichub/shipments.html', tone: 'outline', icon: 'fa-solid fa-boxes-stacked' },
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

  const data = await window.LogisticHubCore.apiRequest('/dashboard');

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

  const colors = ['#007bff', '#17a2b8', '#6f42c1', '#dc3545', '#28a745', '#ffc107'];

  new Chart(document.querySelector('#operationsChart'), {
    type: 'bar',
    data: { labels: ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'], datasets: [{ label: 'Movimientos', data: data.charts.operationalEvolution, backgroundColor: colors[0] }] },
  });

  new Chart(document.querySelector('#statusChart'), {
    type: 'doughnut',
    data: { labels: ['Pendientes', 'En ruta', 'Entregados', 'Otros'], datasets: [{ data: data.charts.packageStatus, backgroundColor: colors.slice(0, 4) }] },
  });

  new Chart(document.querySelector('#hourlyChart'), {
    type: 'line',
    data: { labels: ['08', '09', '10', '11', '12', '13', '14'], datasets: [{ label: 'Entregas', data: data.charts.deliveriesByHour, borderColor: colors[4], backgroundColor: 'rgba(40,167,69,0.16)', fill: true }] },
  });

  new Chart(document.querySelector('#routeStateChart'), {
    type: 'bar',
    data: { labels: ['Preparacion', 'En ejecucion', 'Completadas', 'Canceladas'], datasets: [{ label: 'Rutas', data: data.charts.routeState, backgroundColor: colors.slice(1, 5) }] },
  });
});