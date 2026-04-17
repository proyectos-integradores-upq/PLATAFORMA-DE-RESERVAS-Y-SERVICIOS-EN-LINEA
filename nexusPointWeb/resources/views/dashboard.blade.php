document.addEventListener('DOMContentLoaded', () => {

    // 1. Modal de Descarga
    const modal = document.getElementById('modalDescarga');
    const btnDownload = document.querySelector('.btn-download');

    if (btnDownload && modal && !btnDownload.disabled) {
        btnDownload.addEventListener('click', () => {
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.style.display = 'none';
                alert("El reporte PDF se ha generado correctamente.");
            }, 3000);
        });
    }

    // 2. Gráfica de Barras — usa datos reales pasados desde el blade
    const ctxBar = document.getElementById('barChart');
    if (ctxBar && typeof barData !== 'undefined') {
        new Chart(ctxBar.getContext('2d'), {
            type: 'bar',
            data: {
                labels: barData.labels,
                datasets: [{
                    label: 'Solicitudes',
                    data: barData.values,
                    backgroundColor: '#3eb5bc',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    // 3. Gráfica de Pastel — usa datos reales pasados desde el blade
    const ctxPie = document.getElementById('pieChart');
    if (ctxPie && typeof pieData !== 'undefined' && pieData.values.length > 0) {
        new Chart(ctxPie.getContext('2d'), {
            type: 'pie',
            data: {
                labels: pieData.labels,
                datasets: [{
                    data: pieData.values,
                    backgroundColor: ['#283566', '#3eb5bc', '#1a234a', '#D9B18F', '#5c7cfa', '#f76707'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
});