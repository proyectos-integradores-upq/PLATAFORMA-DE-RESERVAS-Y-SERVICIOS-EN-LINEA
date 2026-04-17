// ─────────────────────────────────────────────────────
// services/usuariosService.js
// Mapea exactamente a app/routers/usuarios.py
//
// UsuarioOut:
//   id_usuario  matricula?  nombre  apellido_p  apellido_m?
//   correo  cuatrimestre?  id_rol  id_carrera?
//
// Roles (rol):
//   1 = Alumno
//   2 = Docente
//   3 = Encargado
//   4 = Administrador
// ─────────────────────────────────────────────────────

import { api } from './api';

export const usuariosService = {
  // GET /usuarios/
  // retorna: UsuarioOut[]  (solo admin)
  listarTodos() {
    return api.get('/usuarios/');
  },

  // GET /usuarios/{id_usuario}
  // retorna: UsuarioOut  (404 si no existe)
  obtener(idUsuario) {
    return api.get(`/usuarios/${idUsuario}`);
  },

  // PUT /usuarios/{id_usuario}
  // body: UsuarioUpdate (todos opcionales)
  //   nombre?  apellido_p?  apellido_m?  cuatrimestre?  id_carrera?
  // retorna: UsuarioOut
  actualizar(idUsuario, payload) {
    return api.put(`/usuarios/${idUsuario}`, payload);
  },

  // DELETE /usuarios/{id_usuario}  → 204  (solo admin)
  eliminar(idUsuario) {
    return api.del(`/usuarios/${idUsuario}`);
  },

  // ── Catálogos ──────────────────────────────────────

  // GET /usuarios/catalogos/roles
  // retorna: RolOut[] → { id_rol, nombre_rol }
  listarRoles() {
    return api.get('/usuarios/catalogos/roles');
  },

  // GET /usuarios/catalogos/carreras
  // retorna: CarreraOut[] → { id_carrera, nombre_carrera, clave_carrera? }
  listarCarreras() {
    return api.get('/usuarios/catalogos/carreras');
  },

  
};
