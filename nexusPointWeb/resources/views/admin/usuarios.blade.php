@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
<header class="section-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1>Gestión de <span class="text-primario">Usuarios</span></h1>
        <p>Administra los usuarios registrados en el sistema.</p>
    </div>
    <a href="{{ route('admin.usuarios.create') }}"
       style="display:inline-flex; align-items:center; background:var(--color-secundario); color:white; padding:12px 28px; border-radius:50px; text-decoration:none; font-weight:700; border:2px solid var(--color-primario);">
        + Nuevo Usuario
    </a>
</header>

<section class="search-bar-container">
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Buscar por matrícula o nombre..." onkeyup="filterTable()">
    </div>
</section>

<section class="table-container">
    @if(count($usuarios) === 0)
        <p style="text-align:center; color:#aaa; padding:40px 0;">No hay usuarios registrados todavía.</p>
    @else
    <table id="usuariosTable">
        <thead>
            <tr>
                <th>Matrícula</th>
                <th>Nombre Completo</th>
                <th>Correo</th>
                <th>Carrera</th>
                <th>Rol</th>
                <th>Estado</th>
                <th style="text-align:center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($usuarios as $u)
            <tr>
                <td>{{ $u['matricula'] ?? '—' }}</td>
                <td><strong>{{ ($u['nombre'] ?? '') . ' ' . ($u['apellido_p'] ?? '') . ' ' . ($u['apellido_m'] ?? '') }}</strong></td>
                <td>{{ $u['correo'] ?? '—' }}</td>
                <td>{{ $u['nombre_carrera'] }}</td>
                <td>
                    @php $rol = $u['id_rol'] ?? 0; @endphp
                    @if($rol == 1)
                        <span class="badge" style="background: #d1fae5; color: #374151; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 0.85rem;">
                            Alumno
                        </span>
                    @elseif($rol == 2)
                        <span class="badge" style="background: #e0e7ff; color: #3730a3; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 0.85rem;">
                            Docente
                        </span>
                    @elseif($rol == 3)
                        <span class="badge" style="background: #fef3c7; color: #92400e; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 0.85rem;">
                            Encargado
                        </span>
                    @elseif($rol == 4)
                        <span class="badge" style="background: #f3f4f6; color: #065f46; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 0.85rem;">
                            Admin
                        </span>
                    @else
                        <span class="badge" style="background: #fee2e2; color: #991b1b; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 0.85rem;">
                            Sin Rol
                        </span>
                    @endif
                </td>
                <td>
                    @php $activo = $u['activo'] ?? true; @endphp
                    <span class="status {{ $activo ? 'status-approved' : 'status-suspended' }}">
                        {{ $activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </td>
                <td>
                    <div class="actions-wrapper" style="display:flex; gap:10px; justify-content:center;">
                        <a href="{{ route('admin.usuarios.edit', $u['id_usuario'] ?? $u['id']) }}"
                        class="btn-action btn-edit" title="Editar">✎</a>
                        <button class="btn-action btn-delete" title="Eliminar"
                            onclick="openDeleteModal({{ $u['id_usuario'] ?? $u['id'] ?? 0 }}, '{{ $u['nombre'] ?? '' }}')">🗑</button>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</section>

{{-- Modal eliminar --}}
<div id="deleteModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        {{-- Estado: confirmar --}}
        <div id="deleteStateConfirm">
            <div style="font-size:2.5rem; margin-bottom:10px;">⚠️</div>
            <h3 style="color:var(--color-secundario);">¿Eliminar usuario?</h3>
            <p id="deleteText" style="color:#666; margin-top:8px;"></p>
            <div style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
                <button style="background:#eee; color:#333; border:none; padding:8px 20px; border-radius:50px; cursor:pointer; font-weight:700;"
                    onclick="closeDeleteModal()">Cancelar</button>
                <button id="btnConfirmDelete" style="background:#e74c3c; color:white; border:none; padding:8px 20px; border-radius:50px; cursor:pointer; font-weight:700;"
                    onclick="confirmDelete()">Eliminar</button>
            </div>
        </div>
        {{-- Estado: error (usuario con registros vinculados u otro error de API) --}}
        <div id="deleteStateError" style="display:none;">
            <div style="font-size:2.5rem; margin-bottom:10px;">🚫</div>
            <h3 style="color:#c0392b;">No se puede eliminar</h3>
            <p id="deleteErrorText" style="color:#666; margin-top:8px; line-height:1.5;"></p>
            <button style="background:var(--color-secundario); color:white; border:none; padding:10px 28px; border-radius:50px; cursor:pointer; font-weight:700; margin-top:20px;"
                onclick="closeDeleteModal()">Entendido</button>
        </div>
        {{-- Estado: cargando --}}
        <div id="deleteStateLoading" style="display:none;">
            <div style="font-size:2rem; margin-bottom:10px;">⏳</div>
            <p style="color:#666;">Eliminando usuario...</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let deleteId = 0;

    function setDeleteState(state) {
        document.getElementById('deleteStateConfirm').style.display  = state === 'confirm'  ? 'block' : 'none';
        document.getElementById('deleteStateError').style.display    = state === 'error'    ? 'block' : 'none';
        document.getElementById('deleteStateLoading').style.display  = state === 'loading'  ? 'block' : 'none';
    }

    function openDeleteModal(id, nombre) {
        deleteId = id;
        document.getElementById('deleteText').innerText = `Se eliminará a "${nombre}" del sistema permanentemente.`;
        setDeleteState('confirm');
        document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    function confirmDelete() {
        if (deleteId === 0) return;

        setDeleteState('loading');

        fetch(`/admin/usuarios/${deleteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        // Siempre leer el JSON sin importar el status HTTP
        .then(r => r.json().then(data => ({ status: r.status, data })))
        .then(({ status, data }) => {
            if (data.success) {
                closeDeleteModal();
                location.reload();
                return;
            }

            // 422 = la API rechazó la operación (ej: usuario con reservaciones vinculadas)
            // 500 = error inesperado en el servidor
            const msg = data.message
                || (status === 422
                    ? 'Este usuario tiene registros vinculados (reservaciones u otros) y no puede eliminarse directamente. Reasigna o elimina esos registros primero.'
                    : 'Ocurrió un error inesperado. Intenta de nuevo más tarde.');

            document.getElementById('deleteErrorText').innerText = msg;
            setDeleteState('error');
        })
        .catch(() => {
            document.getElementById('deleteErrorText').innerText =
                'No se pudo conectar con el servidor. Verifica tu conexión e intenta de nuevo.';
            setDeleteState('error');
        });
    }

    function filterTable() {
        const filter = document.getElementById("searchInput").value.toUpperCase();
        document.querySelectorAll("#usuariosTable tbody tr").forEach(row => {
            row.style.display = row.textContent.toUpperCase().includes(filter) ? '' : 'none';
        });
    }
</script>
@endsection