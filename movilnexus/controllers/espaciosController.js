// ─────────────────────────────────────────────────────
// controllers/espaciosController.js
// Lógica de negocio para la pantalla ReservarScreen.
// ─────────────────────────────────────────────────────

import { espaciosService } from '../services/espaciosService';

export const espaciosController = {
  // ── cargarDisponibles ────────────────────────────
  // ReservarScreen lo llama al montar para poblar el selector de espacios.
  // Solo trae los que tienen id_estado_espacio = 1 (Disponible).
  async cargarDisponibles({ setEspacios, setLoading, setError }) {
    setLoading(true);
    setError('');
    try {
      const lista = await espaciosService.porEstado(1);
      setEspacios(lista);
    } catch (e) {
      setError(e.message || 'No se pudieron cargar los espacios');
    } finally {
      setLoading(false);
    }
  },

  // ── cargarPorTipo ────────────────────────────────
  // Cuando el usuario elige una categoría en la grilla de ReservarScreen.
  // idTipo: 1=Lab 2=Aula 3=Cancha 4=Cómputo 5=Auditorio
  //         6=Sala 7=Psicológico 8=Tutoría 9=Biblioteca
  async cargarPorTipo(idTipo, { setEspacios, setLoading, setError }) {
    setLoading(true);
    setError('');
    try {
      const lista = await espaciosService.porTipo(idTipo);
      setEspacios(lista);
    } catch (e) {
      setError(e.message || 'Error al cargar espacios');
    } finally {
      setLoading(false);
    }
  },

  // ── cargarCatalogos ──────────────────────────────
  // Carga tipos, edificios y (opcionalmente) pisos de un edificio.
  // Se usa si quieres mostrar filtros avanzados en el futuro.
  async cargarCatalogos({ setTipos, setEdificios, setLoading }) {
    setLoading(true);
    try {
      const [tipos, edificios] = await Promise.all([
        espaciosService.listarTipos(),
        espaciosService.listarEdificios(),
      ]);
      setTipos(tipos);
      setEdificios(edificios);
    } catch {
      // Si falla no bloqueamos la pantalla, los filtros simplemente no aparecen
    } finally {
      setLoading(false);
    }
  },
};
