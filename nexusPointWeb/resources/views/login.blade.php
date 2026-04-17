<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- CSRF token requerido por login.js para el header X-CSRF-TOKEN --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NexusPoint | Login</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="login-container">
        <div class="login-branding">
            <img src="{{ asset('img/logo.png') }}" alt="NexusPoint Logo" class="big-logo">
        </div>
        <div class="login-form-side">
            <div class="form-content">
                <h2><span class="text-secundario">Nexus</span>Point</h2>
                <p>Ingresa tus credenciales para acceder</p>

                {{-- Mostrar error de sesión expirada si viene del middleware --}}
                @if (session('error'))
                    <p style="color: red; text-align: center;">{{ session('error') }}</p>
                @endif

                <form id="loginForm">
                    <div class="input-group">
                        <p>Correo electrónico</p>
                        {{-- ID corregido: era 'usuario', debe ser 'email' para que login.js lo lea --}}
                        <input type="email" id="email" placeholder="ejemplo@nexuspoint.com" required>
                    </div>
                    <div class="input-group">
                        <p>Contraseña</p>
                        {{-- ID corregido: era 'contrasena', debe ser 'password' para que login.js lo lea --}}
                        <input type="password" id="password" placeholder="•••••••••" required>
                    </div>

                    <button type="submit" class="btn-submit">ENTRAR</button>

                    <p id="msg" class="msg" style="margin-top: 15px; text-align: center; font-size: 0.9rem;"></p>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/login.js') }}"></script>
</body>
</html>