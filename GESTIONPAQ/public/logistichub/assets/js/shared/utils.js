(function () {
  /**
   * Utilidades DOM - Reducen repetición y mejoran rendimiento
   */
  const DOMUtils = {
    /**
     * Cachea selectores para evitar búsquedas repetidas
     */
    _cache: {},

    /**
     * Obtiene elemento con caché
     */
    query: function (selector) {
      if (!this._cache[selector]) {
        this._cache[selector] = document.querySelector(selector);
      }
      return this._cache[selector];
    },

    /**
     * Obtiene todos los elementos coincidentes
     */
    queryAll: function (selector) {
      return Array.from(document.querySelectorAll(selector));
    },

    /**
     * Establece contenido HTML de forma segura
     */
    setHTML: function (target, html) {
      const element = typeof target === 'string' ? this.query(target) : target;
      if (element) {
        element.innerHTML = html;
      }
    },

    /**
     * Establece texto de un elemento
     */
    setText: function (target, text) {
      const element = typeof target === 'string' ? this.query(target) : target;
      if (element) {
        element.textContent = text;
      }
    },

    /**
     * Añade clase a elemento
     */
    addClass: function (target, className) {
      const element = typeof target === 'string' ? this.query(target) : target;
      if (element) {
        element.classList.add(className);
      }
    },

    /**
     * Elimina clase de elemento
     */
    removeClass: function (target, className) {
      const element = typeof target === 'string' ? this.query(target) : target;
      if (element) {
        element.classList.remove(className);
      }
    },

    /**
     * Alternar clase en elemento
     */
    toggleClass: function (target, className) {
      const element = typeof target === 'string' ? this.query(target) : target;
      if (element) {
        element.classList.toggle(className);
      }
    },

    /**
     * Verifica si elemento tiene clase
     */
    hasClass: function (target, className) {
      const element = typeof target === 'string' ? this.query(target) : target;
      return element ? element.classList.contains(className) : false;
    },

    /**
     * Limpia caché de selectores
     */
    clearCache: function (selector) {
      if (selector) {
        delete this._cache[selector];
      } else {
        this._cache = {};
      }
    },

    /**
     * Añade event listener a elemento
     */
    on: function (target, event, handler) {
      const element = typeof target === 'string' ? this.query(target) : target;
      if (element) {
        element.addEventListener(event, handler);
      }
    },

    /**
     * Añade event listener a múltiples elementos
     */
    onAll: function (selector, event, handler) {
      this.queryAll(selector).forEach(el => {
        el.addEventListener(event, handler);
      });
    },

    /**
     * Obtiene valor de atributo
     */
    getAttr: function (target, attr) {
      const element = typeof target === 'string' ? this.query(target) : target;
      return element ? element.getAttribute(attr) : null;
    },

    /**
     * Establece valor de atributo
     */
    setAttr: function (target, attr, value) {
      const element = typeof target === 'string' ? this.query(target) : target;
      if (element) {
        element.setAttribute(attr, value);
      }
    }
  };

  // Exponer globalmente
  window.LogisticHubUtils = DOMUtils;
})();
