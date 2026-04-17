# NexusPoint

Sistema de gestión y reserva de espacios institucionales (aulas, laboratorios, cubículos, etc.).

## Arquitectura del sistema

NexusPoint está compuesto por **3 capas completamente desacopladas** que se comunican entre sí a través de HTTP:

```
┌─────────────────────┐        HTTP/JSON        ┌─────────────────────┐        SQL        ┌─────────────────────┐
│                     │  ─────────────────────► │                     │  ──────────────►  │                     │
│   nexusPointWeb     │                         │   NexusPoint API    │                   │   Base de Datos     │
│   (Laravel / PHP)   │ ◄─────────────────────  │   (FastAPI / Python)│ ◄──────────────   │   (PostgreSQL)      │
│                     │        JSON             │                     │        ORM        │                     │
└─────────────────────┘                         └─────────────────────┘                   └─────────────────────┘
       Capa Web                                       Capa API                              Capa de Datos
  Panel administrativo                           Lógica de negocio                          Persistencia
```

> **Este repositorio contiene únicamente la Capa Web (`nexusPointWeb`).**  
> La API y la base de datos son proyectos independientes con sus propios repositorios.

---

## Capas del sistema

| Capa | Tecnología | Responsabilidad |
|------|-----------|-----------------|
| **Web** (este repo) | Laravel 12 / PHP 8.2 | Panel de administración, sesiones, vistas |
| **API** | FastAPI / Python | Lógica de negocio, autenticación JWT, validaciones |
| **Base de Datos** | PostgreSQL | Persistencia de datos, relaciones |

La capa web **nunca accede directamente a la base de datos**. Todo pasa por la API REST.

---

## Repositorios

- **Web (este):** `nexusPointWeb/` — Panel administrativo Laravel
- **API:** repositorio independiente — FastAPI desplegada en Render
- **BD:** gestionada directamente desde la API mediante ORM (SQLAlchemy)

---

## Documentación por capa

- 📄 [`nexusPointWeb/README.md`](nexusPointWeb/README.md) — Guía completa de instalación y uso del panel web