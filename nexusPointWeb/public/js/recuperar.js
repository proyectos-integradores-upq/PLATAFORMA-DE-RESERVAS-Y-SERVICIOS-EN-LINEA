/**
 * Lógica para Recuperar Contraseña - NexusPoint
 */
document.addEventListener('DOMContentLoaded', () => {
    const recoveryForm = document.querySelector('form');
    
    if (recoveryForm) {
        recoveryForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const emailInput = e.target.querySelector('input[type="email"]');
            const btn = e.target.querySelector('.btn-submit');

            // Feedback visual
            btn.textContent = "ENVIANDO ENLACE...";
            btn.disabled = true;
            btn.style.opacity = "0.7";

            // Simulación de envío
            setTimeout(() => {
                alert(`Si el correo ${emailInput.value} está registrado, recibirás un enlace de recuperación.`);
                window.location.href = "login.html";
            }, 2000);
        });
    }
});