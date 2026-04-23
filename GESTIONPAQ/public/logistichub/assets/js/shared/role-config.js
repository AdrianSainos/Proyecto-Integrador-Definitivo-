(function () {
  /**
   * Configuración centralizada de roles
   * Evita duplicación de datos entre múltiples archivos
   */
  const ROLE_CONFIG = {
    admin: {
      label: 'Administrador',
      landingPage: '/logistichub/inicio.html',
      icon: 'fa-solid fa-shield-halved',
      mode: 'Gobierno integral',
      auth: 'Control total',
      data: 'Vista completa',
      dashboardEyebrow: 'Direccion ejecutiva',
      dashboardTitle: 'Plataforma operativa integral',
      dashboardDescription: 'Supervision de servicio, capacidad y configuracion desde una sola cabina.',
      dashboardExperience: {
        eyebrow: 'Direccion ejecutiva',
        title: 'Tablero ejecutivo',
        description: 'Supervisión de servicio, capacidad y cumplimiento del período.',
        intro: 'Indicadores ejecutivos, cobertura y servicio en una sola vista.',
        actions: [
          { label: 'Nuevo envio', href: '/logistichub/envio-form.html', tone: 'success', icon: 'fa-solid fa-plus' },
          { label: 'Ver rutas', href: '/logistichub/rutas.html', tone: 'outline', icon: 'fa-solid fa-route' },
          { label: 'Reportes', href: '/logistichub/reportes.html', tone: 'primary', icon: 'fa-solid fa-chart-line' },
        ],
        highlights: ['Visibilidad completa', 'Configuracion habilitada', 'KPIs ejecutivos'],
      },
    },
    operator: {
      label: 'Operador',
      landingPage: '/logistichub/operaciones.html',
      icon: 'fa-solid fa-tower-broadcast',
      mode: 'Mesa operativa',
      auth: 'Ejecucion diaria',
      data: 'Despacho y seguimiento',
      dashboardEyebrow: 'Operacion central',
      dashboardTitle: 'Flujo diario bajo control',
      dashboardDescription: 'Priorizacion de salidas, incidencias y carga pendiente para el turno actual.',
      dashboardExperience: {
        eyebrow: 'Operacion central',
        title: 'Flujo diario bajo control',
        description: 'Priorización de salidas, incidencias y carga pendiente para el turno actual.',
        intro: 'Pendientes, despacho y continuidad del turno actual.',
        actions: [
          { label: 'Ir a operaciones', href: '/logistichub/operaciones.html', tone: 'primary', icon: 'fa-solid fa-wave-square' },
          { label: 'Nuevo envio', href: '/logistichub/envio-form.html', tone: 'success', icon: 'fa-solid fa-plus' },
          { label: 'Rastrear codigo', href: '/logistichub/rastreo.html', tone: 'outline', icon: 'fa-solid fa-location-crosshairs' },
        ],
        highlights: ['Salida prioritaria', 'Monitoreo de cola', 'Incidencias visibles'],
      },
    },
    supervisor: {
      label: 'Supervisor',
      landingPage: '/logistichub/inicio.html',
      icon: 'fa-solid fa-binoculars',
      mode: 'Supervision tactica',
      auth: 'Coordinacion regional',
      data: 'SLA y capacidad',
      dashboardEyebrow: 'Capa de supervision',
      dashboardTitle: 'Rendimiento y excepciones',
      dashboardDescription: 'Lectura de cumplimiento, desbalance operativo y calidad de ejecucion.',
      dashboardExperience: {
        eyebrow: 'Capa de supervision',
        title: 'Rendimiento y excepciones',
        description: 'Lectura de cumplimiento, desbalance operativo y calidad de ejecución.',
        intro: 'Brechas de SLA, balance operativo y seguimiento de cumplimiento.',
        actions: [
          { label: 'Abrir reportes', href: '/logistichub/reportes.html', tone: 'primary', icon: 'fa-solid fa-chart-line' },
          { label: 'Revisar clientes', href: '/logistichub/clientes.html', tone: 'outline', icon: 'fa-solid fa-users-line' },
          { label: 'Ver flota', href: '/logistichub/vehiculos.html', tone: 'outline', icon: 'fa-solid fa-truck-fast' },
        ],
        highlights: ['SLA y capacidad', 'Alertas laterales', 'Comparativos rapidos'],
      },
    },
    dispatcher: {
      label: 'Despachador',
      landingPage: '/logistichub/rutas.html',
      icon: 'fa-solid fa-route',
      mode: 'Orquestacion de rutas',
      auth: 'Asignacion en vivo',
      data: 'Rutas y flota',
      dashboardEyebrow: 'Cabina de despacho',
      dashboardTitle: 'Capacidad en movimiento',
      dashboardDescription: 'Asignaciones, rutas activas y cobertura de salida con foco en ejecucion.',
      dashboardExperience: {
        eyebrow: 'Cabina de despacho',
        title: 'Capacidad en movimiento',
        description: 'Asignaciones, rutas activas y cobertura de salida con foco en ejecución.',
        intro: 'Rutas, unidades y cobertura de salida en tiempo real.',
        actions: [
          { label: 'Gestionar rutas', href: '/logistichub/rutas.html', tone: 'primary', icon: 'fa-solid fa-route' },
          { label: 'Ver flota', href: '/logistichub/vehiculos.html', tone: 'outline', icon: 'fa-solid fa-truck-fast' },
          { label: 'Cola de envios', href: '/logistichub/envios.html', tone: 'success', icon: 'fa-solid fa-boxes-stacked' },
        ],
        highlights: ['Asignacion viva', 'Cobertura de ruta', 'Capacidad util'],
      },
    },
    driver: {
      label: 'Conductor',
      landingPage: '/logistichub/rutas.html',
      icon: 'fa-solid fa-id-card-clip',
      mode: 'Ruta asignada',
      auth: 'Operacion en calle',
      data: 'Manifiesto personal',
      dashboardEyebrow: 'Operacion de ultima milla',
      dashboardTitle: 'Tu jornada en ruta',
      dashboardDescription: 'Entregas asignadas, secuencia de eventos y visibilidad de progreso personal.',
      dashboardExperience: {
        eyebrow: 'Operación de última milla',
        title: 'Tu jornada en ruta',
        description: 'Entregas asignadas, secuencia de eventos y visibilidad de progreso personal.',
        intro: 'Ruta asignada, progreso y entregas visibles del turno.',
        actions: [
          { label: 'Abrir rutas', href: '/logistichub/rutas.html', tone: 'primary', icon: 'fa-solid fa-route' },
          { label: 'Ver rastreo', href: '/logistichub/rastreo.html', tone: 'outline', icon: 'fa-solid fa-location-crosshairs' },
        ],
        highlights: ['Ruta personal', 'Progreso diario', 'Eventos de entrega'],
      },
    },
    customer: {
      label: 'Cliente',
      landingPage: '/logistichub/rastreo.html',
      icon: 'fa-solid fa-user-tie',
      mode: 'Portal de seguimiento',
      auth: 'Consulta segura',
      data: 'Tus envios',
      dashboardEyebrow: 'Portal de cliente',
      dashboardTitle: 'Seguimiento con contexto',
      dashboardDescription: 'Vista clara del estado de tus envios, hitos y trazabilidad reciente.',
      dashboardExperience: {
        eyebrow: 'Portal de cliente',
        title: 'Seguimiento con contexto',
        description: 'Vista clara del estado de tus envios, hitos y trazabilidad reciente.',
        intro: 'Estado, hitos y trazabilidad reciente de tus envios.',
        actions: [
          { label: 'Rastrear envio', href: '/logistichub/rastreo.html', tone: 'primary', icon: 'fa-solid fa-location-crosshairs' },
          { label: 'Ver tus envios', href: '/logistichub/envios.html', tone: 'outline', icon: 'fa-solid fa-boxes-stacked' },
        ],
        highlights: ['Visibilidad propia', 'Timeline claro', 'Consulta segura'],
      },
    },
  };

  // Helper para obtener configuración de rol
  const getRoleConfig = (role) => {
    return ROLE_CONFIG[role] || ROLE_CONFIG.operator;
  };

  // Exponer globalmente
  window.LogisticHubRoleConfig = {
    all: ROLE_CONFIG,
    get: getRoleConfig,
  };
})();
