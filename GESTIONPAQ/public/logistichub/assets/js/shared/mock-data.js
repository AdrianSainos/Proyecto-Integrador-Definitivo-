(function () {
  const STORAGE_KEY = 'logistichub.mock.store';

  const clone = (value) => JSON.parse(JSON.stringify(value));

  const today = new Date();
  const tomorrow = new Date(today.getTime() + 86400000);
  const yesterday = new Date(today.getTime() - 86400000);

  function isoDate(date) {
    return new Date(date).toISOString().slice(0, 10);
  }

  function fullName(person) {
    return [person.nombre, person.apellidoPaterno].filter(Boolean).join(' ');
  }

  function seedStore() {
    return {
      users: [
        { id: 1, email: 'admin@logistichub.local', password: 'admin123', role: 'admin', active: true, name: 'Alicia Ortega' },
        { id: 2, email: 'supervisor@logistichub.local', password: 'super123', role: 'supervisor', active: true, name: 'Bruno Salas' },
        { id: 3, email: 'dispatcher@logistichub.local', password: 'dispatch123', role: 'dispatcher', active: true, name: 'Camila Soto' },
      ],
      customers: [
        {
          id: 1,
          code: 'CLI-001',
          name: 'Distribuidora Norte',
          email: 'operaciones@norte.mx',
          phone: '555-101-1001',
          serviceLevel: 'premium',
          addresses: [
            { id: 101, label: 'Centro Monterrey', address: 'Av. Colosio 120', city: 'Monterrey', state: 'Nuevo Leon', postalCode: '64000', latitude: 25.6866, longitude: -100.3161 },
            { id: 102, label: 'Bodega Apodaca', address: 'Carretera Miguel Aleman 845', city: 'Apodaca', state: 'Nuevo Leon', postalCode: '66600', latitude: 25.7821, longitude: -100.1888 },
          ],
        },
        {
          id: 2,
          code: 'CLI-002',
          name: 'Farmacia Central',
          email: 'recepcion@farmaciacentral.mx',
          phone: '555-201-4400',
          serviceLevel: 'estandar',
          addresses: [
            { id: 201, label: 'Sucursal Obispado', address: 'Hidalgo 440', city: 'Monterrey', state: 'Nuevo Leon', postalCode: '64060', latitude: 25.6732, longitude: -100.3456 },
          ],
        },
        {
          id: 3,
          code: 'CLI-003',
          name: 'Retail Express',
          email: 'compras@retailexpress.mx',
          phone: '555-900-3322',
          serviceLevel: 'corporativo',
          addresses: [],
        },
      ],
      warehouses: [
        { id: 1, code: 'ALM-MTY', name: 'Almacen Monterrey', address: 'Parque Industrial 500', city: 'Monterrey', state: 'Nuevo Leon', postalCode: '64100' },
        { id: 2, code: 'ALM-APD', name: 'Hub Apodaca', address: 'Circuito Logistico 16', city: 'Apodaca', state: 'Nuevo Leon', postalCode: '66605' },
      ],
      routes: [
        { id: 1, code: 'RUTA-1201', warehouseId: 1, warehouseName: 'Almacen Monterrey', distanceKm: 48, timeMinutes: 85, status: 'En ejecucion', vehicleId: 1, vehiclePlate: 'TRK-204', driverId: 1, driverName: 'Javier Molina', scheduledDate: isoDate(today), optimizationScore: 94 },
        { id: 2, code: 'RUTA-1202', warehouseId: 2, warehouseName: 'Hub Apodaca', distanceKm: 32, timeMinutes: 60, status: 'Preparacion', vehicleId: 2, vehiclePlate: 'VAN-881', driverId: 2, driverName: 'Lucia Vega', scheduledDate: isoDate(today), optimizationScore: 89 },
        { id: 3, code: 'RUTA-1203', warehouseId: 1, warehouseName: 'Almacen Monterrey', distanceKm: 70, timeMinutes: 126, status: 'Completada', vehicleId: 3, vehiclePlate: 'BOX-550', driverId: 3, driverName: 'Mario Santos', scheduledDate: isoDate(yesterday), optimizationScore: 97 },
      ],
      drivers: [
        { id: 1, personId: 11, name: 'Javier Molina', phone: '555-981-2201', status: 'Activo', active: true, deliveriesToday: 18, shift: '06:00 - 14:00' },
        { id: 2, personId: 12, name: 'Lucia Vega', phone: '555-812-0123', status: 'En ruta', active: true, deliveriesToday: 11, shift: '07:00 - 15:00' },
        { id: 3, personId: 13, name: 'Mario Santos', phone: '555-744-9211', status: 'Fuera de turno', active: true, deliveriesToday: 20, shift: '08:00 - 16:00' },
        { id: 4, personId: 14, name: 'Andrea Reyes', phone: '555-233-8832', status: 'Disponible', active: true, deliveriesToday: 7, shift: '09:00 - 17:00' },
      ],
      vehicles: [
        { id: 1, plate: 'TRK-204', type: 'Camion ligero', status: 'Operativo', capacity: '1600 kg', fuelConsumptionKm: '10.4', maintenance: false },
        { id: 2, plate: 'VAN-881', type: 'Van', status: 'Operativo', capacity: '900 kg', fuelConsumptionKm: '13.8', maintenance: false },
        { id: 3, plate: 'BOX-550', type: 'Camion caja seca', status: 'Mantenimiento', capacity: '2400 kg', fuelConsumptionKm: '8.9', maintenance: true },
      ],
      shipments: [
        {
          id: 1,
          tracking: 'CF-240001',
          senderId: 1,
          recipientId: 2,
          originWarehouseId: 1,
          routeId: 1,
          vehicleId: 1,
          driverId: 1,
          packageType: 'Medicamento',
          initialStatus: 'En ruta',
          status: 'En ruta',
          priority: 'alta',
          weightKg: 125.4,
          volumeM3: 1.8,
          quantity: 14,
          scheduledDate: isoDate(today),
          originAddress: 'Parque Industrial 500',
          destinationAddressId: 201,
          destinationAddress: 'Hidalgo 440',
          destinationCity: 'Monterrey',
          destinationState: 'Nuevo Leon',
          destinationPostalCode: '64060',
          destinationLatitude: 25.6732,
          destinationLongitude: -100.3456,
          description: 'Entrega de inventario refrigerado',
          declaredValue: 18200,
          createdAt: new Date(today.getTime() - 3600 * 1000 * 6).toISOString(),
        },
        {
          id: 2,
          tracking: 'CF-240002',
          senderId: 3,
          recipientId: 1,
          originWarehouseId: 2,
          routeId: 2,
          vehicleId: 2,
          driverId: 2,
          packageType: 'Documentacion',
          initialStatus: 'Pendiente',
          status: 'Pendiente',
          priority: 'estandar',
          weightKg: 18,
          volumeM3: 0.22,
          quantity: 4,
          scheduledDate: isoDate(tomorrow),
          originAddress: 'Circuito Logistico 16',
          destinationAddressId: 101,
          destinationAddress: 'Av. Colosio 120',
          destinationCity: 'Monterrey',
          destinationState: 'Nuevo Leon',
          destinationPostalCode: '64000',
          description: 'Paqueteria de sucursales',
          declaredValue: 5600,
          createdAt: new Date(today.getTime() - 3600 * 1000 * 12).toISOString(),
        },
        {
          id: 3,
          tracking: 'CF-240003',
          senderId: 2,
          recipientId: 3,
          originWarehouseId: 1,
          routeId: 3,
          vehicleId: 3,
          driverId: 3,
          packageType: 'Electronica',
          initialStatus: 'Entregado',
          status: 'Entregado',
          priority: 'alta',
          weightKg: 310,
          volumeM3: 3.45,
          quantity: 22,
          scheduledDate: isoDate(yesterday),
          originAddress: 'Parque Industrial 500',
          destinationAddressId: null,
          destinationAddress: 'Av. Fundadores 2200',
          destinationCity: 'Monterrey',
          destinationState: 'Nuevo Leon',
          destinationPostalCode: '64710',
          description: 'Reposicion de tienda',
          declaredValue: 65500,
          createdAt: new Date(today.getTime() - 3600 * 1000 * 32).toISOString(),
        },
      ],
      settings: {
        companyName: 'CompraFacil Logistica',
        supportEmail: 'soporte@comprafacil.mx',
        supportPhone: '555-000-4455',
        dispatchStartTime: '06:00',
        defaultLeadDays: 2,
        maxDeliveryAttempts: 3,
        requirePhoto: true,
        requireSignature: true,
      },
    };
  }

  function loadStore() {
    const raw = window.localStorage.getItem(STORAGE_KEY);

    if (!raw) {
      const seeded = seedStore();
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(seeded));
      return seeded;
    }

    return JSON.parse(raw);
  }

  function saveStore(store) {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(store));
  }

  function getCustomer(store, id) {
    return store.customers.find((item) => item.id === Number(id));
  }

  function getRoute(store, id) {
    return store.routes.find((item) => item.id === Number(id));
  }

  function getVehicle(store, id) {
    return store.vehicles.find((item) => item.id === Number(id));
  }

  function getDriver(store, id) {
    return store.drivers.find((item) => item.id === Number(id));
  }

  function enrichShipment(store, shipment) {
    const sender = getCustomer(store, shipment.senderId);
    const recipient = getCustomer(store, shipment.recipientId);
    const route = getRoute(store, shipment.routeId);
    const vehicle = getVehicle(store, shipment.vehicleId);
    const driver = getDriver(store, shipment.driverId);
    const warehouse = store.warehouses.find((item) => item.id === Number(shipment.originWarehouseId));

    return {
      ...shipment,
      senderName: sender ? sender.name : 'Sin remitente',
      recipientName: recipient ? recipient.name : 'Sin destinatario',
      customerName: sender ? sender.name : 'Sin cliente',
      routeCode: route ? route.code : 'Pendiente',
      routeStatus: route ? route.status : 'Sin ruta',
      vehiclePlate: vehicle ? vehicle.plate : 'Pendiente',
      driverName: driver ? driver.name : 'Pendiente',
      warehouseName: warehouse ? warehouse.name : 'Asignacion automatica',
      latestAssignment: route
        ? {
            route: route.code,
            vehicle: vehicle ? vehicle.plate : null,
            driver: driver ? driver.name : null,
          }
        : null,
    };
  }

  function computeDashboard(store) {
    const shipments = store.shipments.map((item) => enrichShipment(store, item));
    const pending = shipments.filter((item) => item.status === 'Pendiente').length;
    const inRoute = shipments.filter((item) => item.status === 'En ruta').length;
    const deliveredToday = shipments.filter((item) => item.status === 'Entregado').length;

    return {
      kpis: [
        { title: 'Paquetes Totales', value: shipments.length, detail: 'Volumen operativo total' },
        { title: 'Rutas', value: store.routes.length, detail: 'Planeadas y en ejecucion' },
        { title: 'Vehiculos', value: store.vehicles.length, detail: 'Disponibilidad de flota' },
        { title: 'Conductores', value: store.drivers.length, detail: 'Capacidad de despacho' },
      ],
      richKpis: [
        { title: 'Envios totales', value: shipments.length, tone: 'primary' },
        { title: 'Pendientes', value: pending, tone: 'warning' },
        { title: 'En ruta', value: inRoute, tone: 'info' },
        { title: 'Entregados hoy', value: deliveredToday, tone: 'success' },
      ],
      strip: [
        { title: 'Despachos activos', value: 7, subtitle: 'Ventanas sincronizadas', className: 'accent-soft' },
        { title: 'Nivel SLA', value: '96.4%', subtitle: 'Ultimas 24 horas', className: 'brand-soft' },
        { title: 'Despacho automatizado', value: '18 reglas', subtitle: 'Ruteo dinamico', className: 'dark-gradient' },
        { title: 'Capacidad usada', value: '74%', subtitle: 'Flota + rutas', className: '' },
      ],
      charts: {
        operationalEvolution: [22, 26, 29, 31, 34, 38, 41],
        packageStatus: [pending, inRoute, deliveredToday, 1],
        deliveriesByHour: [3, 4, 6, 9, 12, 8, 5],
        routeState: [1, 1, 1, 0],
      },
      exceptions: {
        pendingDeparture: shipments.filter((item) => item.status === 'Pendiente').slice(0, 3),
        maintenanceUnits: store.vehicles.filter((item) => item.maintenance),
        outOfShiftDrivers: store.drivers.filter((item) => item.status === 'Fuera de turno'),
        activeRoutes: store.routes.filter((item) => item.status === 'En ejecucion' || item.status === 'Preparacion'),
      },
      pulse: {
        completedRoutes: store.routes.filter((item) => item.status === 'Completada').length,
        vehiclesInUse: store.vehicles.filter((item) => item.status === 'Operativo').length,
        activeDrivers: store.drivers.filter((item) => item.status !== 'Fuera de turno').length,
      },
      leaderboards: {
        drivers: store.drivers.slice(0, 3),
        customers: store.customers.slice(0, 3),
      },
    };
  }

  function computeOperations(store) {
    const shipments = store.shipments.map((item) => enrichShipment(store, item));

    return {
      overview: [
        { label: 'Despachos en espera', value: shipments.filter((item) => item.status === 'Pendiente').length },
        { label: 'Unidades operativas', value: store.vehicles.filter((item) => item.status === 'Operativo').length },
        { label: 'Conductores disponibles', value: store.drivers.filter((item) => item.status === 'Disponible' || item.status === 'Activo').length },
      ],
      dispatchQueue: shipments,
    };
  }

  function computeReports(store) {
    return {
      cards: [
        { title: 'Operaciones diarias', value: '41 entregas', detail: 'Corte de hoy 18:00' },
        { title: 'Desempeno conductores', value: '94.2%', detail: 'Promedio de cumplimiento' },
        { title: 'Eficiencia rutas', value: '88 pts', detail: 'Score de optimizacion' },
        { title: 'Costos operativos', value: '$18,240', detail: 'Costo diario estimado' },
        { title: 'Satisfaccion cliente', value: '4.7/5', detail: 'Ultimos 30 dias' },
      ],
      rows: [
        { metric: 'Entregas exitosas', value: 41, variation: '+8%' },
        { metric: 'Incidencias abiertas', value: 3, variation: '-2' },
        { metric: 'Uso de combustible', value: '426 L', variation: '+4%' },
        { metric: 'Tiempo promedio de ruta', value: '78 min', variation: '-6%' },
      ],
    };
  }

  function nextId(items) {
    return items.length ? Math.max(...items.map((item) => Number(item.id))) + 1 : 1;
  }

  function parseEndpoint(endpoint) {
    const url = new URL(endpoint, 'http://mock.local');
    return {
      path: url.pathname.replace(/\/$/, '') || '/',
      params: Object.fromEntries(url.searchParams.entries()),
    };
  }

  function jsonResponse(data, status) {
    return Promise.resolve({ status: status || 200, data: clone(data) });
  }

  function mutateEntity(store, collection, payload, entityId) {
    const list = store[collection];

    if (entityId) {
      const index = list.findIndex((item) => Number(item.id) === Number(entityId));

      if (index >= 0) {
        list[index] = { ...list[index], ...payload };
        return list[index];
      }
    }

    const created = { ...payload, id: nextId(list) };
    list.unshift(created);
    return created;
  }

  function handleLogin(store, payload) {
    const user = store.users.find((item) => item.email === payload.email && item.password === payload.password && item.active);

    if (!user) {
      return Promise.reject(new Error('Credenciales invalidas.'));
    }

    return jsonResponse({
      token: `mock-token-${user.id}`,
      user: {
        id: user.id,
        email: user.email,
        role: user.role,
        name: user.name,
      },
    });
  }

  function shipmentOptions(store) {
    return {
      customers: store.customers,
      packageTypes: ['Documentacion', 'Medicamento', 'Electronica', 'Fragil', 'Carga general'],
      statuses: ['Pendiente', 'Registrado', 'En ruta', 'Entregado'],
      priorities: ['estandar', 'alta', 'urgente'],
      warehouses: store.warehouses,
    };
  }

  function routeOptions(store) {
    return {
      warehouses: store.warehouses,
      statuses: ['Preparacion', 'En ejecucion', 'Completada', 'Cancelada'],
      vehicles: store.vehicles,
      drivers: store.drivers,
    };
  }

  function driverOptions(store) {
    return {
      people: store.drivers.map((item) => ({ id: item.personId, name: item.name })),
      statuses: ['Activo', 'Disponible', 'En ruta', 'Fuera de turno'],
    };
  }

  function vehicleOptions() {
    return {
      types: ['Van', 'Camion ligero', 'Camion caja seca', 'Moto'],
      statuses: ['Operativo', 'Disponible', 'Mantenimiento', 'Fuera de servicio'],
    };
  }

  function trackShipment(store, trackingCode) {
    const shipment = store.shipments.find((item) => item.tracking === trackingCode);

    if (!shipment) {
      return null;
    }

    return {
      shipment: enrichShipment(store, shipment),
      events: [
        { id: 1, type: 'Registro', description: 'Envio registrado en plataforma', location: shipment.originAddress, timestamp: shipment.createdAt },
        { id: 2, type: 'Asignacion', description: `Asignado a ${getRoute(store, shipment.routeId)?.code || 'ruta pendiente'}`, location: 'Mesa de despacho', timestamp: new Date(today.getTime() - 3600 * 1000 * 4).toISOString() },
        { id: 3, type: 'Transito', description: `Unidad ${getVehicle(store, shipment.vehicleId)?.plate || 'sin asignar'} en movimiento`, location: shipment.destinationCity, timestamp: new Date(today.getTime() - 3600 * 1000 * 2).toISOString() },
      ],
    };
  }

  function removeEntity(store, collection, entityId) {
    store[collection] = store[collection].filter((item) => Number(item.id) !== Number(entityId));
  }

  window.LogisticHubMockApi = {
    reset() {
      const seeded = seedStore();
      saveStore(seeded);
      return seeded;
    },
    request(endpoint, options) {
      const settings = options || {};
      const method = (settings.method || 'GET').toUpperCase();
      const payload = settings.body ? JSON.parse(settings.body) : null;
      const parsed = parseEndpoint(endpoint);
      const path = parsed.path;
      const store = loadStore();

      if (path === '/auth/login' && method === 'POST') {
        return handleLogin(store, payload);
      }

      if (path === '/dashboard' && method === 'GET') {
        return jsonResponse(computeDashboard(store));
      }

      if (path === '/operations' && method === 'GET') {
        return jsonResponse(computeOperations(store));
      }

      if (path === '/customers' && method === 'GET') {
        return jsonResponse(store.customers);
      }

      if (path === '/shipments/options' && method === 'GET') {
        return jsonResponse(shipmentOptions(store));
      }

      if (path === '/routes/options' && method === 'GET') {
        return jsonResponse(routeOptions(store));
      }

      if (path === '/drivers/options' && method === 'GET') {
        return jsonResponse(driverOptions(store));
      }

      if (path === '/vehicles/options' && method === 'GET') {
        return jsonResponse(vehicleOptions());
      }

      if (path === '/shipments' && method === 'GET') {
        return jsonResponse(store.shipments.map((item) => enrichShipment(store, item)));
      }

      if (/^\/shipments\/\d+$/.test(path) && method === 'GET') {
        const id = path.split('/').pop();
        const shipment = store.shipments.find((item) => Number(item.id) === Number(id));
        return jsonResponse(enrichShipment(store, shipment));
      }

      if (path === '/shipments' && method === 'POST') {
        const created = mutateEntity(store, 'shipments', {
          tracking: payload.tracking,
          senderId: Number(payload.senderId),
          recipientId: Number(payload.recipientId),
          originWarehouseId: payload.originWarehouseId ? Number(payload.originWarehouseId) : null,
          routeId: payload.originWarehouseId ? store.routes[0]?.id || null : null,
          vehicleId: payload.originWarehouseId ? store.routes[0]?.vehicleId || null : null,
          driverId: payload.originWarehouseId ? store.routes[0]?.driverId || null : null,
          packageType: payload.packageType,
          initialStatus: payload.initialStatus,
          status: payload.initialStatus || 'Pendiente',
          priority: payload.priority,
          weightKg: Number(payload.weightKg || 0),
          volumeM3: Number(payload.volumeM3 || 0),
          quantity: Number(payload.quantity || 1),
          scheduledDate: payload.scheduledDate,
          originAddress: payload.originAddress,
          destinationAddressId: payload.destinationAddressId ? Number(payload.destinationAddressId) : null,
          destinationAddress: payload.destinationAddress,
          destinationCity: payload.destinationCity,
          destinationState: payload.destinationState,
          destinationPostalCode: payload.destinationPostalCode,
          destinationLatitude: payload.destinationLatitude ? Number(payload.destinationLatitude) : null,
          destinationLongitude: payload.destinationLongitude ? Number(payload.destinationLongitude) : null,
          description: payload.description,
          declaredValue: Number(payload.declaredValue || 0),
          createdAt: new Date().toISOString(),
        });

        saveStore(store);

        if (created.routeId) {
          const route = getRoute(store, created.routeId);
          return jsonResponse({
            item: enrichShipment(store, created),
            message: `Envio creado y asignado automaticamente a ${route.code}.`,
          }, 201);
        }

        return jsonResponse({
          item: enrichShipment(store, created),
          message: 'Envio creado, pendiente de asignacion automatica.',
        }, 201);
      }

      if (/^\/shipments\/\d+$/.test(path) && method === 'PUT') {
        const id = path.split('/').pop();
        const updated = mutateEntity(store, 'shipments', { ...payload }, id);
        saveStore(store);
        return jsonResponse({ item: enrichShipment(store, updated), message: 'Envio actualizado correctamente.' });
      }

      if (/^\/shipments\/\d+$/.test(path) && method === 'DELETE') {
        const id = path.split('/').pop();
        removeEntity(store, 'shipments', id);
        saveStore(store);
        return jsonResponse(null, 204);
      }

      if (path === '/routes' && method === 'GET') {
        return jsonResponse(store.routes);
      }

      if (/^\/routes\/\d+$/.test(path) && method === 'GET') {
        const id = path.split('/').pop();
        return jsonResponse(store.routes.find((item) => Number(item.id) === Number(id)));
      }

      if (path === '/routes' && method === 'POST') {
        const warehouse = store.warehouses.find((item) => item.id === Number(payload.warehouseId));
        const created = mutateEntity(store, 'routes', {
          code: `RUTA-${String(Date.now()).slice(-4)}`,
          warehouseId: Number(payload.warehouseId),
          warehouseName: warehouse ? warehouse.name : 'Sin almacen',
          distanceKm: Number(payload.distanceKm || 0),
          timeMinutes: Number(payload.timeMinutes || 0),
          status: payload.status,
          vehicleId: payload.vehicleId ? Number(payload.vehicleId) : null,
          vehiclePlate: getVehicle(store, payload.vehicleId)?.plate || 'Pendiente',
          driverId: payload.driverId ? Number(payload.driverId) : null,
          driverName: getDriver(store, payload.driverId)?.name || 'Pendiente',
          optimizationScore: 90,
        });
        saveStore(store);
        return jsonResponse({ item: created, message: 'Ruta guardada correctamente.' }, 201);
      }

      if (/^\/routes\/\d+$/.test(path) && method === 'PUT') {
        const id = path.split('/').pop();
        const updated = mutateEntity(store, 'routes', { ...payload }, id);
        saveStore(store);
        return jsonResponse({ item: updated, message: 'Ruta actualizada correctamente.' });
      }

      if (/^\/routes\/\d+$/.test(path) && method === 'DELETE') {
        const id = path.split('/').pop();
        removeEntity(store, 'routes', id);
        saveStore(store);
        return jsonResponse(null, 204);
      }

      if (path === '/drivers' && method === 'GET') {
        return jsonResponse(store.drivers);
      }

      if (/^\/drivers\/\d+$/.test(path) && method === 'GET') {
        const id = path.split('/').pop();
        return jsonResponse(store.drivers.find((item) => Number(item.id) === Number(id)));
      }

      if (path === '/drivers' && method === 'POST') {
        const created = mutateEntity(store, 'drivers', {
          personId: Number(payload.personId),
          name: payload.name,
          phone: payload.phone,
          status: payload.status,
          active: true,
          deliveriesToday: 0,
          shift: '09:00 - 17:00',
        });
        saveStore(store);
        return jsonResponse({ item: created, message: 'Conductor guardado correctamente.' }, 201);
      }

      if (/^\/drivers\/\d+$/.test(path) && method === 'PUT') {
        const id = path.split('/').pop();
        const updated = mutateEntity(store, 'drivers', { ...payload }, id);
        saveStore(store);
        return jsonResponse({ item: updated, message: 'Conductor actualizado correctamente.' });
      }

      if (/^\/drivers\/\d+$/.test(path) && method === 'DELETE') {
        const id = path.split('/').pop();
        removeEntity(store, 'drivers', id);
        saveStore(store);
        return jsonResponse(null, 204);
      }

      if (path === '/vehicles' && method === 'GET') {
        return jsonResponse(store.vehicles);
      }

      if (/^\/vehicles\/\d+$/.test(path) && method === 'GET') {
        const id = path.split('/').pop();
        return jsonResponse(store.vehicles.find((item) => Number(item.id) === Number(id)));
      }

      if (path === '/vehicles' && method === 'POST') {
        const created = mutateEntity(store, 'vehicles', {
          plate: payload.plate,
          type: payload.type,
          status: payload.status,
          capacity: payload.capacity,
          fuelConsumptionKm: payload.fuelConsumptionKm,
          maintenance: payload.status === 'Mantenimiento',
        });
        saveStore(store);
        return jsonResponse({ item: created, message: 'Vehiculo guardado correctamente.' }, 201);
      }

      if (/^\/vehicles\/\d+$/.test(path) && method === 'PUT') {
        const id = path.split('/').pop();
        const updated = mutateEntity(store, 'vehicles', { ...payload }, id);
        saveStore(store);
        return jsonResponse({ item: updated, message: 'Vehiculo actualizado correctamente.' });
      }

      if (/^\/vehicles\/\d+$/.test(path) && method === 'DELETE') {
        const id = path.split('/').pop();
        removeEntity(store, 'vehicles', id);
        saveStore(store);
        return jsonResponse(null, 204);
      }

      if (path === '/users' && method === 'GET') {
        return jsonResponse(store.users.map((item) => ({ id: item.id, email: item.email, role: item.role, active: item.active, name: item.name })));
      }

      if (/^\/users\/\d+$/.test(path) && method === 'GET') {
        const id = path.split('/').pop();
        const user = store.users.find((item) => Number(item.id) === Number(id));
        return jsonResponse({ id: user.id, email: user.email, role: user.role, active: user.active, name: user.name });
      }

      if (path === '/users' && method === 'POST') {
        const created = mutateEntity(store, 'users', {
          email: payload.email,
          password: payload.password,
          role: payload.role,
          active: Boolean(payload.active),
          name: payload.name || payload.email,
        });
        saveStore(store);
        return jsonResponse({ item: created, message: 'Usuario guardado correctamente.' }, 201);
      }

      if (/^\/users\/\d+$/.test(path) && method === 'PUT') {
        const id = path.split('/').pop();
        const updated = mutateEntity(store, 'users', {
          email: payload.email,
          role: payload.role,
          active: Boolean(payload.active),
          password: payload.password || undefined,
        }, id);

        if (!payload.password) {
          delete updated.password;
        }

        saveStore(store);
        return jsonResponse({ item: updated, message: 'Usuario actualizado correctamente.' });
      }

      if (/^\/users\/\d+$/.test(path) && method === 'DELETE') {
        const id = path.split('/').pop();
        removeEntity(store, 'users', id);
        saveStore(store);
        return jsonResponse(null, 204);
      }

      if (path === '/settings' && method === 'GET') {
        return jsonResponse(store.settings);
      }

      if (path === '/settings' && method === 'PUT') {
        store.settings = { ...store.settings, ...payload };
        saveStore(store);
        return jsonResponse({ item: store.settings, message: 'Configuracion guardada correctamente.' });
      }

      if (path === '/reports' && method === 'GET') {
        return jsonResponse(computeReports(store));
      }

      if (/^\/tracking\/.+$/.test(path) && method === 'GET') {
        const trackingCode = decodeURIComponent(path.split('/').pop());
        const tracked = trackShipment(store, trackingCode);

        if (!tracked) {
          return Promise.reject(new Error('No se encontro el codigo de rastreo indicado.'));
        }

        return jsonResponse(tracked);
      }

      return Promise.reject(new Error(`Mock API sin soporte para ${method} ${path}`));
    },
  };
})();