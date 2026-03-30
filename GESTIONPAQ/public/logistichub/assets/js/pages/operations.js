window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'operator', 'dispatcher', 'customer'])) {
    return;
  }

  const user = window.LogisticHubCore.getUser();
  const profile = window.LogisticHubCore.getRoleProfile(user.role);
  const persona = {
    admin: {
      eyebrow: 'Vista de coordinacion',
      title: 'Operaciones y cola de salida',
      description: 'Control de carga lista para despacho, saturacion de unidades y lectura de cumplimiento operativo.',
      message: 'Estado actual de la cola, la capacidad y los despachos activos.',
    },
    operator: {
      eyebrow: 'Mesa operativa',
      title: 'Despacho del turno actual',
      description: 'Prioriza pendientes, confirma capacidad y monitorea el flujo de salida del dia.',
      message: 'Pendientes, asignaciones y continuidad del turno.',
    },
    dispatcher: {
      eyebrow: 'Coordinacion de salida',
      title: 'Asignaciones pendientes y cobertura',
      description: 'Relacion directa entre paquete, ruta, unidad y conductor para reaccionar en tiempo real.',
      message: 'Cobertura de rutas y asignaciones pendientes del periodo.',
    },
    customer: {
      eyebrow: 'Portal de cliente',
      title: 'Movimientos visibles de tus solicitudes',
      description: 'Seguimiento de envios con lectura de estado y asignacion asociada cuando exista.',
      message: 'Resumen visible de tus solicitudes y su estado actual.',
    },
  }[user.role] || {
    eyebrow: profile.dashboardEyebrow,
    title: profile.dashboardTitle,
    description: profile.dashboardDescription,
    message: 'Estado operativo visible para tu perfil.',
  };

  window.LogisticHubCore.applyShellIntro(persona);
  document.querySelector('#operationsPersona').innerHTML = `
    <article class="persona-panel">
      <div>
        <div class="small-label">${profile.label}</div>
        <h2 class="card-title">${persona.title}</h2>
        <p class="card-subtitle">${persona.message}</p>
      </div>
      <span class="role-pill"><i class="${profile.icon}"></i>${profile.mode}</span>
    </article>
  `;

  const data = await window.LogisticHubCore.apiRequest('/operations');

  document.querySelector('#operationsOverview').innerHTML = data.overview
    .map((item, index) => `<article class="strip-card ${index === 0 ? 'accent-soft' : index === 1 ? 'brand-soft' : ''}"><div class="strip-label">${item.label}</div><div class="strip-value">${item.value}</div></article>`)
    .join('');

  document.querySelector('#operationsTableBody').innerHTML = data.dispatchQueue
    .map((item) => `
      <tr>
        <td>${item.tracking}</td>
        <td>${item.customerName}</td>
        <td><span class="${window.LogisticHubCore.badgeClass(item.status)}">${item.status}</span></td>
        <td>${item.routeCode}</td>
        <td>${item.vehiclePlate}</td>
        <td>${item.driverName}</td>
      </tr>
    `)
    .join('');
});