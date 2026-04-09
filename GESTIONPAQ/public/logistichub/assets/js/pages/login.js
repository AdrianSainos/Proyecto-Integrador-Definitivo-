window.LogisticHubCore.ready(() => {
  const form = document.querySelector('#loginForm');
  const notice = document.querySelector('#loginNotice');

  if (window.LogisticHubCore.getToken()) {
    const savedUser = window.LogisticHubCore.getUser();
    if (savedUser && savedUser.role) {
      window.location.href = window.LogisticHubCore.landingPageFor(savedUser);
      return;
    }
    // Token sin usuario válido — limpiar y mostrar el formulario
    window.LogisticHubCore.clearToken();
    window.LogisticHubCore.clearUser();
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const payload = {
      login: form.login.value.trim(),
      password: form.password.value,
    };

    try {
      const response = await window.LogisticHubCore.apiRequest('/auth/login', {
        method: 'POST',
        data: payload,
        skipAuth: true,
      });

      window.LogisticHubCore.setToken(response.token);
      window.LogisticHubCore.setUser(response.user);
      window.location.href = window.LogisticHubCore.landingPageFor(response.user);
    } catch (error) {
      window.LogisticHubCore.renderNotice(notice, { type: 'error', message: error.message || 'No fue posible iniciar sesion.' });
    }
  });
});