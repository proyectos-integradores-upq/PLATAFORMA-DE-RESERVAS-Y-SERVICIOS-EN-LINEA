# nexusPointWeb — Panel Administrativo NexusPoint

Panel de administración web construido con **Laravel 12** para el sistema de gestión de espacios institucionales **NexusPoint**.

> ⚠️ Esta capa **no contiene lógica de negocio ni accede directamente a la base de datos**.  
> Actúa como cliente HTTP de la API REST de NexusPoint, la cual maneja toda la lógica y persistencia.

---

## Tabla de contenidos

1. [¿Qué hace este proyecto?](#qué-hace-este-proyecto)
2. [Cómo funciona la autenticación](#cómo-funciona-la-autenticación)
3. [Requisitos previos](#requisitos-previos)
4. [Instalación](#instalación)
5. [Variables de entorno](#variables-de-entorno)
6. [Estructura del proyecto](#estructura-del-proyecto)
7. [Rutas disponibles](#rutas-disponibles)
8. [Módulos del panel](#módulos-del-panel)
9. [Flujo de una petición](#flujo-de-una-petición)
10. [Despliegue](#despliegue)
11. [Solución de problemas comunes](#solución-de-problemas-comunes)

---

## ¿Qué hace este proyecto?

`nexusPointWeb` es el **panel de administración** del sistema. Permite a los administradores:

- Iniciar sesión con credenciales institucionales
- Ver estadísticas y gráficas del dashboard
- Gestionar **solicitudes de reserva** (aprobar / rechazar)
- Administrar **espacios** (aulas, laboratorios, cubículos, etc.) y su equipamiento
- Administrar **usuarios** del sistema
- Generar y exportar **reportes** en PDF y Excel
- Ver y editar el **perfil** del administrador

Todo lo anterior se logra consumiendo la API REST de NexusPoint mediante peticiones HTTP con el token JWT del usuario autenticado.

---

## Cómo funciona la autenticación

Laravel **no maneja usuarios propios** en este proyecto. La autenticación ocurre así:

```
Usuario ingresa         Laravel envía          API valida y         Laravel guarda
credenciales     →      POST /auth/login   →   devuelve JWT    →    token en sesión
en el formulario        a la API               + datos usuario       (api_token)
```

1. El usuario escribe su correo y contraseña en `/` (login).
2. Laravel hace un `POST` a `{NEXUSPOINT_API_URL}/auth/login`.
3. La API responde con un `access_token` (JWT).
4. Laravel guarda ese token en la sesión PHP (`Session::put('api_token', $token)`).
5. **Todas las peticiones siguientes** incluyen ese token en el header `Authorization: Bearer {token}`.
6. El middleware `CheckApiToken` verifica que la sesión tenga token antes de permitir acceso a rutas protegidas.

---

## Requisitos previos

Antes de instalar, asegúrate de tener lo siguiente:

| Requisito | Versión mínima | Cómo verificar |
|-----------|---------------|----------------|
| PHP | 8.2 | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 18.x | `node -v` |
| npm | 9.x | `npm -v` |
| Git | cualquiera | `git -v` |

Extensiones PHP requeridas (normalmente ya vienen con PHP 8.2):
- `ext-mbstring`
- `ext-openssl`
- `ext-pdo`
- `ext-tokenizer`
- `ext-ctype`
- `ext-json`
- `ext-curl`

> **¿Necesito base de datos local?** No. Este proyecto usa SQLite únicamente para las tablas internas de Laravel (sesiones, caché, colas). No almacena datos del negocio.

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio>
cd nexusPointWeb
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Instalar dependencias JavaScript

```bash
npm install
```

### 4. Copiar el archivo de entorno

```bash
cp .env.example .env
```

### 5. Generar la clave de la aplicación

```bash
php artisan key:generate
```

### 6. Crear la base de datos SQLite local

```bash
touch database/database.sqlite
```

### 7. Ejecutar las migraciones

```bash
php artisan migrate
```

Esto crea las tablas de Laravel: `sessions`, `cache`, `jobs`, `failed_jobs`.

### 8. Compilar los assets del frontend

```bash
# Para desarrollo (con hot reload):
npm run dev

# Para producción:
npm run build
```

### 9. Iniciar el servidor de desarrollo

```bash
php artisan serve
```

La aplicación estará disponible en: **http://localhost:8000**

---

## Variables de entorno

Abre el archivo `.env` y configura las siguientes variables:

```env
# ─── GENERAL ──────────────────────────────────────────────
APP_NAME=NexusPoint
APP_ENV=local          # Cambiar a "production" en producción
APP_DEBUG=true         # Cambiar a false en producción
APP_URL=http://localhost

# ─── CLAVE DE LA APLICACIÓN ───────────────────────────────
# Se genera automáticamente con: php artisan key:generate
APP_KEY=

# ─── CONEXIÓN CON LA API (OBLIGATORIO) ────────────────────
# URL base de la API REST de NexusPoint.
# Si trabajas en local con la API corriendo también en local:
NEXUSPOINT_API_URL=http://localhost:8001
#
# Si usas la API desplegada en Render:
# NEXUSPOINT_API_URL=https://nexuspoint-api.onrender.com

# ─── BASE DE DATOS LOCAL (solo para sesiones/caché) ────────
DB_CONNECTION=sqlite
# DB_DATABASE se toma automáticamente de database/database.sqlite

# ─── SESIONES ─────────────────────────────────────────────
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

### Variable más importante: `NEXUSPOINT_API_URL`

Esta variable define a qué API le habla el panel. Sin ella, **nada funciona**.

| Escenario | Valor |
|-----------|-------|
| API en local | `http://localhost:8001` |
| API en Render (producción) | `https://nexuspoint-api.onrender.com` |
| API en otro servidor | `https://tu-servidor.com/api` |

---

## Estructura del proyecto

```
nexusPointWeb/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminController.php   ← Todos los módulos del panel
│   │   │   └── AuthController.php    ← Login y logout
│   │   └── Middleware/
│   │       └── CheckApiToken.php     ← Protege rutas con sesión
│   └── Models/
│       └── User.php                  ← Modelo vacío (no se usa)
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   └── app.blade.php         ← Layout principal con sidebar
│   │   ├── admin/
│   │   │   ├── dashboard.blade.php
│   │   │   ├── solicitudes.blade.php
│   │   │   ├── espacios.blade.php
│   │   │   ├── espacios-form.blade.php
│   │   │   ├── usuarios.blade.php
│   │   │   ├── usuarios-form.blade.php
│   │   │   ├── reportes.blade.php
│   │   │   ├── perfil.blade.php
│   │   │   └── perfil-form.blade.php
│   │   └── login.blade.php
│   ├── css/app.css                   ← Tailwind CSS
│   └── js/app.js
│
├── public/
│   ├── css/app.css                   ← CSS propio del panel (NO Tailwind)
│   ├── js/
│   │   ├── login.js
│   │   └── dashboard.js
│   └── img/                          ← Logo y recursos visuales
│
├── routes/
│   └── web.php                       ← Todas las rutas del panel
│
├── database/
│   └── database.sqlite               ← BD local (sesiones/caché)
│
├── .env                              ← Variables de entorno (no subir a git)
├── .env.example                      ← Plantilla del .env
└── composer.json
```

### Archivos clave para entender el proyecto

| Archivo | Qué hace |
|---------|----------|
| `app/Http/Controllers/AdminController.php` | Contiene **todos los métodos del panel**: dashboard, solicitudes, espacios, usuarios, reportes, perfil. Cada método hace peticiones HTTP a la API y pasa los datos a la vista. |
| `app/Http/Controllers/AuthController.php` | Maneja el login (llama a `POST /auth/login` en la API) y el logout (limpia la sesión). |
| `app/Http/Middleware/CheckApiToken.php` | Middleware que verifica si existe `api_token` en la sesión. Si no existe, redirige al login. |
| `routes/web.php` | Define todas las rutas, agrupa las protegidas bajo el middleware `check.api.token`. |
| `resources/views/layouts/app.blade.php` | Layout con el sidebar de navegación. Todas las vistas del panel extienden este layout. |
| `public/css/app.css` | Estilos personalizados del panel (variables CSS, componentes, responsive). |

---

## Rutas disponibles

### Públicas

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/` | Formulario de login |
| POST | `/api-login` | Procesa el login (llama a la API) |
| GET | `/logout` | Cierra la sesión |

### Protegidas (requieren sesión activa)

Todas bajo el prefijo `/admin`:

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/admin/dashboard` | Panel de estadísticas |
| GET | `/admin/solicitudes` | Lista de reservaciones por estado |
| PATCH | `/admin/solicitudes/{id}` | Aprobar o rechazar una solicitud |
| GET | `/admin/espacios` | Lista de espacios |
| GET | `/admin/espacios/crear` | Formulario nuevo espacio |
| GET | `/admin/espacios/{id}/editar` | Formulario editar espacio |
| POST | `/admin/espacios` | Guardar nuevo espacio |
| PUT | `/admin/espacios/{id}` | Actualizar espacio |
| DELETE | `/admin/espacios/{id}` | Eliminar espacio |
| POST | `/admin/espacios/{id}/equipamiento` | Agregar equipamiento |
| DELETE | `/admin/espacios/{id}/equipamiento/{idEquip}` | Eliminar equipamiento |
| GET | `/admin/usuarios` | Lista de usuarios |
| GET | `/admin/usuarios/crear` | Formulario nuevo usuario |
| GET | `/admin/usuarios/{id}/editar` | Formulario editar usuario |
| POST | `/admin/usuarios` | Registrar nuevo usuario |
| PUT | `/admin/usuarios/{id}` | Actualizar usuario |
| DELETE | `/admin/usuarios/{id}` | Eliminar usuario |
| GET | `/admin/reportes` | Sección de reportes y exportaciones |
| GET | `/admin/perfil` | Ver perfil del administrador |
| GET | `/admin/perfil/editar` | Formulario editar perfil |
| PUT | `/admin/perfil` | Actualizar perfil o contraseña |
| GET | `/admin/ping-api` | Verifica si la API está activa |

---

## Módulos del panel

### Dashboard
Muestra estadísticas generales: total de reservaciones, espacios disponibles, tasa de aprobación, gráfica de actividad por día (Chart.js) y gráfica de ocupación por edificio. Permite exportar el reporte mensual en **PDF** (jsPDF) o **Excel** (ExcelJS).

### Solicitudes
Muestra todas las reservaciones agrupadas por estado:
- 🟡 **Pendientes** — con botones para Aprobar / Rechazar
- 🟢 **Aprobadas** — próximas reservaciones confirmadas
- 🔴 **Rechazadas**
- ⚫ **Finalizadas** — historial
- ◼ **Canceladas** — canceladas por el usuario

### Espacios
CRUD completo de espacios. Cada espacio tiene: nombre, código, tipo, nivel/piso, capacidad, estatus y equipamiento asignado. El equipamiento se gestiona con peticiones independientes a la API.

### Usuarios
CRUD completo de usuarios. Muestra matrícula, nombre, correo, carrera, rol y estado. Los roles disponibles son: Alumno, Docente, Encargado, Administrador.

### Reportes
Genera reportes en PDF o Excel de: solicitudes mensuales, ocupación por edificio, análisis de tendencias. También permite generar reportes personalizados por tipo y rango de fechas. La generación ocurre **100% en el navegador** (sin servidor).

### Perfil
Ver y editar los datos del administrador autenticado. También permite cambiar la contraseña.

---

## Flujo de una petición

Ejemplo: el administrador aprueba una solicitud.

```
1. Admin hace clic en "✓ Aprobar" en la vista solicitudes.blade.php

2. JavaScript ejecuta:
   fetch('/admin/solicitudes/42', {
     method: 'PATCH',
     body: JSON.stringify({ estatus: 'Aprobado', observaciones: '...' })
   })

3. Laravel recibe la petición en AdminController::actualizarSolicitud()

4. AdminController construye el payload y llama a la API:
   Http::withHeaders(['Authorization' => 'Bearer {token}'])
       ->post('{NEXUSPOINT_API_URL}/reservaciones/gestionar', $payload)

5. La API valida, actualiza la BD y responde con éxito/error.

6. AdminController devuelve JSON al navegador:
   { "success": true }

7. JavaScript recarga la página para mostrar el estado actualizado.
```

---

## Despliegue

### Requisitos en el servidor

- PHP 8.2+ con las extensiones mencionadas
- Composer
- Node.js (solo para compilar assets, no necesario en producción)
- Servidor web: Apache o Nginx apuntando a `public/`

### Pasos para producción

```bash
# 1. Clonar y entrar al proyecto
git clone <url> && cd nexusPointWeb

# 2. Instalar dependencias sin paquetes de desarrollo
composer install --no-dev --optimize-autoloader

# 3. Configurar el .env de producción
cp .env.example .env
# Editar .env: APP_ENV=production, APP_DEBUG=false, NEXUSPOINT_API_URL=<url-real>

php artisan key:generate

# 4. Crear y migrar la BD local (solo para sesiones)
touch database/database.sqlite
php artisan migrate --force

# 5. Compilar assets
npm install && npm run build

# 6. Optimizar Laravel para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Configuración de Nginx (ejemplo)

```nginx
server {
    listen 80;
    server_name tudominio.com;
    root /var/www/nexusPointWeb/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## Solución de problemas comunes

### "El servidor está iniciando, espera unos segundos"
La API de NexusPoint puede estar en estado de reposo (el plan gratuito de Render hiberna la app). La primera petición puede tardar 30-60 segundos en despertar el servidor. Es normal.

### Error 500 al iniciar sesión
1. Verifica que `NEXUSPOINT_API_URL` esté configurado en `.env`.
2. Verifica que la API esté corriendo: abre `{NEXUSPOINT_API_URL}/docs` en el navegador.
3. Revisa los logs: `php artisan pail` o `storage/logs/laravel.log`.

### "Sesión expirada" al entrar
Ejecuta `php artisan migrate` para asegurarte de que la tabla `sessions` exista en la BD local.

### Los assets no cargan (CSS/JS)
Si ves la página sin estilos:
```bash
npm run build
```
Asegúrate de que `public/build/` exista. En desarrollo usa `npm run dev`.

### Error CSRF token mismatch
Asegúrate de que el formulario o la petición fetch incluya el header `X-CSRF-TOKEN` con el valor de `{{ csrf_token() }}` en el blade.

### La tabla de sesiones no existe
```bash
php artisan session:table
php artisan migrate
```

---

## Notas de desarrollo

- **No usar** el sistema de autenticación nativo de Laravel (`Auth::user()`). La sesión se maneja manualmente con `Session::get('user_data')` y `Session::get('api_token')`.
- **No agregar** modelos Eloquent para entidades del negocio (reservaciones, espacios, usuarios). Esos datos viven en la API.
- Si la API tarda en responder, el timeout en todas las peticiones HTTP está fijado en **60 segundos** (`->timeout(60)`).
- Los logs temporales de debug (`\Log::info(...)`) en `AdminController` pueden eliminarse en producción.