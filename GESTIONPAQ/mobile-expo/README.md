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
3. Inicia el backend con `npm run api:lan` o levanta Apache/XAMPP si sirves Laravel desde `public`.
4. Define `EXPO_PUBLIC_API_BASE` o `EXPO_PUBLIC_API_BASES` apuntando al backend accesible por red local si quieres forzar una base concreta.
5. Ejecuta `npm run start:lan`.

## Prueba desde telefono en la misma red

Cada vez que la app diga que no puede entrar o que no se puede conectar, sigue este orden:

1. En Windows ejecuta `ipconfig` y toma la IPv4 de la PC conectada al mismo Wi-Fi que el telefono.
2. Levanta el backend con una de estas opciones:
	- `php artisan serve --host 0.0.0.0 --port 8010`
	- Apache/XAMPP sirviendo Laravel desde `GESTIONPAQ/public`
3. Verifica que al menos una de estas bases exista desde la PC:
	- `http://TU_IP_PC:8010/api`
	- `http://TU_IP_PC/GESTIONPAQ/public/api`
	- `http://TU_IP_PC/Proyecto-Integrador-Definitivo-/GESTIONPAQ/public/api`
4. En esta carpeta crea o actualiza `.env` con una base valida de tu red actual:

```env
EXPO_PUBLIC_API_BASE=http://TU_IP_PC:8010/api
EXPO_PUBLIC_API_BASES=http://TU_IP_PC:8010/api,http://TU_IP_PC/GESTIONPAQ/public/api,http://TU_IP_PC/Proyecto-Integrador-Definitivo-/GESTIONPAQ/public/api
```

5. Reinicia Expo con `npm run start:lan`.
6. Cierra Expo Go en el telefono y vuelve a escanear el QR.

Notas:

- Si el telefono no esta en la misma red Wi-Fi que tu PC, no funcionara.
- Si cambiaste de red o Windows te dio otra IP, actualiza `TU_IP_PC`.
- La app prueba varias bases API y guarda la ultima que funciono para el siguiente arranque.
- Tambien detecta automaticamente estas variantes sobre la IP del bundler Expo cuando existen:
  - `http://<tu-ip>/api`
  - `http://<tu-ip>/GESTIONPAQ/public/api`
  - `http://<tu-ip>/Proyecto-Integrador-Definitivo-/GESTIONPAQ/public/api`
- El probe movil usa `POST /auth/login` con cuerpo `{}` y `Accept: application/json`; en un backend Laravel valido responde `422` JSON y evita confundir HTML de Apache con una API real.

## Nota

El proyecto ya fue validado con `expo-doctor` en SDK 54 y el servidor Expo pudo iniciar localmente. Si Expo Go sigue mostrando una version vieja, cierra la app, vuelve a escanear el QR y asegúrate de usar un bundler reiniciado. Si no carga en el telefono, revisa firewall de Windows y que Laravel este escuchando en `0.0.0.0`.