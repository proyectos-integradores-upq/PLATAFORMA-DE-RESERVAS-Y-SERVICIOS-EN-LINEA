@extends('layouts.app')

@section('titulo', 'Editar Perfil')

@section('contenido')
<header class="section-header">
    <div>
        <h1>Editar <span class="text-primario">Información</span></h1>
        <p>Modifica tus datos de contacto.</p>
    </div>
</header>

<section class="form-container-white">
    <form id="profileForm" class="grid-form">

        {{-- Campos no editables enviados como hidden para que el PUT de la API
             reciba el payload completo y no sobreescriba con nulls --}}
        <input type="hidden" name="matricula"  value="{{ $userData['matricula']  ?? '' }}">
        <input type="hidden" name="id_rol"     value="{{ $userData['id_rol']     ?? '' }}">
        <input type="hidden" name="id_carrera" value="{{ $userData['id_carrera'] ?? '' }}">

        <div class="input-group">
            <label>Nombre</label>
            <input type="text" name="nombre" value="{{ $userData['nombre'] ?? '' }}" required>
        </div>
        <div class="input-group">
            <label>Apellido Paterno</label>
            <input type="text" name="apellido_p" value="{{ $userData['apellido_p'] ?? '' }}" required>
        </div>
        <div class="input-group">
            <label>Apellido Materno</label>
            <input type="text" name="apellido_m" value="{{ $userData['apellido_m'] ?? '' }}">
        </div>
        <div class="input-group">
            <label>Correo Electrónico</label>
            <input type="email" name="correo" value="{{ $userData['correo'] ?? '' }}" required>
        </div>
        <div class="input-group">
            <label>Cuatrimestre</label>
            <input type="number" name="cuatrimestre" min="1" max="12" value="{{ $userData['cuatrimestre'] ?? '' }}">
        </div>

        {{-- Campos readonly solo visuales --}}
        <div class="input-group">
            <label>Matrícula</label>
            <input type="text" value="{{ $userData['matricula'] ?? '—' }}" readonly
                   style="background:#f5f5f5; cursor:not-allowed;">
        </div>

        <div class="input-group">
            <label>Rol</label>
            @php
            $rolMap = [1 => 'Alumno', 2 => 'Docente', 3 => 'Encargado', 4 => 'Administrador'];
            @endphp
            <input type="text" value="{{ $rolMap[$userData['id_rol'] ?? 0] ?? 'Sin rol' }}" readonly
            style="background:#f5f5f5; cursor:not-allowed;">
        </div>

        <div class="form-actions">
            <button type="button" class="btn-submit"
                onclick="document.getElementById('confirmSaveModal').style.display='flex'"
                style="border-radius:50px; padding:12px 35px;">
                Guardar Cambios
            </button>
            <a href="{{ route('admin.perfil') }}" class="btn-cancelar" style="border-radius:50px;">Cancelar</a>
        </div>
    </form>
</section>

{{-- Modal confirmación --}}
<div id="confirmSaveModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <h3>¿Guardar cambios?</h3>
        <p>Se actualizará tu información en todo el sistema.</p>
        <div class="modal-actions" style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
            <button onclick="document.getElementById('confirmSaveModal').style.display='none'"
                style="background:#eee; color:#333; border:none; padding:10px 25px; border-radius:50px; cursor:pointer; font-weight:700;">
                Revisar
            </button>
            <button onclick="finalizeSave()"
                style="background:var(--color-secundario); color:white; border:2px solid var(--color-primario); padding:10px 25px; border-radius:50px; cursor:pointer; font-weight:700;">
                Confirmar
            </button>
        </div>
    </div>
</div>

{{-- Modal éxito --}}
<div id="successModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <div style="width:65px; height:65px; background:#d4edda; color:#28a745; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto 15px;">✓</div>
        <h3 style="color:var(--color-secundario);">¡Información actualizada!</h3>
        <p style="color:#666; margin-top:8px;">Tus datos se guardaron correctamente.</p>
        <button onclick="window.location.href='{{ route('admin.perfil') }}'"
            style="background:var(--color-secundario); color:white; border:2px solid var(--color-primario); padding:10px 28px; border-radius:50px; cursor:pointer; font-weight:700; margin-top:20px;">
            Ver mi perfil
        </button>
    </div>
</div>

{{-- Modal error --}}
<div id="errorModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <div style="font-size:2.5rem; margin-bottom:10px;">🚫</div>
        <h3 style="color:#c0392b;">No se pudo guardar</h3>
        <p id="errorMsg" style="color:#666; margin-top:8px; line-height:1.5;"></p>
        <button onclick="document.getElementById('errorModal').style.display='none'"
            style="background:var(--color-secundario); color:white; border:2px solid var(--color-primario); padding:10px 28px; border-radius:50px; cursor:pointer; font-weight:700; margin-top:20px;">
            Cerrar
        </button>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function finalizeSave() {
        document.getElementById('confirmSaveModal').style.display = 'none';

        const formData = new FormData(document.getElementById('profileForm'));
        const data = Object.fromEntries(formData.entries());

        // Castear tipos que la API espera como enteros
        if (data.cuatrimestre) data.cuatrimestre = parseInt(data.cuatrimestre);
        if (data.id_rol)       data.id_rol       = parseInt(data.id_rol);
        if (data.id_carrera)   data.id_carrera   = parseInt(data.id_carrera);

        fetch('{{ route('admin.perfil.update') }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })
        .then(r => r.json().then(res => ({ ok: r.ok, res })))
        .then(({ ok, res }) => {
            if (ok && res.success) {
                document.getElementById('successModal').style.display = 'flex';
            } else {
                document.getElementById('errorMsg').innerText =
                    res.message || 'No se pudieron guardar los cambios.';
                document.getElementById('errorModal').style.display = 'flex';
            }
        })
        .catch(() => {
            document.getElementById('errorMsg').innerText = 'Error de conexión con el servidor.';
            document.getElementById('errorModal').style.display = 'flex';
        });
    }
</script>
@endsection