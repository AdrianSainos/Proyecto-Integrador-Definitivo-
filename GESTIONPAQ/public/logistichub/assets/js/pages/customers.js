window.LogisticHubCore.ready(async () => {
  if (!window.LogisticHubCore.protectPage(['admin', 'operator', 'supervisor'])) {
    return;
  }

  const customers = await window.LogisticHubCore.apiRequest('/customers');

  document.querySelector('#customersTableBody').innerHTML = customers
    .map((item) => `
      <tr>
        <td>${item.code}</td>
        <td>${item.name}</td>
        <td>${item.email}</td>
        <td>${item.phone}</td>
        <td>${item.addresses.length}</td>
        <td>${item.serviceLevel}</td>
      </tr>
    `)
    .join('');
});