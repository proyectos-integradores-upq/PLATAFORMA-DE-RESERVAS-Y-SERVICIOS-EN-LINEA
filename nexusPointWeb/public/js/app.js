// login.js
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('loginForm');
    const msg = document.getElementById('msg');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            msg.textContent = 'Verificando...';
            msg.style.color = 'inherit';

            const email = document.getElementById('usuario').value.trim();
            const password = document.getElementById('contrasena').value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const response = await fetch('/api-login', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    // CORRECCIÓN AQUÍ: Usar las variables 'email' y 'password'
                    body: JSON.stringify({ 
                        email: email, 
                        password: password 
                    }) 
                });

                const data = await response.json();

                if (data.success) {
                    msg.style.color = 'green';
                    msg.textContent = '¡Éxito! Redirigiendo...';
                    window.location.href = data.redirect; 
                } else {
                    msg.textContent = data.message || 'Credenciales incorrectas.';
                    msg.style.color = "red";
                }
            } catch (error) {
                msg.textContent = 'Error de conexión.';
                msg.style.color = "red";
            }
        });
    }
});