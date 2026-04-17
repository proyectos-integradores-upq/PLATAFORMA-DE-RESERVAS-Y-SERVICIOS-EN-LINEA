@extends('layouts.app')

@section('titulo', $usuario ? 'Editar Usuario' : 'Nuevo Usuario')

@section('contenido')
<header class="section-header">
    <div>
        <h1>{{ $usuario ? 'Editar' : 'Nuevo' }} <span class="text-primario">Usuario</span></h1>
        <p>Registra los datos institucionales del usuario.</p>
    </div>
</header>

<section class="form-container-white">
    <form id="userForm" class="grid-form">
        <div class="input-group">
            <label>Matrícula</label>
            <input type="text" name="matricula" placeholder="Ej: A001"
                value="{{ $usuario['matricula'] ?? '' }}" 
                {{ $usuario ? 'readonly' : '' }}
                style="{{ $usuario ? 'background-color: #f5f5f5; cursor: not-allowed;' : '' }}">
        </div>

        <div class="input-group">
            <label>Correo Institucional</label>
            <input type="email" name="correo" 
                value="{{ $usuario['correo'] ?? '' }}" 
                {{ $usuario ? 'readonly' : '' }}
                style="{{ $usuario ? 'background-color: #f5f5f5; cursor: not-allowed;' : '' }}">
        </div>
        
        <div class="input-group">
            <label>Nombre</label>
            <input type="text" name="nombre" value="{{ $usuario['nombre'] ?? '' }}" required>
        </div>

        <div class="input-group">
            <label>Apellido Paterno</label>
            <input type="text" name="apellido_p" value="{{ $usuario['apellido_p'] ?? '' }}" required>
        </div>

        <div class="input-group">
            <label>Apellido Materno</label>
            <input type="text" name="apellido_m" value="{{ $usuario['apellido_m'] ?? '' }}">
        </div>

        @if(!$usuario)
        <div class="input-group">
            <label>Contraseña</label>
            <input type="password" name="contrasenia" required>
        </div>
        @endif

        <div class="input-group">
            <label>Cuatrimestre</label>
            <input type="number" name="cuatrimestre" min="1" max="12"
                   value="{{ $usuario['cuatrimestre'] ?? 1 }}">
        </div>

        <div class="input-group">
            <label>Rol del Usuario</label>

            @if($usuario)
                {{-- Mostrar como texto en editar --}}
                <input type="text" class="custom-select"
                    value="{{ collect($roles)->firstWhere('id_rol', $usuario['id_rol'])['nombre_rol'] ?? collect($roles)->firstWhere('id_rol', $usuario['id_rol'])['nombre'] }}"
                    readonly
                    style="background-color:#f5f5f5; cursor:not-allowed;">

                {{-- Enviar valor real --}}
                <input type="hidden" name="id_rol" value="{{ $usuario['id_rol'] }}">
            @else
                {{-- Select normal en nuevo --}}
                <select name="id_rol" id="id_rol" class="custom-select" required>
                    <option value="">Selecciona un rol</option>
                    @foreach($roles as $rol)
                        <option value="{{ $rol['id_rol'] }}">
                            {{ $rol['nombre_rol'] ?? $rol['nombre'] }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>

        {{-- SELECT DINÁMICO DE CARRERA --}}
        <div class="input-group">
            <label>Carrera / Programa</label>
            <select name="id_carrera" class="custom-select" required>
                <option value="">Seleccione una carrera...</option>
                @foreach($carreras as $c)
                    <option value="{{ $c['id_carrera'] }}" 
                        {{ ($usuario['id_carrera'] ?? '') == $c['id_carrera'] ? 'selected' : '' }}>
                        {{ $c['nombre_carrera'] }} ({{ $c['clave_carrera'] }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">Guardar Usuario</button>
            <a href="{{ route('admin.usuarios') }}" class="btn-cancelar">Cancelar</a>
        </div>
    </form>
</section>

{{-- El Modal de éxito se mantiene igual --}}
<div id="successModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <div class="success-icon">✓</div>
        <h2 id="modalTitle">¡Éxito!</h2>
        <p id="modalMessage"></p>
        <button onclick="window.location.href='{{ route('admin.usuarios') }}'" class="btn-modal">Continuar</button>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const isEdit = {{ $usuario ? 'true' : 'false' }};
    const usuarioId = @json($usuario['id_usuario'] ?? $usuario['id'] ?? 0);

    document.getElementById('userForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        // Conversión estricta para la API de Render
        data.id_rol = parseInt(data.id_rol);
        data.id_carrera = parseInt(data.id_carrera);
        data.cuatrimestre = parseInt(data.cuatrimestre);

        const url = isEdit ? `/admin/usuarios/${usuarioId}` : '/admin/usuarios';
        const method = isEdit ? 'PUT' : 'POST';

        fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                document.getElementById('modalTitle').innerText = isEdit ? 'Actualizado' : 'Registrado';
                document.getElementById('modalMessage').innerText = 'Cambios aplicados en la base de datos de Render.';
                document.getElementById('successModal').style.display = 'flex';
            } else {
                alert('Error de validación: ' + JSON.stringify(res.message));
            }
        })
        .catch(err => console.error('Error:', err));
    });
</script>
@endsection