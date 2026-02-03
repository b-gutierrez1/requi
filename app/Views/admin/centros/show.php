<?php 
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Detalles del Centro de Costo';
?>

<?php View::startSection('content'); ?>
<div class="container">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0">
                <i class="fas fa-building me-2"></i>
                Detalles del Centro de Costo
            </h1>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group" role="group">
                <a href="<?= url('/admin/centros/' . $centro->id . '/edit') ?>" class="btn btn-primary">
                    <i class="fas fa-edit me-2"></i>Editar
                </a>
                <a href="<?= url('/admin/centros') ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
            </div>
        </div>
    </div>

    <!-- Información del Centro -->
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información General
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">ID</label>
                                <div class="fw-bold">#<?= View::e($centro->id) ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Estado</label>
                                <div>
                                    <?php if ($centro->activo ?? true): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times me-1"></i>Inactivo
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label text-muted">Nombre</label>
                                <div class="fw-bold"><?= View::e($centro->nombre ?? 'Sin nombre') ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($centro->codigo)): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Código</label>
                                <div><code><?= View::e($centro->codigo) ?></code></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($centro->descripcion)): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label text-muted">Descripción</label>
                                <div><?= View::e($centro->descripcion) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($centro->unidad_negocio_id)): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Unidad de Negocio</label>
                                <div>ID: <?= View::e($centro->unidad_negocio_id) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label text-muted">Factura Asignada</label>
                                <div>
                                    <span class="badge bg-info fs-6">
                                        <i class="fas fa-file-invoice me-1"></i>
                                        Factura <?= View::e($centro->factura ?? 1) ?>
                                    </span>
                                </div>
                                <small class="text-muted">Los gastos de este centro se cargan a esta factura</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Autorizadores -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Autorizadores
                    </h5>
                </div>
                <div class="card-body">
                    <a href="<?= url('/admin/autorizadores?centro=' . $centro->id) ?>" class="btn btn-outline-info w-100">
                        <i class="fas fa-cog me-2"></i>
                        Gestionar Autorizadores
                    </a>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Estadísticas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <p class="text-muted mb-0">Información de uso disponible en reportes</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones -->
    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="<?= url('/admin/centros') ?>" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Volver a la Lista
            </a>
            <a href="<?= url('/admin/centros/' . $centro->id . '/edit') ?>" class="btn btn-primary me-2">
                <i class="fas fa-edit me-2"></i>Editar Centro
            </a>
            <a href="<?= url('/admin/autorizadores?centro=' . $centro->id) ?>" class="btn btn-outline-info">
                <i class="fas fa-users me-2"></i>Gestionar Autorizadores
            </a>
        </div>
    </div>
</div>
<?php View::endSection(); ?>