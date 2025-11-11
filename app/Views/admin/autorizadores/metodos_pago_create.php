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
    }
    
    .metodo-icon {
        font-size: 1.5rem;
        color: #17a2b8;
        margin-right: 1rem;
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
                <a href="/admin/autorizadores/metodos-pago" class="btn btn-light">
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
                                            <option value="<?= View::e($auth['email'] ?? '') ?>"
                                                    data-nombre="<?= View::e($auth['nombre'] ?? '') ?>"
                                                    data-cargo="<?= View::e($auth['cargo'] ?? '') ?>">
                                                <?= View::e($auth['nombre'] ?? '') ?> (<?= View::e($auth['email'] ?? '') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="help-text">Seleccione la persona que autorizará este método de pago</div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- Sección: Métodos de Pago -->
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-credit-card"></i>
                            Métodos de Pago Autorizados
                        </h3>
                        
                        <div class="row">
                            <div class="col-12">
                                <label class="form-label">
                                    Seleccionar Métodos de Pago <span class="required">*</span>
                                </label>
                                <div class="help-text mb-3">Seleccione uno o más métodos de pago que este autorizador podrá autorizar</div>
                                
                                <div class="row">
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="metodo-pago-item" onclick="toggleMetodo('efectivo')">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-money-bill-wave metodo-icon"></i>
                                                <div>
                                                    <strong>Efectivo</strong>
                                                    <div class="text-muted small">Pagos en efectivo</div>
                                                </div>
                                                <input type="checkbox" name="metodos_pago[]" value="efectivo" id="efectivo" class="form-check-input ms-auto">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="metodo-pago-item" onclick="toggleMetodo('transferencia')">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-exchange-alt metodo-icon"></i>
                                                <div>
                                                    <strong>Transferencia</strong>
                                                    <div class="text-muted small">Transferencias bancarias</div>
                                                </div>
                                                <input type="checkbox" name="metodos_pago[]" value="transferencia" id="transferencia" class="form-check-input ms-auto">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="metodo-pago-item" onclick="toggleMetodo('cheque')">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-file-invoice metodo-icon"></i>
                                                <div>
                                                    <strong>Cheque</strong>
                                                    <div class="text-muted small">Pagos con cheque</div>
                                                </div>
                                                <input type="checkbox" name="metodos_pago[]" value="cheque" id="cheque" class="form-check-input ms-auto">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="metodo-pago-item" onclick="toggleMetodo('tarjeta')">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-credit-card metodo-icon"></i>
                                                <div>
                                                    <strong>Tarjeta de Crédito</strong>
                                                    <div class="text-muted small">Pagos con tarjeta</div>
                                                </div>
                                                <input type="checkbox" name="metodos_pago[]" value="tarjeta" id="tarjeta" class="form-check-input ms-auto">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="metodo-pago-item" onclick="toggleMetodo('deposito')">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-university metodo-icon"></i>
                                                <div>
                                                    <strong>Depósito Bancario</strong>
                                                    <div class="text-muted small">Depósitos a cuentas</div>
                                                </div>
                                                <input type="checkbox" name="metodos_pago[]" value="deposito" id="deposito" class="form-check-input ms-auto">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="metodo-pago-item" onclick="toggleMetodo('otro')">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-ellipsis-h metodo-icon"></i>
                                                <div>
                                                    <strong>Otro</strong>
                                                    <div class="text-muted small">Otros métodos</div>
                                                </div>
                                                <input type="checkbox" name="metodos_pago[]" value="otro" id="otro" class="form-check-input ms-auto">
                                            </div>
                                        </div>
                                    </div>
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
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="activo" id="activo" value="1" checked>
                                    <label class="form-check-label" for="activo">
                                        Activar autorización inmediatamente
                                    </label>
                                    <div class="help-text">Si está marcado, la autorización estará activa desde hoy</div>
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
                            <a href="/admin/autorizadores/metodos-pago" class="btn btn-cancel">
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
        const metodosSeleccionados = document.querySelectorAll('input[name="metodos_pago[]"]:checked');
        
        if (metodosSeleccionados.length === 0) {
            e.preventDefault();
            alert('Debe seleccionar al menos un método de pago');
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
});

function toggleMetodo(metodoId) {
    const checkbox = document.getElementById(metodoId);
    const item = checkbox.closest('.metodo-pago-item');
    
    checkbox.checked = !checkbox.checked;
    
    if (checkbox.checked) {
        item.classList.add('selected');
    } else {
        item.classList.remove('selected');
    }
}
</script>
<?php View::endSection(); ?>