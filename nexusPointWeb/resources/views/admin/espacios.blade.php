@extends('layouts.app')

@section('titulo', 'Espacios')

@section('contenido')
<header class="section-header" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1>Gestión de <span class="text-primario">Espacios</span></h1>
        <p>Administra los espacios disponibles en la institución.</p>
    </div>
    <a href="{{ route('admin.espacios.create') }}"
    style="display:inline-flex; align-items:center; background:var(--color-secundario); color:white; padding:12px 28px; border-radius:50px; text-decoration:none; font-weight:700; border:2px solid var(--color-primario);">
        + Nuevo Espacio
    </a>
</header>

<section class="search-bar-container">
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="Buscar por nombre o edificio..." onkeyup="filterTable()">
    </div>
</section>

<section class="table-container">
    @if(count($espacios) === 0)
        <p style="text-align:center; color:#aaa; padding:40px 0;">No hay espacios registrados todavía.</p>
    @else
    <table id="espaciosTable">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Edificio</th>
                <th>Capacidad</th>
                <th>Estatus</th>
                <th style="text-align:center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($espacios as $e)
            <tr>
                <td><strong>{{ $e['nombre'] ?? '—' }}</strong></td>
                <td>{{ $e['tipo'] ?? '—' }}</td>
                <td>{{ $e['edificio'] ?? '—' }}</td>
                <td>{{ $e['capacidad'] ?? '—' }}</td>
                <td>
                    @php $est = $e['estatus'] ?? 'Disponible'; @endphp
                    <span class="status {{ $est === 'Disponible' ? 'status-approved' : ($est === 'Mantenimiento' ? 'status-pending' : 'status-suspended') }}">
                        {{ $est }}
                    </span>
                </td>
                <td>
                    <div class="actions-wrapper" style="display:flex; gap:10px; justify-content:center;">
                        <a href="{{ route('admin.espacios.edit', $e['id_espacio']) }}" class="btn-action btn-edit" title="Editar">✎</a>
                        <button class="btn-action btn-delete" title="Eliminar"
                            onclick="openDeleteModal({{ $e['id_espacio'] ?? $e['id'] }}, '{{ $e['nombre'] ?? '' }}')">🗑</button>
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
        <div style="font-size:2.5rem; margin-bottom:10px;">🗑</div>
        <h3 style="color:var(--color-secundario);">¿Eliminar espacio?</h3>
        <p id="deleteText" style="color:#666; margin-top:8px;"></p>
        <div style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
            <button style="background:#eee; color:#333; border:none; padding:8px 20px; border-radius:50px; cursor:pointer; font-weight:700;"
                onclick="closeDeleteModal()">Cancelar</button>
            <button style="background:#e74c3c; color:white; border:none; padding:8px 20px; border-radius:50px; cursor:pointer; font-weight:700;"
                onclick="confirmDelete()">Eliminar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let deleteId = 0;

    function openDeleteModal(id, nombre) {
        deleteId = id;
        document.getElementById('deleteText').innerText = `Se eliminará "${nombre}" permanentemente.`;
        document.getElementById('deleteModal').style.display = 'flex';
    }
    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
    function confirmDelete() {
        fetch(`/admin/espacios/${deleteId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(r => r.json())
        .then(data => {
            closeDeleteModal();
            if (data.success) location.reload();
            else alert('Error al eliminar');
        });
    }
    function filterTable() {
        const filter = document.getElementById("searchInput").value.toUpperCase();
        document.querySelectorAll("#espaciosTable tbody tr").forEach(row => {
            const text = row.textContent;
            row.style.display = text.toUpperCase().includes(filter) ? '' : 'none';
        });
    }
</script>
@endsection
