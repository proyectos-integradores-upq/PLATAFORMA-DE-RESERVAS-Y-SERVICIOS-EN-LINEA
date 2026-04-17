// ─────────────────────────────────────────────────────
// services/espaciosService.js
// Mapea exactamente a app/routers/espacios.py
//
// Cada función retorna la forma exacta del schema EspacioOut:
//   id_espacio  codigo_espacio  nombre_espacio  descripcion_espacio?
//   capacidad?  id_tipo_espacio  id_estado_espacio  id_piso
//
// Estados de espacio (estadoespacio):
//   1 = Disponible
//   2 = Reservado Temporalmente
//   3 = No Disponible
// ─────────────────────────────────────────────────────

import { api } from './api';

export const espaciosService = {
  // GET /espacios/
  // retorna: EspacioOut[]
  listarTodos() {
    return api.get('/espacios/');
  },

  // GET /espacios/{id_espacio}
  // retorna: EspacioOut  (404 si no existe)
  obtener(idEspacio) {
    return api.get(`/espacios/${idEspacio}`);
  },

  // GET /espacios/tipo/{id_tipo}
  // retorna: EspacioOut[]
  // id_tipo valores (tipoespacio):
  //   1=Laboratorio 2=Aula 3=Cancha 4=Centro de Cómputo
  //   5=Auditorio 6=Sala 7=Servicio Psicológico 8=Tutoría 9=Biblioteca
  porTipo(idTipo) {
    return api.get(`/espacios/tipo/${idTipo}`);
  },

  // GET /espacios/estado/{id_estado}
  // retorna: EspacioOut[]
  // Filtra por: 1=Disponible  2=Reservado  3=No disponible
  porEstado(idEstado) {
    return api.get(`/espacios/estado/${idEstado}`);
  },

  // ── Catálogos ──────────────────────────────────────

  // GET /espacios/catalogos/tipos
  // retorna: { id_tipo_espacio, nombre_tipo_espacio }[]
  // OJO: el router retorna EspacioOut[] pero la DB tiene TipoEspacio
  // En producción esto devuelve los tipos reales de la BD
  listarTipos() {
    return api.get('/espacios/catalogos/tipos');
  },

  // GET /espacios/catalogos/edificios
  // retorna: EdificioOut[] → { id_edificio, nombre_edificio, clave_edificio? }
  listarEdificios() {
    return api.get('/espacios/catalogos/edificios');
  },

  // GET /espacios/catalogos/pisos/{id_edificio}
  // retorna: PisoOut[] → { id_piso, numero_piso, id_edificio }
  listarPisos(idEdificio) {
    return api.get(`/espacios/catalogos/pisos/${idEdificio}`);
  },

  // ── Mutaciones (solo admin/encargado) ──────────────

  // POST /espacios/
  // body: EspacioCreate
  //   codigo_espacio  nombre_espacio  descripcion_espacio?  capacidad?
  //   id_tipo_espacio  id_estado_espacio  id_piso
  crear(payload) {
    return api.post('/espacios/', payload);
  },

  // PUT /espacios/{id_espacio}
  // body: EspacioUpdate (campos opcionales)
  //   nombre_espacio?  descripcion_espacio?  capacidad?  id_estado_espacio?
  actualizar(idEspacio, payload) {
    return api.put(`/espacios/${idEspacio}`, payload);
  },

  // DELETE /espacios/{id_espacio}  → 204
  eliminar(idEspacio) {
    return api.del(`/espacios/${idEspacio}`);
  },
};
