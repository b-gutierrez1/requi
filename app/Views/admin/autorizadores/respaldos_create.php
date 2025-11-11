<?php 
use App\Helpers\View;
use App\Helpers\Session;
use App\Middlewares\CsrfMiddleware;

$title = 'Crear Autorizador de Respaldo';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
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
        color: #ff6b6b;
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
        border-color: #ff6b6b;
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 107, 0.25);
    }
    
    .btn-save {
        background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
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
    
    .required {
        color: #dc3545;
    }
    
    .help-text {
        font-size: 0.875rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
    
    .alert-info {
        background: #e3f2fd;
        border: 1px solid #1976d2;
        color: #1976d2;
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
        border: 2px solid #ff6b6b;
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
        background: #fff5f5;
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
                    <i class="fas fa-hands-helping me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Configurar un autorizador temporal para respaldo</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/admin/autorizadores/respaldos" class="btn btn-light">
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
                    <strong>Información:</strong> Los autorizadores de respaldo permiten asignar temporalmente las responsabilidades de autorización a otra persona durante períodos específicos (vacaciones, ausencias, etc.).
                </div>

                <form action="/admin/autorizadores/respaldos" method="POST" id="respaldoForm">
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
                                            <option value="<?= View::e($auth['email'] ?? '') ?>"
                                                    data-nombre="<?= View::e($auth['nombre'] ?? '') ?>"
                                                    data-cargo="<?= View::e($auth['cargo'] ?? '') ?>">
                                                <?= View::e($auth['nombre'] ?? '') ?> (<?= View::e($auth['email'] ?? '') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="help-text">Seleccione el autorizador que será respaldado</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="centros_costo_ids" class="form-label">
                                    Centros de Costo <span class="required">*</span>
                                </label>
                                <div id="centros_container" style="border: 2px solid #e9ecef; border-radius: 8px; padding: 15px; max-height: 300px; overflow-y: auto; background: #f8f9fa;">
                                    <div class="mb-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="seleccionar_todos">
                                            <i class="fas fa-check-double"></i> Seleccionar Todos
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="deseleccionar_todos">
                                            <i class="fas fa-times"></i> Deseleccionar Todos
                                        </button>
                                    </div>
                                    <div id="centros_list">
                                        <p class="text-muted">Seleccione un autorizador principal para cargar sus centros de costo</p>
                                    </div>
                                </div>
                                <div class="help-text">Seleccione uno o más centros de costo para aplicar el respaldo</div>
                                <div id="centros_count" class="mt-2" style="display: none;">
                                    <span class="badge bg-primary"><span id="selected_count">0</span> seleccionados</span>
                                </div>
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
                                           autocomplete="off">
                                    <input type="hidden" 
                                           name="autorizador_respaldo_email" 
                                           id="autorizador_respaldo_email" 
                                           required>
                                    <div id="respaldo_suggestions" class="autocomplete-suggestions" style="display: none;"></div>
                                </div>
                                <div class="help-text">Persona que actuará como respaldo temporal</div>
                                <div id="respaldo_selected" class="mt-2" style="display: none;">
                                    <div class="alert alert-success py-2">
                                        <i class="fas fa-check-circle"></i> 
                                        <strong id="respaldo_nombre"></strong> 
                                        <small class="text-muted">(<span id="respaldo_email_display"></span>)</small>
                                        <button type="button" class="btn btn-sm btn-link text-danger float-end" id="respaldo_clear">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="motivo" class="form-label">
                                    Motivo del Respaldo
                                </label>
                                <select name="motivo" id="motivo" class="form-select">
                                    <option value="">Seleccionar motivo...</option>
                                    <option value="Vacaciones">Vacaciones</option>
                                    <option value="Licencia médica">Licencia médica</option>
                                    <option value="Viaje de trabajo">Viaje de trabajo</option>
                                    <option value="Capacitación">Capacitación</option>
                                    <option value="Ausencia temporal">Ausencia temporal</option>
                                    <option value="Otro">Otro</option>
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
                                          placeholder="Detalles adicionales sobre el respaldo (opcional)"></textarea>
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
                                       value="<?= date('Y-m-d') ?>" required>
                                <div class="help-text">Cuando inicia la autorización de respaldo</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="fecha_fin" class="form-label">
                                    Fecha de Fin <span class="required">*</span>
                                </label>
                                <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" required>
                                <div class="help-text">Cuando termina la autorización de respaldo</div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" checked>
                                    <label class="form-check-label" for="activo">
                                        Activar respaldo inmediatamente
                                    </label>
                                    <div class="help-text">Si está marcado, el respaldo estará activo según las fechas especificadas</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Botones de Acción -->
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-save me-3">
                                <i class="fas fa-save me-2"></i>
                                Crear Respaldo
                            </button>
                            <a href="/admin/autorizadores/respaldos" class="btn btn-cancel">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
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
    // Validación del formulario
    const form = document.getElementById('respaldoForm');
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    const principalSelect = document.getElementById('autorizador_principal_email');
    const respaldoSearchInput = document.getElementById('autorizador_respaldo_search');
    const respaldoEmailInput = document.getElementById('autorizador_respaldo_email');
    const respaldoSuggestions = document.getElementById('respaldo_suggestions');
    const respaldoSelected = document.getElementById('respaldo_selected');
    const centrosList = document.getElementById('centros_list');
    const centrosCount = document.getElementById('centros_count');
    const selectedCountSpan = document.getElementById('selected_count');
    
    let centrosDisponibles = [];
    let searchTimeout = null;
    
    // ========================================================================
    // AUTOCOMPLETADO DE USUARIOS PARA RESPALDO
    // ========================================================================
    
    // Buscar usuarios mientras escribe
    respaldoSearchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Limpiar el timeout anterior
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            respaldoSuggestions.style.display = 'none';
            return;
        }
        
        // Esperar 300ms antes de buscar (debouncing)
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
    document.getElementById('respaldo_clear').addEventListener('click', function() {
        respaldoEmailInput.value = '';
        respaldoSearchInput.value = '';
        respaldoSelected.style.display = 'none';
        respaldoSearchInput.style.display = 'block';
        respaldoSearchInput.focus();
    });
    
    // Cerrar sugerencias al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.autocomplete-wrapper')) {
            respaldoSuggestions.style.display = 'none';
        }
    });
    
    // ========================================================================
    // CARGAR CENTROS DE COSTO DEL AUTORIZADOR PRINCIPAL
    // ========================================================================
    
    // Cargar centros de costo del autorizador principal
    principalSelect.addEventListener('change', function() {
        const email = this.value;
        
        if (!email) {
            centrosList.innerHTML = '<p class="text-muted">Seleccione un autorizador principal para cargar sus centros de costo</p>';
            centrosCount.style.display = 'none';
            centrosDisponibles = [];
            return;
        }
        
        // Mostrar loading
        centrosList.innerHTML = '<p class="text-muted"><i class="fas fa-spinner fa-spin"></i> Cargando centros de costo...</p>';
        
        // Hacer petición AJAX
        fetch(`/admin/api/autorizadores/centros-costo?email=${encodeURIComponent(email)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.centros && data.centros.length > 0) {
                    centrosDisponibles = data.centros;
                    renderCentros(data.centros);
                    centrosCount.style.display = 'block';
                    updateSelectedCount();
                } else {
                    centrosList.innerHTML = '<p class="text-danger"><i class="fas fa-exclamation-circle"></i> El autorizador seleccionado no tiene centros de costo asignados</p>';
                    centrosCount.style.display = 'none';
                    centrosDisponibles = [];
                }
            })
            .catch(error => {
                console.error('Error al cargar centros de costo:', error);
                centrosList.innerHTML = '<p class="text-danger"><i class="fas fa-exclamation-circle"></i> Error al cargar los centros de costo</p>';
                centrosCount.style.display = 'none';
                centrosDisponibles = [];
            });
    });
    
    // Renderizar lista de centros con checkboxes
    function renderCentros(centros) {
        let html = '';
        centros.forEach(centro => {
            const nombre = centro.nombre || 'Sin nombre';
            const esPrincipal = centro.es_principal == 1 ? '<i class="fas fa-star text-warning ms-2"></i>' : '';
            
            html += `
                <div class="form-check mb-2">
                    <input class="form-check-input centro-checkbox" type="checkbox" 
                           name="centros_costo_ids[]" value="${centro.id}" 
                           id="centro_${centro.id}">
                    <label class="form-check-label" for="centro_${centro.id}">
                        ${nombre} ${esPrincipal}
                    </label>
                </div>
            `;
        });
        centrosList.innerHTML = html;
        
        // Agregar event listeners a los checkboxes
        document.querySelectorAll('.centro-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });
    }
    
    // Actualizar contador de seleccionados
    function updateSelectedCount() {
        const selected = document.querySelectorAll('.centro-checkbox:checked').length;
        selectedCountSpan.textContent = selected;
    }
    
    // Botón seleccionar todos
    document.getElementById('seleccionar_todos').addEventListener('click', function() {
        document.querySelectorAll('.centro-checkbox').forEach(checkbox => {
            checkbox.checked = true;
        });
        updateSelectedCount();
    });
    
    // Botón deseleccionar todos
    document.getElementById('deseleccionar_todos').addEventListener('click', function() {
        document.querySelectorAll('.centro-checkbox').forEach(checkbox => {
            checkbox.checked = false;
        });
        updateSelectedCount();
    });
    
    // Validar que la fecha de fin sea posterior a la de inicio
    function validarFechas() {
        if (fechaInicio.value && fechaFin.value) {
            if (fechaFin.value <= fechaInicio.value) {
                fechaFin.setCustomValidity('La fecha de fin debe ser posterior a la fecha de inicio');
                return false;
            } else {
                fechaFin.setCustomValidity('');
                return true;
            }
        }
        return true;
    }
    
    fechaInicio.addEventListener('change', validarFechas);
    fechaFin.addEventListener('change', validarFechas);
    
    // Evitar que el principal y respaldo sean la misma persona
    function validarAutorizadores() {
        if (principalSelect.value && respaldoEmailInput.value) {
            if (principalSelect.value === respaldoEmailInput.value) {
                respaldoEmailInput.setCustomValidity('El respaldo debe ser diferente al autorizador principal');
                return false;
            } else {
                respaldoEmailInput.setCustomValidity('');
                return true;
            }
        }
        return true;
    }
    
    principalSelect.addEventListener('change', validarAutorizadores);
    
    // Validación al enviar el formulario
    form.addEventListener('submit', function(e) {
        // Validar que al menos un centro esté seleccionado
        const centrosSeleccionados = document.querySelectorAll('.centro-checkbox:checked').length;
        
        if (centrosSeleccionados === 0 && principalSelect.value) {
            e.preventDefault();
            alert('Debe seleccionar al menos un centro de costo');
            return false;
        }
        
        if (!validarFechas() || !validarAutorizadores()) {
            e.preventDefault();
            alert('Por favor corrige los errores en el formulario');
            return false;
        }
        
        // Confirmar creación múltiple
        if (centrosSeleccionados > 1) {
            if (!confirm(`Se crearán ${centrosSeleccionados} respaldos (uno para cada centro de costo). ¿Desea continuar?`)) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Establecer fecha mínima de fin
    fechaInicio.addEventListener('change', function() {
        fechaFin.min = this.value;
    });
    
    // Establecer fecha mínima inicial
    const hoy = new Date().toISOString().split('T')[0];
    fechaInicio.min = hoy;
    fechaFin.min = hoy;
});
</script>
<?php View::endSection(); ?>