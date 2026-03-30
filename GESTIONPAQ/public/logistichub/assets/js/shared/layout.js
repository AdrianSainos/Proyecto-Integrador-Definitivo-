(function () {
  const NAV_ITEMS = [
    { key: 'dashboard', label: 'Inicio', icon: 'fa-solid fa-house', href: 'dashboard.html', roles: ['admin', 'operator', 'supervisor', 'dispatcher', 'driver', 'customer'] },
    { key: 'operations', label: 'Operaciones', icon: 'fa-solid fa-wave-square', href: 'operations.html', roles: ['admin', 'operator', 'dispatcher', 'customer'] },
    { key: 'customers', label: 'Clientes', icon: 'fa-solid fa-users-line', href: 'customers.html', roles: ['admin', 'operator', 'supervisor'] },
    { key: 'shipments', label: 'Envios', icon: 'fa-solid fa-boxes-stacked', href: 'shipments.html', roles: ['admin', 'operator', 'supervisor', 'dispatcher', 'customer'] },
    { key: 'routes', label: 'Rutas', icon: 'fa-solid fa-route', href: 'routes.html', roles: ['admin', 'supervisor', 'dispatcher', 'driver'] },
    { key: 'vehicles', label: 'Flota', icon: 'fa-solid fa-truck-fast', href: 'vehicles.html', roles: ['admin', 'supervisor', 'dispatcher'] },
    { key: 'maintenance', label: 'Mantenimiento', icon: 'fa-solid fa-screwdriver-wrench', href: 'maintenance.html', roles: ['admin', 'supervisor', 'dispatcher'] },
    { key: 'drivers', label: 'Conductores', icon: 'fa-solid fa-id-card-clip', href: 'drivers.html', roles: ['admin', 'supervisor', 'dispatcher'] },
    { key: 'tracking', label: 'Rastreo', icon: 'fa-solid fa-location-crosshairs', href: 'tracking.html', roles: ['admin', 'operator', 'supervisor', 'dispatcher', 'customer'] },
    { key: 'evidences', label: 'Evidencias', icon: 'fa-solid fa-camera-retro', href: 'evidences.html', roles: ['admin', 'supervisor', 'dispatcher'] },
    { key: 'reports', label: 'Reportes', icon: 'fa-solid fa-chart-line', href: 'reports.html', roles: ['admin', 'supervisor'] },
    { key: 'settings', label: 'Configuracion', icon: 'fa-solid fa-sliders', href: 'settings.html', roles: ['admin'] },
  ];

  function shellConfig(user) {
    const body = document.body;
    const profile = window.LogisticHubCore.getRoleProfile(user.role);
    const isDashboard = (body.dataset.page || 'dashboard') === 'dashboard';

    return {
      page: body.dataset.page || 'dashboard',
      eyebrow: body.dataset.eyebrow || (isDashboard ? profile.dashboardEyebrow : 'Plataforma logistica'),
      title: body.dataset.title || (isDashboard ? profile.dashboardTitle : 'GESTIONPAQ'),
      description: body.dataset.description || (isDashboard ? profile.dashboardDescription : 'Centro logistico operativo.'),
    };
  }

  function renderSidebar(user) {
    const cfg = shellConfig(user);
    const profile = window.LogisticHubCore.getRoleProfile(user.role);
    const nav = NAV_ITEMS
      .filter((item) => item.roles.includes(user.role))
      .map((item) => {
        const activeClass = cfg.page === item.key ? 'is-active' : '';
        return `<a class="nav-link ${activeClass}" href="/logistichub/${item.href}"><i class="${item.icon}"></i><span>${item.label}</span></a>`;
      })
      .join('');

    return `
      <aside class="sidebar">
        <div class="sidebar-card">
          <div class="brand-row">
            <div class="brand-badge"><i class="fa-solid fa-truck-fast"></i></div>
            <div>
              <div class="brand-name">GESTIONPAQ</div>
              <div class="brand-subtitle">Operacion de paqueteria y distribucion</div>
            </div>
          </div>
        </div>
        <div class="sidebar-scroll">
          <div class="sidebar-card user-chip">
            <div class="small-label">Perfil activo</div>
            <div>
              <div class="brand-name">${user.name}</div>
              <div class="text-muted">${user.email}</div>
            </div>
            <span class="role-pill">${profile.label}</span>
          </div>
          <div class="sidebar-card role-spotlight">
            <div class="role-spotlight-icon"><i class="${profile.icon}"></i></div>
            <div>
              <div class="small-label">Enfoque del rol</div>
              <div class="brand-name">${profile.mode}</div>
              <div class="text-muted">${profile.data}</div>
            </div>
          </div>
          <div class="sidebar-card summary-list">
            <div class="small-label">Resumen rapido</div>
            <div class="summary-row"><span>Modo de operacion</span><strong>${profile.mode}</strong></div>
            <div class="summary-row"><span>Autenticacion</span><strong>${profile.auth}</strong></div>
            <div class="summary-row"><span>Datos</span><strong>${profile.data}</strong></div>
          </div>
          <div class="sidebar-card nav-list">
            <div class="small-label">Navegacion</div>
            ${nav}
          </div>
        </div>
      </aside>
    `;
  }

  function renderTopbar(user) {
    const cfg = shellConfig(user);
    const profile = window.LogisticHubCore.getRoleProfile(user.role);

    return `
      <header class="topbar">
        <div class="topbar-left">
          <button class="btn btn-outline btn-sm drawer-toggle" type="button" data-sidebar-toggle>
            <i class="fa-solid fa-bars"></i>
            <span>Menu</span>
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
            <input type="search" id="trackingQuickInput" placeholder="Buscar envio o tracking..." />
          </form>
          <div class="topbar-account">
            <div class="user-summary">
              <div class="avatar">${window.LogisticHubCore.initials(user.name)}</div>
              <div>
                <div class="brand-name">${user.name}</div>
                <div class="text-muted">${profile.label}</div>
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

  function bindShellEvents() {
    const logoutButton = document.querySelector('#logoutButton');
    const toggleButtons = Array.from(document.querySelectorAll('[data-sidebar-toggle]'));
    const searchForm = document.querySelector('#trackingQuickSearch');
    const navLinks = Array.from(document.querySelectorAll('.sidebar .nav-link'));

    const closeSidebar = () => document.body.classList.remove('sidebar-open');

    if (logoutButton) {
      logoutButton.addEventListener('click', window.LogisticHubCore.logout);
    }

    toggleButtons.forEach((button) => {
      button.addEventListener('click', () => {
        document.body.classList.toggle('sidebar-open');
      });
    });

    navLinks.forEach((link) => {
      link.addEventListener('click', closeSidebar);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeSidebar();
      }
    });

    if (searchForm) {
      searchForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const input = document.querySelector('#trackingQuickInput');

        if (!input || !input.value.trim()) {
          return;
        }

        window.location.href = `/logistichub/tracking.html?code=${encodeURIComponent(input.value.trim())}`;
      });
    }
  }

  function boot() {
    const body = document.body;

    if (body.dataset.layout === 'none') {
      return;
    }

    const user = window.LogisticHubCore.getUser();

    if (!user) {
      return;
    }

    const content = body.querySelector('[data-page-content]');

    if (!content) {
      return;
    }

    const fragment = content.innerHTML;

    body.innerHTML = `
      <div class="sidebar-backdrop" data-sidebar-toggle></div>
      <div class="wrapper">
        ${renderSidebar(user)}
        <main class="main-content">
          ${renderTopbar(user)}
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