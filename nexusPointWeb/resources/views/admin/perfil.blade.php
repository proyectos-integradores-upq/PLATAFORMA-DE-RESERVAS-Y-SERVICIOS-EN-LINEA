@extends('layouts.app')

@section('titulo', 'Mi Perfil')

@section('head')
<style>
    .profile-header-card {
        background: linear-gradient(135deg, var(--color-secundario) 0%, #344484 100%);
        border-radius: 15px; padding: 40px; color: white;
        display: flex; align-items: center; gap: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 25px rgba(40, 53, 102, 0.2);
        position: relative; overflow: hidden;
    }
    .profile-header-card::after {
        content: ""; position: absolute; top: -50px; right: -50px;
        width: 150px; height: 150px;
        background: rgba(62, 181, 188, 0.1); border-radius: 50%;
    }
    .profile-avatar-big {
        width: 110px; height: 110px;
        background-color: var(--color-primario); color: var(--color-secundario);
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 2.8rem; font-weight: 800;
        border: 5px solid rgba(255,255,255,0.15);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1;
    }
    .info-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); }
    .info-card h3 { margin-bottom: 25px; color: var(--color-secundario); border-bottom: 2px solid #f0f4f8; padding-bottom: 12px; font-size: 1.2rem; }
    .detail-group { margin-bottom: 20px; }
    .detail-group label { display: block; font-size: 0.75rem; text-transform: uppercase; color: #999; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 5px; }
    .detail-group p { font-size: 1.05rem; color: var(--color-secundario); font-weight: 600; }
    .btn-nexus {
        display: inline-flex; align-items: center; justify-content: center;
        background-color: var(--color-secundario); color: white !important;
        padding: 12px 30px; border-radius: 50px; text-decoration: none;
        font-weight: 700; border: 2px solid var(--color-primario);
        transition: all 0.3s ease; cursor: pointer; font-size: 0.9rem;
    }
    .btn-nexus:hover {
        background-color: var(--color-primario); color: var(--color-secundario) !important;
        box-shadow: 0 5px 15px rgba(62, 181, 188, 0.4); transform: translateY(-2px);
    }
</style>

@section('contenido')
@php
    $nombre    = ($userData['nombre'] ?? '') . ' ' . ($userData['apellido_p'] ?? '') . ' ' . ($userData['apellido_m'] ?? '');
    $inicial   = strtoupper(substr($userData['nombre'] ?? 'A', 0, 1));
    $correo    = $userData['correo'] ?? '—';
    $matricula = $userData['matricula'] ?? '—';
   $rolMap = [1 => 'Alumno', 2 => 'Docente', 3 => 'Encargado', 4 => 'Administrador'];
    $rol       = $rolMap[$userData['id_rol'] ?? 0] ?? 'Sin rol';
@endphp

<header class="section-header">
    <div>
        <h1>Mi <span class="text-primario">Perfil</span></h1>
        <p>Gestiona tu presencia en la plataforma NexusPoint.</p>
    </div>
</header>

<section class="profile-header-card">
    <div class="profile-avatar-big">{{ $inicial }}</div>
    <div>
        <h2 style="font-size:2rem; font-weight:800; margin-bottom:5px;">{{ trim($nombre) }}</h2>
        <p style="opacity:0.9; font-size:1.1rem; font-weight:300;">{{ $correo }}</p>
    </div>
</section>

<section class="info-card" style="max-width:900px; margin:0 auto;">
    <h3>Información Personal</h3>
    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:30px; margin-bottom:10px;">
        <div class="detail-group">
            <label>Nombre completo</label>
            <p>{{ trim($nombre) }}</p>
        </div>
        <div class="detail-group">
            <label>Matrícula</label>
            <p>{{ $matricula }}</p>
        </div>
        <div class="detail-group">
            <label>Correo electrónico</label>
            <p>{{ $correo }}</p>
        </div>
        <div class="detail-group">
            <label>Rol</label>
            <p>{{ $rol }}</p>
        </div>
        <div class="detail-group">
            <label>Cuatrimestre</label>
            <p>{{ $userData['cuatrimestre'] ?? '—' }}</p>
        </div>
    </div>
    <div style="border-top:2px solid #f0f4f8; padding-top:20px; margin-top:10px;">
        <a href="{{ route('admin.perfil.edit') }}" class="btn-nexus">Editar mi Información</a>
    </div>
</section>

@endsection

@section('contenido')
@php
    $nombre   = ($userData['nombre'] ?? '') . ' ' . ($userData['apellido_p'] ?? '') . ' ' . ($userData['apellido_m'] ?? '');
    $inicial  = strtoupper(substr($userData['nombre'] ?? 'A', 0, 1));
    $correo   = $userData['correo'] ?? '—';
    $matricula = $userData['matricula'] ?? '—';
    $rolMap   = [1 => 'Alumno', 2 => 'Docente', 3 => 'Encargado', 4 => 'Administrador'];
    $rol      = $rolMap[$userData['id_rol'] ?? 0] ?? 'Sin rol';
@endphp

<header class="section-header">
    <div>
        <h1>Mi <span class="text-primario">Perfil</span></h1>
        <p>Gestiona tu presencia en la plataforma NexusPoint.</p>
    </div>
</header>

<section class="profile-header-card">
    <div class="profile-avatar-big">{{ $inicial }}</div>
    <div>
        <h2 style="font-size:2rem; font-weight:800; margin-bottom:5px;">{{ trim($nombre) }}</h2>
        <p style="opacity:0.9; font-size:1.1rem; font-weight:300;">{{ $correo }}</p>
    </div>
</section>

<div class="profile-grid">
    <section class="info-card">
        <h3>Información Personal</h3>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
            <div class="detail-group"><label>Nombre</label><p>{{ trim($nombre) }}</p></div>
            <div class="detail-group"><label>Matrícula</label><p>{{ $matricula }}</p></div>
            <div class="detail-group"><label>Correo</label><p>{{ $correo }}</p></div>
            <div class="detail-group"><label>Rol</label><p>{{ $rol }}</p></div>
            <div class="detail-group"><label>Cuatrimestre</label><p>{{ $userData['cuatrimestre'] ?? '—' }}</p></div>
        </div>
        <a href="{{ route('admin.perfil.edit') }}" class="btn-nexus" style="margin-top:20px;">Editar mi Información</a>
    </section>

{{-- Modal cambio contraseña (multi-estado) --}}
<div id="confirmPassModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        {{-- Confirmar --}}
        <div id="passStateConfirm">
            <div style="font-size:3rem; margin-bottom:10px;">🔑</div>
            <h3>¿Cambiar contraseña?</h3>
            <p style="color:#666;">Se actualizará tu contraseña de acceso al sistema.</p>
            <div style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
                <button onclick="document.getElementById('confirmPassModal').style.display='none'"
                    style="background:#eee; color:#333; border:none; padding:10px 25px; border-radius:50px; cursor:pointer; font-weight:700;">
                    Cancelar
                </button>
                <button onclick="savePass()"
                    style="background:var(--color-secundario); color:white; border:2px solid var(--color-primario); padding:10px 25px; border-radius:50px; cursor:pointer; font-weight:700;">
                    Sí, confirmar
                </button>
            </div>
        </div>
        {{-- Cargando --}}
        <div id="passStateLoading" style="display:none;">
            <div style="font-size:2rem; margin-bottom:10px;">⏳</div>
            <p style="color:#666;">Actualizando contraseña...</p>
        </div>
        {{-- Éxito --}}
        <div id="passStateSuccess" style="display:none;">
            <div style="width:65px; height:65px; background:#d4edda; color:#28a745; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto 15px;">✓</div>
            <h3 style="color:var(--color-secundario);">¡Contraseña actualizada!</h3>
            <p style="color:#666; margin-top:8px;">Tu nueva contraseña ya está activa.</p>
            <button onclick="document.getElementById('confirmPassModal').style.display='none'; setPassModalState('confirm')"
                style="background:var(--color-secundario); color:white; border:2px solid var(--color-primario); padding:10px 28px; border-radius:50px; cursor:pointer; font-weight:700; margin-top:20px;">
                Cerrar
            </button>
        </div>
        {{-- Error --}}
        <div id="passStateError" style="display:none;">
            <div style="font-size:2.5rem; margin-bottom:10px;">🚫</div>
            <h3 style="color:#c0392b;">No se pudo actualizar</h3>
            <p id="passErrorMsg" style="color:#666; margin-top:8px; line-height:1.5;"></p>
            <button onclick="setPassModalState('confirm')"
                style="background:var(--color-secundario); color:white; border:2px solid var(--color-primario); padding:10px 28px; border-radius:50px; cursor:pointer; font-weight:700; margin-top:20px;">
                Intentar de nuevo
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function savePass() {
        const password = document.getElementById('newPassword').value;
        if (!password || password.length < 8) {
            setPassModalState('error', 'La contraseña debe tener al menos 8 caracteres.');
            return;
        }

        setPassModalState('loading');

        fetch('{{ route('admin.perfil.update') }}', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ contrasenia: password })
        })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (ok && data.success) {
                setPassModalState('success');
                document.getElementById('newPassword').value = '';
            } else {
                setPassModalState('error', data.message || 'No se pudo actualizar la contraseña.');
            }
        })
        .catch(() => setPassModalState('error', 'Error de conexión con el servidor.'));
    }

    function setPassModalState(state, msg = '') {
        document.getElementById('passStateConfirm').style.display = state === 'confirm' ? 'block' : 'none';
        document.getElementById('passStateLoading').style.display = state === 'loading' ? 'block' : 'none';
        document.getElementById('passStateSuccess').style.display = state === 'success' ? 'block' : 'none';
        document.getElementById('passStateError').style.display   = state === 'error'   ? 'block' : 'none';
        if (state === 'error') document.getElementById('passErrorMsg').innerText = msg;
    }
</script>
@endsection