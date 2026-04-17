import { useEffect } from 'react';
import { View, Text, StyleSheet, Image } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';

export default function SplashScreen({ onTerminar }) {
  useEffect(() => {
    const timer = setTimeout(() => {
      onTerminar();
    }, 2500);
    return () => clearTimeout(timer);
  }, []);

  return (
    <LinearGradient
      colors={['#4A90E2', '#38B3B8', '#88d5d8']}
      start={{ x: 0, y: 0 }}
      end={{ x: 1, y: 1 }}
      style={styles.container}
    >
      {/* Logo */}
      <View style={styles.logoCirculo}>
        <Image
          source={require('../assets/logo.png')}
          style={styles.logoImg}
        />
      </View>

      {/* Nombre */}
      <View style={styles.nombreWrap}>
        <Text style={styles.nombre}>
          <Text style={styles.nombreBold}>Nexus</Text>Point
        </Text>
      </View>

      {/* Subtítulo */}
      <Text style={styles.sub}>Reserva espacios</Text>

    </LinearGradient>
  );
}

const styles = StyleSheet.create({
  container: {
    flex:           1,
    alignItems:     'center',
    justifyContent: 'center',
    gap:            20,
  },

  // Logo
  logoCirculo: {
    width:           150,
    height:          150,
    borderRadius:    75,
    backgroundColor: 'rgba(164,226,235,0.74)',
    alignItems:      'center',
    justifyContent:  'center',
  },
  logoImg: {
    width:  85,
    height: 85,
  },

  // Nombre
  nombreWrap: {
    alignItems: 'center',
  },
  nombre: {
    fontSize:   36,
    fontWeight: '400',
    color:      '#e1ebec',
  },
  nombreBold: {
    fontSize:   36,
    fontWeight: '900',
    color:      '#c1d8da',
  },

  // Subtítulo
  sub: {
    fontSize:   14,
    color:      '#ccd4d4',
    fontWeight: '500',
    opacity:    0.8,
  },
});