<?php 
use App\Helpers\View;
use App\Helpers\Session;
use App\Middlewares\CsrfMiddleware;

$title = 'Editar Autorizador de Respaldo';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header {
        background: var(--gradient-accent);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 15px 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .form-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        padding: 2rem;
        border: 1px solid #e9ecef;
    }
    
    .form-section {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .section-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--accent-color);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
    }
    
    .section-title i {
        margin-right: 10px;
    }
    
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .form-control, .form-select {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 0.75rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--accent-color);
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
    }
    
    .btn-save {
        background: var(--gradient-accent);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        color: white;
    }
    
    .btn-cancel {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-cancel:hover {
        background: #5a6268;
        color: white;
    }
    
    .btn-delete {
        background: linear-gradient(135deg, var(--danger-color), #dc2626);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-delete:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
        color: white;
    }
    
    .required {
        color: var(--danger-color);
    }
    
    .help-text {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
    
    .alert-info {
        background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(2, 132, 199, 0.1));
        border: 1px solid var(--info-color);
        color: #0c4a6e;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .autocomplete-wrapper {
        position: relative;
    }
    
    .autocomplete-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        z-index: 1000;
        background: white;
        border: 2px solid var(--accent-color);
        border-top: none;
        border-radius: 0 0 8px 8px;
        max-height: 300px;
        overflow-y: auto;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .autocomplete-item {
        padding: 12px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: all 0.2s ease;
    }
    
    .autocomplete-item:hover {
        background: rgba(37, 99, 235, 0.05);
    }
    
    .autocomplete-item:last-child {
        border-bottom: none;
    }
    
    .autocomplete-item .item-name {
        font-weight: 600;
        color: #333;
    }
    
    .autocomplete-item .item-email {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .autocomplete-item .item-cargo {
        font-size: 0.75rem;
        color: #999;
        font-style: italic;
    }
</style>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0">
                    <i class="fas fa-edit me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Modificar autorizador de respaldo existente</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?= url('/admin/autorizadores/respaldos') ?>" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver a la Lista
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
                    <strong>Información:</strong> Puede modificar los datos del autorizador de respaldo. Los cambios se aplicarán inmediatamente si el respaldo está activo.
                </div>

                <form action="/admin/autorizadores/respaldos/<?= View::e($respaldo['id'] ?? '') ?>" method="POST" id="respaldoForm">
                    <input type="hidden" name="_method" value="PUT">
                    <?= CsrfMiddleware::field() ?>
                    
                    <!-- Sección: Autorizador Principal -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i>
                            Autorizador Principal
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="autorizador_principal_email" class="form-label">
                                    Email del Autorizador Principal <span class="required">*</span>
                                </label>
                                <select name="autorizador_principal_email" id="autorizador_principal_email" class="form-select" required>
                                    <option value="">Seleccionar autorizador...</option>
                                    <?php if (!empty($autorizadores)): ?>
                                        <?php foreach ($autorizadores as $auth): ?>
                                            <?php $selected = ($auth['email'] ?? '') === ($respaldo['autorizador_principal_email'] ?? '') ? 'selected' : ''; ?>
                                            <option value="<?= View::e($auth['email'] ?? '') ?>"
                                                    data-nombre="<?= View::e($auth['nombre'] ?? '') ?>"
                                                    data-cargo="<?= View::e($auth['cargo'] ?? '') ?>"
                                                    <?= $selected ?>>
                                                <?= View::e($auth['nombre'] ?? '') ?> (<?= View::e($auth['email'] ?? '') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="help-text">Seleccione el autorizador que será respaldado</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="centro_costo_id" class="form-label">
                                    Centro de Costo <span class="required">*</span>
                                </label>
                                <select name="centro_costo_id" id="centro_costo_id" class="form-select" required>
                                    <option value="">Seleccionar centro de costo...</option>
                                    <?php if (!empty($centros)): ?>
                                        <?php foreach ($centros as $centro): ?>
                                            <?php $selected = ($centro->id ?? '') == ($respaldo['centro_costo_id'] ?? '') ? 'selected' : ''; ?>
                                            <option value="<?= View::e($centro->id ?? '') ?>" <?= $selected ?>>
                                                <?= View::e($centro->nombre ?? 'Sin nombre') ?>
                                                <?php if (!empty($centro->codigo)): ?>
                                                    (<?= View::e($centro->codigo) ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="help-text">Centro de costo para el cual aplica el respaldo</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección: Autorizador de Respaldo -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-user-plus"></i>
                            Autorizador de Respaldo
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="autorizador_respaldo_email" class="form-label">
                                    Email del Respaldo <span class="required">*</span>
                                </label>
                                <div class="autocomplete-wrapper position-relative">
                                    <input type="text" 
                                           class="form-control" 
                                           id="autorizador_respaldo_search" 
                                           placeholder="Buscar por nombre o email..."
                                           value="<?= View::e($respaldo['autorizador_respaldo_nombre'] ?? '') ?>"
                                           autocomplete="off">
                                    <input type="hidden" 
                                           name="autorizador_respaldo_email" 
                                           id="autorizador_respaldo_email" 
                                           value="<?= View::e($respaldo['autorizador_respaldo_email'] ?? '') ?>"
                                           required>
                                    <div id="respaldo_suggestions" class="autocomplete-suggestions" style="display: none;"></div>
                                </div>
                                <div class="help-text">Persona que actuará como respaldo temporal</div>
                                <div id="respaldo_selected" class="mt-2" style="<?= !empty($respaldo['autorizador_respaldo_email']) ? 'display: block;' : 'display: none;' ?>">
                                    <div class="alert alert-success py-2">
                                        <i class="fas fa-check-circle"></i> 
                                        <strong id="respaldo_nombre"><?= View::e($respaldo['autorizador_respaldo_nombre'] ?? 'Sin nombre') ?></strong> 
                                        <small class="text-muted">(<span id="respaldo_email_display"><?= View::e($respaldo['autorizador_respaldo_email'] ?? '') ?></span>)</small>
                                        <button type="button" class="btn btn-sm btn-link text-danger float-end" id="respaldo_clear">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php if (!empty($respaldo['autorizador_respaldo_email'])): ?>
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            document.getElementById('autorizador_respaldo_search').style.display = 'none';
                                        });
                                    </script>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="motivo" class="form-label">
                                    Motivo del Respaldo
                                </label>
                                <select name="motivo" id="motivo" class="form-select">
                                    <option value="">Seleccionar motivo...</option>
                                    <?php
                                    $motivos = ['Vacaciones', 'Licencia médica', 'Viaje de trabajo', 'Capacitación', 'Ausencia temporal', 'Otro'];
                                    foreach ($motivos as $motivo): 
                                        $selected = ($respaldo['motivo'] ?? '') === $motivo ? 'selected' : '';
                                    ?>
                                        <option value="<?= View::e($motivo) ?>" <?= $selected ?>><?= View::e($motivo) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="help-text">Razón de la asignación del respaldo</div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="descripcion" class="form-label">
                                    Descripción Adicional
                                </label>
                                <textarea name="descripcion" id="descripcion" class="form-control" rows="3" 
                                          placeholder="Detalles adicionales sobre el respaldo (opcional)"><?= View::e($respaldo['notas'] ?? '') ?></textarea>
                                <div class="help-text">Información adicional o instrucciones específicas</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección: Período de Vigencia -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-calendar"></i>
                            Período de Vigencia
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="fecha_inicio" class="form-label">
                                    Fecha de Inicio <span class="required">*</span>
                                </label>
                                <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" 
                                       value="<?= View::e($respaldo['fecha_inicio'] ?? date('Y-m-d')) ?>" required>
                                <div class="help-text">Cuando inicia la autorización de respaldo</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="fecha_fin" class="form-label">
                                    Fecha de Fin <span class="required">*</span>
                                </label>
                                <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" 
                                       value="<?= View::e($respaldo['fecha_fin'] ?? '') ?>" required>
                                <div class="help-text">Cuando termina la autorización de respaldo</div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="switch-container">
                                    <div class="flex-grow-1">
                                        <label class="switch-label" for="activo">
                                            <i class="fas fa-play-circle me-2 text-success"></i>
                                            Activar respaldo inmediatamente
                                        </label>
                                        <p class="switch-description">
                                            Si está marcado, el respaldo estará activo según las fechas especificadas
                                        </p>
                                    </div>
                                    <div class="custom-switch">
                                        <?php $checked = ($respaldo['estado'] ?? '') === 'activo' ? 'checked' : ''; ?>
                                        <input type="checkbox" name="activo" id="activo" value="1" <?= $checked ?>>
                                        <span class="custom-switch-slider" onclick="toggleSwitchContainer(this)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de Acción -->
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-save me-3">
                                <i class="fas fa-save me-2"></i>
                                Guardar Cambios
                            </button>
                            <a href="<?= url('/admin/autorizadores/respaldos') ?>" class="btn btn-cancel me-3">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                            <button type="button" class="btn btn-delete" onclick="confirmarEliminacion()">
                                <i class="fas fa-trash me-2"></i>
                                Eliminar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Formulario oculto para eliminación -->
<form id="deleteForm" action="/admin/autorizadores/respaldos/<?= View::e($respaldo['id'] ?? '') ?>" method="POST" style="display: none;">
    <input type="hidden" name="_method" value="DELETE">
    <?= CsrfMiddleware::field() ?>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación del formulario
    const form = document.getElementById('respaldoForm');
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    const respaldoSearchInput = document.getElementById('autorizador_respaldo_search');
    const respaldoEmailInput = document.getElementById('autorizador_respaldo_email');
    const respaldoSuggestions = document.getElementById('respaldo_suggestions');
    const respaldoSelected = document.getElementById('respaldo_selected');
    
    let searchTimeout = null;
    
    // Validar fechas
    fechaInicio.addEventListener('change', function() {
        if (fechaFin.value && fechaInicio.value > fechaFin.value) {
            alert('La fecha de inicio no puede ser posterior a la fecha de fin');
            fechaInicio.value = fechaFin.value;
        }
    });
    
    fechaFin.addEventListener('change', function() {
        if (fechaInicio.value && fechaFin.value < fechaInicio.value) {
            alert('La fecha de fin no puede ser anterior a la fecha de inicio');
            fechaFin.value = fechaInicio.value;
        }
    });
    
    // ========================================================================
    // AUTOCOMPLETADO DE USUARIOS PARA RESPALDO
    // ========================================================================
    
    // Solo mostrar el input de búsqueda si no hay email seleccionado
    if (!respaldoEmailInput.value) {
        respaldoSearchInput.style.display = 'block';
    }
    
    // Buscar usuarios mientras escribe
    respaldoSearchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            respaldoSuggestions.style.display = 'none';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            buscarUsuarios(query);
        }, 300);
    });
    
    // Función para buscar usuarios
    function buscarUsuarios(query) {
        respaldoSuggestions.innerHTML = '<div class="autocomplete-item"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
        respaldoSuggestions.style.display = 'block';
        
        fetch(`/admin/api/usuarios/buscar?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.usuarios && data.usuarios.length > 0) {
                    renderSugerenciasUsuarios(data.usuarios);
                } else {
                    respaldoSuggestions.innerHTML = '<div class="autocomplete-item text-muted">No se encontraron usuarios</div>';
                }
            })
            .catch(error => {
                console.error('Error buscando usuarios:', error);
                respaldoSuggestions.innerHTML = '<div class="autocomplete-item text-danger">Error al buscar</div>';
            });
    }
    
    // Renderizar sugerencias
    function renderSugerenciasUsuarios(usuarios) {
        let html = '';
        usuarios.forEach(usuario => {
            const nombre = usuario.nombre || 'Sin nombre';
            const email = usuario.email || '';
            const cargo = usuario.cargo ? `<div class="item-cargo">${usuario.cargo}</div>` : '';
            
            html += `
                <div class="autocomplete-item" data-email="${email}" data-nombre="${nombre}">
                    <div class="item-name">${nombre}</div>
                    <div class="item-email">${email}</div>
                    ${cargo}
                </div>
            `;
        });
        
        respaldoSuggestions.innerHTML = html;
        
        // Agregar event listeners a los items
        document.querySelectorAll('.autocomplete-item[data-email]').forEach(item => {
            item.addEventListener('click', function() {
                seleccionarUsuarioRespaldo(this.dataset.email, this.dataset.nombre);
            });
        });
    }
    
    // Seleccionar usuario
    function seleccionarUsuarioRespaldo(email, nombre) {
        respaldoEmailInput.value = email;
        respaldoSearchInput.value = '';
        respaldoSuggestions.style.display = 'none';
        
        // Mostrar selección
        document.getElementById('respaldo_nombre').textContent = nombre;
        document.getElementById('respaldo_email_display').textContent = email;
        respaldoSelected.style.display = 'block';
        respaldoSearchInput.style.display = 'none';
    }
    
    // Limpiar selección
    const respaldoClearBtn = document.getElementById('respaldo_clear');
    if (respaldoClearBtn) {
        respaldoClearBtn.addEventListener('click', function() {
            respaldoEmailInput.value = '';
            respaldoSearchInput.value = '';
            respaldoSelected.style.display = 'none';
            respaldoSearchInput.style.display = 'block';
            respaldoSearchInput.focus();
        });
    }
    
    // Cerrar sugerencias al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.autocomplete-wrapper')) {
            respaldoSuggestions.style.display = 'none';
        }
    });
    
    // Interceptar envío del formulario para usar PUT
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validar fechas
        if (fechaInicio.value && fechaFin.value && fechaInicio.value > fechaFin.value) {
            alert('La fecha de inicio no puede ser posterior a la fecha de fin');
            return;
        }
        
        // Crear FormData
        const formData = new FormData(form);
        formData.append('_method', 'PUT');
        
        // Enviar con fetch
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.redirected) {
                window.location.href = response.url;
            } else {
                return response.json();
            }
        })
        .then(data => {
            if (data && data.error) {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar los cambios');
        });
    });
});

// Confirmar eliminación
function confirmarEliminacion() {
    if (confirm('¿Está seguro de que desea eliminar este autorizador de respaldo? Esta acción no se puede deshacer.')) {
        document.getElementById('deleteForm').submit();
    }
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

