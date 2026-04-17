// ─────────────────────────────────────────────────────
// screens/ReservarScreen.js — Corregido
// Cambios:
//   - Payload alineado con ReservacionCreate del backend
//     (fecha_reserva, hora_inicio, hora_fin, id_espacio)
//   - Estilos calDiaActivo y calDiaActivoTexto añadidos
//   - Validación de fecha futura antes de enviar
//   - Loading state separado para la carga de espacios
// ─────────────────────────────────────────────────────

import React, { useState, useEffect } from 'react';
import {
  View, Text, ScrollView, StyleSheet, TextInput,
  TouchableOpacity, Modal, ActivityIndicator,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { Colors, Radius, Shadows } from '../constants/theme';

import {
  format, addMonths, subMonths,
  startOfMonth, endOfMonth, eachDayOfInterval,
  isSameDay, isBefore, startOfDay,
} from 'date-fns';
import { es } from 'date-fns/locale';

// ── MVC ─────────────────────────────────────────────
import { useUsuario } from '../context/UsuarioContext';
import { espaciosController } from '../controllers/espaciosController';
import { reservacionesController } from '../controllers/reservacionesController';

const categorias = [
  { id_tipo_espacio: 7, nombre: 'Servicio\nPsicológico', icon: 'body-outline' },
  { id_tipo_espacio: 8, nombre: 'Tutoria',               icon: 'school-outline' },
  { id_tipo_espacio: 2, nombre: 'Aulas',                 icon: 'easel-outline' },
  { id_tipo_espacio: 1, nombre: 'Laboratorios',          icon: 'flask-outline' },
  { id_tipo_espacio: 3, nombre: 'Canchas',               icon: 'football-outline' },
  { id_tipo_espacio: 4, nombre: 'Centro de\ncómputo',    icon: 'desktop-outline' },
];

// Franja horaria → { hora_inicio, hora_fin } en formato HH:MM:SS
const FRANJAS = [
  { label: '7:00 - 8:40',   hora_inicio: '07:00:00', hora_fin: '08:40:00' },
  { label: '8:40 - 10:20',  hora_inicio: '08:40:00', hora_fin: '10:20:00' },
  { label: '10:20 - 12:00', hora_inicio: '10:20:00', hora_fin: '12:00:00' },
  { label: '12:00 - 13:40', hora_inicio: '12:00:00', hora_fin: '13:40:00' },
  { label: '14:00 - 15:40', hora_inicio: '14:00:00', hora_fin: '15:40:00' },
  { label: '15:40 - 17:20', hora_inicio: '15:40:00', hora_fin: '17:20:00' },
];

export default function ReservarScreen() {
  const { usuario } = useUsuario();

  // ── Navegación interna ────────────────────────────
  const [categoriaActiva, setCategoriaActiva] = useState(null);
  const [modalVisible,    setModalVisible]    = useState(false);

  // ── Datos ─────────────────────────────────────────
  const [loadingEspacios,  setLoadingEspacios]  = useState(false);
  const [loadingReserva,   setLoadingReserva]   = useState(false);
  const [espaciosFiltrados, setEspaciosFiltrados] = useState([]);
  const [errorGeneral,     setErrorGeneral]     = useState('');
  const [reservaCreada,    setReservaCreada]    = useState(null);

  // ── Formulario ────────────────────────────────────
  const [espacioSel, setEspacioSel] = useState(null);
  const [franjaSel,  setFranjaSel]  = useState(FRANJAS[0]);
  const [motivo,     setMotivo]     = useState('');

  // ── Calendario ────────────────────────────────────
  const [mesActual, setMesActual] = useState(new Date());
  const [fechaSel,  setFechaSel]  = useState(new Date());

  // ── Cargar espacios al cambiar categoría ──────────
  useEffect(() => {
    if (categoriaActiva) {
      setEspaciosFiltrados([]);
      setEspacioSel(null);
      espaciosController.cargarPorTipo(categoriaActiva.id_tipo_espacio, {
        setEspacios: setEspaciosFiltrados,
        setLoading:  setLoadingEspacios,
        setError:    setErrorGeneral,
      });
    }
  }, [categoriaActiva]);

  // ── Helpers calendario ────────────────────────────
  const diasDelMes = () => {
    return eachDayOfInterval({
      start: startOfMonth(mesActual),
      end:   endOfMonth(mesActual),
    });
  };

  const cambiarMes = (dir) => {
    setMesActual(prev => dir === 'next' ? addMonths(prev, 1) : subMonths(prev, 1));
  };

  const seleccionarFecha = (fecha) => {
    // No permitir fechas pasadas
    if (isBefore(startOfDay(fecha), startOfDay(new Date()))) return;
    setFechaSel(fecha);
  };

  // ── Abrir / cerrar modal ──────────────────────────
  const abrirModal = (espacio = null) => {
    setEspacioSel(espacio);
    setReservaCreada(null);
    setErrorGeneral('');
    setMotivo('');
    setFranjaSel(FRANJAS[0]);
    setFechaSel(new Date());
    setMesActual(new Date());
    setModalVisible(true);
  };

  const cerrarModal = () => {
    setModalVisible(false);
    setReservaCreada(null);
  };

  // ── Confirmar reserva ─────────────────────────────
  // Payload exacto que espera ReservacionCreate en FastAPI:
  //   fecha_reserva        : str  (YYYY-MM-DD)
  //   hora_inicio          : str  (HH:MM:SS)
  //   hora_fin             : str  (HH:MM:SS)
  //   capacidad_solicitada : int | None
  //   motivo               : str | None
  //   id_espacio           : int
  const manejarConfirmar = async () => {
    if (!espacioSel) {
      setErrorGeneral('Selecciona un espacio');
      return;
    }

    const payload = {
      fecha_reserva:        format(fechaSel, 'yyyy-MM-dd'),
      hora_inicio:          franjaSel.hora_inicio,
      hora_fin:             franjaSel.hora_fin,
      capacidad_solicitada: espacioSel.capacidad ?? null,
      motivo:               motivo.trim() || null,
      id_espacio:           espacioSel.id_espacio,
    };

    await reservacionesController.crear(usuario.id_usuario, payload, {
      setLoading: setLoadingReserva,
      setError:   setErrorGeneral,
      onSuccess:  (nueva) => setReservaCreada(nueva),
    });
  };

  // ── MODAL ─────────────────────────────────────────
  const renderModal = () => (
    <Modal
      visible={modalVisible}
      transparent
      animationType="slide"
      onRequestClose={cerrarModal}
    >
      <View style={styles.modalOverlay}>
        <View style={styles.modalSheet}>
          <View style={styles.modalHandle} />

          <TouchableOpacity style={styles.btnCerrarModal} onPress={cerrarModal}>
            <Ionicons name="close" size={20} color={Colors.textMuted} />
          </TouchableOpacity>

          {reservaCreada ? (
            /* ── Éxito ── */
            <View style={styles.exitoWrap}>
              <Ionicons name="checkmark-circle" size={80} color={Colors.cyan} />
              <Text style={styles.exitoTitulo}>¡Solicitud Enviada!</Text>
              <Text style={styles.exitoSub}>Tu reserva está pendiente de aprobación.</Text>
              <View style={styles.resumenCard}>
                <InfoFila label="Folio"    valor={reservaCreada.folio_reservacion} />
                <InfoFila label="Espacio"  valor={espacioSel?.nombre_espacio} />
                <InfoFila label="Fecha"    valor={format(fechaSel, 'dd/MM/yyyy')} />
                <InfoFila label="Horario"  valor={franjaSel.label} />
              </View>
              <TouchableOpacity style={styles.btnConfirmar} onPress={cerrarModal}>
                <Text style={styles.btnConfirmarTexto}>Finalizar</Text>
              </TouchableOpacity>
            </View>
          ) : (
            /* ── Formulario ── */
            <ScrollView showsVerticalScrollIndicator={false}>
              <Text style={styles.modalTitulo}>Nueva Reserva</Text>

              {/* Calendario */}
              <View style={styles.calHeader}>
                <TouchableOpacity onPress={() => cambiarMes('prev')}>
                  <Ionicons name="chevron-back" size={24} color={Colors.cyan} />
                </TouchableOpacity>
                <Text style={styles.mesTexto}>
                  {format(mesActual, 'MMMM yyyy', { locale: es }).toUpperCase()}
                </Text>
                <TouchableOpacity onPress={() => cambiarMes('next')}>
                  <Ionicons name="chevron-forward" size={24} color={Colors.cyan} />
                </TouchableOpacity>
              </View>

              <ScrollView
                horizontal
                showsHorizontalScrollIndicator={false}
                style={styles.calScroll}
              >
                {diasDelMes().map((fecha, index) => {
                  const esSel     = isSameDay(fecha, fechaSel);
                  const esPasada  = isBefore(startOfDay(fecha), startOfDay(new Date()));
                  return (
                    <TouchableOpacity
                      key={index}
                      style={[
                        styles.calDia,
                        esSel     && styles.calDiaActivo,
                        esPasada  && styles.calDiaPasada,
                      ]}
                      onPress={() => seleccionarFecha(fecha)}
                      disabled={esPasada}
                    >
                      <Text style={[
                        styles.calDiaNombre,
                        esSel && styles.calDiaNombreActivo,
                      ]}>
                        {format(fecha, 'eee', { locale: es })}
                      </Text>
                      <Text style={[
                        styles.calDiaTexto,
                        esSel    && styles.calDiaActivoTexto,
                        esPasada && styles.calDiaPasadaTexto,
                      ]}>
                        {format(fecha, 'd')}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </ScrollView>

              {/* Paso 1: Espacio */}
              <Text style={styles.inputLabel}>1. Espacio</Text>
              {loadingEspacios ? (
                <ActivityIndicator color={Colors.cyan} style={{ marginVertical: 12 }} />
              ) : espaciosFiltrados.length === 0 ? (
                <Text style={styles.sinDatosTexto}>No hay espacios disponibles para esta categoría.</Text>
              ) : (
                <View style={styles.espaciosGrid}>
                  {espaciosFiltrados.map(esp => {
                    const activo = espacioSel?.id_espacio === esp.id_espacio;
                    return (
                      <TouchableOpacity
                        key={esp.id_espacio}
                        style={[styles.espacioItem, activo && styles.espacioItemActivo]}
                        onPress={() => setEspacioSel(esp)}
                      >
                        <Text style={[
                          styles.espacioItemTexto,
                          activo && styles.espacioItemTextoActivo,
                        ]}>
                          {esp.nombre_espacio}
                        </Text>
                        {esp.capacidad ? (
                          <Text style={[
                            styles.espacioItemCap,
                            activo && { color: 'rgba(255,255,255,0.8)' },
                          ]}>
                            {esp.capacidad} pers.
                          </Text>
                        ) : null}
                      </TouchableOpacity>
                    );
                  })}
                </View>
              )}

              {/* Paso 2: Horario */}
              <Text style={styles.inputLabel}>2. Horario</Text>
              <View style={styles.gridHorarios}>
                {FRANJAS.map(f => {
                  const activo = franjaSel.label === f.label;
                  return (
                    <TouchableOpacity
                      key={f.label}
                      style={[styles.horaItem, activo && styles.horaItemActivo]}
                      onPress={() => setFranjaSel(f)}
                    >
                      <Text style={[styles.horaText, activo && styles.horaTextActivo]}>
                        {f.label}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </View>

              {/* Paso 3: Motivo */}
              <Text style={styles.inputLabel}>3. Motivo (opcional)</Text>
              <TextInput
                style={styles.input}
                placeholder="Ej. Estudio grupal, clase extra…"
                placeholderTextColor={Colors.textMuted}
                value={motivo}
                onChangeText={setMotivo}
                multiline
                numberOfLines={2}
              />

              {errorGeneral ? (
                <View style={styles.errorWrap}>
                  <Ionicons name="alert-circle-outline" size={16} color={Colors.danger} />
                  <Text style={styles.errorText}>{errorGeneral}</Text>
                </View>
              ) : null}

              <TouchableOpacity
                style={[styles.btnConfirmar, loadingReserva && { opacity: 0.7 }]}
                onPress={manejarConfirmar}
                disabled={loadingReserva}
              >
                {loadingReserva
                  ? <ActivityIndicator color="#fff" />
                  : <Text style={styles.btnConfirmarTexto}>Solicitar Reserva</Text>
                }
              </TouchableOpacity>

              <View style={{ height: 30 }} />
            </ScrollView>
          )}
        </View>
      </View>
    </Modal>
  );

  // ── VISTA LISTA POR CATEGORÍA ─────────────────────
  if (categoriaActiva) {
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <TouchableOpacity
            style={styles.btnRegresar}
            onPress={() => { setCategoriaActiva(null); setEspaciosFiltrados([]); }}
          >
            <Ionicons name="arrow-back" size={22} color={Colors.bg} />
          </TouchableOpacity>
          <Text style={styles.titulo}>
            {categoriaActiva.nombre.replace('\n', ' ')}
          </Text>
        </View>

        <ScrollView contentContainerStyle={styles.lista}>
          {loadingEspacios ? (
            <ActivityIndicator size="large" color={Colors.cyan} style={{ marginTop: 40 }} />
          ) : espaciosFiltrados.length === 0 ? (
            <View style={styles.emptyWrap}>
              <Ionicons name="search-outline" size={48} color={Colors.textMuted} />
              <Text style={styles.emptyTexto}>Sin espacios disponibles</Text>
            </View>
          ) : (
            espaciosFiltrados.map(espacio => (
              <TouchableOpacity
                key={espacio.id_espacio}
                style={styles.espacioCard}
                onPress={() => abrirModal(espacio)}
                activeOpacity={0.8}
              >
                <View style={styles.espacioIcono}>
                  <Ionicons name={categoriaActiva.icon} size={24} color={Colors.cyan} />
                </View>
                <View style={styles.espacioInfo}>
                  <Text style={styles.espacioNombre}>{espacio.nombre_espacio}</Text>
                  <Text style={styles.espacioCap}>
                    Capacidad: {espacio.capacidad ?? '—'} personas
                  </Text>
                </View>
                <Ionicons name="chevron-forward" size={20} color={Colors.textMuted} />
              </TouchableOpacity>
            ))
          )}
        </ScrollView>

        {/* FAB para reservar sin preseleccionar espacio */}
        <TouchableOpacity style={styles.btnFlotante} onPress={() => abrirModal()}>
          <Ionicons name="add" size={30} color={Colors.white} />
        </TouchableOpacity>

        {renderModal()}
      </View>
    );
  }

  // ── VISTA GRID PRINCIPAL ──────────────────────────
  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Ionicons name="calendar-outline" size={26} color={Colors.cyan} />
        <Text style={styles.titulo}>Espacios y Servicios</Text>
      </View>

      <ScrollView contentContainerStyle={styles.grid}>
        {categorias.map(cat => (
          <TouchableOpacity
            key={cat.id_tipo_espacio}
            style={styles.catCard}
            onPress={() => setCategoriaActiva(cat)}
            activeOpacity={0.85}
          >
            <LinearGradient
              colors={['#00BCD4', '#004D8C']}
              style={styles.gradiente}
            >
              <Ionicons name={cat.icon} size={40} color="#fff" />
              <Text style={styles.catNombre}>{cat.nombre}</Text>
            </LinearGradient>
          </TouchableOpacity>
        ))}
      </ScrollView>
    </View>
  );
}

// ── Componente auxiliar ───────────────────────────────
function InfoFila({ label, valor }) {
  return (
    <View style={styles.infoFila}>
      <Text style={styles.infoKey}>{label}:</Text>
      <Text style={styles.infoVal}>{valor ?? '—'}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container:  { flex: 1, backgroundColor: '#fff' },
  header:     {
    paddingTop: 60, paddingHorizontal: 20, paddingBottom: 20,
    flexDirection: 'row', alignItems: 'center', gap: 15,
  },
  btnRegresar: {
    width: 40, height: 40, borderRadius: 20,
    backgroundColor: '#F0F0F0',
    alignItems: 'center', justifyContent: 'center',
  },
  titulo: { fontSize: 22, fontWeight: 'bold', color: Colors.bg, flex: 1 },

  // Grid categorías
  grid:    { flexDirection: 'row', flexWrap: 'wrap', padding: 15, justifyContent: 'space-between' },
  catCard: { width: '47%', aspectRatio: 1, borderRadius: 20, marginBottom: 15, ...Shadows.button },
  gradiente: { flex: 1, borderRadius: 20, alignItems: 'center', justifyContent: 'center', padding: 10 },
  catNombre: { color: '#fff', fontWeight: 'bold', textAlign: 'center', marginTop: 10 },

  // Lista espacios
  lista: { padding: 20, paddingBottom: 100 },
  emptyWrap:  { alignItems: 'center', marginTop: 60, gap: 12 },
  emptyTexto: { fontSize: 16, fontWeight: '700', color: Colors.textMuted },
  espacioCard: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: '#fff', padding: 15,
    borderRadius: 15, marginBottom: 10, ...Shadows.card,
  },
  espacioIcono: {
    width: 50, height: 50, borderRadius: 10,
    backgroundColor: 'rgba(0,188,212,0.1)',
    alignItems: 'center', justifyContent: 'center', marginRight: 15,
  },
  espacioInfo:   { flex: 1 },
  espacioNombre: { fontWeight: 'bold', fontSize: 16, color: Colors.bg },
  espacioCap:    { color: Colors.textMuted, fontSize: 12, marginTop: 2 },

  // FAB
  btnFlotante: {
    position: 'absolute', bottom: 30, right: 30,
    width: 60, height: 60, borderRadius: 30,
    backgroundColor: Colors.cyan,
    alignItems: 'center', justifyContent: 'center', ...Shadows.button,
  },

  // Modal
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  modalSheet: {
    backgroundColor: '#fff',
    borderTopLeftRadius: 30, borderTopRightRadius: 30,
    padding: 25, maxHeight: '90%',
  },
  modalHandle: {
    width: 40, height: 5, backgroundColor: '#DDD',
    borderRadius: 10, alignSelf: 'center', marginBottom: 20,
  },
  modalTitulo:    { fontSize: 20, fontWeight: 'bold', marginBottom: 16, color: Colors.bg },
  btnCerrarModal: { position: 'absolute', top: 20, right: 20, zIndex: 10, padding: 4 },

  // Calendario
  calHeader: {
    flexDirection: 'row', justifyContent: 'space-between',
    alignItems: 'center', marginBottom: 12,
  },
  mesTexto:  { fontSize: 15, fontWeight: 'bold', color: Colors.bg },
  calScroll: { marginBottom: 16 },
  calDia: {
    width: 58, height: 72, backgroundColor: '#F5F5F5',
    borderRadius: 14, alignItems: 'center', justifyContent: 'center',
    marginRight: 10,
  },
  calDiaActivo:       { backgroundColor: Colors.cyan },
  calDiaPasada:       { opacity: 0.35 },
  calDiaNombre:       { fontSize: 10, color: '#999', textTransform: 'uppercase', marginBottom: 4 },
  calDiaNombreActivo: { color: '#fff' },
  calDiaTexto:        { fontSize: 18, fontWeight: '700', color: Colors.bg },
  calDiaActivoTexto:  { color: '#fff' },
  calDiaPasadaTexto:  { color: Colors.textMuted },

  // Formulario
  inputLabel: { fontWeight: '700', marginTop: 16, marginBottom: 10, color: Colors.bg, fontSize: 13 },
  sinDatosTexto: { color: Colors.textMuted, fontSize: 13, marginBottom: 8, textAlign: 'center' },

  // Espacios en modal
  espaciosGrid:         { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  espacioItem:          { padding: 12, borderWidth: 1.5, borderColor: '#DDD', borderRadius: 12 },
  espacioItemActivo:    { backgroundColor: Colors.cyan, borderColor: Colors.cyan },
  espacioItemTexto:     { fontSize: 12, fontWeight: '700', color: Colors.bg },
  espacioItemTextoActivo: { color: '#fff' },
  espacioItemCap:       { fontSize: 10, color: Colors.textMuted, marginTop: 2 },

  // Horarios
  gridHorarios:  { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  horaItem:      { padding: 12, backgroundColor: '#F5F5F5', borderRadius: 10, width: '47%' },
  horaItemActivo:{ backgroundColor: Colors.bg },
  horaText:      { textAlign: 'center', fontSize: 13, fontWeight: '700', color: Colors.bg },
  horaTextActivo:{ color: '#fff' },

  // Input motivo
  input: {
    backgroundColor: '#F5F5F5', padding: 14, borderRadius: 12,
    marginTop: 4, color: Colors.bg, fontSize: 14, textAlignVertical: 'top',
  },

  // Error
  errorWrap: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    backgroundColor: 'rgba(244,67,54,0.08)', borderRadius: 10,
    padding: 10, marginTop: 10,
  },
  errorText: { fontSize: 13, color: Colors.danger, fontWeight: '600', flex: 1 },

  // Botón confirmar
  btnConfirmar: {
    backgroundColor: Colors.cyan, padding: 18,
    borderRadius: 15, marginTop: 20, alignItems: 'center', ...Shadows.button,
  },
  btnConfirmarTexto: { color: '#fff', fontWeight: '800', fontSize: 15 },

  // Éxito
  exitoWrap:  { alignItems: 'center', padding: 20, gap: 12 },
  exitoTitulo:{ fontSize: 24, fontWeight: '800', color: Colors.cyan },
  exitoSub:   { fontSize: 14, color: Colors.textSub, textAlign: 'center' },
  resumenCard:{
    backgroundColor: '#F9F9F9', padding: 20,
    borderRadius: 15, width: '100%', marginTop: 8, gap: 8,
  },
  infoFila:   { flexDirection: 'row', justifyContent: 'space-between', gap: 8 },
  infoKey:    { fontSize: 13, fontWeight: '700', color: Colors.textMuted },
  infoVal:    { fontSize: 13, fontWeight: '600', color: Colors.bg, flex: 1, textAlign: 'right' },
});
