// Gestión de Autorizadores - index_agrupado

// Calcular estadísticas mejoradas
document.addEventListener('DOMContentLoaded', function() {
    const autorizadores = document.querySelectorAll('.autorizador-item');
    const totalAutorizadores = autorizadores.length;

    let totalCentros = 0;
    let totalRegistrosDuplicados = 0;

    autorizadores.forEach(item => {
        const countElement = item.querySelector('.centros-count');
        if (countElement) {
            totalCentros += parseInt(countElement.textContent);
        }

        // Contar registros duplicados
        const registrosElement = item.querySelector('.badge-sm');
        if (registrosElement) {
            const registrosText = registrosElement.textContent;
            const match = registrosText.match(/(\d+) registros/);
            if (match) {
                totalRegistrosDuplicados += parseInt(match[1]) - 1; // -1 porque el original no es duplicado
            }
        }
    });

    const promedioCentros = totalAutorizadores > 0 ? (totalCentros / totalAutorizadores).toFixed(1) : 0;

    document.getElementById('total-autorizadores').textContent = totalAutorizadores;
    document.getElementById('total-centros').textContent = totalCentros;
    document.getElementById('promedio-centros').textContent = promedioCentros;

    // Mostrar alerta si hay duplicados
    if (totalRegistrosDuplicados > 0) {
        mostrarAlertaDuplicados(totalRegistrosDuplicados);
    }
});

// Búsqueda mejorada con resaltado
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const items = document.querySelectorAll('.autorizador-item');
    let visibleCount = 0;

    items.forEach(item => {
        const name = item.dataset.name || '';
        const email = item.dataset.email || '';
        const centrosText = item.querySelector('.centros-list')?.textContent.toLowerCase() || '';

        if (name.includes(searchTerm) || email.includes(searchTerm) || centrosText.includes(searchTerm)) {
            item.style.display = '';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    // Actualizar contador de resultados
    actualizarContadorResultados(visibleCount, items.length);
});

// Función para consolidar autorizador
function consolidarAutorizador(email, nombre) {
    if (confirm(`¿Deseas consolidar todos los registros duplicados de ${nombre}?\n\nEsto combinará todos los centros de costo y permisos en un solo registro.`)) {
        // Aquí iría la llamada AJAX para consolidar
        fetch('/admin/autorizadores/consolidar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                email: email,
                accion: 'consolidar'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarExito('Registros consolidados exitosamente');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                mostrarError(data.error || 'Error al consolidar registros');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Error de conexión al consolidar registros');
        });
    }
}

// Mostrar alerta de duplicados
function mostrarAlertaDuplicados(count) {
    const alertHtml = `
        <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Atención:</strong> Se detectaron ${count} registros duplicados en el sistema.
            <small class="d-block mt-1">Usa el botón <i class="fas fa-compress-arrows-alt"></i> para consolidar registros del mismo autorizador.</small>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    document.querySelector('.container').insertAdjacentHTML('afterbegin', alertHtml);
}

// Actualizar contador de resultados de búsqueda
function actualizarContadorResultados(visible, total) {
    let contadorElement = document.getElementById('resultados-contador');
    if (!contadorElement) {
        contadorElement = document.createElement('small');
        contadorElement.id = 'resultados-contador';
        contadorElement.className = 'text-muted ms-2';
        document.getElementById('searchInput').parentNode.appendChild(contadorElement);
    }

    if (visible < total) {
        contadorElement.textContent = `${visible} de ${total} resultados`;
        contadorElement.style.display = 'inline';
    } else {
        contadorElement.style.display = 'none';
    }
}

// Mostrar mensajes de éxito y error
function mostrarExito(mensaje) {
    mostrarToast(mensaje, 'success');
}

function mostrarError(mensaje) {
    mostrarToast(mensaje, 'danger');
}

function mostrarToast(mensaje, tipo) {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${tipo} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${mensaje}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();

    // Limpiar después de mostrar
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Efecto hover en las tarjetas de estadísticas
document.querySelectorAll('.stats-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
    });

    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});

// Actualizar filtros activos basado en URL
(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const filtro = urlParams.get('filtro');

    if (filtro) {
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        const activeBtn = document.querySelector(
            `.filter-btn[href*="filtro=${filtro}"]`
        );
        if (activeBtn) {
            activeBtn.classList.add('active');
        }
    }
})();

// Expandir/colapsar lista de centros para grupos grandes
document.querySelectorAll('.centros-list').forEach(lista => {
    const centros = lista.querySelectorAll('.centro-item');
    if (centros.length > 6) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'btn btn-link btn-sm mt-2';
        toggleBtn.textContent = `Ver todos los ${centros.length} centros`;

        // Ocultar centros después del 6to
        centros.forEach((centro, index) => {
            if (index >= 6) {
                centro.style.display = 'none';
            }
        });

        toggleBtn.addEventListener('click', function() {
            const ocultos = Array.from(centros).slice(6);
            const mostrarTodos = ocultos[0].style.display === 'none';

            ocultos.forEach(centro => {
                centro.style.display = mostrarTodos ? '' : 'none';
            });

            this.textContent = mostrarTodos ? 'Ver menos' : `Ver todos los ${centros.length} centros`;
        });

        lista.appendChild(toggleBtn);
    }
});
