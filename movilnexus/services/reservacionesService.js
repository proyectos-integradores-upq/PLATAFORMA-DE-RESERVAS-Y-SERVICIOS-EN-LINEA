// ─────────────────────────────────────────────────────
// services/reservacionesService.js — Corregido
// El método crear ahora envía el payload exacto que
// espera ReservacionCreate en FastAPI:
//   fecha_reserva        : str  (YYYY-MM-DD)
//   hora_inicio          : str  (HH:MM:SS)
//   hora_fin             : str  (HH:MM:SS)
//   capacidad_solicitada : int | None
//   motivo               : str | None
//   id_espacio           : int
// ─────────────────────────────────────────────────────

import { api } from './api';

export const reservacionesService = {
  // GET /reservaciones/
  listarTodas() {
    return api.get('/reservaciones/');
  },

  // GET /reservaciones/{id_reservacion}
  obtener(idReservacion) {
    return api.get(`/reservaciones/${idReservacion}`);
  },

  // GET /reservaciones/usuario/{id_usuario}
  porUsuario(idUsuario) {
    return api.get(`/reservaciones/usuario/${idUsuario}`);
  },

  // GET /reservaciones/estado/{id_estado}
  porEstado(idEstado) {
    return api.get(`/reservaciones/estado/${idEstado}`);
  },

  // POST /reservaciones/{id_usuario}
  // payload debe ser un objeto ReservacionCreate:
  //   { fecha_reserva, hora_inicio, hora_fin,
  //     capacidad_solicitada?, motivo?, id_espacio }
  crear(idUsuario, payload) {
    return api.post(`/reservaciones/${idUsuario}`, payload);
  },

  // PUT /reservaciones/{id_reservacion}/cancelar
  cancelar(idReservacion) {
    return api.put(`/reservaciones/${idReservacion}/cancelar`);
  },

  // POST /reservaciones/gestionar (encargado/admin)
  gestionar(payload) {
    return api.post('/reservaciones/gestionar', payload);
  },
};
