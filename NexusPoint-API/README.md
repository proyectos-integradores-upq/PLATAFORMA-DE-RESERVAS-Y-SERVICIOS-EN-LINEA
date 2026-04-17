# NexusPoint API — Capa de Lógica y Negocio

API REST construida con **FastAPI / Python** para el sistema de gestión de espacios institucionales **NexusPoint**.

> ⚠️ Esta capa **concentra toda la lógica de negocio y es el único punto de acceso a la base de datos**.  
> Tanto el panel web (`nexusPointWeb`) como la app móvil consumen esta API mediante peticiones HTTP con JWT.

---

## Tabla de contenidos

1. [¿Qué hace este proyecto?](#qué-hace-este-proyecto)
2. [Cómo funciona la autenticación](#cómo-funciona-la-autenticación)
3. [Requisitos previos](#requisitos-previos)
4. [Instalación](#instalación)
5. [Variables de entorno](#variables-de-entorno)
6. [Estructura del proyecto](#estructura-del-proyecto)
7. [Endpoints disponibles](#endpoints-disponibles)
8. [Módulos de la API](#módulos-de-la-api)
9. [Flujo de una petición](#flujo-de-una-petición)
10. [Despliegue](#despliegue)
11. [Solución de problemas comunes](#solución-de-problemas-comunes)

---

## ¿Qué hace este proyecto?

`NexusPoint API` es el **núcleo del sistema**. Es la única capa que habla directamente con la base de datos PostgreSQL. Se encarga de:

- Autenticar usuarios y emitir tokens JWT
- Gestionar **espacios** (aulas, laboratorios, cubículos, etc.) y su equipamiento
- Gestionar **reservaciones** (crear, aprobar, rechazar, cancelar)
- Enviar **notificaciones** automáticas a los usuarios según el estado de sus solicitudes
- Administrar el catálogo de **usuarios**, roles y carreras
- Exponer documentación interactiva en `/docs` (Swagger UI) y `/redoc`

---

## Cómo funciona la autenticación

La API emite tokens **JWT** (JSON Web Token) firmados. El flujo es:

```
Cliente envía          API verifica           API responde          Cliente incluye
POST /auth/login  →    correo + contraseña →  con JWT firmado  →   token en header
correo+contraseña       con bcrypt              (HS256, 60 min)     Authorization: Bearer {token}
```

1. El cliente hace `POST /auth/login` con correo y contraseña.
2. La API verifica las credenciales con `bcrypt` contra la base de datos.
3. Si son correctas, genera un JWT con `sub = id_usuario` y expiración de 60 minutos.
4. El cliente guarda el token y lo incluye en cada petición posterior.
5. Para verificar la identidad del usuario autenticado, se usa `GET /auth/me?token={jwt}`.

> **Nota:** La verificación del token en endpoints protegidos actualmente se hace por query param (`token`). El header `Authorization: Bearer` está implementado en `get_usuario_actual()` pero no todos los endpoints lo usan aún.

---

## Requisitos previos

| Requisito | Versión mínima | Cómo verificar |
|-----------|---------------|----------------|
| Python | 3.11 | `python --version` |
| pip | cualquiera | `pip --version` |
| PostgreSQL | 14+ | `psql --version` |
| Git | cualquiera | `git -v` |

Paquetes Python principales (ver `requirements.txt` para la lista completa):

| Paquete | Para qué se usa |
|---------|----------------|
| `fastapi` | Framework web |
| `uvicorn` | Servidor ASGI |
| `sqlalchemy` | ORM / conexión a la BD |
| `psycopg2-binary` | Driver PostgreSQL |
| `python-jose` | Generación y verificación de JWT |
| `bcrypt` | Hash de contraseñas |
| `python-dotenv` | Carga de variables de entorno |

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone <url-del-repositorio>
cd nexuspoint-api
```

### 2. Crear y activar el entorno virtual

```bash
python -m venv venv

# Windows
venv\Scripts\activate

# macOS / Linux
source venv/bin/activate
```

### 3. Instalar dependencias

```bash
pip install -r requirements.txt
```

### 4. Copiar el archivo de entorno

```bash
cp .env.example .env
```

### 5. Configurar las variables de entorno

Edita el archivo `.env` con tus datos de PostgreSQL y la clave secreta (ver sección [Variables de entorno](#variables-de-entorno)).

### 6. Asegurarse de que la base de datos exista

```sql
-- Conéctate a PostgreSQL y ejecuta:
CREATE DATABASE nexuspoint;
```

Las tablas **se crean automáticamente** al iniciar la API gracias a `Base.metadata.create_all(bind=engine)` en `main.py`.

### 7. Iniciar el servidor de desarrollo

```bash
uvicorn main:app --reload --port 8001
```

La API estará disponible en: **http://localhost:8001**  
Documentación Swagger: **http://localhost:8001/docs**

---

## Variables de entorno

Configura el archivo `.env` con los siguientes valores:

```env
# ─── BASE DE DATOS (OBLIGATORIO) ──────────────────────────
DATABASE_URL=postgresql://postgres:tu_password@localhost:5432/nexuspoint

# ─── JWT (OBLIGATORIO) ────────────────────────────────────
SECRET_KEY=cambia_esto_por_una_clave_segura_larga
ALGORITHM=HS256
ACCESS_TOKEN_EXPIRE_MINUTES=60
```

> ⚠️ **El `.env` nunca debe subirse al repositorio.** Está incluido en `.gitignore`.

### Variable más importante: `DATABASE_URL`

Esta variable define la conexión a PostgreSQL. Sin ella, **nada funciona**.

| Escenario | Valor |
|-----------|-------|
| BD local | `postgresql://postgres:password@localhost:5432/nexuspoint` |
| BD en Render / Railway | URL proporcionada por el servicio |
| BD en otro servidor | `postgresql://usuario:password@host:5432/nombre_bd` |

### Variable crítica: `SECRET_KEY`

Se usa para firmar todos los tokens JWT. Si cambias este valor en producción, **todos los tokens existentes quedan invalidados**.

---

## Estructura del proyecto

```
nexuspoint-api/
│
├── main.py                        ← Punto de entrada, registra routers y middleware CORS
│
├── app/
│   ├── __init__.py
│   ├── database.py                ← Conexión SQLAlchemy, sesión y Base declarativa
│   ├── models.py                  ← Todos los modelos ORM (tablas de la BD)
│   ├── schemas.py                 ← Esquemas Pydantic (validación de entrada/salida)
│   └── routers/
│       ├── __init__.py
│       ├── auth.py                ← Login, registro, /me y utilidades JWT
│       ├── usuarios.py            ← CRUD de usuarios y catálogos (roles, carreras)
│       ├── espacios.py            ← CRUD de espacios, equipamiento y catálogos
│       ├── reservaciones.py       ← CRUD de reservaciones y gestión (aprobar/rechazar)
│       └── notificaciones.py      ← Lectura y gestión de notificaciones
│
├── .env                           ← Variables de entorno (NO subir a git)
├── .env.example                   ← Plantilla del .env
├── .gitignore
├── requirements.txt               ← Dependencias Python
└── .python-version                ← Versión de Python (3.11.9)
```

### Archivos clave para entender el proyecto

| Archivo | Qué hace |
|---------|----------|
| `main.py` | Crea la app FastAPI, registra los 5 routers con sus prefijos, configura CORS y crea las tablas al arrancar. |
| `app/database.py` | Define el engine de SQLAlchemy, la sesión (`SessionLocal`) y la clase `Base`. La función `get_db()` se inyecta como dependencia en cada endpoint. |
| `app/models.py` | Contiene **todos los modelos ORM**: Rol, Carrera, Usuario, Edificio, Piso, TipoEspacio, EstadoEspacio, Espacio, EspacioEquipamiento, HorarioEspacio, TipoServicio, Servicio, EncargadoEspacio, Reservacion, ReservacionServicio, Gestion, TipoNotificacion, Notificacion. |
| `app/schemas.py` | Esquemas Pydantic para validar entradas (`*Create`, `*Update`) y serializar salidas (`*Out`). |
| `app/routers/auth.py` | Utilidades JWT (`crear_token`, `verificar_password`, `hashear_password`) y endpoints de autenticación. |

---

## Endpoints disponibles

### Auth — `/auth`

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/auth/login` | Inicia sesión, devuelve JWT |
| POST | `/auth/registro` | Registra un nuevo usuario |
| GET | `/auth/me?token={jwt}` | Devuelve datos del usuario autenticado |

### Usuarios — `/usuarios`

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/usuarios/` | Listar todos los usuarios |
| GET | `/usuarios/{id}` | Obtener un usuario |
| POST | `/usuarios/` | Crear usuario |
| PUT | `/usuarios/{id}` | Actualizar usuario |
| DELETE | `/usuarios/{id}` | Eliminar usuario |
| GET | `/usuarios/catalogos/roles` | Listar roles disponibles |
| GET | `/usuarios/catalogos/carreras` | Listar carreras disponibles |

### Espacios — `/espacios`

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/espacios/catalogos/tipos` | Listar tipos de espacio |
| GET | `/espacios/catalogos/edificios` | Listar edificios |
| GET | `/espacios/catalogos/pisos/{id_edificio}` | Listar pisos de un edificio |
| GET | `/espacios/catalogos/equipamiento` | Listar tipos de equipamiento |
| GET | `/espacios/` | Listar todos los espacios |
| GET | `/espacios/tipo/{id_tipo}` | Filtrar por tipo |
| GET | `/espacios/estado/{id_estado}` | Filtrar por estado |
| GET | `/espacios/{id}` | Obtener un espacio |
| POST | `/espacios/` | Crear espacio |
| PUT | `/espacios/{id}` | Actualizar espacio |
| DELETE | `/espacios/{id}` | Eliminar espacio |
| GET | `/espacios/{id}/equipamiento` | Listar equipamiento del espacio |
| POST | `/espacios/{id}/equipamiento` | Agregar equipamiento |
| DELETE | `/espacios/{id}/equipamiento/{id_equip}` | Eliminar equipamiento |
| GET | `/espacios/admin/fix-secuencia` | Corregir secuencia de `espacioequipamiento` |
| GET | `/espacios/admin/fix-secuencia-espacios` | Corregir secuencia de `espacio` |

### Reservaciones — `/reservaciones`

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/reservaciones/` | Listar todas las reservaciones |
| GET | `/reservaciones/usuario/{id_usuario}` | Reservaciones de un usuario |
| GET | `/reservaciones/estado/{id_estado}` | Filtrar por estado |
| GET | `/reservaciones/{id}` | Obtener una reservación |
| POST | `/reservaciones/{id_usuario}` | Crear reservación (genera folio automático) |
| POST | `/reservaciones/gestionar` | Aprobar o rechazar una solicitud |
| PUT | `/reservaciones/{id}/cancelar` | Cancelar una reservación |

### Notificaciones — `/notificaciones`

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/notificaciones/usuario/{id}` | Todas las notificaciones de un usuario |
| GET | `/notificaciones/usuario/{id}/no-leidas` | Solo las no leídas |
| GET | `/notificaciones/usuario/{id}/contador` | Conteo de no leídas |
| PUT | `/notificaciones/{id}/leer` | Marcar una como leída |
| PUT | `/notificaciones/usuario/{id}/leer-todas` | Marcar todas como leídas |
| DELETE | `/notificaciones/{id}` | Eliminar una notificación |

---

## Módulos de la API

### Auth
Maneja el ciclo completo de autenticación. Las contraseñas se almacenan con hash `bcrypt`. Los tokens JWT expiran en 60 minutos (configurable). Incluye utilidades reutilizables: `hashear_password()`, `verificar_password()`, `crear_token()` y `get_usuario_actual()`, importadas por otros routers cuando necesitan autenticación.

### Usuarios
CRUD completo. Al crear un usuario (vía `/usuarios/` o `/auth/registro`), la contraseña se hashea automáticamente antes de persistirse. Incluye endpoints de catálogo para roles y carreras, que son necesarios para poblar formularios en el panel web.

### Espacios
CRUD de espacios con validación de código único. La gestión de equipamiento está anidada bajo el espacio (`/espacios/{id}/equipamiento`). Los endpoints `/admin/fix-secuencia*` corrigen desfases en las secuencias de PostgreSQL (útil tras cargar datos con `INSERT` manual).

### Reservaciones
Módulo más complejo. Al **crear** una reservación:
- Valida que el espacio exista y esté disponible (`id_estado_espacio == 1`).
- Verifica que no haya choque de horario ese día para el mismo espacio.
- Genera un folio único con formato `RES-{año}-{4 dígitos}`.
- Cambia el estado del espacio a "Reservado Temporalmente" (`id_estado_espacio = 2`).
- Crea automáticamente una notificación para el solicitante.

Al **gestionar** (aprobar/rechazar), se registra una `Gestion`, se actualiza el estado de la reservación y se envía notificación automática. Si se rechaza o cancela, el espacio vuelve a estado "Disponible".

### Notificaciones
Las notificaciones se crean automáticamente desde los routers de reservaciones. Este módulo solo expone endpoints de **lectura y actualización** para que el cliente las consuma. Los tipos de notificación son:
- `1` — Aprobación
- `2` — Rechazo
- `3` — Cancelación
- `4` — Sistema (confirmación de solicitud enviada)

---

## Flujo de una petición

Ejemplo: un alumno crea una reservación.

```
1. App móvil hace:
   POST /reservaciones/7
   Body: { fecha_reserva, hora_inicio, hora_fin, id_espacio, motivo }
   Header: Authorization: Bearer {jwt}   (o ?token={jwt})

2. FastAPI ejecuta crear_reservacion() en reservaciones.py

3. El endpoint:
   a. Verifica que el usuario 7 exista
   b. Verifica que el espacio esté disponible (id_estado_espacio == 1)
   c. Verifica que no haya choque de horario
   d. Genera folio único (RES-2026-XXXX)
   e. Crea el registro en la tabla reservacion (estado = Pendiente)
   f. Cambia estado del espacio a Reservado Temporalmente
   g. Crea una notificacion automática para el usuario

4. La API responde 201 Created con el objeto ReservacionOut

5. App móvil muestra el folio y el estado "Pendiente"
```

---

## Despliegue

### Requisitos en el servidor

- Python 3.11+
- PostgreSQL 14+ accesible desde el servidor
- Variables de entorno configuradas (`DATABASE_URL`, `SECRET_KEY`)

### Pasos para producción (servidor Linux)

```bash
# 1. Clonar y entrar al proyecto
git clone <url> && cd nexuspoint-api

# 2. Crear entorno virtual e instalar dependencias
python -m venv venv
source venv/bin/activate
pip install -r requirements.txt

# 3. Configurar el .env de producción
cp .env.example .env
# Editar .env con DATABASE_URL real y SECRET_KEY segura

# 4. Iniciar con uvicorn (en producción, usar gunicorn o un proceso manager)
uvicorn main:app --host 0.0.0.0 --port 8001
```

### Despliegue en Render (recomendado para demo)

1. Crear un nuevo servicio **Web Service** en Render.
2. Conectar el repositorio.
3. Configurar:
   - **Build Command:** `pip install -r requirements.txt`
   - **Start Command:** `uvicorn main:app --host 0.0.0.0 --port $PORT`
4. Agregar las variables de entorno en el panel de Render (`DATABASE_URL`, `SECRET_KEY`, `ALGORITHM`, `ACCESS_TOKEN_EXPIRE_MINUTES`).

> ⚠️ El plan gratuito de Render **hiberna la app** tras 15 minutos de inactividad. La primera petición puede tardar 30-60 segundos en responder.

---

## Solución de problemas comunes

### Error de conexión a la base de datos al iniciar
1. Verifica que `DATABASE_URL` esté correctamente configurada en `.env`.
2. Verifica que PostgreSQL esté corriendo: `pg_isready` o `psql -U postgres`.
3. Verifica que la base de datos `nexuspoint` exista: `psql -U postgres -l`.

### `sqlalchemy.exc.OperationalError` al crear tablas
La base de datos no existe o las credenciales son incorrectas. Verifica el `DATABASE_URL` y crea la base de datos manualmente si es necesario.

### Error 401 al llamar a un endpoint protegido
El token JWT es inválido, expiró o no se está enviando correctamente. Los tokens expiran a los **60 minutos**. Vuelve a hacer `POST /auth/login` para obtener uno nuevo.

### `400 — El espacio ya tiene una reservación en ese horario`
Hay un choque de horario con una reservación en estado Pendiente (1) o Aprobada (2). Elige otro horario o espacio.

### `400 — El espacio no está disponible`
El espacio tiene un `id_estado_espacio` distinto de 1. Puede estar reservado temporalmente o fuera de servicio.

### Las secuencias de PostgreSQL están desfasadas (IDs duplicados)
Ocurre tras insertar datos manualmente con `INSERT`. Usa los endpoints de corrección:
```
GET /espacios/admin/fix-secuencia
GET /espacios/admin/fix-secuencia-espacios
```

### `ImportError` al iniciar el servidor
Asegúrate de tener el entorno virtual activado y todas las dependencias instaladas:
```bash
source venv/bin/activate
pip install -r requirements.txt
```

---

## Notas de desarrollo

- El archivo `main.py` llama a `Base.metadata.create_all(bind=engine)` al arrancar, lo que **crea las tablas automáticamente** si no existen. No se usan migraciones (Alembic) en este proyecto.
- El CORS está configurado con `allow_origins=["*"]` para facilitar el desarrollo. En producción, restringir a los dominios del panel web y la app móvil.
- Los endpoints de catálogo (`/catalogos/*`) no requieren autenticación y son de solo lectura. Son consumidos por los formularios del panel web y la app móvil.
- La lógica de notificaciones está **embebida en los routers de reservaciones**, no en el router de notificaciones. Ese router solo expone lectura y marcado.
- No hay seeds ni fixtures incluidos. Los datos de catálogo (roles, carreras, tipos de espacio, estados, edificios, pisos) deben insertarse manualmente en la BD antes de usar el sistema.