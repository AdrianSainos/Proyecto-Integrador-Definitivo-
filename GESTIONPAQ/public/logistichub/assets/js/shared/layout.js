(function () {
  const NAV_ITEMS = [
    { key: 'dashboard', label: 'Inicio', icon: 'fa-solid fa-house', href: 'inicio.html', roles: ['admin', 'operator', 'supervisor', 'dispatcher', 'driver', 'customer'] },
    { key: 'operations', label: 'Operaciones', icon: 'fa-solid fa-wave-square', href: 'operaciones.html', roles: ['admin', 'operator', 'dispatcher', 'customer'] },
    { key: 'customers', label: 'Clientes', icon: 'fa-solid fa-users-line', href: 'clientes.html', roles: ['admin', 'operator', 'supervisor'] },
    { key: 'shipments', label: 'Envios', icon: 'fa-solid fa-boxes-stacked', href: 'envios.html', roles: ['admin', 'operator', 'supervisor', 'dispatcher', 'customer'] },
    { key: 'routes', label: 'Rutas', icon: 'fa-solid fa-route', href: 'rutas.html', roles: ['admin', 'supervisor', 'dispatcher', 'driver'] },
    { key: 'vehicles', label: 'Flota', icon: 'fa-solid fa-truck-fast', href: 'vehiculos.html', roles: ['admin', 'supervisor', 'dispatcher'] },
    { key: 'maintenance', label: 'Mantenimiento', icon: 'fa-solid fa-screwdriver-wrench', href: 'mantenimiento.html', roles: ['admin', 'supervisor', 'dispatcher'] },
    { key: 'drivers', label: 'Conductores', icon: 'fa-solid fa-id-card-clip', href: 'conductores.html', roles: ['admin', 'supervisor', 'dispatcher'] },
    { key: 'tracking', label: 'Rastreo', icon: 'fa-solid fa-location-crosshairs', href: 'rastreo.html', roles: ['admin', 'operator', 'supervisor', 'dispatcher', 'customer'] },
    { key: 'evidences', label: 'Evidencias', icon: 'fa-solid fa-camera-retro', href: 'evidencias.html', roles: ['admin', 'supervisor', 'dispatcher'] },
    { key: 'reports', label: 'Reportes', icon: 'fa-solid fa-chart-line', href: 'reportes.html', roles: ['admin', 'supervisor'] },
    { key: 'settings', label: 'Configuracion', icon: 'fa-solid fa-sliders', href: 'configuracion.html', roles: ['admin'] },
  ];

  function getShellConfig(user) {
    const body = document.body;
    const profile = window.LogisticHubCore.getRoleProfile(user.role);
    const isDashboard = (body.dataset.page || 'dashboard') === 'dashboard';

    return {
      page: body.dataset.page || 'dashboard',
      eyebrow: body.dataset.eyebrow || (isDashboard ? profile.dashboardEyebrow : 'Plataforma logística'),
      title: body.dataset.title || (isDashboard ? profile.dashboardTitle : 'GESTIONPAQ'),
      description: body.dataset.description || (isDashboard ? profile.dashboardDescription : 'Centro logístico operativo.'),
    };
  }

  function buildNavItems(user, currentPage) {
    const userRole = user.role;
    return NAV_ITEMS
      .filter((item) => item.roles.includes(userRole))
      .map((item) => {
        const activeClass = currentPage === item.key ? 'is-active' : '';
        return `<a class="nav-link ${activeClass}" href="/logistichub/${item.href}"><i class="${item.icon}"></i><span>${item.label}</span></a>`;
      })
      .join('');
  }

  function buildSidebarCard(title, content) {
    return `<div class="sidebar-card">${title}${content}</div>`;
  }

  function renderSidebar(user) {
    const cfg = getShellConfig(user);
    const profile = window.LogisticHubCore.getRoleProfile(user.role);
    const navHTML = buildNavItems(user, cfg.page);

    const brandCard = buildSidebarCard(
      '',
      `<div class="brand-row">
        <div class="brand-badge"><i class="fa-solid fa-truck-fast"></i></div>
        <div>
          <div class="brand-name">GESTIONPAQ</div>
          <div class="brand-subtitle">Operación de paquetería y distribución</div>
        </div>
      </div>`
    );

    const userChip = buildSidebarCard(
      '<div class="small-label">Perfil activo</div>',
      `<div>
        <div class="brand-name">${user.name}</div>
        <div class="text-muted">${user.username ? `@${user.username} · ` : ''}${user.email}</div>
      </div>
      <span class="role-pill">${profile.label}</span>`
    );

    const roleSpotlight = buildSidebarCard(
      '',
      `<div class="role-spotlight-icon"><i class="${profile.icon}"></i></div>
      <div>
        <div class="small-label">Enfoque del rol</div>
        <div class="brand-name">${profile.mode}</div>
        <div class="text-muted">${profile.data}</div>
      </div>`
    );

    const summaryList = buildSidebarCard(
      '<div class="small-label">Resumen rápido</div>',
      `<div class="summary-row"><span>Modo de operación</span><strong>${profile.mode}</strong></div>
      <div class="summary-row"><span>Autenticación</span><strong>${profile.auth}</strong></div>
      <div class="summary-row"><span>Datos</span><strong>${profile.data}</strong></div>`
    );

    const navList = buildSidebarCard(
      '<div class="small-label">Navegación</div>',
      navHTML
    );

    return `
      <aside class="sidebar">
        ${brandCard}
        <div class="sidebar-scroll">
          ${userChip}
          ${roleSpotlight}
          ${summaryList}
          ${navList}
        </div>
      </aside>
    `;
  }

  function renderTopbar(user, initials) {
    const cfg = getShellConfig(user);
    const profile = window.LogisticHubCore.getRoleProfile(user.role);

    return `
      <header class="topbar">
        <div class="topbar-left">
          <button class="btn btn-outline btn-sm drawer-toggle" type="button" data-sidebar-toggle>
            <i class="fa-solid fa-bars"></i>
            <span>Menú</span>
          </button>
          <div>
            <div class="eyebrow">${cfg.eyebrow}</div>
            <h1 class="page-title">${cfg.title}</h1>
            <p class="page-description">${cfg.description}</p>
          </div>
        </div>
        <div class="topbar-right">
          <form class="search-box" id="trackingQuickSearch">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" id="trackingQuickInput" placeholder="Buscar envío o tracking..." />
          </form>
          <div class="topbar-account">
            <div class="user-summary">
              <div class="avatar">${initials}</div>
              <div>
                <div class="brand-name">${user.name}</div>
                <div class="text-muted">${user.username ? `@${user.username} · ` : ''}${profile.label}</div>
              </div>
            </div>
            <button class="btn btn-brand btn-sm logout-button" type="button" id="logoutButton">
              <i class="fa-solid fa-right-from-bracket"></i>
              <span>Salir</span>
            </button>
          </div>
        </div>
      </header>
    `;
  }

  function setupSidebarToggle() {
    const toggleButtons = document.querySelectorAll('[data-sidebar-toggle]');
    const navLinks = document.querySelectorAll('.sidebar .nav-link');
    
    const closeSidebar = () => document.body.classList.remove('sidebar-open');

    toggleButtons.forEach((button) => {
      button.addEventListener('click', () => {
        document.body.classList.toggle('sidebar-open');
      });
    });

    navLinks.forEach((link) => {
      link.addEventListener('link', closeSidebar);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') closeSidebar();
    });
  }

  function setupSearchForm() {
    const searchForm = document.querySelector('#trackingQuickSearch');
    if (!searchForm) return;

    searchForm.addEventListener('submit', (event) => {
      event.preventDefault();
      const input = document.querySelector('#trackingQuickInput');
      if (input && input.value.trim()) {
        window.location.href = `/logistichub/rastreo.html?code=${encodeURIComponent(input.value.trim())}`;
      }
    });
  }

  function setupLogoutButton() {
    const logoutButton = document.querySelector('#logoutButton');
    if (logoutButton) {
      logoutButton.addEventListener('click', window.LogisticHubCore.logout);
    }
  }

  function bindShellEvents() {
    setupSidebarToggle();
    setupSearchForm();
    setupLogoutButton();
  }

  function boot() {
    const body = document.body;

    if (body.dataset.layout === 'none') return;

    const user = window.LogisticHubCore.getUser();
    if (!user) return;

    const content = body.querySelector('[data-page-content]');
    if (!content) return;

    const fragment = content.innerHTML;
    const initials = window.LogisticHubCore.initials(user.name);

    body.innerHTML = `
      <div class="sidebar-backdrop" data-sidebar-toggle></div>
      <div class="wrapper">
        ${renderSidebar(user)}
        <main class="main-content">
          ${renderTopbar(user, initials)}
          <section class="page-shell slide-in-up">
            <div id="globalNotice"></div>
            ${fragment}
          </section>
        </main>
      </div>
    `;

    const notice = window.LogisticHubCore.consumeNotice();
    window.LogisticHubCore.renderNotice('#globalNotice', notice);
    window.LogisticHubCore.initBackButtons();
    bindShellEvents();
  }

  window.LogisticHubCore.ready(boot);
})();