

import { reservacionesService } from '../services/reservacionesService';
import { espaciosService }      from '../services/espaciosService';

export const reservacionesController = {

  
  async cargarPorUsuario(idUsuario, { setLista, setLoading, setError }) {
    setLoading(true);
    setError('');
    try {
      // Traemos reservaciones y todos los espacios en paralelo
      const [lista, espacios] = await Promise.all([
        reservacionesService.porUsuario(idUsuario),
        espaciosService.listarTodos(),
      ]);

      // Construimos un mapa id → nombre para O(1) lookup
      const mapaEspacios = {};
      espacios.forEach(e => {
        mapaEspacios[e.id_espacio] = e.nombre_espacio;
      });

      // Inyectamos nombre_espacio en cada reservación
      const enriquecida = lista.map(r => ({
        ...r,
        nombre_espacio: mapaEspacios[r.id_espacio] ?? `Espacio #${r.id_espacio}`,
      }));

      // Ordenar: más recientes primero
      const ordenada = [...enriquecida].sort(
        (a, b) => new Date(b.fecha_solicitud ?? 0) - new Date(a.fecha_solicitud ?? 0)
      );

      setLista(ordenada);
    } catch (e) {
      setError(e.message || 'Error al cargar reservaciones');
    } finally {
      setLoading(false);
    }
  },

  // Crea una nueva reservación.
  async crear(idUsuario, payload, { setLoading, setError, onSuccess }) {
    setLoading(true);
    setError('');
    try {
      const nueva = await reservacionesService.crear(idUsuario, payload);
      onSuccess(nueva);
    } catch (e) {
      setError(e.message || 'Error al crear la reservación');
    } finally {
      setLoading(false);
    }
  },
};