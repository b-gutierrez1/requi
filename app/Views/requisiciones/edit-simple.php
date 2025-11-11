<?php
use App\Helpers\View;
use App\Middlewares\CsrfMiddleware;

View::startSection('title', 'Editar Requisición');
View::startSection('content');
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="h3 mb-2">
                <i class="fas fa-edit me-2"></i>
                Editar Requisición #<?php echo View::e($requisicion['orden']->id ?? $requisicion['orden']['id'] ?? ''); ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/requisiciones">Requisiciones</a></li>
                    <li class="breadcrumb-item active">Editar #<?php echo View::e($requisicion['orden']->id ?? $requisicion['orden']['id'] ?? ''); ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <?php 
    $estadoActual = $requisicion['flujo']->estado ?? $requisicion['flujo']['estado'] ?? 'sin_flujo';
    if ($estadoActual !== 'borrador'): 
    ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Atención:</strong> Esta requisición ya está en proceso. Solo puede editar ciertos campos.
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información General</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Folio -->
                <div class="col-md-3">
                    <label class="form-label fw-bold">Folio</label>
                    <input type="text" class="form-control bg-light" value="<?php echo View::e($requisicion['orden']->id ?? $requisicion['orden']['id'] ?? ''); ?>" disabled>
                </div>

                <!-- Estado -->
                <div class="col-md-3">
                    <label class="form-label fw-bold">Estado</label>
                    <input type="text" class="form-control bg-light" 
                           value="<?php echo ucfirst(str_replace('_', ' ', $estadoActual)); ?>" disabled>
                </div>

                <!-- Justificación -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Justificación <span class="text-danger">*</span></label>
                    <textarea name="justificacion" class="form-control" rows="3" required><?php echo View::e($requisicion['orden']->justificacion ?? $requisicion['orden']['justificacion'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Guardar Cambios
        </button>
        <a href="/requisiciones/<?php echo $requisicion['orden']->id ?? $requisicion['orden']['id'] ?? ''; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>
</div>

<?php View::endSection(); ?>















