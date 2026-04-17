// ─────────────────────────────────────────────────────
// App.js  — raíz de la app con MVC integrado
// ─────────────────────────────────────────────────────

import { useState, useEffect } from 'react';
import { View, StyleSheet, ActivityIndicator } from 'react-native';
import { Colors } from './constants/theme';

// Contexto global de sesión
import { UsuarioProvider, useUsuario } from './context/UsuarioContext';

// Controlador de autenticación (restaurar sesión en frío)
import { authController } from './controllers/authController';

// Pantallas
import SplashScreen         from './screens/SplashScreen';
import LoginScreen          from './screens/LoginScreen';
import RecuperarScreen      from './screens/RecuperarScreen';
import HomeScreen           from './screens/homeScreen';
import ReservarScreen       from './screens/ReservarScreen';
import NotificacionesScreen from './screens/NotificacionesScreen';
import PerfilScreen         from './screens/PerfilScreen';
import BottomNav            from './components/BottomNav';

// ── Núcleo de la app (necesita acceso al contexto) ───
function AppCore() {
  const { usuario, setUsuario } = useUsuario();
  const [vista,      setVista]      = useState('splash');
  const [pantalla,   setPantalla]   = useState('home');
  const [restaurando, setRestaurando] = useState(false);

  // Al terminar el splash, intenta restaurar sesión desde AsyncStorage
  const handleSplashTerminar = async () => {
    setRestaurando(true);
    try {
      const usuarioGuardado = await authController.restaurarSesion();
      if (usuarioGuardado) {
        setUsuario(usuarioGuardado);
        setVista('app');
      } else {
        setVista('login');
      }
    } catch {
      setVista('login');
    } finally {
      setRestaurando(false);
    }
  };

  // ── Splash ─────────────────────────────────────────
  if (vista === 'splash') {
    return <SplashScreen onTerminar={handleSplashTerminar} />;
  }

  // ── Cargando sesión ────────────────────────────────
  if (restaurando) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color={Colors.cyan} />
      </View>
    );
  }

  // ── Login ──────────────────────────────────────────
  if (vista === 'login') {
    return (
      <LoginScreen
        // onLogin recibe el UsuarioOut desde authController.login()
        onLogin={(usuarioData) => {
          setUsuario(usuarioData);
          setVista('app');
        }}
        onRecuperar={() => setVista('recuperar')}
      />
    );
  }

  // ── Recuperar contraseña ───────────────────────────
  if (vista === 'recuperar') {
    return <RecuperarScreen onRegresar={() => setVista('login')} />;
  }

  // ── App principal ──────────────────────────────────
  const renderPantalla = () => {
    switch (pantalla) {
      case 'home':
        return <HomeScreen onNavegar={setPantalla} />;

      case 'reservar':
        return <ReservarScreen onNavegar={setPantalla} />;

      case 'notifs':
        return <NotificacionesScreen />;

      case 'perfil':
        return (
          <PerfilScreen
            onNavegar={(destino) => {
              if (destino === 'cerrarSesion') {
                authController.logout(() => {
                  setUsuario(null);
                  setPantalla('home');
                  setVista('login');
                });
              }
            }}
          />
        );

      default:
        return <HomeScreen onNavegar={setPantalla} />;
    }
  };

  return (
    <View style={styles.safe}>
      <View style={styles.container}>
        {renderPantalla()}
        <BottomNav pantalla={pantalla} onNavegar={setPantalla} />
      </View>
    </View>
  );
}

// ── Raíz: envuelve todo en el Provider ──────────────
export default function App() {
  return (
    <UsuarioProvider>
      <AppCore />
    </UsuarioProvider>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: Colors.white,
  },
  container: {
    flex: 1,
    backgroundColor: Colors.white,
  },
  center: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: Colors.white,
  },
});
