<?php 
use App\Helpers\View;
use App\Helpers\Session;
use App\Middlewares\CsrfMiddleware;

$title = 'Editar Autorizador de Método de Pago';
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
    .form-control:focus {
        border-color: #17a2b8;
        box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.25);
    }
    .btn-primary {
        background: linear-gradient(135deg, #17a2b8, #138496);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        background: linear-gradient(135deg, #138496, #17a2b8);
    }
    .btn-secondary {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        font-weight: 600;
    }
    .alert {
        border-radius: 8px;
        border: none;
    }
    .current-value {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 10px;
        border-left: 4px solid #17a2b8;
    }
</style>

<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0"><i class="fas fa-edit me-3"></i><?= View::e($title) ?></h1>
                <p class="mb-0 opacity-75">Modificar configuración del autorizador por método de pago</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/admin/autorizadores/metodos-pago" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Volver a la Lista
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php 
            $flash = Session::getFlash();
            if ($flash):
            ?>
                <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?= $flash['type'] === 'error' ? 'exclamation-triangle' : ($flash['type'] === 'success' ? 'check-circle' : 'info-circle') ?> me-2"></i>
                    <?= View::e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="/admin/autorizadores/metodos-pago/<?= urlencode($autorizador['email']) ?>/edit">
                    <?= CsrfMiddleware::field() ?>
                    <input type="hidden" name="_method" value="PUT">
                    
                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-user"></i>Información del Autorizador
                        </h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="current-value">
                                    <strong>Email:</strong><br>
                                    <span><?= View::e($autorizador['email']) ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="current-value">
                                    <strong>ID:</strong><br>
                                    <span>#<?= View::e($autorizador['id'] ?? 'N/A') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-credit-card"></i>Configuración de Método de Pago
                        </h3>
                        
                        <div class="mb-3">
                            <label for="metodo_pago" class="form-label">
                                <i class="fas fa-credit-card me-1"></i>Método de Pago Autorizado <span class="text-danger">*</span>
                            </label>
                            <div class="current-value">
                                <strong>Valor actual:</strong> 
                                <?php 
                                $metodo_actual = $autorizador['metodo_pago_actual'] ?? '';
                                $metodo_texto = $metodos_pago[$metodo_actual] ?? $metodo_actual;
                                echo View::e($metodo_texto ?: 'No especificado');
                                ?>
                            </div>
                            <select class="form-control" id="metodo_pago" name="metodo_pago" required>
                                <option value="">-- Seleccionar Método de Pago --</option>
                                <?php foreach ($metodos_pago as $valor => $texto): ?>
                                    <option value="<?= View::e($valor) ?>" 
                                            <?= ($autorizador['metodo_pago_actual'] ?? '') === $valor ? 'selected' : '' ?>>
                                        <?= View::e($texto) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">
                            <i class="fas fa-toggle-on"></i>Estado del Autorizador
                        </h3>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" 
                                   <?= (!isset($autorizador['activo']) || $autorizador['activo']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="activo">
                                <strong>Autorizador Activo</strong>
                                <br>
                                <small class="text-muted">
                                    Si está marcado, este autorizador puede aprobar métodos de pago. 
                                    Si no está marcado, el autorizador estará deshabilitado.
                                </small>
                            </label>
                        </div>
                        
                        <div class="current-value mt-3">
                            <strong>Estado actual:</strong>
                            <span class="badge <?= (!isset($autorizador['activo']) || $autorizador['activo']) ? 'bg-success' : 'bg-danger' ?>">
                                <?= (!isset($autorizador['activo']) || $autorizador['activo']) ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-primary me-3">
                                <i class="fas fa-save me-2"></i>Guardar Cambios
                            </button>
                            <a href="/admin/autorizadores/metodos-pago" class="btn btn-secondary">
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
    // Agregar confirmación antes de enviar
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!confirm('¿Está seguro de que desea guardar estos cambios?')) {
            e.preventDefault();
        }
    });
    
    // Highlight del método de pago seleccionado
    const selectMetodo = document.getElementById('metodo_pago');
    selectMetodo.addEventListener('change', function() {
        if (this.value) {
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
        }
    });
});
</script>
<?php View::endSection(); ?>