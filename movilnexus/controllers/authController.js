import { authService } from '../services/authService';
import AsyncStorage from '@react-native-async-storage/async-storage';

export const authController = {
  async login(correo, contrasenia, { setLoading, setError, onSuccess }) {
    setLoading(true);
    setError('');
    try { 
      
      await authService.login(correo, contrasenia);
      
      
      const usuario = await authService.me();
      
      
      await AsyncStorage.setItem('@nexus_usuario', JSON.stringify(usuario));
      
      onSuccess(usuario);
    } catch (e) {
      setError(e.message || 'Correo o contraseña incorrectos');
    } finally {
      setLoading(false);
    }
  },

  async registro(datos, { setLoading, setError, onSuccess }) {
    setLoading(true);
    setError('');
    try {
      const usuario = await authService.registro({
        ...datos,
        id_rol: 1, // Alumno por defecto
      });
      onSuccess(usuario);
    } catch (e) {
      setError(e.message || 'Error al registrar');
    } finally {
      setLoading(false);
    }
  },

  async logout(onSuccess) {
    await authService.logout();
    onSuccess();
  },

  async getUsuarioGuardado() {
    const raw = await AsyncStorage.getItem('usuario');
    return raw ? JSON.parse(raw) : null;
  },
};