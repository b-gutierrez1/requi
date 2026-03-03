<?php 
use App\Helpers\View;
use App\Helpers\Session;
use App\Middlewares\CsrfMiddleware;

$title = 'Crear Autorizador por Método de Pago';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header {
        background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
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
        color: #17a2b8;
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
        border-color: #17a2b8;
        box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
    }
    
    .btn-save {
        background: linear-gradient(135deg, #17a2b8, #138496);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
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
    
    .metodo-pago-item {
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.5rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .metodo-pago-item:hover {
        border-color: #17a2b8;
        background: #e3f2fd;
    }
    
    .metodo-pago-item.selected {
        border-color: #17a2b8;
        background: #e3f2fd;
        position: relative;
    }
    
    .metodo-pago-item.selected::after {
        content: "✓";
        position: absolute;
        top: 8px;
        right: 8px;
        background: #17a2b8;
        color: white;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .metodo-icon {
        font-size: 1.5rem;
        color: #17a2b8;
        margin-right: 1rem;
    }
    
    /* Toggle Switch Styles */
    .toggle-switch {
        width: 60px;
        height: 30px;
        position: relative;
        cursor: pointer;
        margin: 0;
    }
    
    .toggle-switch:checked {
        background-color: #17a2b8;
        border-color: #17a2b8;
    }
    
    .toggle-switch:focus {
        border-color: #17a2b8;
        box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
    }
    
    .toggle-label {
        margin-left: 10px;
        font-weight: 600;
        color: #495057;
    }
    
    .toggle-text-on, .toggle-text-off {
        display: none;
    }
    
    .toggle-switch:checked + .toggle-label .toggle-text-on {
        display: inline;
        color: #17a2b8;
    }
    
    .toggle-switch:not(:checked) + .toggle-label .toggle-text-off {
        display: inline;
        color: #6c757d;
    }
    
    .form-switch .form-check-input {
        width: 60px;
        height: 30px;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='%23fff'/%3e%3c/svg%3e");
        background-position: left center;
        background-size: contain;
        transition: all 0.3s ease;
    }
    
    .form-switch .form-check-input:checked {
        background-position: right center;
        background-color: #17a2b8;
        border-color: #17a2b8;
    }
</style>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0">
                    <i class="fas fa-credit-card me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Configurar autorizador para métodos de pago específicos</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?= url('/admin/autorizadores/metodos-pago') ?>" class="btn btn-light">
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
                    <strong>Información:</strong> Los autorizadores por método de pago permiten asignar personas específicas para autorizar requisiciones según la forma de pago utilizada.
                    <br><small class="mt-2 d-block">
                        <strong>Estado actual:</strong> 
                        <?= count($autorizadores ?? []) ?> autorizadores disponibles • 
                        <?= count($metodos_pago ?? []) ?> métodos de pago en sistema • 
                        <?= count($autorizadores_existentes ?? []) ?> autorizadores ya configurados
                    </small>
                </div>

                <form action="/admin/autorizadores/metodos-pago" method="POST" id="metodoPagoForm">
                    <?= CsrfMiddleware::field() ?>
                    
                    <!-- Sección: Información del Autorizador -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i>
                            Información del Autorizador
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="autorizador_email" class="form-label">
                                    Email del Autorizador <span class="required">*</span>
                                </label>
                                <select name="autorizador_email" id="autorizador_email" class="form-select" required>
                                    <option value="">Seleccionar autorizador...</option>
                                    <?php if (!empty($autorizadores)): ?>
                                        <?php foreach ($autorizadores as $auth): ?>
                                            <?php 
                                            $email = $auth['email'] ?? '';
                                            $nombre = $auth['nombre'] ?? '';
                                            $yaExiste = in_array($email, $autorizadores_existentes ?? []);
                                            ?>
                                            <option value="<?= View::e($email) ?>"
                                                    data-nombre="<?= View::e($nombre) ?>"
                                                    <?= $yaExiste ? 'class="text-muted" title="Ya tiene autorizaciones configuradas"' : '' ?>>
                                                <?= View::e($nombre) ?> (<?= View::e($email) ?>)
                                                <?= $yaExiste ? ' ⚠️' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>No hay autorizadores disponibles</option>
                                    <?php endif; ?>
                                </select>
                                <div class="help-text">Seleccione la persona que autorizará este método de pago</div>
                                <?php if (!empty($autorizadores_existentes)): ?>
                                    <div class="alert alert-warning mt-2" style="font-size: 0.875rem;">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <strong>Nota:</strong> Los autorizadores marcados con ⚠️ ya tienen configuraciones previas.
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-chart-bar me-1"></i>
                                    Estadísticas Rápidas
                                </label>
                                <div class="p-3 bg-light rounded">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="h5 mb-0 text-primary"><?= count($autorizadores ?? []) ?></div>
                                            <small class="text-muted">Autorizadores</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="h5 mb-0 text-success"><?= count($metodos_pago ?? []) ?></div>
                                            <small class="text-muted">Métodos</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="h5 mb-0 text-info"><?= count($autorizadores_existentes ?? []) ?></div>
                                            <small class="text-muted">Configurados</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección: Métodos de Pago -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-credit-card"></i>
                            Método de Pago Autorizado
                        </h3>
                        
                        <div class="row">
                            <div class="col-12">
                                <label class="form-label">
                                    Seleccionar Método de Pago <span class="required">*</span>
                                </label>
                                <div class="help-text mb-3">Seleccione un método de pago que este autorizador podrá autorizar</div>
                                
                                <div class="row">
                                    <?php if (!empty($metodos_pago)): ?>
                                        <?php foreach ($metodos_pago as $key => $descripcion): ?>
                                            <?php
                                            // Determinar icono basado en el tipo de método de pago
                                            $iconos = [
                                                'contado' => 'fa-hand-holding-usd',
                                                'tarjeta_credito_lic_milton' => 'fa-credit-card',
                                                'cheque' => 'fa-file-invoice',
                                                'transferencia' => 'fa-exchange-alt',
                                                'credito' => 'fa-calendar-alt',
                                                // Métodos adicionales que pueden estar en la BD
                                                'efectivo' => 'fa-money-bill-wave',
                                                'transferencia_bancaria' => 'fa-exchange-alt',
                                                'credito_30' => 'fa-calendar-alt'
                                            ];
                                            $icono = $iconos[$key] ?? 'fa-credit-card';
                                            ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="metodo-pago-item" onclick="toggleMetodo('<?= View::e($key) ?>')">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas <?= $icono ?> metodo-icon"></i>
                                                        <div class="flex-grow-1">
                                                            <strong><?= View::e($descripcion) ?></strong>
                                                            <div class="text-muted small">
                                                                <?php 
                                                                $subtitulos = [
                                                                    'contado' => 'Pago inmediato',
                                                                    'tarjeta_credito_lic_milton' => 'Tarjeta especial Lic. Milton',
                                                                    'cheque' => 'Pagos con cheque',
                                                                    'transferencia' => 'Transferencias bancarias',
                                                                    'credito' => 'Pago a crédito',
                                                                    // Métodos adicionales que pueden estar en la BD
                                                                    'efectivo' => 'Pagos en efectivo',
                                                                    'transferencia_bancaria' => 'Transferencias bancarias',
                                                                    'credito_30' => 'Pago a 30 días'
                                                                ];
                                                                echo View::e($subtitulos[$key] ?? 'Método de pago');
                                                                ?>
                                                            </div>
                                                        </div>
                                                        <input type="radio" name="metodo_pago" value="<?= View::e($key) ?>" 
                                                               id="<?= View::e($key) ?>" class="form-check-input ms-auto" style="display: none;">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                No se encontraron métodos de pago en el sistema.
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sección: Información Adicional -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-info"></i>
                            Información Adicional
                        </h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="prioridad" class="form-label">
                                    Prioridad
                                </label>
                                <select name="prioridad" id="prioridad" class="form-select">
                                    <option value="normal">Normal</option>
                                    <option value="alta">Alta</option>
                                    <option value="critica">Crítica</option>
                                </select>
                                <div class="help-text">Prioridad de este autorizador para estos métodos</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="fecha_inicio" class="form-label">
                                    Fecha de Inicio
                                </label>
                                <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" 
                                       value="<?= date('Y-m-d') ?>">
                                <div class="help-text">Cuando inicia la autorización (opcional)</div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <label for="observaciones" class="form-label">
                                    Observaciones
                                </label>
                                <textarea name="observaciones" id="observaciones" class="form-control" rows="3" 
                                          placeholder="Notas adicionales sobre esta autorización (opcional)"></textarea>
                                <div class="help-text">Información adicional o restricciones específicas</div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="switch-container">
                                    <div class="flex-grow-1">
                                        <label class="switch-label" for="activo">
                                            <i class="fas fa-toggle-on me-2 text-info"></i>
                                            Estado del Autorizador
                                        </label>
                                        <p class="switch-description">
                                            Si está activado, este autorizador podrá procesar autorizaciones inmediatamente
                                        </p>
                                    </div>
                                    <div class="custom-switch custom-switch-info">
                                        <input type="checkbox" name="activo" id="activo" value="1" checked>
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
                                Crear Autorizador
                            </button>
                            <a href="<?= url('/admin/autorizadores/metodos-pago') ?>" class="btn btn-cancel">
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
    const form = document.getElementById('metodoPagoForm');
    
    // Validación del formulario
    form.addEventListener('submit', function(e) {
        const metodoSeleccionado = document.querySelector('input[name="metodo_pago"]:checked');
        
        if (!metodoSeleccionado) {
            e.preventDefault();
            alert('Debe seleccionar un método de pago');
            return false;
        }
        
        const autorizadorEmail = document.getElementById('autorizador_email').value;
        if (!autorizadorEmail) {
            e.preventDefault();
            alert('Debe seleccionar un autorizador');
            return false;
        }
    });
    
    // Establecer fecha mínima
    const fechaInicio = document.getElementById('fecha_inicio');
    const hoy = new Date().toISOString().split('T')[0];
    fechaInicio.min = hoy;
    
    // Toggle switch functionality - visual feedback
    const toggleSwitch = document.getElementById('activo');
    if (toggleSwitch) {
        toggleSwitch.addEventListener('change', function() {
            const toggleContainer = this.closest('.bg-light');
            if (this.checked) {
                toggleContainer.style.borderLeft = '4px solid #28a745';
                toggleContainer.style.backgroundColor = '#f8fff8';
            } else {
                toggleContainer.style.borderLeft = '4px solid #dc3545';
                toggleContainer.style.backgroundColor = '#fff8f8';
            }
        });
        
        // Set initial state
        toggleSwitch.dispatchEvent(new Event('change'));
    }
});

function toggleMetodo(metodoId) {
    const radioButton = document.getElementById(metodoId);
    
    // Quitar selección de todos los otros métodos
    const allRadios = document.querySelectorAll('input[name="metodo_pago"]');
    const allItems = document.querySelectorAll('.metodo-pago-item');
    
    allItems.forEach(item => item.classList.remove('selected'));
    
    // Seleccionar el método actual
    radioButton.checked = true;
    const item = radioButton.closest('.metodo-pago-item');
    item.classList.add('selected');
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