// ─────────────────────────────────────────────────────
// context/UsuarioContext.js
// Estado global de la sesión. Evita pasar `usuario` como
// prop por 4 niveles de componentes.
//
// Uso en cualquier screen:
//   const { usuario, setUsuario } = useUsuario();
// ─────────────────────────────────────────────────────

import React, { createContext, useContext, useState } from 'react';

const UsuarioContext = createContext(null);

export function UsuarioProvider({ children }) {
  const [usuario, setUsuario] = useState(null);
  return (
    <UsuarioContext.Provider value={{ usuario, setUsuario }}>
      {children}
    </UsuarioContext.Provider>
  );
}

export function useUsuario() {
  const ctx = useContext(UsuarioContext);
  if (!ctx) throw new Error('useUsuario debe usarse dentro de <UsuarioProvider>');
  return ctx;
}
