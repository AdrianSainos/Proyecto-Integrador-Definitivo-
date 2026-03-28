window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin'])) {
    return;
  }

  window.LogisticHubCore.renderNotice('#pageNotice', window.LogisticHubCore.consumeNotice());

  async function loadUsers() {
    const items = await window.LogisticHubCore.apiRequest('/users');
    const body = document.querySelector('#usersTableBody');
    body.innerHTML = items.map((item) => `
      <tr>
        <td>${item.id}</td>
        <td>${item.email}</td>
        <td>${item.role}</td>
        <td>${item.active ? 'Si' : 'No'}</td>
        <td>
          <div class="table-actions">
            <a class="btn btn-outline btn-sm" href="/logistichub/user-form.html?id=${item.id}">Editar</a>
            <button class="btn btn-danger btn-sm" data-delete-id="${item.id}">Eliminar</button>
          </div>
        </td>
      </tr>
    `).join('');

    body.querySelectorAll('[data-delete-id]').forEach((button) => {
      button.addEventListener('click', async () => {
        await window.LogisticHubCore.apiRequest(`/users/${button.dataset.deleteId}`, { method: 'DELETE' });
        window.LogisticHubCore.setNotice('success', 'Usuario eliminado correctamente.');
        loadUsers();
      });
    });
  }

  loadUsers();
});