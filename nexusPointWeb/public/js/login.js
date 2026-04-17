// login.js
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const msg = document.getElementById('msg');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            msg.textContent = 'Verificando...';
            msg.style.color = 'inherit';

            // CORRECCIÓN: IDs corregidos para que coincidan con login.blade.php
            // Antes: getElementById('usuario') y getElementById('contrasena') — no existían
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            // CSRF token: requerido por Laravel para peticiones POST
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const response = await fetch('/api-login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    // CORRECCIÓN: antes usaba 'correoValue' y 'contrasenaValue' — variables no definidas
                    body: JSON.stringify({
                        email: email,
                        password: password
                    })
                });

                const data = await response.json();

                if (data.success) {
                    msg.style.color = 'green';
                    msg.textContent = '¡Éxito! Redirigiendo...';
                    window.location.href = data.redirect; // Redirige a /admin/dashboard
                } else {
                    msg.textContent = data.message || 'Credenciales incorrectas.';
                    msg.style.color = 'red';
                }
            } catch (error) {
                msg.textContent = 'Error de conexión.';
                msg.style.color = 'red';
            }
        });
    }
});