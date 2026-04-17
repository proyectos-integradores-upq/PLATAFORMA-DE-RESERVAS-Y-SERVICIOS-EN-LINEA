import { useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity,
  StyleSheet, KeyboardAvoidingView, Platform, ScrollView,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { Colors, Radius, Shadows } from '../constants/theme';

export default function RecuperarScreen({ onRegresar }) {
  const [paso,         setPaso]         = useState(1); // 1=correo, 2=código, 3=nueva pass
  const [correo,       setCorreo]       = useState('');
  const [codigo,       setCodigo]       = useState('');
  const [nuevaPass,    setNuevaPass]    = useState('');
  const [confirmaPass, setConfirmaPass] = useState('');
  const [verNueva,     setVerNueva]     = useState(false);
  const [verConfirma,  setVerConfirma]  = useState(false);
  const [error,        setError]        = useState('');
  const [exitoso,      setExitoso]      = useState(false);

  const siguientePaso = () => {
    if (paso === 1) {
      if (!correo.includes('@')) {
        setError('Ingresa un correo válido');
        return;
      }
      setError('');
      setPaso(2);
    } else if (paso === 2) {
      if (codigo.length < 4) {
        setError('Ingresa el código de 4 dígitos');
        return;
      }
      setError('');
      setPaso(3);
    } else if (paso === 3) {
      if (nuevaPass.length < 6) {
        setError('La contraseña debe tener al menos 6 caracteres');
        return;
      }
      if (nuevaPass !== confirmaPass) {
        setError('Las contraseñas no coinciden');
        return;
      }
      setError('');
      setExitoso(true);
    }
  };

  // ── Barra de progreso ─────────────────────────────
  const renderProgreso = () => (
    <View style={styles.progresoWrap}>
      {[1, 2, 3].map(n => (
        <View key={n} style={styles.progresoItem}>
          <View style={[
            styles.progresoPunto,
            paso >= n && styles.progresoPuntoActivo,
          ]}>
            {paso > n ? (
              <Ionicons name="checkmark" size={14} color="#FFFFFF"/>
            ) : (
              <Text style={[
                styles.progresoNum,
                paso >= n && styles.progresoNumActivo,
              ]}>
                {n}
              </Text>
            )}
          </View>
          {n < 3 && (
            <View style={[
              styles.progresoLinea,
              paso > n && styles.progresoLineaActiva,
            ]}/>
          )}
        </View>
      ))}
    </View>
  );

  return (
    <LinearGradient
      colors={['#00BCD4', '#004D8C']}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={styles.fondo}
    >
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.keyboardView}
      >
        <ScrollView
          contentContainerStyle={styles.scroll}
          showsVerticalScrollIndicator={false}
          keyboardShouldPersistTaps="handled"
        >

          {/* Header */}
          <View style={styles.headerWrap}>
            <TouchableOpacity
              style={styles.btnRegresar}
              onPress={onRegresar}
            >
              <Ionicons name="arrow-back" size={22} color="#FFFFFF"/>
            </TouchableOpacity>
            <Text style={styles.headerTitulo}>Recuperar Contraseña</Text>
          </View>

          {/* Card */}
          <View style={styles.card}>

            {exitoso ? (
              // ── ÉXITO ──────────────────────────────
              <View style={styles.exitoWrap}>
                <View style={styles.exitoIcono}>
                  <Ionicons name="checkmark-circle" size={72} color={Colors.cyan}/>
                </View>
                <Text style={styles.exitoTitulo}>¡Contraseña Actualizada!</Text>
                <Text style={styles.exitoSub}>
                  Tu contraseña ha sido restablecida correctamente.
                </Text>
                <TouchableOpacity
                  style={styles.btnPrimario}
                  onPress={onRegresar}
                >
                  <Text style={styles.btnPrimarioTexto}>Ir a Iniciar Sesión</Text>
                </TouchableOpacity>
              </View>

            ) : (
              <>
                {/* Progreso */}
                {renderProgreso()}

                {/* Paso 1 — Correo */}
                {paso === 1 && (
                  <View style={styles.pasoWrap}>
                    <View style={styles.pasoIconoWrap}>
                      <Ionicons name="mail-outline" size={32} color={Colors.cyan}/>
                    </View>
                    <Text style={styles.pasoTitulo}>Ingresa tu correo</Text>
                    <Text style={styles.pasoSub}>
                      Te enviaremos un código para restablecer tu contraseña.
                    </Text>
                    <TextInput
                      style={[styles.input, error && styles.inputError]}
                      placeholder="Correo Electrónico"
                      placeholderTextColor={Colors.textMuted}
                      keyboardType="email-address"
                      autoCapitalize="none"
                      value={correo}
                      onChangeText={text => { setCorreo(text); setError(''); }}
                    />
                  </View>
                )}

                {/* Paso 2 — Código */}
                {paso === 2 && (
                  <View style={styles.pasoWrap}>
                    <View style={styles.pasoIconoWrap}>
                      <Ionicons name="keypad-outline" size={32} color={Colors.cyan}/>
                    </View>
                    <Text style={styles.pasoTitulo}>Código de verificación</Text>
                    <Text style={styles.pasoSub}>
                      Ingresa el código de 4 dígitos enviado a{'\n'}
                      <Text style={styles.correoDestacado}>{correo}</Text>
                    </Text>
                    <TextInput
                      style={[styles.inputCodigo, error && styles.inputError]}
                      placeholder="0000"
                      placeholderTextColor={Colors.textMuted}
                      keyboardType="number-pad"
                      maxLength={4}
                      value={codigo}
                      onChangeText={text => { setCodigo(text); setError(''); }}
                    />
                    <TouchableOpacity style={styles.btnReenviar}>
                      <Text style={styles.btnReenviarTexto}>
                        ¿No recibiste el código? Reenviar
                      </Text>
                    </TouchableOpacity>
                  </View>
                )}

                {/* Paso 3 — Nueva contraseña */}
                {paso === 3 && (
                  <View style={styles.pasoWrap}>
                    <View style={styles.pasoIconoWrap}>
                      <Ionicons name="lock-closed-outline" size={32} color={Colors.cyan}/>
                    </View>
                    <Text style={styles.pasoTitulo}>Nueva contraseña</Text>
                    <Text style={styles.pasoSub}>
                      Crea una contraseña segura de al menos 6 caracteres.
                    </Text>

                    {/* Nueva pass */}
                    <View style={[styles.inputRow, error && styles.inputError]}>
                      <TextInput
                        style={styles.inputFlex}
                        placeholder="Nueva contraseña"
                        placeholderTextColor={Colors.textMuted}
                        secureTextEntry={!verNueva}
                        value={nuevaPass}
                        onChangeText={text => { setNuevaPass(text); setError(''); }}
                      />
                      <TouchableOpacity
                        onPress={() => setVerNueva(!verNueva)}
                        style={styles.eyeBtn}
                      >
                        <Ionicons
                          name={verNueva ? 'eye-off-outline' : 'eye-outline'}
                          size={20}
                          color={Colors.textMuted}
                        />
                      </TouchableOpacity>
                    </View>

                    {/* Confirmar pass */}
                    <View style={[styles.inputRow, error && styles.inputError]}>
                      <TextInput
                        style={styles.inputFlex}
                        placeholder="Confirmar contraseña"
                        placeholderTextColor={Colors.textMuted}
                        secureTextEntry={!verConfirma}
                        value={confirmaPass}
                        onChangeText={text => { setConfirmaPass(text); setError(''); }}
                      />
                      <TouchableOpacity
                        onPress={() => setVerConfirma(!verConfirma)}
                        style={styles.eyeBtn}
                      >
                        <Ionicons
                          name={verConfirma ? 'eye-off-outline' : 'eye-outline'}
                          size={20}
                          color={Colors.textMuted}
                        />
                      </TouchableOpacity>
                    </View>
                  </View>
                )}

                {/* Error */}
                {error ? (
                  <View style={styles.errorWrap}>
                    <Ionicons name="alert-circle-outline" size={16} color={Colors.danger}/>
                    <Text style={styles.errorTexto}>{error}</Text>
                  </View>
                ) : null}

                {/* Botón siguiente */}
                <TouchableOpacity
                  style={styles.btnPrimario}
                  onPress={siguientePaso}
                  activeOpacity={0.85}
                >
                  <Text style={styles.btnPrimarioTexto}>
                    {paso === 3 ? 'Restablecer Contraseña' : 'Continuar'}
                  </Text>
                </TouchableOpacity>
              </>
            )}

          </View>

        </ScrollView>
      </KeyboardAvoidingView>
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  fondo:       { flex: 1 },
  keyboardView:{ flex: 1 },
  scroll: {
    flexGrow:       1,
    alignItems:     'center',
    justifyContent: 'center',
    padding:        24,
    gap:            24,
  },

  // Header
  headerWrap: {
    flexDirection: 'row',
    alignItems:    'center',
    gap:           12,
    alignSelf:     'flex-start',
  },
  btnRegresar: {
    width:           40,
    height:          40,
    borderRadius:    20,
    backgroundColor: 'rgba(255,255,255,0.2)',
    alignItems:      'center',
    justifyContent:  'center',
  },
  headerTitulo: {
    fontSize:   20,
    fontWeight: '800',
    color:      '#FFFFFF',
  },

  // Card
  card: {
    width:           '100%',
    backgroundColor: Colors.white,
    borderRadius:    24,
    padding:         24,
    gap:             16,
    ...Shadows.card,
  },

  // Progreso
  progresoWrap: {
    flexDirection:  'row',
    alignItems:     'center',
    justifyContent: 'center',
    marginBottom:   8,
  },
  progresoItem: {
    flexDirection: 'row',
    alignItems:    'center',
  },
  progresoPunto: {
    width:           32,
    height:          32,
    borderRadius:    16,
    backgroundColor: '#F0F0F0',
    alignItems:      'center',
    justifyContent:  'center',
    borderWidth:     2,
    borderColor:     '#E0E0E0',
  },
  progresoPuntoActivo: {
    backgroundColor: Colors.cyan,
    borderColor:     Colors.cyan,
  },
  progresoNum: {
    fontSize:   13,
    fontWeight: '700',
    color:      Colors.textMuted,
  },
  progresoNumActivo: {
    color: Colors.white,
  },
  progresoLinea: {
    width:           40,
    height:          2,
    backgroundColor: '#E0E0E0',
  },
  progresoLineaActiva: {
    backgroundColor: Colors.cyan,
  },

  // Paso
  pasoWrap: {
    gap: 12,
  },
  pasoIconoWrap: {
    width:           60,
    height:          60,
    borderRadius:    30,
    backgroundColor: 'rgba(0,188,212,0.1)',
    alignItems:      'center',
    justifyContent:  'center',
    alignSelf:       'center',
  },
  pasoTitulo: {
    fontSize:   18,
    fontWeight: '800',
    color:      Colors.bg,
    textAlign:  'center',
  },
  pasoSub: {
    fontSize:  13,
    color:     Colors.textSub,
    textAlign: 'center',
    lineHeight: 20,
  },
  correoDestacado: {
    fontWeight: '700',
    color:      Colors.cyan,
  },

  // Inputs
  input: {
    backgroundColor: '#F5F5F5',
    borderRadius:    12,
    padding:         14,
    fontSize:        14,
    color:           Colors.bg,
    borderWidth:     1.5,
    borderColor:     'transparent',
  },
  inputCodigo: {
    backgroundColor: '#F5F5F5',
    borderRadius:    12,
    padding:         14,
    fontSize:        28,
    fontWeight:      '800',
    color:           Colors.bg,
    borderWidth:     1.5,
    borderColor:     'transparent',
    textAlign:       'center',
    letterSpacing:   12,
  },
  inputRow: {
    flexDirection:   'row',
    alignItems:      'center',
    backgroundColor: '#F5F5F5',
    borderRadius:    12,
    borderWidth:     1.5,
    borderColor:     'transparent',
    paddingRight:    10,
  },
  inputFlex: {
    flex:     1,
    padding:  14,
    fontSize: 14,
    color:    Colors.bg,
  },
  inputError: {
    borderColor: Colors.danger,
  },
  eyeBtn: {
    padding: 4,
  },
  btnReenviar: {
    alignItems: 'center',
  },
  btnReenviarTexto: {
    fontSize:   13,
    color:      Colors.cyan,
    fontWeight: '600',
  },

  // Error
  errorWrap: {
    flexDirection:   'row',
    alignItems:      'center',
    gap:             6,
    backgroundColor: 'rgba(244,67,54,0.08)',
    borderRadius:    10,
    padding:         10,
  },
  errorTexto: {
    fontSize:   13,
    color:      Colors.danger,
    fontWeight: '600',
    flex:       1,
  },

  // Botón primario
  btnPrimario: {
    backgroundColor: Colors.cyan,
    borderRadius:    Radius.button,
    padding:         16,
    alignItems:      'center',
    ...Shadows.button,
  },
  btnPrimarioTexto: {
    fontSize:   15,
    fontWeight: '800',
    color:      Colors.white,
  },

  // Éxito
  exitoWrap: {
    alignItems: 'center',
    gap:        12,
    paddingVertical: 8,
  },
  exitoIcono: {
    marginBottom: 4,
  },
  exitoTitulo: {
    fontSize:   22,
    fontWeight: '800',
    color:      Colors.cyan,
  },
  exitoSub: {
    fontSize:  14,
    color:     Colors.textSub,
    textAlign: 'center',
    lineHeight: 20,
  },
});