// ─────────────────────────────────────────────────────
// services/notificacionesService.js
// Mapea exactamente a app/routers/notificaciones.py
//
// NotificacionOut:
//   id_notificacion   id_usuario_destino  id_reservacion?
//   id_tipo_notificacion  titulo_notificacion  cuerpo_notificacion?
//   leida (0|1)  fecha_envio?
//
// Tipos de notificación (tiponotificacion):
//   1 = Aprobacion
//   2 = Rechazo
//   3 = Cancelacion
//   4 = Sistema
//   5 = Recordatorio
// ─────────────────────────────────────────────────────

import { api } from './api';

export const notificacionesService = {
  // GET /notificaciones/usuario/{id_usuario}
  // retorna: NotificacionOut[]  ordenadas por fecha_envio DESC
  porUsuario(idUsuario) {
    return api.get(`/notificaciones/usuario/${idUsuario}`);
  },

  // GET /notificaciones/usuario/{id_usuario}/no-leidas
  // retorna: NotificacionOut[]  solo leida=0, fecha DESC
  noLeidas(idUsuario) {
    return api.get(`/notificaciones/usuario/${idUsuario}/no-leidas`);
  },

  // GET /notificaciones/usuario/{id_usuario}/contador
  // retorna: { id_usuario: number, no_leidas: number }
  contador(idUsuario) {
    return api.get(`/notificaciones/usuario/${idUsuario}/contador`);
  },

  // PUT /notificaciones/{id_notificacion}/leer
  // Marca leida=1 en la notificación
  // retorna: NotificacionOut actualizada  (404 si no existe)
  marcarLeida(idNotificacion) {
    return api.put(`/notificaciones/${idNotificacion}/leer`);
  },

  // PUT /notificaciones/usuario/{id_usuario}/leer-todas
  // Bulk update: leida=0 → leida=1 para ese usuario
  // retorna: { mensaje: "Todas las notificaciones marcadas como leídas" }
  marcarTodasLeidas(idUsuario) {
    return api.put(`/notificaciones/usuario/${idUsuario}/leer-todas`);
  },

  // DELETE /notificaciones/{id_notificacion}  → 204
  eliminar(idNotificacion) {
    return api.del(`/notificaciones/${idNotificacion}`);
  },
};
