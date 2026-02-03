<?php 
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Nuevo Autorizador';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 15px 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .form-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border: 1px solid #e9ecef;
    }
    
    .btn-create {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
    }
    
    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
        color: white;
    }
    
    .permission-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .form-check-custom {
        position: relative;
        margin-bottom: 0.75rem;
    }
    
    .form-check-custom .form-check-input {
        width: 1.25rem;
        height: 1.25rem;
        margin-top: 0;
        border-color: #e74c3c;
    }
    
    .form-check-custom .form-check-input:checked {
        background-color: #e74c3c;
        border-color: #e74c3c;
    }
    
    .form-check-custom .form-check-label {
        font-weight: 500;
        color: #495057;
        margin-left: 0.5rem;
    }
    
    .permission-description {
        font-size: 0.875rem;
        color: #6c757d;
        margin-left: 2rem;
        margin-top: -0.25rem;
    }
</style>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="mb-0">
                    <i class="fas fa-user-plus me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Crear un nuevo autorizador para el sistema</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="<?= url('/admin/autorizadores') ?>" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver al Listado
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Formulario Principal -->
            <div class="form-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-user-shield me-2 text-danger"></i>
                        Información del Autorizador
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= url('/admin/autorizadores') ?>" id="formNuevoAutorizador">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?= Session::get('csrf_token') ?>">
                        
                        <!-- Información Personal -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre Completo *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required
                                           value="<?= View::e(Session::old('nombre') ?? '') ?>">
                                    <div class="form-text">Nombre completo del autorizador</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?= View::e(Session::old('email') ?? '') ?>">
                                    <div class="form-text">Dirección de correo electrónico</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cargo" class="form-label">Cargo</label>
                                    <input type="text" class="form-control" id="cargo" name="cargo"
                                           value="<?= View::e(Session::old('cargo') ?? '') ?>">
                                    <div class="form-text">Cargo o posición en la organización</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="centro_costo_id" class="form-label">Centro de Costo *</label>
                                    <select class="form-control" id="centro_costo_id" name="centro_costo_id" required>
                                        <option value="">Seleccionar centro de costo...</option>
                                        <?php if (!empty($centros)): ?>
                                            <?php foreach ($centros as $centro): ?>
                                                <option value="<?= View::e($centro->id) ?>"
                                                    <?= (Session::old('centro_costo_id') == $centro->id) ? 'selected' : '' ?>>
                                                    <?= View::e($centro->nombre) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <div class="form-text">Centro de costo al que pertenece</div>
                                </div>
                            </div>
                        </div>

                        <!-- Permisos de Autorización -->
                        <div class="permission-section">
                            <h6 class="mb-3">
                                <i class="fas fa-key me-2 text-warning"></i>
                                Permisos de Autorización
                            </h6>
                            <p class="text-muted mb-3">Selecciona qué tipos de autorizaciones puede realizar este autorizador:</p>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check form-check-custom">
                                        <input class="form-check-input" type="checkbox" 
                                               id="puede_autorizar_centro_costo" name="puede_autorizar_centro_costo" value="1"
                                               <?= Session::old('puede_autorizar_centro_costo') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_autorizar_centro_costo">
                                            Autorización por Centro de Costo
                                        </label>
                                    </div>
                                    <div class="permission-description">
                                        Puede aprobar o rechazar requisiciones de su centro de costo
                                    </div>

                                    <div class="form-check form-check-custom">
                                        <input class="form-check-input" type="checkbox" 
                                               id="puede_autorizar_flujo" name="puede_autorizar_flujo" value="1"
                                               <?= Session::old('puede_autorizar_flujo') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_autorizar_flujo">
                                            Autorización de Flujo
                                        </label>
                                    </div>
                                    <div class="permission-description">
                                        Puede revisar el flujo general de autorizaciones
                                    </div>

                                    <div class="form-check form-check-custom">
                                        <input class="form-check-input" type="checkbox" 
                                               id="puede_autorizar_cuenta_contable" name="puede_autorizar_cuenta_contable" value="1"
                                               <?= Session::old('puede_autorizar_cuenta_contable') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_autorizar_cuenta_contable">
                                            Autorización por Cuenta Contable
                                        </label>
                                    </div>
                                    <div class="permission-description">
                                        Puede autorizar requisiciones con cuentas contables específicas
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-check form-check-custom">
                                        <input class="form-check-input" type="checkbox" 
                                               id="puede_autorizar_metodo_pago" name="puede_autorizar_metodo_pago" value="1"
                                               <?= Session::old('puede_autorizar_metodo_pago') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_autorizar_metodo_pago">
                                            Autorización por Método de Pago
                                        </label>
                                    </div>
                                    <div class="permission-description">
                                        Puede autorizar requisiciones con métodos de pago específicos
                                    </div>

                                    <div class="form-check form-check-custom">
                                        <input class="form-check-input" type="checkbox" 
                                               id="puede_autorizar_respaldo" name="puede_autorizar_respaldo" value="1"
                                               <?= Session::old('puede_autorizar_respaldo') ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="puede_autorizar_respaldo">
                                            Autorizador de Respaldo
                                        </label>
                                    </div>
                                    <div class="permission-description">
                                        Puede actuar como respaldo de otros autorizadores
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="row">
                            <div class="col-md-10">
                                <div class="clean-field" id="estadoField">
                                    <div class="field-content">
                                        <div class="field-label">
                                            <i class="fas fa-power-off me-2 text-primary"></i>
                                            Estado del Autorizador
                                        </div>
                                        <div class="field-description">
                                            Controla si este autorizador está activo y puede procesar requisiciones
                                        </div>
                                    </div>
                                    <div class="modern-switch">
                                        <input type="checkbox" id="activo" name="activo" value="1" 
                                               <?= (Session::old('activo', '1') == '1') ? 'checked' : '' ?>>
                                        <span class="modern-switch-track" onclick="toggleModernStatus(this)"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <a href="<?= url('/admin/autorizadores') ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar
                                </a>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="submit" class="btn btn-create">
                                    <i class="fas fa-save me-2"></i>
                                    Crear Autorizador
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2 text-info"></i>
                        Información Importante
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-lightbulb me-2"></i>
                            ¿Qué sucede después de crear el autorizador?
                        </h6>
                        <ul class="mb-0">
                            <li><strong>Notificaciones:</strong> El autorizador recibirá notificaciones por email cuando tenga requisiciones pendientes de autorización.</li>
                            <li><strong>Permisos:</strong> Solo podrá autorizar los tipos de requisiciones que hayas seleccionado.</li>
                            <li><strong>Centro de Costo:</strong> Tendrá acceso para autorizar requisiciones de su centro de costo asignado.</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <h6 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Recomendaciones
                        </h6>
                        <ul class="mb-0">
                            <li>Asegúrate que el email sea correcto, ya que se usará para notificaciones.</li>
                            <li>Revisa cuidadosamente los permisos antes de crear el autorizador.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validación del formulario
document.getElementById('formNuevoAutorizador').addEventListener('submit', function(e) {
    const nombre = document.getElementById('nombre').value.trim();
    const email = document.getElementById('email').value.trim();
    const centro_costo_id = document.getElementById('centro_costo_id').value;
    
    // Validar campos requeridos
    if (nombre.length < 2) {
        e.preventDefault();
        alert('El nombre debe tener al menos 2 caracteres');
        document.getElementById('nombre').focus();
        return false;
    }
    
    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        e.preventDefault();
        alert('Por favor ingresa un email válido');
        document.getElementById('email').focus();
        return false;
    }
    
    if (!centro_costo_id) {
        e.preventDefault();
        alert('Por favor selecciona un centro de costo');
        document.getElementById('centro_costo_id').focus();
        return false;
    }
    
    // Validar que al menos un permiso esté seleccionado
    const permisos = document.querySelectorAll('input[type="checkbox"][name^="puede_autorizar"]');
    let tienePermisos = false;
    permisos.forEach(permiso => {
        if (permiso.checked) {
            tienePermisos = true;
        }
    });
    
    if (!tienePermisos) {
        e.preventDefault();
        alert('Por favor selecciona al menos un tipo de autorización');
        return false;
    }
    
    
    return true;
});

// Auto-focus en el primer campo
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('nombre').focus();
    updateModernStatusField();
});

// Toggle para el switch moderno
function toggleModernStatus(track) {
    const checkbox = track.parentElement.querySelector('input[type="checkbox"]');
    checkbox.checked = !checkbox.checked;
    updateModernStatusField();
}

// Actualizar apariencia del campo moderno según el estado
function updateModernStatusField() {
    const checkbox = document.getElementById('activo');
    const field = document.getElementById('estadoField');
    
    field.classList.remove('active', 'inactive');
    if (checkbox.checked) {
        field.classList.add('active');
    } else {
        field.classList.add('inactive');
    }
}


// Prevenir envío múltiple
let formSubmitted = false;
document.getElementById('formNuevoAutorizador').addEventListener('submit', function() {
    if (formSubmitted) {
        return false;
    }
    formSubmitted = true;
    
    // Deshabilitar botón de envío
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando...';
    
    return true;
});
</script>
<?php View::endSection(); ?>