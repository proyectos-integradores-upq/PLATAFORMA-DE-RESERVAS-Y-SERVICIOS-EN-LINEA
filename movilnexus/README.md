# MóvilNexus — Aplicación Móvil de Reservación de Espacios

Aplicación móvil para la gestión y reservación de espacios institucionales, construida con **React Native** y **Expo**.

> ⚠️ Esta capa **consume la API REST de NexusPoint** como cliente HTTP.  
> No contiene lógica de negocio ni almacenamiento persistente de datos (excepto el token JWT en AsyncStorage).

---

## Tabla de contenidos

1. [¿Qué hace este proyecto?](#qué-hace-este-proyecto)
2. [Cómo funciona la autenticación](#cómo-funciona-la-autenticación)
3. [Requisitos previos](#requisitos-previos)
4. [Instalación](#instalación)
5. [Variables de entorno](#variables-de-entorno)
6. [Estructura del proyecto](#estructura-del-proyecto)
7. [Pantallas disponibles](#pantallas-disponibles)
8. [Módulos funcionales](#módulos-funcionales)
9. [Flujo de una petición](#flujo-de-una-petición)
10. [Compilación y despliegue](#compilación-y-despliegue)
11. [Solución de problemas comunes](#solución-de-problemas-comunes)

---

## ¿Qué hace este proyecto?

`MóvilNexus` es la **aplicación móvil del sistema NexusPoint**. Permite a estudiantes, docentes y personal:

- Autenticarse con credenciales institucionales
- Explorar espacios disponibles (aulas, laboratorios, cubículos, etc.)
- Realizar reservaciones de espacios con calendario integrado
- Recibir notificaciones en tiempo real sobre estado de solicitudes
- Gestionar y consultar reservaciones activas
- Ver y editar información del perfil personal
- Historial de reservaciones pasadas

Todo lo anterior se logra consumiendo la API REST de NexusPoint mediante peticiones HTTP con el token JWT del usuario autenticado, almacenado localmente en AsyncStorage.

---

## Cómo funciona la autenticación

La autenticación ocurre completamente con la API. Los datos del usuario **no se almacenan en la app**, solo el token JWT.

```
Usuario ingresa         App envía               API valida y         App guarda
credenciales     →      POST /auth/login    →   devuelve JWT    →    token en
en el formulario        a la API                + datos usuario       AsyncStorage
```

1. El usuario ingresa correo y contraseña en `LoginScreen`.
2. La app ejecuta: `apiFetch('/auth/login', { method: 'POST', body: {...} })`
3. La API valida y responde con `{ access_token: "jwt...", user: {...} }`
4. La app guarda el token: `AsyncStorage.setItem('token', access_token)`
5. **Todas las peticiones siguientes** incluyen ese token en el header `Authorization: Bearer {token}`.
6. El middleware `CheckApiToken` en el cliente valida que el token exista antes de permitir acceso a pantallas protegidas.
7. Si el token expira, la app redirige al login automáticamente.

---

## Requisitos previos

Antes de instalar, asegúrate de tener lo siguiente:

| Requisito | Versión mínima | Cómo verificar |
|-----------|---------------|----------------|
| Node.js | 18.x | `node -v` |
| npm | 9.x | `npm -v` |
| Expo CLI | 2.x | `expo -v` |
| Git | cualquiera | `git -v` |

### Dependencias del sistema operativo

**Para Android:**
- Android Studio (o solo Android SDK)
- Emulador Android o dispositivo físico con USB habilitado

**Para iOS (solo Mac):**
- Xcode 14+
- iOS Simulator o dispositivo físico con Xcode Developer Tools

**Para Web:**
- Navegador moderno (Chrome, Firefox, Safari, Edge)

> **¿Necesito una API local?** No. La app se conecta a `https://nexuspoint-api.onrender.com` por defecto.

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio>
cd movilnexus
```

### 2. Instalar dependencias

```bash
npm install
```

### 3. Configurar variables de entorno (opcional)

La app ya viene con `BASE_URL = 'https://nexuspoint-api.onrender.com'` configurado en `services/api.js`.

Si deseas cambiar la URL de la API para desarrollo local:
- Edita `services/api.js`
- Reemplaza `'https://nexuspoint-api.onrender.com'` con `'http://localhost:8001'` (si tienes la API local)

### 4. Iniciar la aplicación

```bash
# Inicia el metro bundler de Expo
npm start
```

Verás un código QR en la terminal. Escanéalo con tu dispositivo:
- **Android**: Abre Expo Go y escanea el código QR
- **iOS**: Abre la cámara y escanea el código QR (Expo Go se abrirá automáticamente)
- **Web**: Presiona `w` en la terminal

---

## 📲 Comandos disponibles

```bash
# Inicia la aplicación en modo desarrollo (metro bundler)
npm start

# Ejecutar directamente en Android (requiere Android Emulator abierto o dispositivo conectado)
npm run android

# Ejecutar directamente en iOS (solo macOS, requiere iOS Simulator abierto)
npm run ios

# Ejecutar en navegador web
npm run web
```

---

## Variables de entorno

La configuración principal está en [services/api.js](services/api.js):

```javascript
export const BASE_URL = 'https://nexuspoint-api.onrender.com';
```

### Cambiar la URL de la API

Para desarrollo local, modifica `services/api.js`:

```javascript
// ← Desarrollo local
export const BASE_URL = 'http://localhost:8001';

// ← Producción (Render)
export const BASE_URL = 'https://nexuspoint-api.onrender.com';

// ← Otro servidor
export const BASE_URL = 'https://tu-servidor.com/api';
```

La app automáticamente incluirá el token JWT en el header de todas las peticiones HTTP:
```
Authorization: Bearer {token_jwt}
```

---

## Estructura del Proyecto

```
movilnexus/
│
├── screens/
│   ├── SplashScreen.js         ← Pantalla de bienvenida (logo + loading)
│   ├── LoginScreen.js          ← Formulario de login
│   ├── RecuperarScreen.js      ← Recuperación de contraseña olvidada
│   ├── homeScreen.js           ← Inicio con búsqueda de espacios
│   ├── ReservarScreen.js       ← Calendario y formulario de reservación
│   ├── NotificacionesScreen.js ← Notificaciones y alertas
│   └── PerfilScreen.js         ← Datos de usuario y opciones
│
├── components/
│   └── BottomNav.js            ← Navegación inferior (tabs)
│
├── services/
│   ├── api.js                  ← Cliente HTTP base (fetch + token)
│   ├── authService.js          ← Operaciones de autenticación
│   ├── espaciosService.js      ← Listar, buscar y obtener espacios
│   ├── notificacionesService.js ← Obtener notificaciones
│   └── reservacionesService.js ← CRUD de reservaciones
│
├── controllers/
│   ├── authController.js       ← Lógica de login/logout/recuperar
│   ├── espaciosController.js   ← Lógica de búsqueda y filtrado
│   ├── notificacionesController.js ← Procesamiento de notificaciones
│   └── reservacionesController.js ← Lógica de reservaciones
│
├── constants/
│   └── theme.js                ← Colores, tipografías, estilos globales
│
├── assets/
│   ├── icon.png                ← Icono de la app
│   ├── splash-icon.png         ← Splash screen
│   └── android-icon-*.png      ← Iconos Android (foreground, background, etc.)
│
├── App.js                       ← Componente raíz (navegación principal)
├── app.json                     ← Configuración de Expo
├── index.js                     ← Entry point
└── package.json
```

### Archivos clave para entender el proyecto

| Archivo | Qué hace |
|---------|----------|
| `services/api.js` | Define `BASE_URL` y la función `apiFetch()` que automáticamente incluye el token JWT en cada petición. **Es el corazón de la comunicación con la API**. |
| `services/authService.js` | Funciones `login()`, `logout()`, `recuperarPassword()`. Llaman a `apiFetch()` con los endpoints correspondientes. |
| `services/espaciosService.js` | Funciones para obtener lista de espacios, buscar, filtrar por tipo/disponibilidad. |
| `controllers/authController.js` | Orquesta el flujo de autenticación: llama a `authService`, maneja errores, actualiza la navegación. |
| `App.js` | Sistema de navegación principal. Define dos flujos: `vista` (login/app) y `pantalla` (home/reservar/notifs/perfil). |
| `screens/LoginScreen.js` | Formulario de login que llama a `authController.login()`. |
| `screens/homeScreen.js` | Pantalla principal con búsqueda y listado de espacios. |
| `constants/theme.js` | Define colores, tamaños de fuente, espacios (padding/margin) para mantener consistencia visual. |

---

## Pantallas disponibles

### SplashScreen
Pantalla inicial con el logo de NexusPoint. Se muestra durante 2-3 segundos mientras la app carga configuraciones locales. Redirige automáticamente a LoginScreen.

### LoginScreen
Formulario de autenticación:
- Campo de correo electrónico
- Campo de contraseña
- Botón "Iniciar sesión"
- Enlace "¿Olvidaste tu contraseña?"
- Validación de campos y manejo de errores

### RecuperarScreen
Pantalla para recuperar contraseña:
- Campo de correo
- Envío de instrucciones de recuperación
- Botón para volver a login

### HomeScreen
Pantalla principal de la app (requiere autenticación):
- Saludo personalizado al usuario
- Barra de búsqueda de espacios
- Filtros: tipo (Aula, Lab, Cubículo), disponibilidad, capacidad
- Listado de espacios con:
  - Nombre, código y tipo
  - Capacidad y ocupación actual
  - Icono de equipamiento
  - Botón "Reservar"

### ReservarScreen
Gestión de reservaciones:
- **Tab 1: Reservar nuevo**
  - Seleccionar espacio (autocompletado)
  - Calendario para elegir fecha
  - Rango horario (hora inicio / hora fin)
  - Campo de observaciones (opcional)
  - Botón "Enviar solicitud"

- **Tab 2: Mis reservaciones**
  - Filtrar por estado: Pendientes, Aprobadas, Rechazadas, Finalizadas
  - Mostrar cada reservación con detalles
  - Botón "Cancelar" (solo si está Pendiente o Aprobada)

### NotificacionesScreen
- Listado de notificaciones con timestamp
- Tipos de notificación:
  - Solicitud Pendiente de revisión
  - Solicitud Aprobada
  - Solicitud Rechazada
  - Recordatorio: tu reservación es hoy
  - Tu reservación finalizó
- Marcar como leída/no leída
- Eliminar notificaciones antiguas

### PerfilScreen
- Ver datos personales:
  - Nombre completo
  - Matrícula
  - Email
  - Carrera/Departamento
  - Rol (Alumno, Docente, Encargado)
  - Teléfono (si aplica)

- Opciones:
  - Editar perfil (nombre, teléfono)
  - Cambiar contraseña
  - Ver mis solicitudes (todas las reservaciones)
  - Cerrar sesión

---

## Módulos funcionales

### Autenticación
- Login con correo y contraseña
- Validación de credenciales contra la API
- Almacenamiento seguro del token JWT en AsyncStorage
- Recuperación de contraseña por email
- Logout (limpia AsyncStorage)
- Manejo de expiración de sesión

### Exploración de Espacios
- Listar todos los espacios disponibles
- Búsqueda por nombre o código
- Filtros: tipo, capacidad, disponibilidad
- Ver detalles de cada espacio:
  - Capacidad total
  - Equipamiento disponible
  - Horarios de operación
  - Ocupación actual
- Historial de disponibilidad (próximos 7 días)

### Sistema de Reservaciones
- Crear nuevas solicitudes de reservación
- Seleccionar fecha y horario
- Ver estado de solicitudes: Pendiente, Aprobada, Rechazada, Finalizada
- Cancelar reservaciones (solo si están Pendientes o Aprobadas)
- Ver historial completo de reservaciones
- Descargar comprobante de reservación (PDF)

### Notificaciones
- Recibir notificaciones en tiempo real (push notifications)
- Notificaciones locales cuando la app está abierta
- Historial de notificaciones
- Clasificación por tipo y fecha

### Gestión de Perfil
- Ver información personal
- Editar nombre y teléfono
- Cambiar contraseña
- Visualizar estadísticas personales (total de reservaciones, aprobadas, etc.)

---

## Flujo de una petición

Ejemplo: el usuario busca espacios disponibles.

```
1. Usuario escribe "Aula" en la barra de búsqueda en homeScreen

2. JavaScript ejecuta:
   espaciosService.buscar('Aula', { tipo: 'Aula' })

3. espaciosService llama a:
   apiFetch('/espacios?nombre=Aula&tipo=Aula', { method: 'GET' })

4. apiFetch construye el header con el token:
   fetch('https://nexuspoint-api.onrender.com/espacios?...',
         headers: { Authorization: 'Bearer {token}' })

5. La API valida el token, busca en la BD y responde:
   { success: true, data: [{id: 1, nombre: "Aula 101", ...}, ...] }

6. espaciosService devuelve los datos al controller

7. espaciosController actualiza el estado de React:
   setEspacios([...resultados])

8. homeScreen se re-renderiza y muestra la lista actualizada

9. Si el usuario presiona "Reservar" en un espacio:
   - Se navega a ReservarScreen
   - Se pre-llena el espacio seleccionado
   - Usuario completa fecha, hora y observaciones
   - Al presionar "Enviar solicitud":
     reservacionesService.crear({espacioId, fecha, horaInicio, horaFin, ...})
   - apiFetch hace POST a /reservaciones con los datos
   - La API crea la solicitud y responde con { id, estatus: 'Pendiente' }
   - La app navega a NotificacionesScreen mostrando éxito
```

---

## Compilación y despliegue

### Compilación para distribución

#### Android

```bash
# Crear APK para testing
eas build --platform android --local

# Crear AAB para Google Play Store
eas build --platform android --release
```

#### iOS

```bash
# Crear IPA para TestFlight / App Store
eas build --platform ios --release
```

> Requiere certificados de código válidos. Ver: https://docs.expo.dev/build/setup/

### Publicar en tiendas

#### Google Play Store

1. Crear cuenta de desarrollador en Google Play Console
2. Compilar con `eas build --platform android --release`
3. Subir el AAB a Google Play Console
4. Configurar descripción, capturas, permisos
5. Enviar a revisión

#### App Store (iOS)

1. Crear cuenta de desarrollador Apple ($99/año)
2. Configurar certificados en Apple Developer
3. Compilar con `eas build --platform ios --release`
4. Subir con Transporter a App Store Connect
5. Completar metadata y enviar a revisión

### Publicar web

Puedes desplegar la versión web en Netlify, Vercel o cualquier servidor estático:

```bash
# Generar build para web
npm run web

# El código compilado está en .web-build/
```

---

## Solución de problemas comunes

### "Metro bundler no arranca"

```bash
# Limpiar caché
npm start -- --reset-cache

# O manualmente
rm -rf node_modules
npm install
npm start
```

### "No aparece el código QR en la terminal"

- Asegúrate de estar en la carpeta correcta: `cd movilnexus`
- Verifica que el puerto 19000 esté disponible: `netstat -an | grep 19000`
- Reinicia el metro bundler: `npm start`

### "El dispositivo no se conecta a Expo Go"

1. Asegúrate de que tu teléfono está en la **misma red WiFi** que la computadora
2. En Expo Go, selecciona "Escanear código QR"
3. Si aún no funciona, usa: `npm start -- --tunnel` (más lento pero más confiable)

### "TypeError: Cannot read property 'token' of undefined"

El token JWT no se guardó correctamente. Soluciones:
1. Verifica que AsyncStorage esté importado: `import AsyncStorage from '@react-native-async-storage/async-storage'`
2. Revisa que la API devuelve `access_token` en la respuesta de login
3. Intenta hacer login nuevamente
4. Revisa los logs: `npm start` (muestra errores de red)

### "Error 401 Unauthorized en todas las peticiones"

1. El token expiró. Haz logout e intenta login nuevamente.
2. Verifica que `NEXUSPOINT_API_URL` en `services/api.js` sea correcto
3. Revisa que la API está corriendo: abre `{BASE_URL}/docs` en el navegador

### "La API tarda mucho en responder"

Si usas `https://nexuspoint-api.onrender.com` (plan gratuito), el servidor hiberna después de inactividad. La primera petición puede tardar 30-60 segundos. Es normal. Para desarrollo, considera usar una API local.

### "Los estilos no se ven bien en mi dispositivo"

- Verifica que importas los estilos de `constants/theme.js` correctamente
- En emulador Android: verifica la densidad de pantalla
- En iOS: asegúrate de que el sistema y la app están en la misma escala

### "AsyncStorage vacío después de cerrar la app"

Es comportamiento normal. AsyncStorage persiste datos locales pero puede limpiarlos si:
- Desinstalaste la app y la volviste a instalar
- Limpiaste el caché de la app
- Ejecutaste `npm start -- --reset-cache`

### "¿Cómo cambio la URL de la API después de compilar?"

Para versiones compiladas (APK/IPA), debes:
1. Editar `services/api.js`
2. Cambiar `BASE_URL`
3. Recompilar con `eas build`

No hay forma de cambiar la URL en tiempo de ejecución sin recompilar.

---

## Notas de desarrollo

- **No usar** `fetch` directamente. Siempre usa `apiFetch()` de `services/api.js` para garantizar que el token JWT se incluye automáticamente.
- **El token es crítico**. Si se pierde, el usuario debe hacer login nuevamente. No intentes cachear datos sensibles más allá del token.
- **AsyncStorage solo para token**. No almacenes datos del usuario, espacios o reservaciones. Estos datos siempre deben venir de la API.
- Si la API tarda más de 60 segundos, la petición fallará con timeout. Muestra un mensaje de error amigable al usuario.
- Los logs de debug (console.log) pueden causar problemas en iOS. En producción, considera usar una librería de logging.
- Todos los controllers deben tener try/catch para manejar errores de la API de forma elegante.

---

**Versión**: 1.0.0  
**Stack**: React Native 0.83.2 | Expo 55.0.6 | AsyncStorage 2.2.0  
**Última actualización**: 2026
