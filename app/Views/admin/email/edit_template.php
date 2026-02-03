<?php
use App\Helpers\View;

View::startSection('title', 'Editar Plantilla: ' . $template);
View::startSection('content');
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-edit me-2"></i>Editar Plantilla: <?= View::e($template) ?>
            </h1>
            <p class="text-muted">Edita el contenido HTML de la plantilla de correo</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="<?= url('/admin/email/templates/' . $template . '/save') ?>">
                        <?php echo App\Middlewares\CsrfMiddleware::field(); ?>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Contenido HTML</label>
                            <textarea class="form-control font-monospace" id="content" name="content" 
                                      rows="25" style="font-size: 12px;" required><?= View::e($content) ?></textarea>
                            <small class="form-text text-muted">Puedes usar variables como {{variable_name}} que se reemplazarán al enviar</small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= url('/admin/email/templates') ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Plantilla
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Variables Disponibles</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">Usa estas variables en tu plantilla:</p>
                    <ul class="list-unstyled small">
                        <?php foreach ($variables as $var): ?>
                            <li class="mb-2">
                                <code>{{<?= View::e($var) ?>}}</code>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <hr>
                    
                    <h6 class="small">Variables Comunes:</h6>
                    <ul class="list-unstyled small">
                        <li><code>{{app_name}}</code> - Nombre de la aplicación</li>
                        <li><code>{{year}}</code> - Año actual</li>
                        <li><code>{{titulo}}</code> - Título del correo</li>
                        <li><code>{{content}}</code> - Contenido principal</li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Precaución</h6>
                </div>
                <div class="card-body">
                    <p class="small mb-0">
                        Asegúrate de mantener la estructura HTML válida. 
                        Las plantillas se validan antes de guardar.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php View::endSection(); ?>



