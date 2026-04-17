
import AsyncStorage from '@react-native-async-storage/async-storage';
import { api } from './api';

export const authService = {
  // POST /auth/login
  // body: { correo: string, contrasenia: string }
  // retorna: { access_token: string, token_type: "bearer" }
  async login(correo, contrasenia) {
    const data = await api.post('/auth/login', { correo, contrasenia });
    // Guardamos el token para que buildHeaders() lo use en todas las peticiones
    await AsyncStorage.setItem('@nexus_token', data.access_token);
    return data; // { access_token, token_type }
  },

  // POST /auth/registro
  // body: UsuarioCreate — ver schemas.py
  //   matricula?  nombre  apellido_p  apellido_m?  correo
  //   contrasenia  cuatrimestre?  id_rol  id_carrera?
  // retorna: UsuarioOut
  async registro(payload) {
    return api.post('/auth/registro', payload);
  },

  // GET /auth/me?token=<jwt>
  // retorna: UsuarioOut
  //   id_usuario  matricula?  nombre  apellido_p  apellido_m?
  //   correo  cuatrimestre?  id_rol  id_carrera?
  async me() {
    const token = await AsyncStorage.getItem('@nexus_token');
    if (!token) throw new Error('No hay sesión activa');
    // El router recibe el token como query param, no como header
    return api.get(`/auth/me?token=${token}`);
  },

  // Cierra sesión: borra token y usuario del storage
  async logout() {
    await AsyncStorage.multiRemove(['@nexus_token', '@nexus_usuario']);
  },
};
