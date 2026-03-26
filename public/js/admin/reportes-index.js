// Reportes Admin - index
document.addEventListener('DOMContentLoaded', function () {
    const hoy = new Date();
    const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    document.getElementById('fecha_inicio').value = primerDiaMes.toISOString().split('T')[0];
    document.getElementById('fecha_fin').value = hoy.toISOString().split('T')[0];
});

function descargar(tipo) {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin    = document.getElementById('fecha_fin').value;

    if (!fechaInicio || !fechaFin) {
        alert('Selecciona un rango de fechas antes de descargar.');
        return;
    }
    if (new Date(fechaInicio) > new Date(fechaFin)) {
        alert('La fecha de inicio debe ser anterior a la fecha fin.');
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                   || document.querySelector('input[name="_token"]')?.value
                   || '';

    const url = window.REPORT_URLS[tipo];
    if (!url) {
        alert('Reporte no reconocido: ' + tipo);
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = url;

    [['fecha_inicio', fechaInicio], ['fecha_fin', fechaFin], ['_token', csrfToken]].forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = name;
        input.value = value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
