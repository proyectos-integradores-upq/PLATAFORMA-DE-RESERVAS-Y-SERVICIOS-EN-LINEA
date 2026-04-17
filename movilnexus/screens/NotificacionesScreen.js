// ─────────────────────────────────────────────────────
// screens/NotificacionesScreen.js — Vista con MVC
// ─────────────────────────────────────────────────────

import { useState, useEffect, useCallback } from 'react';
import {
  View, Text, ScrollView, StyleSheet,
  TouchableOpacity, Modal, ActivityIndicator, RefreshControl,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { Colors, Radius, Shadows } from '../constants/theme';

// ── MVC ─────────────────────────────────────────────
import { useUsuario } from '../context/UsuarioContext';
import { notificacionesController } from '../controllers/notificacionesController';

// ── Config visual por tipo de notificación ──────────
const tipoConfig = {
  1: { icono: 'checkmark-circle-outline', color: Colors.success, bg: 'rgba(0,200,83,0.1)'    }, // Aprobacion
  2: { icono: 'close-circle-outline',     color: Colors.danger,  bg: 'rgba(244,67,54,0.1)'   }, // Rechazo
  3: { icono: 'ban-outline',              color: Colors.warning, bg: 'rgba(255,152,0,0.1)'   }, // Cancelacion
  4: { icono: 'notifications-outline',   color: Colors.cyan,    bg: 'rgba(0,188,212,0.1)'   }, // Sistema
  5: { icono: 'alarm-outline',            color: '#1565C0',      bg: 'rgba(21,101,192,0.12)' }, // Recordatorio
};

export default function NotificacionesScreen() {
  const { usuario } = useUsuario();

  // ── Estado de UI ──────────────────────────────────
  const [lista,           setLista]           = useState([]);
  const [contador,        setContador]        = useState(0);
  const [loading,         setLoading]         = useState(false);
  const [refreshing,      setRefreshing]      = useState(false);
  const [error,           setError]           = useState('');
  const [modalVisible,    setModalVisible]    = useState(false);
  const [accionModal,     setAccionModal]     = useState(null); // 'confirmar' | 'cancelar'
  const [notifActual,     setNotifActual]     = useState(null);

  // ── Carga inicial ─────────────────────────────────
  useEffect(() => {
    if (usuario) cargar();
  }, [usuario]);

  const cargar = () => {
    notificacionesController.cargar(usuario.id_usuario, {
      setLista,
      setContador,
      setLoading,
      setError,
    });
  };

  // Pull-to-refresh
  const onRefresh = useCallback(() => {
    setRefreshing(true);
    notificacionesController.cargar(usuario.id_usuario, {
      setLista,
      setContador,
      setLoading: setRefreshing,
      setError,
    });
  }, [usuario]);

  // ── Acción en notificación de aprobación ──────────
  const ejecutarAccion = async (notif, accion) => {
    setNotifActual(notif);
    setAccionModal(accion);
    // Marca como leída en el servidor y actualiza lista local
    await notificacionesController.marcarLeida(
      notif.id_notificacion,
      usuario.id_usuario,
      { setLista, setContador }
    );
    setModalVisible(true);
  };

  const cerrarModal = () => {
    setModalVisible(false);
    setAccionModal(null);
    setNotifActual(null);
  };

  // ── Marcar todas como leídas ──────────────────────
  const marcarTodas = () => {
    notificacionesController.marcarTodasLeidas(usuario.id_usuario, {
      setLista,
      setContador,
      setLoading,
    });
  };

  return (
    <View style={styles.container}>

      {/* Header */}
      <View style={styles.header}>
        <Ionicons name="notifications-outline" size={26} color={Colors.cyan} />
        <Text style={styles.titulo}>Notificaciones</Text>
        {contador > 0 && (
          <View style={styles.badge}>
            <Text style={styles.badgeTexto}>{contador}</Text>
          </View>
        )}
        {contador > 0 && (
          <TouchableOpacity onPress={marcarTodas} style={styles.btnTodasLeidas}>
            <Text style={styles.btnTodasLeidasTexto}>Leer todas</Text>
          </TouchableOpacity>
        )}
      </View>

      {/* Error */}
      {error ? (
        <View style={styles.errorBox}>
          <Ionicons name="alert-circle-outline" size={16} color={Colors.danger} />
          <Text style={styles.errorTexto}>{error}</Text>
        </View>
      ) : null}

      {/* Loading inicial */}
      {loading && lista.length === 0 ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color={Colors.cyan} />
        </View>
      ) : (

        <ScrollView
          contentContainerStyle={styles.lista}
          showsVerticalScrollIndicator={false}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} tintColor={Colors.cyan} />}
        >
          {lista.length === 0 && !loading ? (
            <View style={styles.empty}>
              <Ionicons name="notifications-off-outline" size={48} color={Colors.textMuted} />
              <Text style={styles.emptyTexto}>Sin notificaciones</Text>
            </View>
          ) : (
            lista.map(notif => {
              const config = tipoConfig[notif.id_tipo_notificacion] || tipoConfig[4];
              const leida  = notif.leida === 1;
              return (
                <TouchableOpacity
                  key={notif.id_notificacion}
                  style={[styles.notifCard, leida && styles.notifCardLeida]}
                  activeOpacity={0.8}
                  onPress={() => {
                    if (!leida) {
                      notificacionesController.marcarLeida(
                        notif.id_notificacion,
                        usuario.id_usuario,
                        { setLista, setContador }
                      );
                    }
                  }}
                >
                  {!leida && <View style={styles.puntito} />}

                  <View style={[styles.iconoWrap, { backgroundColor: config.bg }]}>
                    <Ionicons name={config.icono} size={24} color={config.color} />
                  </View>

                  <View style={styles.notifInfo}>
                    <Text style={[styles.notifTitulo, leida && styles.notifTituloLeida]}>
                      {notif.titulo_notificacion}
                    </Text>
                    {notif.cuerpo_notificacion ? (
                      <Text style={styles.notifCuerpo}>{notif.cuerpo_notificacion}</Text>
                    ) : null}

                    {/* Botones solo para Aprobacion (tipo 1) no leída */}
                    {notif.id_tipo_notificacion === 1 && !leida && (
                      <View style={styles.accionesRow}>
                        <TouchableOpacity style={styles.btnConfirmar} onPress={() => ejecutarAccion(notif, 'confirmar')}>
                          <Text style={styles.btnConfirmarTexto}>Confirmar</Text>
                        </TouchableOpacity>
                        <TouchableOpacity style={styles.btnCancelarAccion} onPress={() => ejecutarAccion(notif, 'cancelar')}>
                          <Text style={styles.btnCancelarAccionTexto}>Cancelar</Text>
                        </TouchableOpacity>
                      </View>
                    )}
                  </View>
                </TouchableOpacity>
              );
            })
          )}
        </ScrollView>
      )}

      {/* Modal resultado acción */}
      <Modal visible={modalVisible} transparent animationType="fade" onRequestClose={cerrarModal}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalBox}>
            <View style={[styles.modalIconoWrap, {
              backgroundColor: accionModal === 'confirmar' ? 'rgba(0,200,83,0.1)' : 'rgba(244,67,54,0.1)',
            }]}>
              <Ionicons
                name={accionModal === 'confirmar' ? 'checkmark-circle' : 'close-circle'}
                size={64}
                color={accionModal === 'confirmar' ? Colors.success : Colors.danger}
              />
            </View>
            <Text style={styles.modalTitulo}>
              {accionModal === 'confirmar' ? '¡Reserva Confirmada!' : 'Reserva Cancelada'}
            </Text>
            <Text style={styles.modalSub}>
              {accionModal === 'confirmar'
                ? 'La reserva ha sido aprobada exitosamente.'
                : 'La reserva ha sido rechazada.'}
            </Text>
            <View style={styles.modalDetalle}>
              <Text style={styles.modalDetalleTexto}>{notifActual?.titulo_notificacion}</Text>
            </View>
            <TouchableOpacity style={styles.btnPrimario} onPress={cerrarModal}>
              <Text style={styles.btnPrimarioTexto}>Entendido</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container:  { flex: 1, backgroundColor: Colors.white },
  header:     { flexDirection: 'row', alignItems: 'center', gap: 10, paddingTop: 56, paddingBottom: 20, paddingHorizontal: 20 },
  titulo:     { fontSize: 22, fontWeight: '800', color: Colors.bg, flex: 1 },
  badge:      { backgroundColor: Colors.danger, borderRadius: 12, minWidth: 24, height: 24, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 6 },
  badgeTexto: { fontSize: 12, fontWeight: '800', color: Colors.white },
  btnTodasLeidas:      { backgroundColor: 'rgba(0,188,212,0.1)', borderRadius: 8, paddingVertical: 4, paddingHorizontal: 10 },
  btnTodasLeidasTexto: { fontSize: 12, fontWeight: '700', color: Colors.cyan },
  errorBox:   { flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: 'rgba(244,67,54,0.08)', borderRadius: 10, padding: 10, marginHorizontal: 16 },
  errorTexto: { fontSize: 13, color: Colors.danger, fontWeight: '600', flex: 1 },
  center:     { flex: 1, alignItems: 'center', justifyContent: 'center' },
  lista:      { padding: 16, paddingTop: 4, gap: 10 },
  empty:      { alignItems: 'center', marginTop: 60, gap: 12 },
  emptyTexto: { fontSize: 16, fontWeight: '700', color: Colors.textMuted },
  notifCard:       { backgroundColor: Colors.white, borderRadius: Radius.card, padding: 14, flexDirection: 'row', alignItems: 'flex-start', gap: 12, position: 'relative', ...Shadows.card },
  notifCardLeida:  { backgroundColor: '#FAFAFA', opacity: 0.8 },
  puntito:         { position: 'absolute', top: 14, right: 14, width: 8, height: 8, borderRadius: 4, backgroundColor: Colors.cyan },
  iconoWrap:       { width: 48, height: 48, borderRadius: 14, alignItems: 'center', justifyContent: 'center', flexShrink: 0 },
  notifInfo:       { flex: 1 },
  notifTitulo:     { fontSize: 14, fontWeight: '800', color: Colors.bg, marginBottom: 2 },
  notifTituloLeida:{ fontWeight: '600', color: Colors.textMuted },
  notifCuerpo:     { fontSize: 13, color: Colors.textSub, marginTop: 2 },
  accionesRow:     { flexDirection: 'row', gap: 8, marginTop: 10 },
  btnConfirmar:    { backgroundColor: Colors.cyan, borderRadius: 8, paddingVertical: 6, paddingHorizontal: 16 },
  btnConfirmarTexto: { fontSize: 12, fontWeight: '800', color: Colors.white },
  btnCancelarAccion: { backgroundColor: Colors.danger, borderRadius: 8, paddingVertical: 6, paddingHorizontal: 16 },
  btnCancelarAccionTexto: { fontSize: 12, fontWeight: '800', color: Colors.white },
  modalOverlay:   { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', alignItems: 'center', justifyContent: 'center', padding: 24 },
  modalBox:       { backgroundColor: Colors.white, borderRadius: 24, padding: 24, width: '100%', alignItems: 'center', gap: 12, ...Shadows.card },
  modalIconoWrap: { width: 88, height: 88, borderRadius: 44, alignItems: 'center', justifyContent: 'center', marginBottom: 4 },
  modalTitulo:    { fontSize: 20, fontWeight: '800', color: Colors.bg, textAlign: 'center' },
  modalSub:       { fontSize: 14, color: Colors.textSub, textAlign: 'center', lineHeight: 20 },
  modalDetalle:   { backgroundColor: '#F5F5F5', borderRadius: 12, padding: 12, width: '100%', alignItems: 'center' },
  modalDetalleTexto: { fontSize: 13, fontWeight: '700', color: Colors.bg, textAlign: 'center' },
  btnPrimario:    { backgroundColor: Colors.cyan, borderRadius: 12, padding: 14, alignItems: 'center', width: '100%', ...Shadows.button },
  btnPrimarioTexto: { fontSize: 14, fontWeight: '800', color: Colors.white },
});
