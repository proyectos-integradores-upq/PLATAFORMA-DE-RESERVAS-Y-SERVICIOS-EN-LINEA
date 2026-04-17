@extends('layouts.app')

@section('titulo', 'Reportes')

@section('head')
    {{-- Librerías necesarias para exportar --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/exceljs@4.3.0/dist/exceljs.min.js"></script>
@endsection

@section('contenido')
<header class="section-header">
    <h1>Reportes y <span class="text-primario">Exportaciones</span></h1>
    <p style="color:var(--color-secundario)">Descarga informes detallados y analiza el rendimiento.</p>
</header>

<section style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:20px; margin-top:20px;">
    @php
        // Estos datos normalmente vendrían del controlador, pero los mantenemos aquí como pediste
        $reportesRapidos = [
            [
                'id' => 'mensual_solicitudes',
                'titulo' => 'Reporte Mensual de Solicitudes',
                'desc'   => 'Historial completo de todas las solicitudes realizadas por docentes y personal.',
                'periodo'=> 'Periodo: ' . \Carbon\Carbon::now()->locale('es')->isoFormat('MMMM YYYY')
            ],
            [
                'id' => 'ocupacion_edificio',
                'titulo' => 'Ocupación por Edificio',
                'desc'   => 'Estadísticas detalladas de uso, horas pico y ocupación por niveles académicos.',
                'periodo'=> 'Periodo: ' . \Carbon\Carbon::now()->year . ' — Q' . \Carbon\Carbon::now()->quarter
            ],
            [
                'id' => 'analisis_tendencias',
                'titulo' => 'Análisis de Tendencias',
                'desc'   => 'Patrones de uso y predicciones basadas en datos históricos.',
                'periodo'=> 'Periodo: ' . \Carbon\Carbon::now()->locale('es')->isoFormat('MMMM YYYY')
            ],
        ];
    @endphp

    @foreach($reportesRapidos as $r)
    <div style="background:white; padding:25px; border-radius:15px; box-shadow:0 4px 15px rgba(0,0,0,0.05); display:flex; flex-direction:column; justify-content:space-between; border-top:4px solid var(--color-primario); transition:transform 0.3s;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform=''">
        <div>
            <h3 style="color:var(--color-secundario); margin-bottom:10px; font-size:1.1rem;">{{ $r['titulo'] }}</h3>
            <p style="font-size:0.9rem; color:#666; margin-bottom:15px; line-height:1.4;">{{ $r['desc'] }}</p>
            <span style="font-size:0.75rem; font-weight:700; color:var(--color-primario); text-transform:uppercase;">{{ $r['periodo'] }}</span>
        </div>
        <div style="display:flex; gap:10px; margin-top:20px;">
            <button onclick="exportarRapido('pdf', '{{ $r['titulo'] }}')"
                style="flex:1; background:#FFEBEE; color:#C62828; border:1px solid #FFCDD2; padding:8px; border-radius:6px; font-weight:600; font-size:0.8rem; cursor:pointer;">
                PDF
            </button>
            <button onclick="exportarRapido('excel', '{{ $r['titulo'] }}')"
                style="flex:1; background:#E8F5E9; color:#2E7D32; border:1px solid #C8E6C9; padding:8px; border-radius:6px; font-weight:600; font-size:0.8rem; cursor:pointer;">
                Excel
            </button>
        </div>
    </div>
    @endforeach
</section>

<section class="form-container-white" style="margin-top:40px;">
    <h2 style="margin-bottom:20px; color:var(--color-secundario);">Generar Reporte Personalizado</h2>
    <form class="grid-form" id="formPersonalizado">
        <div class="input-group">
            <label>Tipo de Reporte</label>
            <select class="custom-select" id="tipo_reporte">
                <option value="Solicitudes por Usuario">Solicitudes por Usuario</option>
                <option value="Uso de Laboratorios">Uso de Laboratorios</option>
                <option value="Inventario de Equipamiento">Inventario de Equipamiento</option>
                <option value="Incidencias Reportadas">Incidencias Reportadas</option>
            </select>
        </div>
        <div class="input-group">
            <label>Formato de Salida</label>
            <select class="custom-select" id="formato_salida">
                <option value="pdf">PDF (.pdf)</option>
                <option value="excel">Excel (.xlsx)</option>
            </select>
        </div>
        <div class="input-group">
            <label>Fecha Inicio</label>
            <input type="date" id="fecha_inicio" required>
        </div>
        <div class="input-group">
            <label>Fecha Fin</label>
            <input type="date" id="fecha_fin" required>
        </div>
        <div class="form-actions" style="grid-column:span 2; display:flex; justify-content:center; margin-top:20px;">
            <button type="submit"
                style="display:inline-flex; align-items:center; background:var(--color-secundario); color:white; padding:12px 30px; border-radius:50px; border:2px solid var(--color-primario); font-weight:700; cursor:pointer;">
                Generar Reporte
            </button>
        </div>
    </form>
</section>

{{-- Modal de Carga/Éxito --}}
<div id="modalReporte" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <h3 id="modalTitle" style="color:var(--color-secundario); margin-bottom:15px;">Procesando...</h3>
        <p id="modalMsg" style="color:#666; margin-bottom:20px;">Generando documento, por favor espere.</p>
        <button id="btnCerrarModal" onclick="document.getElementById('modalReporte').style.display='none'"
            style="display:none; align-items:center; background:var(--color-secundario); color:white; padding:12px 30px; border-radius:50px; border:2px solid var(--color-primario); font-weight:700; cursor:pointer;">
            Aceptar
        </button>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Configuración de colores (reutilizada de tu dashboard)
    const C = {
        azulOscuro: 'FF1E3A64',
        azulMedio: 'FF2A6496',
        azulClaro: 'FFD6E4F0',
        blanco: 'FFFFFFFF',
        negro: 'FF1E1E1E'
    };

    // Formulario personalizado
    document.getElementById('formPersonalizado').addEventListener('submit', function(e) {
        e.preventDefault();
        const tipo    = document.getElementById('tipo_reporte').value;
        const formato = document.getElementById('formato_salida').value;
        const inicio  = document.getElementById('fecha_inicio').value;
        const fin     = document.getElementById('fecha_fin').value;
        const titulo  = `${tipo} (${inicio} a ${fin})`;

        // Datos según el tipo de reporte seleccionado
        const mapasDatos = {
            'Solicitudes por Usuario': [
                { concepto: 'Total de solicitudes',      info: `Periodo ${inicio} – ${fin}`,    valor: 'Ver sistema'    },
                { concepto: 'Usuarios activos',          info: 'Con al menos 1 solicitud',      valor: 'Ver sistema'    },
                { concepto: 'Promedio por usuario',      info: 'Solicitudes / usuario',         valor: 'Ver sistema'    },
                { concepto: 'Usuario más activo',        info: 'Mayor número de reservas',      valor: 'Ver sistema'    },
                { concepto: 'Tasa de aprobación',        info: 'Solicitudes aceptadas',         valor: 'Ver sistema'    },
            ],
            'Uso de Laboratorios': [
                { concepto: 'Total de usos registrados', info: `Periodo ${inicio} – ${fin}`,    valor: 'Ver sistema'    },
                { concepto: 'Laboratorio más usado',     info: 'Mayor cantidad de reservas',    valor: 'Ver sistema'    },
                { concepto: 'Laboratorio menos usado',   info: 'Menor ocupación registrada',    valor: 'Ver sistema'    },
                { concepto: 'Horas promedio de uso',     info: 'Por sesión de laboratorio',     valor: 'Ver sistema'    },
                { concepto: 'Horario pico',              info: 'Mayor demanda del día',         valor: 'Ver sistema'    },
            ],
            'Inventario de Equipamiento': [
                { concepto: 'Total de equipos',          info: 'Registrados en el sistema',     valor: 'Ver sistema'    },
                { concepto: 'Equipos disponibles',       info: 'En buen estado',                valor: 'Ver sistema'    },
                { concepto: 'Equipos en mantenimiento',  info: 'Fuera de servicio temporal',    valor: 'Ver sistema'    },
                { concepto: 'Tipo más común',            info: 'Categoría predominante',        valor: 'Ver sistema'    },
                { concepto: 'Espacios sin equipamiento', info: 'Sin ningún equipo asignado',    valor: 'Ver sistema'    },
            ],
            'Incidencias Reportadas': [
                { concepto: 'Total de incidencias',      info: `Periodo ${inicio} – ${fin}`,    valor: 'Ver sistema'    },
                { concepto: 'Resueltas',                 info: 'Cerradas correctamente',        valor: 'Ver sistema'    },
                { concepto: 'Pendientes',                info: 'Sin resolución aún',            valor: 'Ver sistema'    },
                { concepto: 'Espacio con más reportes',  info: 'Mayor número de incidencias',   valor: 'Ver sistema'    },
                { concepto: 'Tiempo promedio resolución',info: 'Desde reporte hasta cierre',    valor: 'Ver sistema'    },
            ],
        };

        const datos = mapasDatos[tipo] ?? [];

        if (formato === 'pdf') generarPDF(titulo, datos);
        else generarExcel(titulo, datos);
    });

    function exportarRapido(formato, titulo) {
        let datos = [];

        if (titulo.includes("Mensual de Solicitudes")) {
            datos = [
                { concepto: 'Total Solicitudes',      info: 'Mes actual',                      valor: '145'       },
                { concepto: 'Aprobadas',               info: 'Solicitudes confirmadas',          valor: '118'       },
                { concepto: 'Pendientes',              info: 'En espera de revisión',            valor: '12'        },
                { concepto: 'Rechazadas',              info: 'No autorizadas',                   valor: '15'        },
                { concepto: 'Espacio más solicitado',  info: 'Laboratorio de Cómputo 1',         valor: '28 usos'   },
                { concepto: 'Día con más solicitudes', info: 'Martes',                           valor: '34 reqs'   },
                { concepto: 'Horario pico',            info: 'Turno matutino',                   valor: '09:00 AM'  },
            ];
        } else if (titulo.includes("Ocupación")) {
            datos = [
                { concepto: 'Edificio A',         info: 'Piso 1 y 2',       valor: '85% Ocupado'  },
                { concepto: 'Edificio B',         info: 'Planta Baja',      valor: '40% Ocupado'  },
                { concepto: 'Edificio C',         info: 'Todos los pisos',  valor: '60% Ocupado'  },
                { concepto: 'Biblioteca',         info: 'Sala de estudio',  valor: '72% Ocupado'  },
                { concepto: 'CAPTA',              info: 'Laboratorios',     valor: '55% Ocupado'  },
                { concepto: 'CIDEA',              info: 'Auditorio y salas',valor: '30% Ocupado'  },
                { concepto: 'Total de espacios',  info: 'Registrados',      valor: '42 espacios'  },
                { concepto: 'Espacios activos',   info: 'Disponibles hoy',  valor: '38 espacios'  },
            ];
        } else {
            datos = [
                { concepto: 'Tendencia mensual',      info: 'Crecimiento de solicitudes',    valor: '+15%'           },
                { concepto: 'Horario pico',           info: 'Mayor demanda del día',         valor: '10:00 – 12:00'  },
                { concepto: 'Día más activo',         info: 'Promedio histórico',            valor: 'Martes'         },
                { concepto: 'Tipo de espacio líder',  info: 'Más reservado',                 valor: 'Laboratorios'   },
                { concepto: 'Tasa de aprobación',     info: 'Solicitudes aceptadas',         valor: '81%'            },
                { concepto: 'Tiempo promedio',        info: 'Resolución de solicitud',       valor: '4.2 hrs'        },
            ];
        }

        if (formato === 'pdf') generarPDF(titulo, datos);
        else generarExcel(titulo, datos);
    }

    // Lógica para PDF
    function generarPDF(titulo, datos = []) {
        showModal("Generando PDF", "Preparando el archivo para descarga...");

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        // Encabezado
        doc.setFillColor(30, 58, 100);
        doc.rect(0, 0, 210, 25, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(16);
        doc.text('NexusPoint — Reporte de Sistema', 14, 15);

        // Metadatos
        doc.setTextColor(30, 30, 30);
        doc.setFontSize(12);
        doc.text('Título: ' + titulo, 14, 38);
        doc.text('Fecha de generación: ' + new Date().toLocaleDateString(), 14, 46);

        // Tabla con datos reales o placeholder
        const body = datos.length > 0
            ? datos.map(d => [d.concepto, d.info, d.valor])
            : [['Sin datos', 'No se encontraron registros para este periodo', '—']];

        doc.autoTable({
            startY: 56,
            head: [['Concepto', 'Información', 'Valor / Detalle']],
            body: body,
            headStyles: { fillColor: [30, 58, 100] },
            alternateRowStyles: { fillColor: [214, 228, 240] },
            styles: { fontSize: 10 }
        });

        doc.save(`${titulo.replace(/ /g, '_')}.pdf`);
        finishModal();
    }

    // Lógica para EXCEL
    async function generarExcel(titulo, datos = []) {
        showModal("Generando Excel", "Organizando celdas y estilos...");

        const wb = new ExcelJS.Workbook();
        const ws = wb.addWorksheet('Reporte');

        // Configuración de columnas
        ws.columns = [
            { key: 'col1', width: 40 },
            { key: 'col2', width: 30 },
            { key: 'col3', width: 20 }
        ];

        // Encabezado principal
        const headerRow = ws.getRow(1);
        headerRow.height = 25;
        headerRow.getCell(1).value = 'NexusPoint — ' + titulo;
        headerRow.getCell(1).font = { bold: true, size: 14, color: { argb: 'FFFFFFFF' } };
        headerRow.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: C.azulOscuro } };
        headerRow.getCell(1).alignment = { vertical: 'middle' };
        ws.mergeCells(1, 1, 1, 3);

        ws.addRow(['Fecha de Generación:', new Date().toLocaleDateString()]);
        ws.addRow([]); // Espacio en blanco

        // Sub-encabezados de tabla
        const subHeader = ws.addRow(['Concepto', 'Información', 'Valor / Detalle']);
        subHeader.eachCell(cell => {
            cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: C.azulMedio } };
            cell.border = { top:{style:'thin'}, left:{style:'thin'}, bottom:{style:'thin'}, right:{style:'thin'} };
        });

        // Filas de datos reales
        if (datos.length > 0) {
            datos.forEach(item => {
                ws.addRow([item.concepto, item.info, item.valor]);
            });
        } else {
            ws.addRow(['Sin datos', 'No se encontraron registros para este periodo', '—']);
        }

        // Bordes en las filas de datos (a partir de la fila 5)
        ws.eachRow((row, rowNumber) => {
            if (rowNumber > 4) {
                row.eachCell(cell => {
                    cell.border = { top:{style:'thin'}, left:{style:'thin'}, bottom:{style:'thin'}, right:{style:'thin'} };
                });
            }
        });

        const buffer = await wb.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${titulo.replace(/ /g, '_')}.xlsx`;
        link.click();

        finishModal();
    }

    // Helpers para el Modal
    function showModal(titulo, msg) {
        document.getElementById('modalTitle').innerText = titulo;
        document.getElementById('modalMsg').innerText = msg;
        document.getElementById('btnCerrarModal').style.display = 'none';
        document.getElementById('modalReporte').style.display = 'flex';
    }

    function finishModal() {
        document.getElementById('modalTitle').innerText = "¡Listo!";
        document.getElementById('modalMsg').innerText = "El documento se ha descargado correctamente.";
        document.getElementById('btnCerrarModal').style.display = 'inline-flex';
    }
</script>
@endsection