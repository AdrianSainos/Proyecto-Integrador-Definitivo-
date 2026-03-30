# GESTIONPAQ Frontend Architecture

## Estado actual comprobado

- Laravel arranca correctamente con `php artisan about`.
- Laravel conecta a `gestionpaq` sobre MariaDB 10.4.32.
- `php artisan db:show` no es confiable en este servidor porque intenta consultar `performance_schema.session_status`.
- La capa API REST ya esta implementada en `routes/api.php` y controladores `App\Http\Controllers\Api\*`.
- La base queda preparada para sembrar solo catalogos y un usuario admin inicial.
- El modulo de reportes ya exporta CSV y PDF reales desde la API.
- Existe un cliente movil Expo scaffolded en `mobile-expo/` consumiendo la misma API.

## Decision de arquitectura

La capa visual queda completamente desacoplada de Laravel Blade y vive en archivos estaticos dentro de `public/logistichub`.

- HTML: estructura de cada pantalla.
- CSS: sistema visual premium administrativo, responsive y consistente.
- JavaScript vanilla: shell, navegacion, autenticacion, consumo API, render de tablas, formularios y graficas.
- Laravel/PHP: solo backend, autenticacion, reglas de negocio, acceso a base de datos y futuros endpoints REST.

## Estructura propuesta

```text
public/logistichub/
  index.html
  login.html
  dashboard.html
  operations.html
  customers.html
  shipments.html
  shipment-form.html
  routes.html
  route-form.html
  drivers.html
  driver-form.html
  vehicles.html
  vehicle-form.html
  users.html
  user-form.html
  settings.html
  reports.html
  tracking.html
  assets/
    css/
      styles.css
    js/
      core.js
      shared/
        layout.js
        mock-data.js
      pages/
        login.js
        dashboard.js
        operations.js
        customers.js
        shipments.js
        shipment-form.js
        routes.js
        route-form.js
        drivers.js
        driver-form.js
        vehicles.js
        vehicle-form.js
        users.js
        user-form.js
        settings.js
        reports.js
        tracking.js
mobile-expo/
  App.js
  app.json
  package.json
  src/
    api.js
    config.js
    theme.js
    components/
      Ui.js
    screens/
      LoginScreen.js
      HomeScreen.js
      ShipmentsScreen.js
      RoutesScreen.js
      TrackingScreen.js
      ProfileScreen.js
```

## Principios del frontend

1. Ninguna vista final depende de Blade para renderizar layout, cards, formularios o tablas.
2. Toda llamada de datos pasa por `apiRequest(endpoint, options)`.
3. El token Bearer vive en `localStorage`.
4. Notices temporales entre pantallas viven en `sessionStorage`.
5. El menu lateral se construye por roles y oculta modulos no autorizados.
6. El layout es responsivo y soporta sidebar fija en desktop y drawer en movil.
7. La interfaz traduce nombres tecnicos o IDs del backend a etiquetas legibles.

## Modulos compartidos

### `assets/js/core.js`

Responsabilidades:

- `API_BASE` configurable por `localStorage` o `window.LOGISTICHUB_CONFIG`
- `getToken`, `setToken`, `clearToken`
- `apiRequest`
- `logout`
- `protectPage`
- `setNotice`, `consumeNotice`, `renderNotice`
- `initBackButtons`
- helpers de formato, roles y query string

### `assets/js/shared/layout.js`

Responsabilidades:

- Renderizar sidebar, top navbar y page shell en todas las pantallas privadas.
- Aplicar visibilidad por roles.
- Pintar resumen de usuario y accesos rapidos.
- Manejar toggle movil del sidebar.
- Resolver busqueda rapida de tracking desde la navbar.

### `assets/js/shared/mock-data.js`

Responsabilidades:

- Simular endpoints REST mientras el backend real no exista.
- Persistir datos demo en `localStorage`.
- Permitir crear, editar y eliminar entidades desde la UI.
- Servir como contrato inicial para futuras respuestas del backend.

## Contrato de API sugerido

### Auth

- `POST /auth/login`
- `POST /auth/logout`
- `GET /auth/me`

### Dashboard

- `GET /dashboard`

### Clientes

- `GET /customers`
- `GET /customers/{id}`

### Envio y despacho

- `GET /shipments`
- `GET /shipments/options`
- `GET /shipments/{id}`
- `POST /shipments`
- `PUT /shipments/{id}`
- `DELETE /shipments/{id}`

### Rutas

- `GET /routes`
- `GET /routes/{id}`
- `POST /routes`
- `PUT /routes/{id}`
- `DELETE /routes/{id}`

### Conductores

- `GET /drivers`
- `GET /drivers/{id}`
- `POST /drivers`
- `PUT /drivers/{id}`
- `DELETE /drivers/{id}`

### Vehiculos

- `GET /vehicles`
- `GET /vehicles/{id}`
- `POST /vehicles`
- `PUT /vehicles/{id}`
- `DELETE /vehicles/{id}`

### Usuarios

- `GET /users`
- `GET /users/{id}`
- `POST /users`
- `PUT /users/{id}`
- `DELETE /users/{id}`

### Configuracion

- `GET /settings`
- `PUT /settings`

### Reportes

- `GET /reports?type=daily&range=today`
- `GET /reports/export/csv?range=today`
- `GET /reports/export/pdf?range=today`

### Tracking

- `GET /tracking/{trackingCode}`

## Traduccion entre BD y UI

El frontend debe absorber inconsistencias del esquema real. Ejemplos:

- `codigo_tracking` o `tracking_code` -> `tracking`
- `peso`, `peso_kg`, `weight_kg` -> `weightKg`
- `volumen`, `volumen_m3`, `volume_m3` -> `volumeM3`
- `almacen_origen_id`, `warehouse_id`, `origin_warehouse_id` -> `originWarehouseId`
- `conductor_id` o `driver_id` -> `driverId`
- `vehiculo_id` o `vehicle_id` -> `vehicleId`

La UI nunca debe exponer campos tecnicos como `direccion_destino_id` como input visible. Debe mostrar selects legibles y formularios guiados.

## Mapeo base del esquema compartido

### Catalogos maestros

- `roles`
- `tipo_paquete`
- `estado_paquete`
- `tipo_vehiculo`
- `estado_vehiculo`
- `estado_conductor`
- `estado_ruta`
- `tipo_mantenimiento`

### Entidades operativas

- `usuarios`
- `personas`
- `clientes`
- `conductores`
- `almacenes`
- `vehiculos`
- `paquetes`
- `rutas`
- `ruta_paradas`
- `asignaciones`
- `tracking`
- `historial_estado_paquete`
- `evidencias`
- `mantenimiento`
- `configuracion_sistema`
- `cliente_direcciones`
- `turnos_conductor`

## Integracion futura con Laravel

Cuando exista la API real, el frontend actual solo necesitara:

1. Configurar `localStorage.setItem('logistichub.apiBase', 'http://tu-backend/api')`.
2. Implementar los endpoints REST indicados.
3. Devolver JSON normalizado o permitir que el frontend traduzca campos heredados.
4. Dejar a Laravel fuera del renderizado visual.

## Cliente movil Expo

El cliente movil vive en `mobile-expo/` y consume la misma API desacoplada.

Pantallas iniciales:

- Login contra `POST /api/auth/login`
- Inicio con resumen movil usando `GET /api/dashboard`
- Envios usando `GET /api/shipments`
- Rutas usando `GET /api/routes`
- Rastreo usando `GET /api/tracking/{trackingCode}`
- Perfil y cierre de sesion

Para ejecutarlo se requiere Node.js 18+ y ajustar `mobile-expo/src/config.js` con la IP o dominio del backend.

## Credenciales iniciales del backend

- Email: `admin@gestionpaq.local`
- Password: `admin123`

Estas credenciales funcionan como bootstrap minimo del sistema. Los datos operativos deben venir de tu base real.