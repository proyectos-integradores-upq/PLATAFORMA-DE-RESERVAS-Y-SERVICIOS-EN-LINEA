// ─────────────────────────────────────────────────────
// screens/MisReservacionesScreen.js
// CRUD de reservaciones del usuario autenticado:
//   - Listar todas con estado visual
//   - Ver detalle en modal
//   - Cancelar reservas pendientes/aprobadas
//   - Pull-to-refresh
// ─────────────────────────────────────────────────────

import { useState, useEffect, useCallback } from 'react';
import {
  View, Text, ScrollView, StyleSheet,
  TouchableOpacity, Modal, ActivityIndicator,
  RefreshControl, Alert,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Colors, Radius, Shadows } from '../constants/theme';
import { useUsuario } from '../context/UsuarioContext';
import { reservacionesController } from '../controllers/reservacionesController';
import { reservacionesService } from '../services/reservacionesService';

// ── Configuración visual por estado ──────────────────
const ESTADOS = {
  1: { texto: 'Pendiente',   color: Colors.warning, bg: 'rgba(255,152,0,0.12)',   icon: 'time-outline'             },
  2: { texto: 'Aprobada',    color: Colors.success, bg: 'rgba(0,200,83,0.12)',    icon: 'checkmark-circle-outline' },
  3: { texto: 'Rechazada',   color: Colors.danger,  bg: 'rgba(244,67,54,0.12)',   icon: 'close-circle-outline'     },
  4: { texto: 'Cancelada',   color: Colors.danger,  bg: 'rgba(244,67,54,0.08)',   icon: 'ban-outline'              },
  5: { texto: 'Finalizada',  color: Colors.textMuted, bg: 'rgba(107,122,153,0.1)', icon: 'checkmark-done-outline'  },
};

// ── Formatear fecha/hora ──────────────────────────────
const fmtFecha = (iso) => {
  if (!iso) return '—';
  const [y, m, d] = iso.split('T')[0].split('-');
  return `${d}/${m}/${y}`;
};
const fmtHora = (t) => (t ? t.slice(0, 5) : '—');

export default function MisReservacionesScreen({ onRegresar }) {
  const { usuario } = useUsuario();

  // ── Estado ────────────────────────────────────────
  const [lista,         setLista]         = useState([]);
  const [loading,       setLoading]       = useState(false);
  const [refreshing,    setRefreshing]    = useState(false);
  const [error,         setError]         = useState('');
  const [filtroEstado,  setFiltroEstado]  = useState(0);  // 0 = todos

  // Modal de detalle
  const [reservaSel,    setReservaSel]    = useState(null);
  const [modalVisible,  setModalVisible]  = useState(false);

  // Modal confirmación cancelar
  const [modalCancelar, setModalCancelar] = useState(false);
  const [cancelando,    setCancelando]    = useState(false);

  // ── Carga ─────────────────────────────────────────
  useEffect(() => {
    if (usuario) cargar();
  }, [usuario]);

  const cargar = () => {
    reservacionesController.cargarPorUsuario(usuario.id_usuario, {
      setLista,
      setLoading,
      setError,
    });
  };

  const onRefresh = useCallback(() => {
    setRefreshing(true);
    reservacionesController.cargarPorUsuario(usuario.id_usuario, {
      setLista,
      setLoading: setRefreshing,
      setError,
    });
  }, [usuario]);

  // ── Filtrar lista ─────────────────────────────────
  const listaFiltrada = filtroEstado === 0
    ? lista
    : lista.filter(r => r.id_estado_reservacion === filtroEstado);

  // Conteos para badges de filtros
  const conteo = (id) => lista.filter(r => r.id_estado_reservacion === id).length;

  // ── Abrir detalle ─────────────────────────────────
  const verDetalle = (reserva) => {
    setReservaSel(reserva);
    setModalVisible(true);
  };

  // ── Cancelar reserva ──────────────────────────────
  const confirmarCancelar = () => {
    setModalCancelar(true);
  };

  const ejecutarCancelar = async () => {
    if (!reservaSel) return;
    setCancelando(true);
    try {
      await reservacionesService.cancelar(reservaSel.id_reservacion);
      // Actualiza la lista localmente: cambia estado a 4 (Cancelada)
      setLista(prev =>
        prev.map(r =>
          r.id_reservacion === reservaSel.id_reservacion
            ? { ...r, id_estado_reservacion: 4 }
            : r
        )
      );
      setReservaSel(prev => prev ? { ...prev, id_estado_reservacion: 4 } : null);
      setModalCancelar(false);
    } catch (e) {
      Alert.alert('Error', e.message || 'No se pudo cancelar la reserva');
    } finally {
      setCancelando(false);
    }
  };

  // ── RENDER ────────────────────────────────────────
  return (
    <View style={styles.container}>

      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.btnBack} onPress={onRegresar}>
          <Ionicons name="arrow-back" size={22} color={Colors.bg} />
        </TouchableOpacity>
        <Text style={styles.titulo}>Mis Reservaciones</Text>
      </View>

      {/* Filtros */}
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        style={styles.filtrosScroll}
        contentContainerStyle={styles.filtros}
      >
        <FiltroChip
          label="Todas"
          activo={filtroEstado === 0}
          conteo={lista.length}
          onPress={() => setFiltroEstado(0)}
        />
        {[1, 2, 3, 4, 5].map(id => (
          <FiltroChip
            key={id}
            label={ESTADOS[id].texto}
            activo={filtroEstado === id}
            conteo={conteo(id)}
            color={ESTADOS[id].color}
            onPress={() => setFiltroEstado(id)}
          />
        ))}
      </ScrollView>

      {/* Error */}
      {error ? (
        <View style={styles.errorBox}>
          <Ionicons name="alert-circle-outline" size={16} color={Colors.danger} />
          <Text style={styles.errorTexto}>{error}</Text>
        </View>
      ) : null}

      {/* Lista */}
      {loading && lista.length === 0 ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color={Colors.cyan} />
        </View>
      ) : (
        <ScrollView
          contentContainerStyle={styles.lista}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              tintColor={Colors.cyan}
            />
          }
        >
          {listaFiltrada.length === 0 ? (
            <View style={styles.emptyWrap}>
              <Ionicons name="calendar-outline" size={52} color={Colors.textMuted} />
              <Text style={styles.emptyTexto}>Sin reservaciones</Text>
              <Text style={styles.emptySub}>
                {filtroEstado !== 0
                  ? 'No tienes reservaciones con este estado.'
                  : 'Aún no has realizado ninguna reserva.'}
              </Text>
            </View>
          ) : (
            listaFiltrada.map(reserva => {
              const estado = ESTADOS[reserva.id_estado_reservacion] ?? ESTADOS[1];
              return (
                <TouchableOpacity
                  key={reserva.id_reservacion}
                  style={styles.card}
                  onPress={() => verDetalle(reserva)}
                  activeOpacity={0.8}
                >
                  {/* Indicador de estado lateral */}
                  <View style={[styles.cardBar, { backgroundColor: estado.color }]} />

                  <View style={styles.cardBody}>
                    {/* Folio y badge */}
                    <View style={styles.cardTop}>
                      <Text style={styles.cardFolio}>{reserva.folio_reservacion}</Text>
                      <View style={[styles.estadoBadge, { backgroundColor: estado.bg }]}>
                        <Ionicons name={estado.icon} size={11} color={estado.color} />
                        <Text style={[styles.estadoTexto, { color: estado.color }]}>
                          {estado.texto}
                        </Text>
                      </View>
                    </View>

                    {/* Info */}
                    <View style={styles.cardInfo}>
                      <View style={styles.cardInfoItem}>
                        <Ionicons name="calendar-outline" size={13} color={Colors.textMuted} />
                        <Text style={styles.cardInfoTexto}>
                          {fmtFecha(reserva.fecha_reserva)}
                        </Text>
                      </View>
                      <View style={styles.cardInfoItem}>
                        <Ionicons name="time-outline" size={13} color={Colors.textMuted} />
                        <Text style={styles.cardInfoTexto}>
                          {fmtHora(reserva.hora_inicio)} - {fmtHora(reserva.hora_fin)}
                        </Text>
                      </View>
                      <View style={styles.cardInfoItem}>
                        <Ionicons name="location-outline" size={13} color={Colors.textMuted} />
                        <Text style={styles.cardInfoTexto}>
                          {reserva.nombre_espacio}
                        </Text>
                      </View>
                    </View>

                    {reserva.motivo ? (
                      <Text style={styles.cardMotivo} numberOfLines={1}>
                        {reserva.motivo}
                      </Text>
                    ) : null}
                  </View>

                  <Ionicons name="chevron-forward" size={18} color={Colors.textMuted} />
                </TouchableOpacity>
              );
            })
          )}

          <View style={{ height: 20 }} />
        </ScrollView>
      )}

      {/* ── MODAL DETALLE ── */}
      <Modal
        visible={modalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalSheet}>
            <View style={styles.modalHandle} />

            <TouchableOpacity
              style={styles.btnCerrarModal}
              onPress={() => setModalVisible(false)}
            >
              <Ionicons name="close" size={20} color={Colors.textMuted} />
            </TouchableOpacity>

            {reservaSel ? (() => {
              const est = ESTADOS[reservaSel.id_estado_reservacion] ?? ESTADOS[1];
              const puedeCancelar = [1, 2].includes(reservaSel.id_estado_reservacion);
              return (
                <ScrollView showsVerticalScrollIndicator={false}>
                  {/* Icono estado */}
                  <View style={[styles.detEstadoIcono, { backgroundColor: est.bg }]}>
                    <Ionicons name={est.icon} size={40} color={est.color} />
                  </View>

                  <Text style={styles.detFolio}>{reservaSel.folio_reservacion}</Text>
                  <View style={[styles.estadoBadge, { backgroundColor: est.bg, alignSelf: 'center', marginBottom: 20 }]}>
                    <Text style={[styles.estadoTexto, { color: est.color }]}>{est.texto}</Text>
                  </View>

                  {/* Detalles */}
                  <View style={styles.detCard}>
                    <DetalleRow icon="location-outline"  label="Espacio"   valor={reservaSel.nombre_espacio} />
                    <DetalleRow icon="calendar-outline"  label="Fecha"     valor={fmtFecha(reservaSel.fecha_reserva)} />
                    <DetalleRow icon="time-outline"      label="Horario"   valor={`${fmtHora(reservaSel.hora_inicio)} - ${fmtHora(reservaSel.hora_fin)}`} />
                    <DetalleRow icon="people-outline"    label="Capacidad" valor={reservaSel.capacidad_solicitada ? `${reservaSel.capacidad_solicitada} pers.` : '—'} />
                    {reservaSel.motivo ? (
                      <DetalleRow icon="chatbubble-outline" label="Motivo" valor={reservaSel.motivo} />
                    ) : null}
                    <DetalleRow
                      icon="receipt-outline"
                      label="Solicitud"
                      valor={reservaSel.fecha_solicitud ? fmtFecha(reservaSel.fecha_solicitud) : '—'}
                    />
                  </View>

                  {/* Cancelar */}
                  {puedeCancelar && (
                    <TouchableOpacity
                      style={styles.btnCancelar}
                      onPress={confirmarCancelar}
                    >
                      <Ionicons name="close-circle-outline" size={18} color={Colors.danger} />
                      <Text style={styles.btnCancelarTexto}>Cancelar Reserva</Text>
                    </TouchableOpacity>
                  )}

                  <View style={{ height: 20 }} />
                </ScrollView>
              );
            })() : null}
          </View>
        </View>
      </Modal>

      {/* ── MODAL CONFIRMAR CANCELACIÓN ── */}
      <Modal
        visible={modalCancelar}
        transparent
        animationType="fade"
        onRequestClose={() => setModalCancelar(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalAlerta}>
            <View style={styles.alertaIcono}>
              <Ionicons name="warning-outline" size={48} color={Colors.warning} />
            </View>
            <Text style={styles.alertaTitulo}>¿Cancelar reserva?</Text>
            <Text style={styles.alertaSub}>
              Esta acción no se puede deshacer y liberará el espacio para otros usuarios.
            </Text>
            <View style={styles.alertaBotones}>
              <TouchableOpacity
                style={styles.btnSecundario}
                onPress={() => setModalCancelar(false)}
                disabled={cancelando}
              >
                <Text style={styles.btnSecundarioTexto}>No, mantener</Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[styles.btnDanger, cancelando && { opacity: 0.7 }]}
                onPress={ejecutarCancelar}
                disabled={cancelando}
              >
                {cancelando
                  ? <ActivityIndicator color="#fff" />
                  : <Text style={styles.btnDangerTexto}>Sí, cancelar</Text>
                }
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
}

// ── Sub-componentes ───────────────────────────────────

function FiltroChip({ label, activo, conteo, color, onPress }) {
  return (
    <TouchableOpacity
      style={[
        styles.chip,
        activo && { backgroundColor: color ?? Colors.cyan, borderColor: color ?? Colors.cyan },
      ]}
      onPress={onPress}
    >
      <Text style={[styles.chipTexto, activo && styles.chipTextoActivo]}>
        {label}
      </Text>
      {conteo > 0 && (
        <View style={[styles.chipBadge, activo && { backgroundColor: 'rgba(255,255,255,0.3)' }]}>
          <Text style={[styles.chipBadgeTexto, activo && { color: '#fff' }]}>
            {conteo}
          </Text>
        </View>
      )}
    </TouchableOpacity>
  );
}

function DetalleRow({ icon, label, valor }) {
  return (
    <View style={styles.detRow}>
      <View style={styles.detRowIcon}>
        <Ionicons name={icon} size={16} color={Colors.cyan} />
      </View>
      <Text style={styles.detRowLabel}>{label}</Text>
      <Text style={styles.detRowValor}>{valor}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container:  { flex: 1, backgroundColor: Colors.white },
  center:     { flex: 1, alignItems: 'center', justifyContent: 'center' },

  // Header
  header: {
    flexDirection: 'row', alignItems: 'center', gap: 14,
    paddingTop: 58, paddingHorizontal: 20, paddingBottom: 16,
  },
  btnBack: {
    width: 40, height: 40, borderRadius: 20,
    backgroundColor: '#F0F0F0',
    alignItems: 'center', justifyContent: 'center',
  },
  titulo: { fontSize: 22, fontWeight: '800', color: Colors.bg },

  // Filtros
  filtrosScroll:      { maxHeight: 52 },
  filtros:            { paddingHorizontal: 16, gap: 8, alignItems: 'center' },
  chip:               {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    paddingVertical: 6, paddingHorizontal: 14,
    borderRadius: 20, borderWidth: 1.5,
    borderColor: '#E0E0E0', backgroundColor: '#F5F5F5',
  },
  chipTexto:          { fontSize: 13, fontWeight: '700', color: Colors.textMuted },
  chipTextoActivo:    { color: '#fff' },
  chipBadge:          {
    backgroundColor: Colors.textMuted,
    borderRadius: 10, minWidth: 20, height: 20,
    alignItems: 'center', justifyContent: 'center', paddingHorizontal: 4,
  },
  chipBadgeTexto: { fontSize: 11, fontWeight: '800', color: '#fff' },

  // Error
  errorBox: {
    flexDirection: 'row', alignItems: 'center', gap: 6,
    backgroundColor: 'rgba(244,67,54,0.08)', borderRadius: 10,
    padding: 10, marginHorizontal: 16, marginTop: 8,
  },
  errorTexto: { fontSize: 13, color: Colors.danger, fontWeight: '600', flex: 1 },

  // Lista
  lista: { padding: 16, paddingTop: 12 },
  emptyWrap: { alignItems: 'center', marginTop: 60, gap: 10 },
  emptyTexto: { fontSize: 18, fontWeight: '800', color: Colors.textMuted },
  emptySub:   { fontSize: 14, color: Colors.textMuted, textAlign: 'center', paddingHorizontal: 20 },

  // Card reserva
  card: {
    flexDirection: 'row', alignItems: 'center',
    backgroundColor: Colors.white, borderRadius: Radius.card,
    marginBottom: 12, overflow: 'hidden', ...Shadows.card,
  },
  cardBar:  { width: 5, alignSelf: 'stretch' },
  cardBody: { flex: 1, padding: 14, gap: 6 },
  cardTop:  { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
  cardFolio:{ fontSize: 14, fontWeight: '800', color: Colors.bg },
  estadoBadge: {
    flexDirection: 'row', alignItems: 'center', gap: 4,
    borderRadius: 20, paddingVertical: 3, paddingHorizontal: 10,
  },
  estadoTexto: { fontSize: 11, fontWeight: '800' },

  cardInfo:     { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 2 },
  cardInfoItem: { flexDirection: 'row', alignItems: 'center', gap: 4 },
  cardInfoTexto:{ fontSize: 12, color: Colors.textSub, fontWeight: '600' },
  cardMotivo:   { fontSize: 12, color: Colors.textMuted, fontStyle: 'italic', marginTop: 2 },

  // Modal base
  modalOverlay: {
    flex: 1, backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'flex-end',
  },
  modalSheet: {
    backgroundColor: '#fff',
    borderTopLeftRadius: 28, borderTopRightRadius: 28,
    padding: 24, maxHeight: '85%',
  },
  modalHandle: {
    width: 40, height: 5, backgroundColor: '#DDD',
    borderRadius: 10, alignSelf: 'center', marginBottom: 20,
  },
  btnCerrarModal: { position: 'absolute', top: 20, right: 20, zIndex: 10, padding: 4 },

  // Detalle
  detEstadoIcono: {
    width: 80, height: 80, borderRadius: 40,
    alignItems: 'center', justifyContent: 'center',
    alignSelf: 'center', marginBottom: 12,
  },
  detFolio: {
    fontSize: 20, fontWeight: '800', color: Colors.bg,
    textAlign: 'center', marginBottom: 6,
  },
  detCard: {
    backgroundColor: '#F9F9F9', borderRadius: 16,
    padding: 16, gap: 12,
  },
  detRow: {
    flexDirection: 'row', alignItems: 'center', gap: 10,
  },
  detRowIcon: {
    width: 28, height: 28, borderRadius: 8,
    backgroundColor: 'rgba(0,188,212,0.1)',
    alignItems: 'center', justifyContent: 'center',
  },
  detRowLabel: { fontSize: 13, fontWeight: '700', color: Colors.textMuted, width: 80 },
  detRowValor: { fontSize: 13, fontWeight: '600', color: Colors.bg, flex: 1 },

  btnCancelar: {
    flexDirection: 'row', alignItems: 'center', justifyContent: 'center',
    gap: 8, marginTop: 16, padding: 16,
    borderRadius: 14, borderWidth: 1.5, borderColor: Colors.danger,
    backgroundColor: 'rgba(244,67,54,0.06)',
  },
  btnCancelarTexto: { fontSize: 14, fontWeight: '800', color: Colors.danger },

  // Modal alerta cancelación
  modalAlerta: {
    backgroundColor: '#fff', borderRadius: 24,
    padding: 24, margin: 24,
    alignItems: 'center', gap: 12, ...Shadows.card,
  },
  alertaIcono: {
    width: 80, height: 80, borderRadius: 40,
    backgroundColor: 'rgba(255,152,0,0.1)',
    alignItems: 'center', justifyContent: 'center', marginBottom: 4,
  },
  alertaTitulo: { fontSize: 20, fontWeight: '800', color: Colors.bg, textAlign: 'center' },
  alertaSub:    { fontSize: 14, color: Colors.textSub, textAlign: 'center', lineHeight: 20 },
  alertaBotones:{ flexDirection: 'row', gap: 10, marginTop: 8, width: '100%' },
  btnSecundario: {
    flex: 1, backgroundColor: '#F5F5F5',
    borderRadius: 12, padding: 14, alignItems: 'center',
  },
  btnSecundarioTexto: { fontSize: 14, fontWeight: '700', color: Colors.textMuted },
  btnDanger: {
    flex: 1, backgroundColor: Colors.danger,
    borderRadius: 12, padding: 14, alignItems: 'center', ...Shadows.card,
  },
  btnDangerTexto: { fontSize: 14, fontWeight: '800', color: '#fff' },
});