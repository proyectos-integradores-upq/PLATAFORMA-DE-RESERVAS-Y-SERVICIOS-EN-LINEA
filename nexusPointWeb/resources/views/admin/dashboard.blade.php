@extends('layouts.app')

@section('titulo', 'Dashboard')

@section('head')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    {{-- ExcelJS: soporta colores, bordes, negritas (gratis) --}}
    <script src="https://cdn.jsdelivr.net/npm/exceljs@4.3.0/dist/exceljs.min.js"></script>

    <style>
        .btn-export-pdf {
            background-color: #E57373;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-export-pdf:hover    { background-color: #C62828; }
        .btn-export-pdf:disabled { opacity: 0.5; cursor: not-allowed; }

        .btn-export-excel {
            background-color: #66BB6A;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-export-excel:hover    { background-color: #2E7D32; }
        .btn-export-excel:disabled { opacity: 0.5; cursor: not-allowed; }

        .report-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
    </style>
@endsection

@section('contenido')
    <header class="section-header">
        <h1>Panel de <span class="text-primario">Estadísticas</span></h1>
        <p>Bienvenido al sistema de gestión NexusPoint</p>
    </header>

    {{-- Tarjetas --}}
    <section class="stats-grid">
        <div class="stat-card">
            <h3>Reservaciones Totales</h3>
            <p class="stat-number">{{ $totalSolicitudes > 0 ? number_format($totalSolicitudes) : '—' }}</p>
            <span class="stat-desc">{{ $totalSolicitudes > 0 ? 'En el sistema' : 'Sin reservaciones aún' }}</span>
        </div>
        <div class="stat-card">
            <h3>Espacios Disponibles</h3>
            <p class="stat-number">{{ $espaciosActivos > 0 ? $espaciosActivos : '—' }}</p>
            <span class="stat-desc">{{ $espaciosActivos > 0 ? 'En toda la institución' : 'Sin espacios disponibles' }}</span>
        </div>
        <div class="stat-card">
            <h3>Tasa de Aprobación</h3>
            <p class="stat-number">{{ $totalSolicitudes > 0 ? $tasaAprobacion . '%' : '—' }}</p>
            <span class="stat-desc">{{ $totalSolicitudes > 0 ? 'Reservaciones aprobadas' : 'Sin datos aún' }}</span>
        </div>
    </section>

    {{-- Gráficas --}}
    <section class="charts-section">
        <div class="chart-container">
            <h3>Días con más reservaciones</h3>
            @if($totalSolicitudes > 0)
                <canvas id="barChart"></canvas>
            @else
                <p style="color:#aaa;font-size:0.9rem;margin-top:12px;">No hay reservaciones registradas todavía.</p>
            @endif
        </div>

        <div class="chart-container">
            <h3>{{ $totalSolicitudes > 0 ? 'Reservaciones por Edificio' : 'Espacios por Edificio' }}</h3>
            @if(count($ocupacionEdificios) > 0)
                <div style="position:relative;width:100%;max-width:260px;margin:0 auto;">
                    <canvas id="pieChart"></canvas>
                </div>
                <ul id="pieLeyenda" style="list-style:none;padding:0;margin:16px 0 0 0;display:flex;flex-direction:column;gap:6px;"></ul>
            @else
                <p style="color:#aaa;font-size:0.9rem;margin-top:12px;">No hay edificios registrados todavía.</p>
            @endif
        </div>
    </section>

    {{-- Reportes --}}
    <section class="reports-section">
        <div class="report-card">
            <div class="report-info">
                <h3>Historial Mensual — {{ \Carbon\Carbon::now()->locale('es')->isoFormat('MMMM YYYY') }}</h3>
                <p>Reporte de estadísticas generales, actividad por día y ocupación por edificio.</p>
            </div>
            <div class="report-actions">
                <button class="btn-export-pdf" {{ $totalSolicitudes == 0 ? 'disabled' : '' }} onclick="exportarPDF()">
                    Exportar PDF
                </button>
                <button class="btn-export-excel" {{ $totalSolicitudes == 0 ? 'disabled' : '' }} onclick="exportarExcel()">
                    Exportar Excel
                </button>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
<script>
    const MES         = "{{ \Carbon\Carbon::now()->locale('es')->isoFormat('MMMM YYYY') }}";
    const TOTAL_RES   = {{ $totalSolicitudes }};
    const ESP_ACTIVOS = {{ $espaciosActivos }};
    const TASA_APRO   = {{ $tasaAprobacion }};

    const barData = {
        labels: {!! json_encode(array_keys($diasSemana)) !!},
        values: {!! json_encode(array_values($diasSemana)) !!}
    };
    const pieData = {
        labels: {!! json_encode(array_keys($ocupacionEdificios)) !!},
        values: {!! json_encode(array_values($ocupacionEdificios)) !!}
    };
    const paleta = [
        '#2A9D8F','#A8DADC','#B5EAD7','#C3B1E1',
        '#FFDAC1','#FFD6A5','#BDE0FE','#FFADAD',
    ];

    // ── Barras: días ────────────────────────────────────────────
    const barCtx = document.getElementById('barChart');
    if (barCtx) {
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: barData.labels,
                datasets: [{
                    label: 'Reservaciones',
                    data: barData.values,
                    backgroundColor: 'rgba(66,153,225,0.75)',
                    borderColor: 'rgba(66,153,225,1)',
                    borderWidth: 1, borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } } }
            }
        });
    }

    // ── Pastel: edificios ───────────────────────────────────────
    const pieCtx = document.getElementById('pieChart');
    if (pieCtx) {
        const colores = paleta.slice(0, pieData.labels.length);
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: pieData.labels,
                datasets: [{ data: pieData.values, backgroundColor: colores, borderColor: '#fff', borderWidth: 2 }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const t = ctx.dataset.data.reduce((a,b) => a+b, 0);
                                const p = t > 0 ? Math.round((ctx.parsed / t) * 100) : 0;
                                return ` ${ctx.label}: ${ctx.parsed} (${p}%)`;
                            }
                        }
                    }
                }
            }
        });
        const leyenda = document.getElementById('pieLeyenda');
        const total   = pieData.values.reduce((a,b) => a+b, 0);
        pieData.labels.forEach(function(label, i) {
            const pct = total > 0 ? Math.round((pieData.values[i] / total) * 100) : 0;
            const li  = document.createElement('li');
            li.style.cssText = 'display:flex;align-items:flex-start;gap:8px;font-size:0.82rem;line-height:1.4;';
            const c = document.createElement('span');
            c.style.cssText = `display:inline-block;min-width:14px;width:14px;height:14px;background:${colores[i]};border-radius:3px;margin-top:2px;flex-shrink:0;`;
            const t = document.createElement('span');
            t.style.color = '#555';
            t.textContent = `${label} — ${pct}%`;
            li.appendChild(c); li.appendChild(t); leyenda.appendChild(li);
        });
    }

    // ══════════════════════════════════════════════════════════════
    // PDF
    // ══════════════════════════════════════════════════════════════
    function exportarPDF() {
        const { jsPDF } = window.jspdf;
        const doc   = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        const azul  = [30, 58, 100];
        const gris  = [100, 100, 100];
        const negro = [30, 30, 30];

        doc.setFillColor(...azul);
        doc.rect(0, 0, 210, 28, 'F');
        doc.setTextColor(255,255,255);
        doc.setFontSize(18); doc.setFont('helvetica','bold');
        doc.text('NexusPoint — Reporte Mensual', 14, 12);
        doc.setFontSize(10); doc.setFont('helvetica','normal');
        doc.text(MES.charAt(0).toUpperCase() + MES.slice(1), 14, 20);
        doc.text('Generado: ' + new Date().toLocaleDateString('es-MX'), 140, 20);

        let y = 36;
        const seccion = (t) => {
            doc.setTextColor(...negro); doc.setFontSize(13); doc.setFont('helvetica','bold');
            doc.text(t, 14, y); y += 6;
        };
        const tabla = (head, body) => {
            doc.autoTable({
                startY: y, head: [head], body,
                styles: { fontSize: 10, cellPadding: 4 },
                headStyles: { fillColor: azul, textColor: 255, fontStyle: 'bold' },
                alternateRowStyles: { fillColor: [245,247,250] },
                margin: { left: 14, right: 14 },
            });
            y = doc.lastAutoTable.finalY + 10;
        };

        seccion('Resumen General');
        tabla(['Indicador','Valor'], [
            ['Reservaciones Totales', TOTAL_RES],
            ['Espacios Disponibles',  ESP_ACTIVOS],
            ['Tasa de Aprobación',    TASA_APRO + '%'],
        ]);
        seccion('Actividad por Día de la Semana');
        tabla(['Día','Reservaciones'], barData.labels.map((d,i) => [d, barData.values[i]]));
        seccion('Ocupación por Edificio');
        const tot = pieData.values.reduce((a,b) => a+b, 0);
        tabla(['Edificio','Reservaciones','% del Total'],
            pieData.labels.map((e,i) => [e, pieData.values[i], Math.round((pieData.values[i]/tot)*100)+'%'])
        );

        const pages = doc.internal.getNumberOfPages();
        for (let p = 1; p <= pages; p++) {
            doc.setPage(p); doc.setFontSize(8); doc.setTextColor(...gris);
            doc.text('NexusPoint © ' + new Date().getFullYear(), 14, 290);
            doc.text('Página ' + p + ' de ' + pages, 180, 290);
        }
        doc.save('reporte-nexuspoint-' + MES.replace(/ /g,'-') + '.pdf');
    }

    // ══════════════════════════════════════════════════════════════
    // EXCEL con colores reales — ExcelJS
    // ══════════════════════════════════════════════════════════════
    async function exportarExcel() {
    const wb = new ExcelJS.Workbook();
    wb.creator = 'NexusPoint';
    wb.created = new Date();

    const tot      = pieData.values.reduce((a, b) => a + b, 0);
    const mesLabel = MES.charAt(0).toUpperCase() + MES.slice(1);
    const hoy      = new Date().toLocaleDateString('es-MX');

    const C = {
        azulOscuro : 'FF1E3A64',
        azulMedio  : 'FF2A6496',
        azulClaro  : 'FFD6E4F0',
        grisClaro  : 'FFF5F7FA',
        blanco     : 'FFFFFFFF',
        negro      : 'FF1E1E1E',
        grisTexto  : 'FF888888',
        grisLinea  : 'FFD0D5DD',
    };

    const thin     = { style: 'thin', color: { argb: C.grisLinea } };
    const borderAll = { top: thin, bottom: thin, left: thin, right: thin };
    const borderBottom = { bottom: { style: 'medium', color: { argb: C.azulMedio } } };

    function setCell(cell, value, {
        bgColor, fontColor = C.negro, bold = false, size = 11,
        hAlign = 'left', vAlign = 'middle', border = null, italic = false
    } = {}) {
        cell.value     = value;
        cell.font      = { bold, size, color: { argb: fontColor }, italic, name: 'Calibri' };
        if (bgColor) cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: bgColor } };
        cell.alignment = { horizontal: hAlign, vertical: vAlign, wrapText: false };
        if (border) cell.border = border;
    }

    const ws = wb.addWorksheet('Reporte Mensual', { views: [{ showGridLines: false }] });

    ws.getColumn(1).width = 36;  // A
    ws.getColumn(2).width = 18;  // B
    ws.getColumn(3).width = 14;  // C
    ws.getColumn(4).width = 4;   // D (margen)

    let r = 1;

    // ── Encabezado principal (filas 1-2, band azul oscura A-D) ──
    [1, 2].forEach(n => {
        const row = ws.getRow(n);
        row.height = n === 1 ? 28 : 17;
        for (let c = 1; c <= 4; c++) {
            row.getCell(c).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: C.azulOscuro } };
        }
    });

    // Fila 1: título
    setCell(ws.getRow(1).getCell(1), 'NexusPoint — Reporte Mensual', {
        bgColor: C.azulOscuro, fontColor: C.blanco, bold: true, size: 16
    });
    ws.mergeCells(1, 1, 1, 3);

    // Fila 2: mes y fecha
    setCell(ws.getRow(2).getCell(1), mesLabel, {
        bgColor: C.azulOscuro, fontColor: 'FFAAC8E8', size: 10
    });
    setCell(ws.getRow(2).getCell(3), 'Generado: ' + hoy, {
        bgColor: C.azulOscuro, fontColor: 'FFAAC8E8', size: 10, hAlign: 'right'
    });

    // Filas 3-4: espacio
    [3, 4].forEach(n => {
        const row = ws.getRow(n);
        row.height = n === 3 ? 10 : 8;
        for (let c = 1; c <= 4; c++) {
            row.getCell(c).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: C.blanco } };
            row.getCell(c).border = {};
        }
    });
    r = 5;

    // ── Helper: espacio entre secciones ─────────────────────────
    function espacio() {
        const row = ws.getRow(r);
        row.height = 12;
        for (let c = 1; c <= 4; c++) {
            row.getCell(c).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: C.blanco } };
            row.getCell(c).border = {};
        }
        r++;
    }

    // ── Helper: sección ─────────────────────────────────────────
    // cols: hasta qué columna llega la franja (2 = A-B, 3 = A-C)
    function seccion(titulo, cols = 2) {
        const row = ws.getRow(r);
        row.height = 22;
        setCell(row.getCell(1), titulo, {
            bgColor: C.azulClaro, fontColor: C.azulOscuro,
            bold: true, size: 12,
            border: borderBottom
        });
        // Colorear todas las celdas de la franja
        for (let c = 1; c <= cols; c++) {
            row.getCell(c).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: C.azulClaro } };
            row.getCell(c).border = borderBottom;
        }
        ws.mergeCells(r, 1, r, cols);
        r++;
    }

    // ── Helper: tabla ────────────────────────────────────────────
    // cols: número de columnas de datos
    function tabla(columnas, filas, { hasTotal = false } = {}) {
        const numCols = columnas.length;

        // Encabezados
        const headRow = ws.getRow(r);
        headRow.height = 20;
        columnas.forEach((col, i) => {
            setCell(headRow.getCell(i + 1), col, {
                bgColor: C.azulMedio, fontColor: C.blanco,
                bold: true, size: 11, hAlign: 'center',
                border: borderAll
            });
        });
        r++;

        // Filas de datos
        filas.forEach((fila, ri) => {
            const isTotal = hasTotal && ri === filas.length - 1;
            const bg      = isTotal ? C.azulOscuro : (ri % 2 === 0 ? C.grisClaro : C.blanco);
            const row     = ws.getRow(r);
            row.height    = isTotal ? 24 : 18;

            fila.forEach((val, ci) => {
                setCell(row.getCell(ci + 1), val, {
                    bgColor   : bg,
                    fontColor : isTotal ? C.blanco : C.negro,
                    bold      : isTotal,
                    size      : isTotal ? 13 : 10,
                    hAlign    : ci === 0 ? 'left' : 'center',
                    border    : borderAll,
                });
            });
            r++;
        });
    }

    // ══ SECCIÓN 1: Resumen (franja A-B) ═════════════════════════
    seccion('Resumen General', 2);
    tabla(
        ['Indicador', 'Valor'],
        [
            ['Reservaciones Totales', TOTAL_RES],
            ['Espacios Disponibles',  ESP_ACTIVOS],
            ['Tasa de Aprobación',    TASA_APRO + '%'],
        ]
    );

    espacio();

    // ══ SECCIÓN 2: Actividad (franja A-B) ═══════════════════════
    seccion('Actividad por Día de la Semana', 2);
    const totalDias = barData.values.reduce((a, b) => a + b, 0);
    tabla(
        ['Día de la Semana', 'Reservaciones'],
        [
            ...barData.labels.map((d, i) => [d, barData.values[i]]),
            ['Total', totalDias],
        ],
        { hasTotal: true }
    );

    espacio();

    // ══ SECCIÓN 3: Edificios (franja A-C) ═══════════════════════
    seccion('Ocupación por Edificio', 3);
    tabla(
        ['Edificio', 'Reservaciones', '% del Total'],
        [
            ...pieData.labels.map((e, i) => {
                const pct = tot > 0 ? Math.round((pieData.values[i] / tot) * 100) : 0;
                return [e, pieData.values[i], pct + '%'];
            }),
            ['Total', tot, '100%'],
        ],
        { hasTotal: true }
    );

    espacio();

    // ── Pie de página ────────────────────────────────────────────
    const pieRow = ws.getRow(r);
    pieRow.height = 16;
    setCell(pieRow.getCell(1), 'NexusPoint © ' + new Date().getFullYear(), {
        fontColor: C.grisTexto, italic: true, size: 9
    });
    setCell(pieRow.getCell(3), 'Página 1 de 1', {
        fontColor: C.grisTexto, italic: true, size: 9, hAlign: 'right'
    });

    // ── Descargar ─────────────────────────────────────────────────
    const buffer = await wb.xlsx.writeBuffer();
    const blob   = new Blob([buffer], {
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    });
    const url  = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href     = url;
    link.download = 'reporte-nexuspoint-' + MES.replace(/ /g, '-') + '.xlsx';
    
    link.click();
    URL.revokeObjectURL(url);
}
</script>
@endsection