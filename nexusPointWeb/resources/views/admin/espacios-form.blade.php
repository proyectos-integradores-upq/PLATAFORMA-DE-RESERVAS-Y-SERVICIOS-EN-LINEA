@extends('layouts.app')

@section('titulo', $espacio ? 'Editar Espacio' : 'Nuevo Espacio')

@section('contenido')
<header class="section-header">
    <div>
        <h1>{{ $espacio ? 'Editar' : 'Nuevo' }} <span class="text-primario">Espacio</span></h1>
        <p>Define las características y el equipamiento del lugar.</p>
    </div>
</header>

<section class="form-container-white">
    <form id="espacioForm" class="grid-form">

        <div class="input-group">
            <label>Nombre del Espacio</label>
            <input type="text" name="nombre" id="nombre_espacio_input" placeholder="Ej: Laboratorio de Cómputo 1"
                   value="{{ $espacio['nombre'] ?? '' }}" required>
        </div>

        <div class="input-group">
            <label>Código del Espacio</label>
            <input type="text" name="codigo_espacio" placeholder="Ej: LAB-01"
                   value="{{ $espacio['codigo_espacio'] ?? '' }}" {{ $espacio ? 'readonly' : 'required' }}>
        </div>

        <div class="input-group">
            <label>Tipo de Espacio</label>
            <select name="id_tipo_espacio" class="custom-select" required>
                <option value="" disabled {{ !$espacio ? 'selected' : '' }}>Selecciona tipo...</option>
                @foreach($tipos as $t)
                    <option value="{{ $t['id_tipo_espacio'] }}"
                        {{ ($espacio['id_tipo_espacio'] ?? '') == $t['id_tipo_espacio'] ? 'selected' : '' }}>
                        {{ $t['nombre_tipo_espacio'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="input-group">
            <label>Ubicación (Nivel)</label>
            <select name="id_piso" class="custom-select" required>
                <option value="" disabled {{ !$espacio ? 'selected' : '' }}>Selecciona ubicación...</option>
                @foreach($pisos as $p)
                    <option value="{{ $p['id_piso'] }}"
                        {{ (isset($espacio['id_piso']) && $espacio['id_piso'] == $p['id_piso']) ? 'selected' : '' }}>
                        {{ $p['nombre_piso'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="input-group">
            <label>Capacidad (Personas)</label>
            <input type="number" name="capacidad" placeholder="Ej: 35"
                   value="{{ $espacio['capacidad'] ?? '' }}" required>
        </div>

        <div class="input-group">
            <label>Estatus</label>
            <select name="id_estado_espacio" class="custom-select">
                <option value="1" {{ ($espacio['id_estado_espacio'] ?? 1) == 1 ? 'selected' : '' }}>Disponible</option>
                <option value="2" {{ ($espacio['id_estado_espacio'] ?? '') == 2 ? 'selected' : '' }}>Mantenimiento</option>
                <option value="3" {{ ($espacio['id_estado_espacio'] ?? '') == 3 ? 'selected' : '' }}>Ocupado</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">Guardar Espacio</button>
            <a href="{{ route('admin.espacios') }}" class="btn-cancelar">Cancelar</a>
        </div>
    </form>
</section>

{{-- Sección de Equipamiento --}}
@if($espacio)
<section class="form-container-white" style="margin-top: 24px;">
    <h3 style="color: var(--color-secundario); font-size: 1rem; font-weight: 700; margin-bottom: 20px;">
        Equipamiento del Espacio
    </h3>

    <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 20px;">
        <select id="selectEquipo" class="custom-select" style="flex: 1; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px;">
            <option value="" disabled selected>Selecciona equipamiento...</option>
            @foreach($tiposEquipamiento as $eq)
                <option value="{{ $eq['id_tipo_equipamiento'] }}">{{ $eq['nombre_tipo_equipamiento'] }}</option>
            @endforeach
        </select>
        <button type="button" id="btnAgregarEquipo" onclick="agregarEquipo()"
            style="background: var(--color-secundario); color: white; border: 2px solid var(--color-primario);
                   padding: 10px 22px; border-radius: 8px; font-weight: 700; cursor: pointer; white-space: nowrap;">
            + Agregar
        </button>
    </div>

    <table id="equipoTable" style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="background: var(--color-secundario); color: white;">
                <th style="padding: 10px 14px; text-align: left; border-radius: 8px 0 0 0;">#</th>
                <th style="padding: 10px 14px; text-align: left;">Equipamiento</th>
                <th style="padding: 10px 14px; text-align: center; border-radius: 0 8px 0 0;">Eliminar</th>
            </tr>
        </thead>
        <tbody id="equipoTbody">
            @forelse($equipamientoAsignado as $eq)
            <tr data-id="{{ $eq['id_espacio_equipamiento'] }}">
                <td style="padding: 10px 14px; border-bottom: 1px solid #eee; color: #aaa; font-size: 0.85rem;">
                    {{ $eq['id_espacio_equipamiento'] }}
                </td>
                <td style="padding: 10px 14px; border-bottom: 1px solid #eee; font-weight: 600;">
                    {{ $eq['nombre_tipo_equipamiento'] }}
                </td>
                <td style="padding: 10px 14px; border-bottom: 1px solid #eee; text-align: center;">
                    <button class="btn-action btn-delete" onclick="eliminarEquipo({{ $eq['id_espacio_equipamiento'] }}, this)" title="Eliminar">🗑</button>
                </td>
            </tr>
            @empty
            <tr id="emptyRow">
                <td colspan="3" style="text-align: center; color: #aaa; padding: 24px;">
                    No hay equipamiento asignado todavía.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</section>
@endif

{{-- Modal éxito --}}
<div id="successModal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <div style="width:70px; height:70px; background:#d4edda; color:#28a745; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:35px; margin:0 auto 20px;">✓</div>
        <h2 id="modalTitle" style="color:var(--color-secundario); margin-bottom:10px;">¡Completado!</h2>
        <p id="modalMessage" style="color:#666;"></p>
        <button onclick="window.location.href='{{ route('admin.espacios') }}'"
            style="background:var(--color-primario); color:var(--color-secundario); border:none; padding:12px 30px; border-radius:50px; cursor:pointer; font-weight:700; margin-top:20px; width:100%;">
            Continuar
        </button>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // --- Debug Logs ---
    const isEdit   = {{ $espacio ? 'true' : 'false' }};
    const espacioId = {{ $espacio ? ($espacio['id_espacio'] ?? $espacio['id'] ?? 0) : 0 }};
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // --- Form Submission ---
    async function enviarEspacio(payload, url, method, btn, intentos = 0) {
        try {
            const r   = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload)
            });
            const res = await r.json();

            if (res.success) {
                document.getElementById('modalTitle').innerText   = isEdit ? 'Espacio Actualizado' : 'Espacio Creado';
                document.getElementById('modalMessage').innerText = isEdit
                    ? 'El espacio ha sido actualizado correctamente.'
                    : 'El nuevo espacio ha sido registrado con éxito.';
                document.getElementById('successModal').style.display = 'flex';
            } else if (!res.success && intentos < 2) {
                // La API puede estar despertando — reintenta hasta 2 veces
                btn.innerText = `Reintentando... (${intentos + 1}/2)`;
                await new Promise(r => setTimeout(r, 5000));
                return enviarEspacio(payload, url, method, btn, intentos + 1);
            } else {
                alert('Error: ' + JSON.stringify(res));
            }
        } catch(err) {
            if (intentos < 2) {
                btn.innerText = `Reintentando... (${intentos + 1}/2)`;
                await new Promise(r => setTimeout(r, 5000));
                return enviarEspacio(payload, url, method, btn, intentos + 1);
            }
            alert('Error de conexión: ' + err.message);
        } finally {
            btn.disabled  = false;
            btn.innerText = 'Guardar Espacio';
        }
    }

    document.getElementById('espacioForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const btn = this.querySelector('.btn-submit');
        btn.disabled  = true;
        btn.innerText = 'Iniciando API...';

        // Despierta la API
        try { await fetch('/admin/ping-api'); } catch(_) {}

        btn.innerText = 'Guardando...';

        const formData = new FormData(this);
        const data     = Object.fromEntries(formData.entries());

        const payload = {
            nombre:            document.getElementById('nombre_espacio_input').value,
            codigo_espacio:    data.codigo_espacio,
            capacidad:         parseInt(data.capacidad),
            id_tipo_espacio:   parseInt(data.id_tipo_espacio),
            id_estado_espacio: parseInt(data.id_estado_espacio),
            id_piso:           parseInt(data.id_piso),
        };

        const url    = isEdit ? `/admin/espacios/${espacioId}` : '/admin/espacios';
        const method = isEdit ? 'PUT' : 'POST';

        await enviarEspacio(payload, url, method, btn);
    });

    // --- Funciones de Equipamiento ---
    function agregarEquipo() {
        const select = document.getElementById('selectEquipo');
        const idVal  = select.value;

        if (!idVal) { 
            alert('Por favor, selecciona un equipamiento de la lista.'); 
            return; 
        }

        const payloadEquip = { id_tipo_equipamiento: parseInt(idVal) };

        fetch(`/admin/espacios/${espacioId}/equipamiento`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': csrfToken 
            },
            body: JSON.stringify(payloadEquip)
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                location.reload(); 
            } else {
                alert('Error: ' + JSON.stringify(res));
            }
        })
        .catch(() => alert('Error crítico de conexión.'));
    }

    function eliminarEquipo(idEquip, btn) {
        if (!confirm('¿Estás seguro de que deseas eliminar este equipamiento?')) return;

        fetch(`/admin/espacios/${espacioId}/equipamiento/${idEquip}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                btn.closest('tr').remove();
                if (document.getElementById('equipoTbody').children.length === 0) {
                    document.getElementById('equipoTbody').innerHTML =
                        `<tr id="emptyRow"><td colspan="3" style="text-align:center; color:#aaa; padding:24px;">No hay equipamiento asignado todavía.</td></tr>`;
                }
            } else {
                alert('No se pudo eliminar el equipamiento: ' + JSON.stringify(res));
            }
        })
        .catch(() => alert('Error de conexión al intentar eliminar.'));
    }
</script>
@endsection