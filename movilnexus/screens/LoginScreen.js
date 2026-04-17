
import { useState } from 'react';
import {
  View, Text, TextInput, TouchableOpacity,
  StyleSheet, KeyboardAvoidingView, Platform,
  ScrollView, Image, ActivityIndicator,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { Colors, Radius, Shadows } from '../constants/theme';


import { authController } from '../controllers/authController';

export default function LoginScreen({ onLogin, onRecuperar }) {
  const [modo,           setModo]           = useState('login');
  const [correo,         setCorreo]         = useState('');
  const [contrasenia,    setContrasenia]    = useState('');
  const [nombre,         setNombre]         = useState('');
  const [apellidoP,      setApellidoP]      = useState('');
  const [apellidoM,      setApellidoM]      = useState('');
  const [matricula,      setMatricula]      = useState('');
  const [verContrasenia, setVerContrasenia] = useState(false);

  // Estados de UI
  const [loading,          setLoading]          = useState(false);
  const [errorGeneral,     setErrorGeneral]     = useState('');
  const [errorCorreo,      setErrorCorreo]      = useState('');
  const [errorPass,        setErrorPass]        = useState('');
  const [errorNombre,      setErrorNombre]      = useState('');
  const [registroExitoso,  setRegistroExitoso]  = useState(false);

  const limpiarErrores = () => {
    setErrorCorreo(''); setErrorPass('');
    setErrorNombre(''); setErrorGeneral('');
  };

  const cambiarModo = (m) => { limpiarErrores(); setRegistroExitoso(false); setModo(m); };

  // ── Validación local antes de llamar al controlador ─
  const validarLogin = () => {
    let ok = true;
    if (!correo.includes('@')) { setErrorCorreo('Ingresa un correo válido'); ok = false; }
    if (contrasenia.length < 4) { setErrorPass('La contraseña es muy corta'); ok = false; }
    if (!ok) return;

    // Delega al controlador — él llama a authService.login() + authService.me()
    authController.login(correo, contrasenia, {
      setLoading,
      setError: setErrorGeneral,
      onSuccess: onLogin,  // App.js recibe el UsuarioOut
    });
  };

  const validarRegistro = () => {
    let ok = true;
    if (nombre.trim().length < 2) { setErrorNombre('Ingresa tu nombre'); ok = false; }
    if (!correo.includes('@'))     { setErrorCorreo('Correo inválido'); ok = false; }
    if (contrasenia.length < 6)   { setErrorPass('Mínimo 6 caracteres'); ok = false; }
    if (!ok) return;

    // Delega al controlador — él llama a authService.registro()
    authController.registro(
      {
        nombre,
        apellido_p:  apellidoP,
        apellido_m:  apellidoM || undefined,
        matricula:   matricula || undefined,
        correo,
        contrasenia,
        id_rol:      1,      // Alumno
        id_carrera:  undefined,
      },
      {
        setLoading,
        setError: setErrorGeneral,
        onSuccess: () => setRegistroExitoso(true),
      }
    );
  };

  return (
    <LinearGradient
      colors={['#4A90E2', '#38B3B8', '#88d5d8']}
      start={{ x: 0, y: 0 }} end={{ x: 1, y: 1 }}
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
          {/* Logo */}
          <View style={styles.logoWrap}>
            <View style={styles.logoCirculo}>
              <Image source={require('../assets/logo.png')} style={styles.logoImg} />
            </View>
            <Text style={styles.nombre}>
              <Text style={styles.nombreBold}>Nexus</Text>Point
            </Text>
          </View>

          {/* Card */}
          <View style={styles.card}>

            {registroExitoso ? (
              // ── Registro exitoso ──
              <View style={styles.exitoWrap}>
                <Ionicons name="checkmark-circle" size={72} color={Colors.cyan} />
                <Text style={styles.exitoTitulo}>¡Registro Exitoso!</Text>
                <Text style={styles.exitoSub}>Tu cuenta ha sido creada correctamente.</Text>
                <TouchableOpacity style={styles.btnPrimario} onPress={() => cambiarModo('login')}>
                  <Text style={styles.btnPrimarioTexto}>Iniciar Sesión</Text>
                </TouchableOpacity>
              </View>
            ) : (
              <>
                {/* Tabs */}
                <View style={styles.tabs}>
                  {['login', 'registro'].map(m => (
                    <TouchableOpacity
                      key={m}
                      style={[styles.tab, modo === m && styles.tabActivo]}
                      onPress={() => cambiarModo(m)}
                    >
                      <Text style={[styles.tabTexto, modo === m && styles.tabTextoActivo]}>
                        {m === 'login' ? 'Iniciar Sesión' : 'Registrarse'}
                      </Text>
                    </TouchableOpacity>
                  ))}
                </View>

                {/* Error general de API */}
                {errorGeneral ? (
                  <View style={styles.errorBox}>
                    <Ionicons name="alert-circle-outline" size={16} color={Colors.danger} />
                    <Text style={styles.errorBoxTexto}>{errorGeneral}</Text>
                  </View>
                ) : null}

                {/* ── Form Login ── */}
                {modo === 'login' && (
                  <View style={styles.formulario}>
                    <View style={styles.inputWrap}>
                      <TextInput
                        style={[styles.input, errorCorreo && styles.inputError]}
                        placeholder="Correo Electrónico"
                        placeholderTextColor={Colors.textMuted}
                        keyboardType="email-address"
                        autoCapitalize="none"
                        value={correo}
                        onChangeText={t => { setCorreo(t); setErrorCorreo(''); }}
                      />
                      {errorCorreo ? <Text style={styles.errorTexto}>{errorCorreo}</Text> : null}
                    </View>

                    <View style={styles.inputWrap}>
                      <View style={[styles.inputRow, errorPass && styles.inputError]}>
                        <TextInput
                          style={styles.inputFlex}
                          placeholder="Contraseña"
                          placeholderTextColor={Colors.textMuted}
                          secureTextEntry={!verContrasenia}
                          value={contrasenia}
                          onChangeText={t => { setContrasenia(t); setErrorPass(''); }}
                        />
                        <TouchableOpacity onPress={() => setVerContrasenia(!verContrasenia)} style={styles.eyeBtn}>
                          <Ionicons name={verContrasenia ? 'eye-off-outline' : 'eye-outline'} size={20} color={Colors.textMuted} />
                        </TouchableOpacity>
                      </View>
                      {errorPass ? <Text style={styles.errorTexto}>{errorPass}</Text> : null}
                    </View>

                    <TouchableOpacity style={styles.btnLogin} onPress={validarLogin} disabled={loading} activeOpacity={0.85}>
                      <LinearGradient colors={['#4A90E2', '#38B3B8']} start={{ x: 0, y: 0 }} end={{ x: 1, y: 0 }} style={styles.btnLoginGradiente}>
                        {loading
                          ? <ActivityIndicator color={Colors.white} />
                          : <Text style={styles.btnLoginTexto}>Iniciar Sesión</Text>
                        }
                      </LinearGradient>
                    </TouchableOpacity>

                    <TouchableOpacity style={styles.btnRecuperar} onPress={onRecuperar}>
                      <Text style={styles.btnRecuperarTexto}>¿Olvidaste tu contraseña?</Text>
                    </TouchableOpacity>
                  </View>
                )}

                {/* ── Form Registro ── */}
                {modo === 'registro' && (
                  <View style={styles.formulario}>
                    <View style={styles.inputWrap}>
                      <TextInput style={[styles.input, errorNombre && styles.inputError]} placeholder="Nombre(s)" placeholderTextColor={Colors.textMuted} value={nombre} onChangeText={t => { setNombre(t); setErrorNombre(''); }} />
                      {errorNombre ? <Text style={styles.errorTexto}>{errorNombre}</Text> : null}
                    </View>
                    <TextInput style={styles.input} placeholder="Apellido paterno" placeholderTextColor={Colors.textMuted} value={apellidoP} onChangeText={setApellidoP} />
                    <TextInput style={styles.input} placeholder="Apellido materno (opcional)" placeholderTextColor={Colors.textMuted} value={apellidoM} onChangeText={setApellidoM} />
                    <TextInput style={styles.input} placeholder="Matrícula (opcional)" placeholderTextColor={Colors.textMuted} keyboardType="number-pad" value={matricula} onChangeText={setMatricula} />

                    <View style={styles.inputWrap}>
                      <TextInput style={[styles.input, errorCorreo && styles.inputError]} placeholder="Correo Electrónico" placeholderTextColor={Colors.textMuted} keyboardType="email-address" autoCapitalize="none" value={correo} onChangeText={t => { setCorreo(t); setErrorCorreo(''); }} />
                      {errorCorreo ? <Text style={styles.errorTexto}>{errorCorreo}</Text> : null}
                    </View>

                    <View style={styles.inputWrap}>
                      <View style={[styles.inputRow, errorPass && styles.inputError]}>
                        <TextInput style={styles.inputFlex} placeholder="Contraseña (mín. 6 caracteres)" placeholderTextColor={Colors.textMuted} secureTextEntry={!verContrasenia} value={contrasenia} onChangeText={t => { setContrasenia(t); setErrorPass(''); }} />
                        <TouchableOpacity onPress={() => setVerContrasenia(!verContrasenia)} style={styles.eyeBtn}>
                          <Ionicons name={verContrasenia ? 'eye-off-outline' : 'eye-outline'} size={20} color={Colors.textMuted} />
                        </TouchableOpacity>
                      </View>
                      {errorPass ? <Text style={styles.errorTexto}>{errorPass}</Text> : null}
                    </View>

                    <TouchableOpacity style={styles.btnLogin} onPress={validarRegistro} disabled={loading} activeOpacity={0.85}>
                      <LinearGradient colors={['#4A90E2', '#38B3B8']} start={{ x: 0, y: 0 }} end={{ x: 1, y: 0 }} style={styles.btnLoginGradiente}>
                        {loading
                          ? <ActivityIndicator color={Colors.white} />
                          : <Text style={styles.btnLoginTexto}>Crear Cuenta</Text>
                        }
                      </LinearGradient>
                    </TouchableOpacity>
                  </View>
                )}
              </>
            )}
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  fondo:        { flex: 1 },
  keyboardView: { flex: 1 },
  scroll: { flexGrow: 1, alignItems: 'center', justifyContent: 'center', padding: 24, gap: 28 },
  logoWrap:     { alignItems: 'center', gap: 12 },
  logoCirculo:  { width: 150, height: 150, borderRadius: 75, backgroundColor: 'rgba(164,226,235,0.74)', alignItems: 'center', justifyContent: 'center' },
  logoImg:      { width: 75, height: 75 },
  nombre:       { fontSize: 28, fontWeight: '400', color: '#FFFFFF' },
  nombreBold:   { fontWeight: '900', color: '#FFFFFF' },
  card:         { width: '100%', backgroundColor: Colors.white, borderRadius: 24, padding: 24, gap: 16, ...Shadows.card },
  tabs:         { flexDirection: 'row', backgroundColor: '#F5F5F5', borderRadius: 14, padding: 4 },
  tab:          { flex: 1, paddingVertical: 10, alignItems: 'center', borderRadius: 10 },
  tabActivo:    { backgroundColor: Colors.white, ...Shadows.card },
  tabTexto:     { fontSize: 13, fontWeight: '700', color: Colors.textMuted },
  tabTextoActivo: { color: Colors.cyan },
  formulario:   { gap: 12 },
  inputWrap:    { gap: 4 },
  input:        { backgroundColor: '#F5F5F5', borderRadius: 12, padding: 14, fontSize: 14, color: Colors.bg, borderWidth: 1.5, borderColor: 'transparent' },
  inputRow:     { flexDirection: 'row', alignItems: 'center', backgroundColor: '#F5F5F5', borderRadius: 12, borderWidth: 1.5, borderColor: 'transparent', paddingRight: 10 },
  inputFlex:    { flex: 1, padding: 14, fontSize: 14, color: Colors.bg },
  inputError:   { borderColor: Colors.danger },
  eyeBtn:       { padding: 4 },
  errorTexto:   { fontSize: 12, color: Colors.danger, fontWeight: '600', marginLeft: 4 },
  errorBox:     { flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: 'rgba(244,67,54,0.08)', borderRadius: 10, padding: 10 },
  errorBoxTexto:{ fontSize: 13, color: Colors.danger, fontWeight: '600', flex: 1 },
  btnLogin:     { borderRadius: Radius.button, overflow: 'hidden', marginTop: 4, ...Shadows.button },
  btnLoginGradiente: { padding: 16, alignItems: 'center' },
  btnLoginTexto: { fontSize: 16, fontWeight: '800', color: Colors.white },
  btnRecuperar: { alignItems: 'center', marginTop: 4 },
  btnRecuperarTexto: { fontSize: 13, color: Colors.textSub, fontWeight: '600' },
  btnPrimario:  { backgroundColor: Colors.cyan, borderRadius: Radius.button, padding: 16, alignItems: 'center', width: '100%', ...Shadows.button },
  btnPrimarioTexto: { fontSize: 15, fontWeight: '800', color: Colors.white },
  exitoWrap:    { alignItems: 'center', paddingVertical: 12, gap: 12 },
  exitoTitulo:  { fontSize: 22, fontWeight: '800', color: Colors.cyan },
  exitoSub:     { fontSize: 14, color: Colors.textSub, textAlign: 'center', lineHeight: 20 },
});
