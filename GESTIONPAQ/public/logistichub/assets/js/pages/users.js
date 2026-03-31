window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin'])) {
    return;
  }

  window.LogisticHubCore.renderNotice('#pageNotice', window.LogisticHubCore.consumeNotice());

  async function loadUsers() {
    const items = await window.LogisticHubCore.apiRequest('/users');
    const body = document.querySelector('#usersTableBody');
    const ROLE_LABELS = { admin: 'Administrador', operator: 'Operador', supervisor: 'Supervisor', dispatcher: 'Despachador', driver: 'Conductor', customer: 'Cliente' };
    body.innerHTML = items.map((item) => `
      <tr>
        <td>${item.id}</td>
        <td>
          <strong>${item.username ? `@${item.username}` : '--'}</strong>
          <div class="text-muted">${item.email}</div>
        </td>
        <td>
          <strong>${item.name}</strong>
          <div class="text-muted">${item.jobTitle || 'Sin puesto'}</div>
        </td>
        <td>${item.schedule || '--'}</td>
        <td>${ROLE_LABELS[item.role] || item.role}</td>
        <td>${item.active ? 'Si' : 'No'}</td>
        <td>
          <div class="table-actions">
            <a class="btn btn-outline btn-sm" href="/logistichub/user-form.html?id=${item.id}">Editar</a>
            <button class="btn btn-danger btn-sm" data-delete-id="${item.id}">Eliminar</button>
          </div>
        </td>
      </tr>
    `).join('');

    window.LogisticHubCore.bindDeleteButtons(body, {
      basePath: '/users',
      noticeTarget: '#pageNotice',
      confirmMessage: 'Se eliminara el usuario seleccionado. ¿Deseas continuar?',
      successMessage: 'Usuario eliminado correctamente.',
      onSuccess: loadUsers,
    });
  }

  loadUsers();
});