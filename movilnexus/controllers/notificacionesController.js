// ─────────────────────────────────────────────────────
// controllers/notificacionesController.js
// Lógica de negocio para NotificacionesScreen.
// ─────────────────────────────────────────────────────

import { notificacionesService } from '../services/notificacionesService';

export const notificacionesController = {
  // ── cargar ───────────────────────────────────────
  // NotificacionesScreen lo llama al montar.
  // Trae TODAS las notificaciones del usuario ordenadas por fecha DESC.
  async cargar(idUsuario, { setLista, setContador, setLoading, setError }) {
    setLoading(true);
    setError('');
    try {
      // Lanzamos ambas peticiones en paralelo para no esperar una tras otra
      const [lista, resContador] = await Promise.all([
        notificacionesService.porUsuario(idUsuario),
        notificacionesService.contador(idUsuario),
      ]);
      setLista(lista);
      setContador(resContador.no_leidas); // { id_usuario, no_leidas }
    } catch (e) {
      setError(e.message || 'No se pudieron cargar las notificaciones');
    } finally {
      setLoading(false);
    }
  },

  // ── marcarLeida ──────────────────────────────────
  // Se llama cuando el usuario toca una notificación no leída
  // o presiona "Confirmar"/"Cancelar" en una de tipo Aprobacion (1).
  // Actualiza la lista localmente sin recargar toda la pantalla.
  async marcarLeida(idNotificacion, idUsuario, { setLista, setContador }) {
    try {
      await notificacionesService.marcarLeida(idNotificacion);
      // Actualiza el estado local: pone leida=1 en esa notificación
      setLista(prev =>
        prev.map(n =>
          n.id_notificacion === idNotificacion ? { ...n, leida: 1 } : n
        )
      );
      // Recarga el contador real desde el servidor
      const res = await notificacionesService.contador(idUsuario);
      setContador(res.no_leidas);
    } catch {
      // Fallo silencioso: la UI ya marcó como leída, consistencia eventual
    }
  },

  // ── marcarTodasLeidas ─────────────────────────────
  // Botón "Marcar todas como leídas" en el header de NotificacionesScreen.
  async marcarTodasLeidas(idUsuario, { setLista, setContador, setLoading }) {
    setLoading(true);
    try {
      await notificacionesService.marcarTodasLeidas(idUsuario);
      setLista(prev => prev.map(n => ({ ...n, leida: 1 })));
      setContador(0);
    } catch {
      // Fallo silencioso
    } finally {
      setLoading(false);
    }
  },

  // ── eliminar ─────────────────────────────────────
  // Swipe-to-delete o botón papelera.
  async eliminar(idNotificacion, idUsuario, { setLista, setContador }) {
    try {
      await notificacionesService.eliminar(idNotificacion);
      // Quita la notificación de la lista local
      setLista(prev => prev.filter(n => n.id_notificacion !== idNotificacion));
      // Recarga el contador por si era una no leída
      const res = await notificacionesService.contador(idUsuario);
      setContador(res.no_leidas);
    } catch {
      // Fallo silencioso
    }
  },
};
