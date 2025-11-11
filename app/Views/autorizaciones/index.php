<?php
use App\Helpers\View;

View::startSection('content');
?>


<div class="container py-4" style="max-width: 1200px;">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">
                <i class="fas fa-check-circle me-2"></i>
                Mis Autorizaciones Pendientes
            </h1>
            <p class="text-muted mb-0">
                Requisiciones que requieren tu autorización
                <span class="badge bg-warning text-dark ms-2">
                    <?php echo $total_pendientes ?? 0; ?> pendiente<?php echo ($total_pendientes ?? 0) != 1 ? 's' : ''; ?>
                </span>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/autorizaciones/historial" class="btn btn-outline-secondary">
                <i class="fas fa-history me-2"></i>Ver Historial
            </a>
        </div>
    </div>

    <!-- Requisiciones Pendientes de Revisión -->
    <?php if (!empty($requisiciones_pendientes_revision)): ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-eye me-2"></i>
                Requisiciones Pendientes de Revisión
            </h5>
            <a href="/autorizaciones/revision/pendientes" class="btn btn-light btn-sm">
                <i class="fas fa-list me-1"></i>
                Ver Todas
            </a>
        </div>
        <div class="card-body">
            <?php foreach ($requisiciones_pendientes_revision as $req): ?>
            <div class="card mb-3 border-start border-primary border-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1">
                                <i class="fas fa-file-invoice me-2"></i>
                                Requisición #<?php echo $req['orden_id']; ?>
                            </h6>
                            <p class="text-muted mb-1">
                                <strong>Solicitante:</strong> <?php echo View::e($req['usuario_nombre'] ?? 'Usuario desconocido'); ?>
                            </p>
                            <p class="text-muted mb-1">
                                <strong>Proveedor:</strong> <?php echo View::e($req['nombre_razon_social']); ?>
                            </p>
                            <p class="text-muted mb-0">
                                <strong>Monto:</strong> Q<?php echo number_format($req['monto_total'], 2); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="mb-2">
                                <small class="text-muted">Fecha: <?php echo date('d/m/Y', strtotime($req['fecha_orden'])); ?></small>
                            </div>
                            <div class="btn-group">
                                <a href="/autorizaciones/<?php echo $req['orden_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>
                                    Revisar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Todas las Autorizaciones Pendientes (Unificadas) -->
    <?php if (!empty($todas_autorizaciones)): ?>
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-check-circle me-2"></i>
                Mis Autorizaciones Pendientes
                <?php if ($es_autorizador_respaldo ?? false): ?>
                    <span class="badge bg-info text-white ms-2">
                        <i class="fas fa-user-shield me-1"></i>Autorizador de Respaldo
                    </span>
                <?php endif; ?>
            </h5>
            <?php if (!empty($tipo_autorizador) && is_array($tipo_autorizador)): ?>
                <span class="badge bg-secondary">Tipo: <?php echo ucfirst(implode(', ', $tipo_autorizador)); ?></span>
            <?php elseif (!empty($tipo_autorizador) && is_string($tipo_autorizador)): ?>
                <span class="badge bg-secondary">Tipo: <?php echo ucfirst($tipo_autorizador); ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php foreach ($todas_autorizaciones as $auth): ?>
            <div class="card mb-3 border-start border-3 
                <?php 
                    echo match($auth['tipo'] ?? 'centro_costo') {
                        'forma_pago' => 'border-success',
                        'cuenta_contable' => 'border-info', 
                        'revision' => 'border-primary',
                        default => 'border-warning'
                    };
                ?>">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <!-- Encabezado según el tipo -->
                            <div class="d-flex align-items-center mb-2">
                                <?php if ($auth['tipo'] === 'forma_pago'): ?>
                                    <i class="fas fa-credit-card me-2 text-success"></i>
                                    <span class="badge bg-success me-2">Especial - Forma de Pago</span>
                                <?php elseif ($auth['tipo'] === 'cuenta_contable'): ?>
                                    <i class="fas fa-calculator me-2 text-info"></i>
                                    <span class="badge bg-info me-2">Especial - Cuenta Contable</span>
                                <?php elseif ($auth['tipo'] === 'revision'): ?>
                                    <i class="fas fa-eye me-2 text-primary"></i>
                                    <span class="badge bg-primary me-2">Revisión</span>
                                <?php else: ?>
                                    <i class="fas fa-building me-2 text-warning"></i>
                                    <span class="badge bg-warning text-dark me-2">Centro de Costo</span>
                                <?php endif; ?>
                                
                                <!-- Badge de respaldo si aplica -->
                                <?php if (isset($auth['metadata']) && $auth['metadata']): 
                                    $metadata = is_string($auth['metadata']) ? json_decode($auth['metadata'], true) : $auth['metadata'];
                                    if ($metadata['es_respaldo'] ?? false):
                                ?>
                                    <span class="badge bg-secondary text-white">
                                        <i class="fas fa-user-shield me-1"></i>Respaldo
                                    </span>
                                <?php 
                                    endif; 
                                endif; 
                                ?>
                            </div>
                            
                            <h6 class="mb-1">
                                Requisición #<?php echo $auth['orden_id'] ?? $auth['requisicion_id']; ?>
                            </h6>
                            <p class="text-muted mb-1">
                                <strong>Proveedor:</strong> <?php echo View::e($auth['nombre_razon_social']); ?>
                            </p>
                            
                            <!-- Información específica por tipo -->
                            <?php if ($auth['tipo'] === 'centro_costo' && isset($auth['centro_nombre'])): ?>
                                <p class="text-muted mb-1">
                                    <strong>Centro:</strong> <?php echo View::e($auth['centro_nombre']); ?>
                                    <?php if (isset($auth['porcentaje'])): ?>
                                        (<?php echo $auth['porcentaje']; ?>%)
                                    <?php endif; ?>
                                </p>
                            <?php elseif ($auth['tipo'] === 'forma_pago' && isset($auth['metadata'])): 
                                $metadata = is_string($auth['metadata']) ? json_decode($auth['metadata'], true) : $auth['metadata'];
                            ?>
                                <p class="text-muted mb-1">
                                    <strong>Forma de Pago:</strong> <?php echo View::e($metadata['forma_pago'] ?? 'No especificada'); ?>
                                </p>
                            <?php elseif ($auth['tipo'] === 'cuenta_contable' && isset($auth['metadata'])): 
                                $metadata = is_string($auth['metadata']) ? json_decode($auth['metadata'], true) : $auth['metadata'];
                            ?>
                                <p class="text-muted mb-1">
                                    <strong>Cuenta:</strong> <?php echo View::e($metadata['cuenta_nombre'] ?? 'No especificada'); ?>
                                </p>
                            <?php endif; ?>
                            
                            <p class="text-muted mb-0">
                                <strong>Monto:</strong> Q<?php echo number_format($auth['monto_total'], 2); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="mb-2">
                                <small class="text-muted">
                                    Fecha: <?php echo date('d/m/Y', strtotime($auth['fecha_orden'] ?? $auth['created_at'])); ?>
                                </small>
                            </div>
                            <div class="btn-group">
                                <a href="/autorizaciones/<?php echo $auth['orden_id'] ?? $auth['requisicion_id']; ?>" 
                                   class="btn btn-sm
                                   <?php 
                                       echo match($auth['tipo'] ?? 'centro_costo') {
                                           'forma_pago' => 'btn-success',
                                           'cuenta_contable' => 'btn-info', 
                                           'revision' => 'btn-primary',
                                           default => 'btn-warning'
                                       };
                                   ?>">
                                    <i class="fas fa-check me-1"></i>
                                    <?php echo ($auth['tipo'] === 'revision') ? 'Revisar' : 'Autorizar'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Autorizaciones Pendientes (Vista de compatibilidad) -->
    <?php if (!empty($autorizaciones_pendientes) && empty($todas_autorizaciones)): ?>
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-check-circle me-2"></i>
                Autorizaciones por Centro de Costo
            </h5>
        </div>
        <div class="card-body">
            <?php foreach ($autorizaciones_pendientes as $auth): ?>
            <div class="card mb-3 border-start border-warning border-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1">
                                <i class="fas fa-building me-2"></i>
                                Centro: <?php echo View::e($auth['centro_nombre']); ?>
                            </h6>
                            <p class="text-muted mb-1">
                                <strong>Requisición:</strong> #<?php echo $auth['orden_id']; ?> - <?php echo View::e($auth['nombre_razon_social']); ?>
                            </p>
                            <p class="text-muted mb-0">
                                <strong>Porcentaje:</strong> <?php echo $auth['porcentaje']; ?>%
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="mb-2">
                                <small class="text-muted">Fecha: <?php echo date('d/m/Y', strtotime($auth['fecha_orden'])); ?></small>
                            </div>
                            <div class="btn-group">
                                <a href="/autorizaciones/<?php echo $auth['orden_id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-check me-1"></i>
                                    Autorizar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Mensaje cuando no hay nada pendiente -->
    <?php if (empty($requisiciones_pendientes_revision) && empty($autorizaciones_pendientes) && empty($todas_autorizaciones)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-check-double fs-1 text-success mb-3"></i>
            <h4 class="text-muted">¡Excelente!</h4>
            <p class="text-muted">No tienes autorizaciones pendientes en este momento.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
View::endSection();

View::startSection('scripts');
?>
<script>
// Los efectos se aplican automáticamente por el CSS y JS global
</script>
<?php View::endSection(); ?>