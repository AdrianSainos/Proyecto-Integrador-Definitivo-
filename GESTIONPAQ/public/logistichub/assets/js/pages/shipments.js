window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'operator', 'supervisor', 'dispatcher', 'customer'])) {
    return;
  }

  const user = window.LogisticHubCore.getUser();
  const profile = window.LogisticHubCore.getRoleProfile(user.role);
  const canManage = ['admin', 'operator', 'supervisor', 'dispatcher'].includes(user.role);
  const notice = window.LogisticHubCore.consumeNotice();
  window.LogisticHubCore.renderNotice('#pageNotice', notice);

  window.LogisticHubCore.applyShellIntro(
    user.role === 'customer'
      ? {
          eyebrow: 'Portal de cliente',
          title: 'Tus envios y estados visibles',
          description: 'Listado con trazabilidad, estado actual y asignacion disponible para tu cuenta.',
        }
      : user.role === 'dispatcher'
        ? {
            eyebrow: 'Despacho',
            title: 'Envios listos para asignacion',
            description: 'Relacion de envios con foco en ruteo, unidad disponible y conductor asociado.',
          }
        : {
            eyebrow: 'Envios',
            title: 'Control de envios y paquetes',
            description: 'Listado administrativo con contexto operativo, estados y asignaciones enriquecidas.',
          }
  );

  document.querySelector('#shipmentsPersona').innerHTML = `
    <article class="persona-panel persona-panel-soft">
      <div>
        <div class="small-label">Experiencia por rol</div>
        <h2 class="card-title">${profile.label}</h2>
        <p class="card-subtitle">${canManage ? 'Mantienes control de alta, actualizacion y depuracion sobre los envios visibles para tu rol.' : 'La vista queda en modo consulta, con el mismo nivel de detalle visual pero sin acciones de edicion.'}</p>
      </div>
      <span class="role-pill"><i class="${profile.icon}"></i>${profile.data}</span>
    </article>
  `;

  const createButton = document.querySelector('#createShipmentButton');

  if (createButton && !canManage) {
    createButton.classList.add('app-hidden');
  }

  async function loadShipments() {
    const items = await window.LogisticHubCore.apiRequest('/shipments');
    const body = document.querySelector('#shipmentsTableBody');

    if (!items.length) {
      window.LogisticHubCore.tableMessage(body, 'No hay envios registrados.', 8);
      return;
    }

    body.innerHTML = items
      .map((item) => `
        <tr>
          <td>${item.tracking}</td>
          <td>${item.customerName}</td>
          <td><span class="${window.LogisticHubCore.badgeClass(item.status)}">${item.status}</span></td>
          <td>${item.routeCode || 'Pendiente'}</td>
          <td>${item.vehiclePlate || 'Pendiente'}</td>
          <td>${item.driverName || 'Pendiente'}</td>
          <td>${item.weightKg} kg</td>
          <td>
            <div class="table-actions">
              ${canManage ? `<a class="btn btn-outline btn-sm" href="/logistichub/envio-form.html?id=${item.id}">Editar</a><button class="btn btn-danger btn-sm" data-delete-id="${item.id}">Eliminar</button>` : '<span class="text-muted">Solo lectura</span>'}
            </div>
          </td>
        </tr>
      `)
      .join('');

    window.LogisticHubCore.bindDeleteButtons(body, {
      basePath: '/shipments',
      noticeTarget: '#pageNotice',
      confirmMessage: 'Se eliminara el envio seleccionado junto con sus asignaciones y trazabilidad operativa. ¿Deseas continuar?',
      successMessage: 'Envio eliminado correctamente.',
      onSuccess: loadShipments,
    });
  }

  loadShipments();
});