(function () {
  const TOKEN_KEY = 'logistichub.token';
  const USER_KEY = 'logistichub.user';
  const NOTICE_KEY = 'logistichub.notice';

  function config() {
    const runtime = window.LOGISTICHUB_CONFIG || {};
    const storedBase = window.localStorage.getItem('logistichub.apiBase');

    return {
      API_BASE: runtime.API_BASE || storedBase || '/api',
      USE_MOCKS: typeof runtime.USE_MOCKS === 'boolean' ? runtime.USE_MOCKS : false,
    };
  }

  function getToken() {
    return window.localStorage.getItem(TOKEN_KEY);
  }

  function setToken(token) {
    window.localStorage.setItem(TOKEN_KEY, token);
  }

  function clearToken() {
    window.localStorage.removeItem(TOKEN_KEY);
  }

  function getUser() {
    const raw = window.localStorage.getItem(USER_KEY);
    return raw ? JSON.parse(raw) : null;
  }

  function setUser(user) {
    window.localStorage.setItem(USER_KEY, JSON.stringify(user));
  }

  function clearUser() {
    window.localStorage.removeItem(USER_KEY);
  }

  function getRoleProfile(role) {
    // Usa la configuración centralizada de roles
    return window.LogisticHubRoleConfig ? window.LogisticHubRoleConfig.get(role) : null;
  }

  function landingPageFor(userOrRole) {
    const role = typeof userOrRole === 'string' ? userOrRole : userOrRole && userOrRole.role;
    return getRoleProfile(role).landingPage;
  }

  function canAccess(userOrRole, allowedRoles) {
    const role = typeof userOrRole === 'string' ? userOrRole : userOrRole && userOrRole.role;
    const roles = Array.isArray(allowedRoles) ? allowedRoles : [];
    return !roles.length || roles.includes(role);
  }

  function applyShellIntro(values) {
    const payload = values || {};
    const utils = window.LogisticHubUtils;

    if (utils) {
      if (payload.eyebrow) utils.setText('.eyebrow', payload.eyebrow);
      if (payload.title) utils.setText('.page-title', payload.title);
      if (payload.description) utils.setText('.page-description', payload.description);
    } else {
      // Fallback si las utilidades no están cargadas
      const eyebrow = document.querySelector('.eyebrow');
      const title = document.querySelector('.page-title');
      const description = document.querySelector('.page-description');

      if (eyebrow && payload.eyebrow) eyebrow.textContent = payload.eyebrow;
      if (title && payload.title) title.textContent = payload.title;
      if (description && payload.description) description.textContent = payload.description;
    }
  }

  function buildUrl(endpoint) {
    if (/^https?:\/\//i.test(endpoint)) {
      return endpoint;
    }

    const normalizedBase = config().API_BASE.replace(/\/$/, '');
    const normalizedEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
    return `${normalizedBase}${normalizedEndpoint}`;
  }

  async function parseResponse(response) {
    if (response.status === 204) {
      return null;
    }

    const text = await response.text();

    if (!text) {
      return null;
    }

    try {
      return JSON.parse(text);
    } catch (error) {
      return text;
    }
  }

  async function apiRequest(endpoint, options) {
    const requestOptions = options || {};
    const method = (requestOptions.method || 'GET').toUpperCase();
    const headers = {
      'Content-Type': 'application/json',
      ...(requestOptions.headers || {}),
    };

    const token = getToken();

    if (token && !requestOptions.skipAuth) {
      headers.Authorization = `Bearer ${token}`;
    }

    const fetchOptions = {
      method,
      headers,
    };

    if (requestOptions.data !== undefined) {
      fetchOptions.body = JSON.stringify(requestOptions.data);
    }

    try {
      const response = await window.fetch(buildUrl(endpoint), fetchOptions);

      if (response.status === 401) {
        clearToken();
        clearUser();
        window.location.href = '/logistichub/acceso.html';
        throw new Error('Sesion expirada');
      }

      const payload = await parseResponse(response);

      if (!response.ok) {
        const message = payload && typeof payload === 'object' ? payload.message || payload.error : null;
        throw new Error(message || 'No se pudo completar la solicitud.');
      }

      return payload;
    } catch (error) {
      throw error;
    }
  }

  async function downloadFile(endpoint, fileName) {
    const headers = {};
    const token = getToken();

    if (token) {
      headers.Authorization = `Bearer ${token}`;
    }

    const response = await window.fetch(buildUrl(endpoint), { method: 'GET', headers });

    if (response.status === 401) {
      clearToken();
      clearUser();
      window.location.href = '/logistichub/acceso.html';
      throw new Error('Sesion expirada');
    }

    if (!response.ok) {
      const payload = await parseResponse(response);
      const message = payload && typeof payload === 'object' ? payload.message || payload.error : null;
      throw new Error(message || 'No se pudo descargar el archivo.');
    }

    const blob = await response.blob();
    const disposition = response.headers.get('Content-Disposition') || '';
    const matched = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/i);
    const resolvedName = matched ? matched[1].replace(/['"]/g, '') : fileName;
    const url = window.URL.createObjectURL(blob);
    const anchor = document.createElement('a');

    anchor.href = url;
    anchor.download = resolvedName || fileName || 'archivo';
    document.body.append(anchor);
    anchor.click();
    anchor.remove();
    window.URL.revokeObjectURL(url);
  }

  function logout() {
    clearToken();
    clearUser();
    window.location.href = '/logistichub/acceso.html';
  }

  function protectPage(allowedRoles) {
    const currentUser = getUser();
    const roles = Array.isArray(allowedRoles) ? allowedRoles : [];

    if (!getToken() || !currentUser) {
      window.location.href = '/logistichub/acceso.html';
      return false;
    }

    if (!canAccess(currentUser, roles)) {
      setNotice('error', 'No tienes permisos para ver este modulo.');
      window.location.href = landingPageFor(currentUser);
      return false;
    }

    return true;
  }

  function setNotice(type, message) {
    window.sessionStorage.setItem(NOTICE_KEY, JSON.stringify({ type, message }));
  }

  function consumeNotice() {
    const raw = window.sessionStorage.getItem(NOTICE_KEY);

    if (!raw) {
      return null;
    }

    window.sessionStorage.removeItem(NOTICE_KEY);
    return JSON.parse(raw);
  }

  function renderNotice(target, notice) {
    const utils = window.LogisticHubUtils;
    
    if (!notice) return;

    const noticeHTML = `<div class="notice notice-${notice.type}">${notice.message}</div>`;
    
    if (utils) {
      utils.setHTML(target, noticeHTML);
    } else {
      const element = typeof target === 'string' ? document.querySelector(target) : target;
      if (element) element.innerHTML = noticeHTML;
    }
  }

  function resolveActionValue(value, ...args) {
    return typeof value === 'function' ? value(...args) : value;
  }

  async function runDeleteAction(button, options) {
    const config = options || {};
    const deleteId = button.dataset.deleteId;
    const endpoint = resolveActionValue(config.endpoint, deleteId, button)
      || (config.basePath ? `${config.basePath}/${deleteId}` : null);

    if (!endpoint) {
      throw new Error('No se pudo resolver el endpoint de eliminacion.');
    }

    const confirmMessage = resolveActionValue(
      config.confirmMessage,
      deleteId,
      button
    ) || 'Esta accion eliminara el registro seleccionado. ¿Deseas continuar?';

    if (confirmMessage && !window.confirm(confirmMessage)) {
      return false;
    }

    const originalText = button.textContent;
    button.disabled = true;
    button.dataset.deleting = '1';
    button.textContent = resolveActionValue(config.loadingText, deleteId, button) || 'Eliminando...';

    try {
      await apiRequest(endpoint, { method: 'DELETE' });

      const successMessage = resolveActionValue(config.successMessage, deleteId, button);

      if (successMessage) {
        renderNotice(config.noticeTarget || '#pageNotice', { type: 'success', message: successMessage });
      }

      if (typeof config.onSuccess === 'function') {
        await config.onSuccess(deleteId, button);
      }

      return true;
    } catch (error) {
      const errorMessage = resolveActionValue(config.errorMessage, deleteId, error, button)
        || error.message
        || 'No se pudo eliminar el registro.';

      renderNotice(config.noticeTarget || '#pageNotice', { type: 'error', message: errorMessage });

      if (typeof config.onError === 'function') {
        config.onError(error, deleteId, button);
      }

      return false;
    } finally {
      button.disabled = false;
      delete button.dataset.deleting;
      button.textContent = originalText;
    }
  }

  function bindDeleteButtons(target, options) {
    const container = typeof target === 'string' ? document.querySelector(target) : target;

    if (!container) {
      return;
    }

    const config = options || {};
    const selector = config.selector || '[data-delete-id]';

    container.querySelectorAll(selector).forEach((button) => {
      if (button.dataset.deleteBound === '1') {
        return;
      }

      button.dataset.deleteBound = '1';
      button.addEventListener('click', async () => {
        if (button.dataset.deleting === '1') {
          return;
        }

        await runDeleteAction(button, config);
      });
    });
  }

  function initBackButtons() {
    const buttons = document.querySelectorAll('[data-back-button]');

    buttons.forEach((button) => {
      const hasHistory = window.history.length > 1;
      const referrer = document.referrer;
      const hasInternalReferrer = referrer && new URL(referrer).origin === window.location.origin;

      if (!hasHistory && !hasInternalReferrer) {
        button.classList.add('app-hidden');
        return;
      }

      button.classList.remove('app-hidden');
      button.addEventListener('click', () => window.history.back());
    });
  }

  function queryParam(name) {
    return new URLSearchParams(window.location.search).get(name);
  }

  function toCurrency(value) {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value || 0));
  }

  function toDate(value) {
    if (!value) {
      return '--';
    }

    return new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium' }).format(new Date(value));
  }

  function initials(name) {
    return String(name || 'LH')
      .split(' ')
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join('');
  }

  function statusLabel(value) {
    return value || '';
  }

  function badgeClass(value) {
    const normalized = String(value || '').toLowerCase();

    if (normalized.includes('entreg') || normalized.includes('activo') || normalized.includes('operativo') || normalized.includes('complet')) {
      return 'status-pill';
    }

    if (normalized.includes('pend') || normalized.includes('prepar') || normalized.includes('planific')) {
      return 'badge-soft';
    }

    if (normalized.includes('asign')) {
      return 'badge-soft';
    }

    if (normalized.includes('manten') || normalized.includes('fuera') || normalized.includes('cancel')) {
      return 'role-pill';
    }

    return 'badge-soft';
  }

  function tableMessage(target, message, colspan) {
    const tableBody = typeof target === 'string' ? document.querySelector(target) : target;

    if (!tableBody) {
      return;
    }

    tableBody.innerHTML = `<tr><td colspan="${colspan}" class="table-empty">${message}</td></tr>`;
  }

  function ready(callback) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', callback);
      return;
    }

    callback();
  }

  window.LogisticHubCore = {
    config,
    getToken,
    setToken,
    clearToken,
    getUser,
    setUser,
    clearUser,
    apiRequest,
    downloadFile,
    logout,
    protectPage,
    getRoleProfile,
    landingPageFor,
    canAccess,
    applyShellIntro,
    setNotice,
    consumeNotice,
    renderNotice,
    bindDeleteButtons,
    runDeleteAction,
    initBackButtons,
    queryParam,
    toCurrency,
    toDate,
    initials,
    badgeClass,
    statusLabel,
    tableMessage,
    ready,
  };
})();
