# GESTIONPAQ Mobile

Cliente movil en React Native con Expo para la operacion de GESTIONPAQ.

## Version actual

- Expo SDK `54`
- React Native `0.81.5`
- React `19.1.0`

## Alcance actual

- Login con `POST /api/auth/login`
- Resumen operativo con `GET /api/dashboard`
- Lista de envios con `GET /api/shipments`
- Lista de rutas con `GET /api/routes`
- Rastreo por codigo con `GET /api/tracking/{trackingCode}`
- Registro de evidencia de entrega con `POST /api/shipments/{id}/evidence`
- Perfil y cierre de sesion

## Como ejecutarlo

1. Instala Node.js 18 o superior.
2. En esta carpeta ejecuta `npm install`.
3. Define `EXPO_PUBLIC_API_BASE` o `EXPO_PUBLIC_API_BASES` apuntando al backend accesible por red local.
4. Ejecuta `npm run start:lan`.

## Prueba desde telefono en la misma red

IP elegida para la prueba LAN actual:

- `192.168.1.81`

Backend sugerido para el movil:

- `http://192.168.1.81:8010/api`
- `http://192.168.10.229:8010/api`

Pasos:

1. En la carpeta Laravel ejecuta `php artisan serve --host 0.0.0.0 --port 8010`.
2. En esta carpeta crea un archivo `.env` con:

```env
EXPO_PUBLIC_API_BASE=http://192.168.1.81:8010/api
EXPO_PUBLIC_API_BASES=http://192.168.1.81:8010/api,http://192.168.10.229:8010/api,http://192.168.1.81:8021/api,http://192.168.10.229:8021/api
```

3. Ejecuta `npm run start:lan`.
4. Abre Expo Go en tu telefono y escanea el QR.

Si el telefono no esta en la misma red Wi-Fi que tu PC, no funcionara.

Si usas otra interfaz de red, puedes intentar tambien:

- `http://192.168.10.229:8010/api`

La app movil ahora puede intentar varias bases API en este orden si la principal falla:

- `http://192.168.1.81:8010/api`
- `http://192.168.10.229:8010/api`
- `http://192.168.1.81:8021/api`
- `http://192.168.10.229:8021/api`

## Nota

El proyecto ya fue validado con `expo-doctor` en SDK 54 y el servidor Expo pudo iniciar localmente. Si Expo Go sigue mostrando una version vieja, cierra la app, vuelve a escanear el QR y asegúrate de usar un bundler reiniciado. Si no carga en el telefono, revisa firewall de Windows y que Laravel este escuchando en `0.0.0.0`.