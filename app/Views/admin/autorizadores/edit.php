<?php 
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Editar Autorizador';
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
    
    .form-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        padding: 2rem;
        border: 1px solid #e9ecef;
    }
    
    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 8px;
    }
    
    .form-control, .form-select {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        padding: 12px 16px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #e74c3c;
        box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
    }
    
    .btn-save {
        background: linear-gradient(135deg, #28a745, #20c997);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
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
    
    .permissions-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1rem;
    }
    
    .permission-item {
        display: flex;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e9ecef;
    }
    
    .permission-item:last-child {
        border-bottom: none;
    }
    
    .permission-label {
        flex: 1;
        margin-left: 10px;
    }
    
    .permission-description {
        font-size: 0.9rem;
        color: #6c757d;
        margin-top: 2px;
    }
    
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
    }
    
    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 24px;
    }
    
    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    input:checked + .slider {
        background-color: #e74c3c;
    }
    
    input:checked + .slider:before {
        transform: translateX(26px);
    }
    
    .current-info {
        background: #e3f2fd;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .current-info h6 {
        color: #1565c0;
        margin-bottom: 0.5rem;
    }
</style>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0">
                    <i class="fas fa-user-edit me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Modifica los datos del autorizador</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/admin/autorizadores" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver a la Lista
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="form-container">
                <!-- Información Actual -->
                <div class="current-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Información Actual</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>ID:</strong> #<?= View::e($autorizador->id ?? 'N/A') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Estado:</strong> 
                            <?php if ($autorizador->activo ?? true): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactivo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <form method="POST" action="/admin/autorizadores/<?= View::e($autorizador->id ?? '') ?>" id="editForm">
                    <?php echo App\Middlewares\CsrfMiddleware::field(); ?>
                    <input type="hidden" name="_method" value="PUT">
                    
                    <div class="row">
                        <!-- Información Básica -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre Completo</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="<?= View::e($autorizador->nombre ?? '') ?>"
                                       required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?= View::e($autorizador->email ?? '') ?>"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cargo" class="form-label">Cargo</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="cargo" 
                                       name="cargo" 
                                       value="<?= View::e($autorizador->cargo ?? '') ?>"
                                       placeholder="Ej: Gerente de Finanzas">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="centro_costo_id" class="form-label">Centro de Costo</label>
                                <select class="form-select" id="centro_costo_id" name="centro_costo_id">
                                    <option value="">Seleccione un centro de costo</option>
                                    <?php if (!empty($centros)): ?>
                                        <?php foreach ($centros as $centro): ?>
                                            <option value="<?= $centro->id ?>" 
                                                    <?= ($centro->id == ($autorizador->centro_costo_id ?? '')) ? 'selected' : '' ?>>
                                                <?= View::e($centro->nombre ?? 'Sin nombre') ?> 
                                                (<?= View::e($centro->codigo ?? 'Sin código') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="prioridad" class="form-label">Prioridad</label>
                                <select class="form-select" id="prioridad" name="prioridad">
                                    <option value="1" <?= (($autorizador->prioridad ?? 1) == 1) ? 'selected' : '' ?>>Alta</option>
                                    <option value="2" <?= (($autorizador->prioridad ?? 1) == 2) ? 'selected' : '' ?>>Media</option>
                                    <option value="3" <?= (($autorizador->prioridad ?? 1) == 3) ? 'selected' : '' ?>>Baja</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="monto_limite" class="form-label">Límite de Autorización (Q)</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="monto_limite" 
                                       name="monto_limite" 
                                       value="<?= View::e($autorizador->monto_limite ?? '') ?>"
                                       step="0.01"
                                       min="0"
                                       placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Permisos de Autorización -->
                    <div class="permissions-section">
                        <h5 class="mb-3">
                            <i class="fas fa-shield-alt me-2"></i>
                            Permisos de Autorización
                        </h5>
                        
                        <div class="permission-item">
                            <label class="switch">
                                <input type="checkbox" 
                                       name="puede_autorizar_centro_costo" 
                                       value="1"
                                       <?= ($autorizador->puede_autorizar_centro_costo ?? false) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="permission-label">
                                <strong>Centro de Costo</strong>
                                <div class="permission-description">Puede autorizar cambios en centros de costo</div>
                            </div>
                        </div>
                        
                        <div class="permission-item">
                            <label class="switch">
                                <input type="checkbox" 
                                       name="puede_autorizar_flujo" 
                                       value="1"
                                       <?= ($autorizador->puede_autorizar_flujo ?? false) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="permission-label">
                                <strong>Flujo de Trabajo</strong>
                                <div class="permission-description">Puede autorizar cambios en el flujo de aprobación</div>
                            </div>
                        </div>
                        
                        <div class="permission-item">
                            <label class="switch">
                                <input type="checkbox" 
                                       name="puede_autorizar_cuenta_contable" 
                                       value="1"
                                       <?= ($autorizador->puede_autorizar_cuenta_contable ?? false) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="permission-label">
                                <strong>Cuenta Contable</strong>
                                <div class="permission-description">Puede autorizar cambios en cuentas contables</div>
                            </div>
                        </div>
                        
                        <div class="permission-item">
                            <label class="switch">
                                <input type="checkbox" 
                                       name="puede_autorizar_metodo_pago" 
                                       value="1"
                                       <?= ($autorizador->puede_autorizar_metodo_pago ?? false) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="permission-label">
                                <strong>Método de Pago</strong>
                                <div class="permission-description">Puede autorizar cambios en métodos de pago</div>
                            </div>
                        </div>
                        
                        <div class="permission-item">
                            <label class="switch">
                                <input type="checkbox" 
                                       name="puede_autorizar_respaldo" 
                                       value="1"
                                       <?= ($autorizador->puede_autorizar_respaldo ?? false) ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="permission-label">
                                <strong>Respaldo</strong>
                                <div class="permission-description">Puede autorizar cambios en respaldos</div>
                            </div>
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Estado</label>
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="activo" 
                                           id="activo_si" 
                                           value="1"
                                           <?= ($autorizador->activo ?? true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="activo_si">
                                        Activo
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="activo" 
                                           id="activo_no" 
                                           value="0"
                                           <?= !($autorizador->activo ?? true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="activo_no">
                                        Inactivo
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_inicio" 
                                       name="fecha_inicio" 
                                       value="<?= View::e($autorizador->fecha_inicio ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-save me-3">
                                <i class="fas fa-save me-2"></i>
                                Guardar Cambios
                            </button>
                            <a href="/admin/autorizadores" class="btn btn-cancel">
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
    // Validación del formulario
    document.getElementById('editForm').addEventListener('submit', function(e) {
        const nombre = document.getElementById('nombre').value.trim();
        const email = document.getElementById('email').value.trim();
        
        if (!nombre || !email) {
            e.preventDefault();
            alert('Por favor complete todos los campos obligatorios.');
            return false;
        }
        
        // Validar email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Por favor ingrese un email válido.');
            return false;
        }
    });

    // Efecto en los switches
    document.querySelectorAll('.switch input').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const slider = this.nextElementSibling;
            if (this.checked) {
                slider.style.backgroundColor = '#e74c3c';
            } else {
                slider.style.backgroundColor = '#ccc';
            }
        });
    });
</script>
<?php View::endSection(); ?>
















