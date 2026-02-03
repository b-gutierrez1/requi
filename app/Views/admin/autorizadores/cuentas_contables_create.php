<?php 
use App\Helpers\View;
use App\Helpers\Session;
use App\Middlewares\CsrfMiddleware;

$title = 'Crear Autorizador por Cuenta Contable';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header { background: linear-gradient(135deg, #6f42c1 0%, #5a2d91 100%); color: white; padding: 2rem 0; margin-bottom: 2rem; border-radius: 0 0 15px 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
    .form-container { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); padding: 2rem; border: 1px solid #e9ecef; }
    .form-section { margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e9ecef; }
    .form-section:last-child { border-bottom: none; margin-bottom: 0; }
    .section-title { font-size: 1.2rem; font-weight: 600; color: #6f42c1; margin-bottom: 1rem; display: flex; align-items: center; }
    .section-title i { margin-right: 10px; }
    .form-control:focus, .form-select:focus { border-color: #6f42c1; box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25); }
    .btn-save { background: linear-gradient(135deg, #6f42c1, #5a2d91); border: none; border-radius: 8px; padding: 12px 30px; color: white; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3); }
    .btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(111, 66, 193, 0.4); color: white; }
    .cuenta-item { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 8px; padding: 1rem; margin-bottom: 0.5rem; transition: all 0.3s ease; cursor: pointer; }
    .cuenta-item:hover { border-color: #6f42c1; background: #f3e5f5; }
    .cuenta-item.selected { border-color: #6f42c1; background: #f3e5f5; }
    .cuenta-item.hidden { display: none; }
    .search-highlight { background-color: #fff3cd; font-weight: bold; }
    .input-group-text.bg-white { border-right: 0; }
    .form-control.border-start-0 { border-left: 0; }
    .form-control:focus.border-start-0 { border-left: 0; box-shadow: none; }
</style>

<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0"><i class="fas fa-calculator me-3"></i><?= View::e($title) ?></h1>
                <p class="mb-0 opacity-75">Configurar autorizador para cuentas contables específicas</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?= url('/admin/autorizadores/cuentas-contables') ?>" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Volver a la Lista
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="form-container">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Información:</strong> Los autorizadores por cuenta contable permiten asignar personas específicas para autorizar requisiciones según las cuentas contables utilizadas.
                </div>
                
                <?php if (!empty($autorizadores) && !empty($cuentas_contables)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Datos cargados:</strong> <?= count($autorizadores) ?> autorizadores y <?= count($cuentas_contables) ?> cuentas contables disponibles.
                    </div>
                <?php elseif (empty($autorizadores) || empty($cuentas_contables)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Atención:</strong> 
                        <?php if (empty($autorizadores)): ?>No se encontraron personas autorizadas en el sistema. <?php endif; ?>
                        <?php if (empty($cuentas_contables)): ?>No se encontraron cuentas contables activas. <?php endif; ?>
                        Usando datos de ejemplo.
                    </div>
                <?php endif; ?>

                <form action="<?= url('/admin/autorizadores/cuentas-contables') ?>" method="POST" id="cuentaContableForm">
                    <?= CsrfMiddleware::field() ?>
                    
                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-user"></i>Información del Autorizador</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="autorizador_email" class="form-label">Email del Autorizador <span class="text-danger">*</span></label>
                                <select name="autorizador_email" id="autorizador_email" class="form-select" required>
                                    <option value="">Seleccionar autorizador...</option>
                                    <?php if (!empty($autorizadores)): ?>
                                        <?php foreach ($autorizadores as $auth): ?>
                                            <option value="<?= View::e($auth['email'] ?? '') ?>">
                                                <?= View::e($auth['nombre'] ?? '') ?> (<?= View::e($auth['email'] ?? '') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-list-alt"></i>Cuentas Contables Autorizadas</h3>
                        <label class="form-label">Seleccionar Cuentas Contables <span class="text-danger">*</span></label>
                        <small class="text-muted d-block mb-3">Seleccione una o más cuentas contables que este autorizador podrá autorizar</small>
                        
                        <!-- Controles de búsqueda y selección -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" id="buscador_cuentas" 
                                           placeholder="Buscar por código o nombre de cuenta..." 
                                           oninput="filtrarCuentas()">
                                    <button type="button" class="btn btn-outline-secondary" onclick="limpiarBusqueda()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary" onclick="seleccionarTodas()" id="btn_seleccionar_todas">
                                        <i class="fas fa-check-square me-2"></i>Seleccionar Todas
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="deseleccionarTodas()" id="btn_deseleccionar_todas">
                                        <i class="fas fa-square me-2"></i>Deseleccionar Todas
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contador de selección -->
                        <div class="mb-3">
                            <small class="text-info">
                                <i class="fas fa-info-circle me-1"></i>
                                Seleccionadas: <strong id="contador_seleccionadas">0</strong> de <strong id="contador_total">0</strong> cuentas
                            </small>
                        </div>
                        
                        <div class="row" id="cuentas_container">
                            <?php 
                            // Usar datos reales de cuentas contables si están disponibles
                            if (!empty($cuentas_contables)) {
                                $cuentasDisponibles = array_map(function($cuenta) {
                                    return [
                                        'id' => $cuenta['id'],
                                        'codigo' => $cuenta['codigo'],
                                        'nombre' => $cuenta['descripcion']
                                    ];
                                }, $cuentas_contables);
                            } else {
                                // Datos de ejemplo si no hay datos reales disponibles
                                $cuentasDisponibles = [
                                    ['id' => 1, 'codigo' => '1101', 'nombre' => 'Caja General'],
                                    ['id' => 2, 'codigo' => '1102', 'nombre' => 'Bancos Nacionales'],
                                    ['id' => 3, 'codigo' => '1103', 'nombre' => 'Bancos Extranjeros'],
                                    ['id' => 4, 'codigo' => '2101', 'nombre' => 'Proveedores Nacionales'],
                                    ['id' => 5, 'codigo' => '2102', 'nombre' => 'Proveedores Extranjeros'],
                                    ['id' => 6, 'codigo' => '5101', 'nombre' => 'Gastos de Oficina'],
                                    ['id' => 7, 'codigo' => '5102', 'nombre' => 'Gastos de Viaje'],
                                    ['id' => 8, 'codigo' => '5103', 'nombre' => 'Gastos de Capacitación']
                                ];
                            }
                            ?>
                            
                            <?php foreach ($cuentasDisponibles as $cuenta): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="cuenta-item" onclick="toggleCuenta('cuenta_<?= View::e($cuenta['codigo']) ?>')">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <strong><?= View::e($cuenta['codigo']) ?></strong>
                                                <div class="text-muted small"><?= View::e($cuenta['nombre']) ?></div>
                                            </div>
                                            <input type="checkbox" name="cuentas_contables[]" value="<?= View::e($cuenta['id']) ?>" 
                                                   id="cuenta_<?= View::e($cuenta['codigo']) ?>" class="form-check-input"
                                                   data-codigo="<?= View::e($cuenta['codigo']) ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-ban"></i>
                            Exclusiones de Centros de Costo
                        </h3>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Atención:</strong> Los centros de costo seleccionados abajo serán <strong>excluidos</strong> del flujo de autorización. 
                            Si una requisición usa uno de estos centros de costo, este autorizador NO será llamado.
                        </div>
                        
                        <label class="form-label">Centros de Costo a Excluir (Opcional)</label>
                        <small class="text-muted d-block mb-3">Seleccione los centros de costo que NO requieren autorización de esta persona</small>
                        
                        <div id="centros_exclusion_container" style="border: 2px solid #e9ecef; border-radius: 8px; padding: 15px; max-height: 300px; overflow-y: auto; background: #f8f9fa;">
                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="select_all_exclusiones">
                                    <i class="fas fa-check-double"></i> Seleccionar Todos
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="deselect_all_exclusiones">
                                    <i class="fas fa-times"></i> Deseleccionar Todos
                                </button>
                            </div>
                            <div id="centros_exclusion_list">
                                <p class="text-muted">Cargando centros de costo...</p>
                            </div>
                        </div>
                        <div id="exclusiones_count" class="mt-2" style="display: none;">
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-ban"></i> <span id="excluded_count">0</span> centros excluidos
                            </span>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-info"></i>Información Adicional</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                                <input type="date" name="fecha_fin" id="fecha_fin" class="form-control">
                                <small class="text-muted">Opcional - dejar vacío para autorización permanente</small>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea name="observaciones" id="observaciones" class="form-control" rows="3" 
                                          placeholder="Notas adicionales sobre esta autorización (opcional)"></textarea>
                            </div>
                        </div>
                        <div class="switch-container mt-3">
                            <div class="flex-grow-1">
                                <label class="switch-label" for="activo">
                                    <i class="fas fa-power-off me-2 text-success"></i>
                                    Activar autorización inmediatamente
                                </label>
                                <p class="switch-description">
                                    Si está marcado, el autorizador estará activo desde el momento de creación
                                </p>
                            </div>
                            <div class="custom-switch">
                                <input type="checkbox" name="activo" id="activo" value="1" checked>
                                <span class="custom-switch-slider" onclick="toggleSwitchContainer(this)"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-save me-3">
                                <i class="fas fa-save me-2"></i>Crear Autorizador
                            </button>
                            <a href="<?= url('/admin/autorizadores/cuentas-contables') ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('cuentaContableForm');
    
    // Inicializar contadores al cargar la página
    const totalCuentas = document.querySelectorAll('.cuenta-item').length;
    document.getElementById('contador_total').textContent = totalCuentas;
    actualizarContadorSeleccionadas();
    
    // Agregar event listeners a todos los checkboxes para actualizar contador
    const checkboxes = document.querySelectorAll('input[name="cuentas_contables[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                this.closest('.cuenta-item').classList.add('selected');
            } else {
                this.closest('.cuenta-item').classList.remove('selected');
            }
            actualizarContadorSeleccionadas();
        });
    });
    
    // Agregar shortcut para limpiar búsqueda con Escape
    const buscadorCuentas = document.getElementById('buscador_cuentas');
    if (buscadorCuentas) {
        buscadorCuentas.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                limpiarBusqueda();
                this.blur(); // Quitar foco del campo
            }
        });
    }
    
    form.addEventListener('submit', function(e) {
        const cuentasSeleccionadas = document.querySelectorAll('input[name="cuentas_contables[]"]:checked');
        
        if (cuentasSeleccionadas.length === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos una cuenta contable');
            return false;
        }
        
        const autorizadorEmail = document.getElementById('autorizador_email').value;
        if (!autorizadorEmail) {
            e.preventDefault();
            alert('Debe seleccionar un autorizador');
            return false;
        }
    });
    
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    const hoy = new Date().toISOString().split('T')[0];
    fechaInicio.min = hoy;
    
    fechaInicio.addEventListener('change', function() {
        fechaFin.min = this.value;
    });
    
    // ========================================================================
    // GESTIÓN DE EXCLUSIONES DE CENTROS DE COSTO
    // ========================================================================
    
    const centrosExclusionList = document.getElementById('centros_exclusion_list');
    const exclusionesCount = document.getElementById('exclusiones_count');
    const excludedCountSpan = document.getElementById('excluded_count');
    
    // Usar centros de costo pasados desde PHP
    const centrosCosto = <?= json_encode($centros_costo ?? []) ?>;
    
    if (centrosCosto && centrosCosto.length > 0) {
        renderCentrosExclusion(centrosCosto);
        exclusionesCount.style.display = 'block';
        updateExcludedCount();
    } else {
        centrosExclusionList.innerHTML = '<p class="text-muted">No hay centros de costo disponibles</p>';
    }
    
    // Renderizar centros de costo para exclusión
    function renderCentrosExclusion(centros) {
        let html = '';
        centros.forEach(centro => {
            const nombre = centro.nombre || 'Sin nombre';
            html += `
                <div class="form-check mb-2">
                    <input class="form-check-input centro-exclusion-checkbox" 
                           type="checkbox" 
                           name="centros_excluidos[]" 
                           value="${centro.id}" 
                           id="exclusion_${centro.id}">
                    <label class="form-check-label" for="exclusion_${centro.id}">
                        ${nombre}
                    </label>
                </div>
            `;
        });
        centrosExclusionList.innerHTML = html;
        
        // Agregar event listeners
        document.querySelectorAll('.centro-exclusion-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateExcludedCount);
        });
    }
    
    // Actualizar contador de exclusiones
    function updateExcludedCount() {
        const count = document.querySelectorAll('.centro-exclusion-checkbox:checked').length;
        excludedCountSpan.textContent = count;
    }
    
    // Botón seleccionar todos las exclusiones
    document.getElementById('select_all_exclusiones').addEventListener('click', function() {
        document.querySelectorAll('.centro-exclusion-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
        updateExcludedCount();
    });
    
    // Botón deseleccionar todas las exclusiones
    document.getElementById('deselect_all_exclusiones').addEventListener('click', function() {
        document.querySelectorAll('.centro-exclusion-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        updateExcludedCount();
    });
});

function toggleCuenta(cuentaId) {
    const checkbox = document.getElementById(cuentaId);
    const item = checkbox.closest('.cuenta-item');
    
    checkbox.checked = !checkbox.checked;
    
    if (checkbox.checked) {
        item.classList.add('selected');
    } else {
        item.classList.remove('selected');
    }
    
    // Actualizar contador
    actualizarContadorSeleccionadas();
}

// ========================================================================
// FUNCIONES DE BÚSQUEDA Y SELECCIÓN DE CUENTAS CONTABLES
// ========================================================================

function filtrarCuentas() {
    const buscador = document.getElementById('buscador_cuentas');
    const termino = buscador.value.toLowerCase();
    const cuentas = document.querySelectorAll('.cuenta-item');
    let contadorVisibles = 0;
    
    cuentas.forEach(cuenta => {
        const codigo = cuenta.querySelector('strong').textContent.toLowerCase();
        const nombre = cuenta.querySelector('.text-muted').textContent.toLowerCase();
        
        if (codigo.includes(termino) || nombre.includes(termino)) {
            cuenta.classList.remove('hidden');
            contadorVisibles++;
            
            // Resaltar términos de búsqueda
            if (termino) {
                resaltarTexto(cuenta, termino);
            } else {
                removerResaltado(cuenta);
            }
        } else {
            cuenta.classList.add('hidden');
        }
    });
    
    // Actualizar contador total visible
    document.getElementById('contador_total').textContent = contadorVisibles;
    
    // Mostrar mensaje si no hay resultados
    const container = document.getElementById('cuentas_container');
    let mensajeSinResultados = container.querySelector('.sin-resultados');
    
    if (contadorVisibles === 0 && termino) {
        if (!mensajeSinResultados) {
            mensajeSinResultados = document.createElement('div');
            mensajeSinResultados.className = 'col-12 sin-resultados';
            mensajeSinResultados.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No se encontraron resultados</h5>
                    <p class="text-muted">No hay cuentas contables que coincidan con "<strong>${termino}</strong>"</p>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="limpiarBusqueda()">
                        <i class="fas fa-times me-1"></i>Limpiar búsqueda
                    </button>
                </div>
            `;
            container.appendChild(mensajeSinResultados);
        }
    } else if (mensajeSinResultados) {
        mensajeSinResultados.remove();
    }
}

function limpiarBusqueda() {
    document.getElementById('buscador_cuentas').value = '';
    const cuentas = document.querySelectorAll('.cuenta-item');
    
    cuentas.forEach(cuenta => {
        cuenta.classList.remove('hidden');
        removerResaltado(cuenta);
    });
    
    // Remover mensaje de sin resultados si existe
    const mensajeSinResultados = document.querySelector('.sin-resultados');
    if (mensajeSinResultados) {
        mensajeSinResultados.remove();
    }
    
    // Restaurar contador total
    document.getElementById('contador_total').textContent = cuentas.length;
}

function seleccionarTodas() {
    const cuentasVisibles = document.querySelectorAll('.cuenta-item:not(.hidden)');
    const terminoBusqueda = document.getElementById('buscador_cuentas').value;
    
    // Si hay búsqueda activa, mostrar confirmación
    if (terminoBusqueda.trim() !== '') {
        const mensaje = `¿Seleccionar las ${cuentasVisibles.length} cuentas visibles que coinciden con la búsqueda "${terminoBusqueda}"?`;
        if (!confirm(mensaje)) {
            return;
        }
    }
    
    cuentasVisibles.forEach(cuenta => {
        const checkbox = cuenta.querySelector('input[type="checkbox"]');
        if (!checkbox.checked) {
            checkbox.checked = true;
            cuenta.classList.add('selected');
        }
    });
    
    actualizarContadorSeleccionadas();
    
    // Mensaje de confirmación
    const mensaje = terminoBusqueda.trim() !== '' 
        ? `Se seleccionaron ${cuentasVisibles.length} cuentas que coinciden con la búsqueda.`
        : `Se seleccionaron todas las ${cuentasVisibles.length} cuentas disponibles.`;
    
    // Mostrar mensaje temporal
    mostrarMensajeTemporal(mensaje, 'success');
}

function deseleccionarTodas() {
    const cuentas = document.querySelectorAll('.cuenta-item input[type="checkbox"]:checked');
    const cantidad = cuentas.length;
    
    if (cantidad === 0) {
        mostrarMensajeTemporal('No hay cuentas seleccionadas para deseleccionar.', 'info');
        return;
    }
    
    cuentas.forEach(checkbox => {
        checkbox.checked = false;
        checkbox.closest('.cuenta-item').classList.remove('selected');
    });
    
    actualizarContadorSeleccionadas();
    mostrarMensajeTemporal(`Se deseleccionaron ${cantidad} cuentas.`, 'info');
}

function actualizarContadorSeleccionadas() {
    const seleccionadas = document.querySelectorAll('input[name="cuentas_contables[]"]:checked');
    document.getElementById('contador_seleccionadas').textContent = seleccionadas.length;
}

function resaltarTexto(elemento, termino) {
    const textos = elemento.querySelectorAll('strong, .text-muted');
    
    textos.forEach(texto => {
        const contenido = texto.textContent;
        const regex = new RegExp(`(${termino})`, 'gi');
        const contenidoResaltado = contenido.replace(regex, '<span class="search-highlight">$1</span>');
        texto.innerHTML = contenidoResaltado;
    });
}

function removerResaltado(elemento) {
    const resaltados = elemento.querySelectorAll('.search-highlight');
    resaltados.forEach(resaltado => {
        resaltado.outerHTML = resaltado.textContent;
    });
}

function mostrarMensajeTemporal(mensaje, tipo = 'info') {
    // Crear elemento de mensaje
    const mensajeDiv = document.createElement('div');
    mensajeDiv.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
    mensajeDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    mensajeDiv.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Agregar al body
    document.body.appendChild(mensajeDiv);
    
    // Auto-remover después de 3 segundos
    setTimeout(() => {
        if (mensajeDiv.parentNode) {
            mensajeDiv.remove();
        }
    }, 3000);
}

// ========================================================================
// FUNCIÓN PARA SWITCHES MODERNOS
// ========================================================================

function toggleSwitchContainer(slider) {
    const checkbox = slider.parentElement.querySelector('input[type="checkbox"]');
    const container = slider.closest('.switch-container');
    
    // Toggle checkbox
    checkbox.checked = !checkbox.checked;
    
    // Add animation class
    container.classList.add('toggling');
    
    // Remove animation class after animation completes
    setTimeout(() => {
        container.classList.remove('toggling');
    }, 200);
    
    // Dispatch change event for any listeners
    checkbox.dispatchEvent(new Event('change', { bubbles: true }));
}
</script>
<?php View::endSection(); ?>