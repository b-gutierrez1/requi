<?php
use App\Helpers\View;

// Helper para obtener valor de objeto o array
function getValue($data, $key, $default = null) {
    if (is_object($data)) {
        return isset($data->$key) ? $data->$key : $default;
    } elseif (is_array($data)) {
        return isset($data[$key]) ? $data[$key] : $default;
    }
    return $default;
}

// Rechazos consolidados (revisión inicial y autorizaciones). Puede venir vacío.
$rechazos = (isset($rechazos) && is_array($rechazos)) ? $rechazos : [];

// Moneda de la requisición (GTQ, USD, EUR). Todos los montos de esta pantalla
// pertenecen a una sola requisición, así que comparten esta moneda.
$monedaOrden = getValue($orden, 'moneda') ?: 'GTQ';

View::startSection('content');
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">
                <i class="fas fa-chart-line me-2"></i>
                Detalle Completo - Requisición #<?php echo getValue($orden, 'id'); ?>
            </h1>
            <p class="text-muted mb-0">Seguimiento paso a paso del flujo de autorización</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= url('/admin/requisiciones/' . getValue($orden, 'id') . '/logs') ?>" class="btn btn-info me-2" title="Ver logs detallados">
                <i class="fas fa-file-alt me-2"></i>Ver Logs
            </a>
            <a href="<?= url('/admin/requisiciones') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver al Panel
            </a>
        </div>
    </div>

    <!-- Detalle del Rechazo (sólo si la requisición fue rechazada) -->
    <?php if (!empty($rechazos)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-danger shadow-sm">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-ban me-2"></i>
                        <?php echo count($rechazos) > 1 ? 'Requisición rechazada' : 'Motivo del rechazo'; ?>
                    </h5>
                    <?php if (count($rechazos) > 1): ?>
                        <span class="badge bg-light text-danger">
                            <?php echo count($rechazos); ?> rechazos registrados
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php foreach ($rechazos as $indiceRechazo => $rechazo): ?>
                    <?php
                        $tieneMotivo     = ($rechazo['motivo'] ?? '') !== '';
                        $tieneComentario = ($rechazo['comentario'] ?? '') !== ''
                            && ($rechazo['comentario'] ?? '') !== ($rechazo['motivo'] ?? '');
                        $autorRechazo    = ($rechazo['autor'] ?? '') !== ''
                            ? $rechazo['autor']
                            : 'Autorizador no registrado';
                        $correoRechazo   = $rechazo['autor_email'] ?? null;
                        $mostrarCorreo   = $correoRechazo && $correoRechazo !== $autorRechazo;
                    ?>
                    <div class="border-start border-3 border-danger ps-3 <?php echo $indiceRechazo < count($rechazos) - 1 ? 'mb-4' : ''; ?>">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <h6 class="mb-0">
                                <i class="fas <?php echo View::e($rechazo['etapa_icono'] ?? 'fa-ban'); ?> text-danger me-1"></i>
                                Rechazada <?php echo View::e($rechazo['etapa_frase'] ?? 'en la autorización'); ?>
                                <?php if (($rechazo['etapa_detalle'] ?? '') !== ''): ?>
                                    <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle ms-1">
                                        <?php echo View::e($rechazo['etapa_detalle']); ?>
                                    </span>
                                <?php endif; ?>
                            </h6>
                            <span class="badge bg-secondary">
                                <?php echo View::e($rechazo['etapa_label'] ?? 'Autorización'); ?>
                            </span>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Rechazada por</small>
                                <span><i class="fas fa-user me-1 text-muted"></i><?php echo View::e($autorRechazo); ?></span>
                                <?php if ($mostrarCorreo): ?>
                                    <br><small class="text-muted"><?php echo View::e($correoRechazo); ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Fecha del rechazo</small>
                                <span>
                                    <i class="fas fa-clock me-1 text-muted"></i>
                                    <?php
                                        $fechaRechazo = $rechazo['fecha'] ?? null;
                                        $tsRechazo = $fechaRechazo ? strtotime($fechaRechazo) : false;
                                        echo $tsRechazo ? date('d/m/Y H:i', $tsRechazo) : 'Fecha no registrada';
                                    ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($tieneMotivo): ?>
                            <div class="alert alert-danger py-2 px-3 mb-2">
                                <small class="d-block fw-bold text-uppercase">Motivo</small>
                                <span class="text-break"><?php echo nl2br(View::e($rechazo['motivo'])); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($tieneComentario): ?>
                            <div class="alert alert-light border py-2 px-3 mb-2">
                                <small class="d-block fw-bold text-uppercase text-muted">Comentario</small>
                                <span class="text-break"><?php echo nl2br(View::e($rechazo['comentario'])); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!$tieneMotivo && !$tieneComentario): ?>
                            <p class="text-muted fst-italic mb-2">
                                <i class="fas fa-info-circle me-1"></i>
                                No se registró un motivo para este rechazo.
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($rechazo['motivo_desde_historial'])): ?>
                            <small class="text-muted">
                                <i class="fas fa-history me-1"></i>Motivo recuperado del historial de la requisición
                            </small>
                        <?php endif; ?>
                    </div>
                    <?php if ($indiceRechazo < count($rechazos) - 1): ?><hr class="my-3"><?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Timeline Principal -->
        <div class="col-lg-8">
            <!-- Información Básica -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información General
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Proveedor:</strong></p>
                            <p><?php echo View::e(getValue($orden, 'nombre_razon_social')); ?></p>
                            
                            <p class="mb-2"><strong>Forma de Pago:</strong></p>
                            <p>
                                <?php echo View::e(getValue($orden, 'forma_pago')); ?>
                                <?php if ($flujo['requiere_autorizacion_especial_pago'] ?? false): ?>
                                    <span class="badge bg-warning text-dark ms-2">Requiere Autorización Especial</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Monto Total:</strong></p>
                            <p class="text-success fs-4"><?php echo View::money(getValue($orden, 'monto_total'), $monedaOrden); ?></p>
                            
                            <p class="mb-2"><strong>Estado Actual:</strong></p>
                            <p>
                                <?php
                                    // Fuente unica: EstadoHelper (antes, mapa propio duplicado).
                                    // null cuando no hay flujo: el helper lo rotula "Borrador".
                                    $badgeFlujo = \App\Helpers\EstadoHelper::getBadgeFlujo($flujo['estado'] ?? null);
                                ?>
                                <span class="badge <?php echo $badgeFlujo['class']; ?> fs-6">
                                    <i class="fas fa-<?php echo $badgeFlujo['icon']; ?> me-1"></i>
                                    <?php echo View::e($badgeFlujo['text']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Barra de progreso -->
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Progreso del Flujo</span>
                            <span class="text-muted"><?php echo $estadisticas['progreso_porcentaje']; ?>% Completado</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="width: <?php echo $estadisticas['progreso_porcentaje']; ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline de Autorizaciones -->
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Timeline del Flujo de Autorización
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($timeline as $index => $evento): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker">
                                <div class="timeline-marker-icon bg-<?php echo $evento['color']; ?>">
                                    <i class="<?php echo $evento['icono']; ?>"></i>
                                </div>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <h6 class="timeline-title">
                                        <?php echo View::e($evento['titulo']); ?>
                                        <?php if (isset($evento['es_respaldo']) && $evento['es_respaldo']): ?>
                                            <span class="badge bg-secondary text-white">
                                                <i class="fas fa-user-shield me-1"></i>Respaldo
                                            </span>
                                        <?php endif; ?>
                                    </h6>
                                    <p class="timeline-date">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php 
                                            if ($evento['fecha'] && $evento['fecha'] !== 'Pendiente') {
                                                echo date('d/m/Y H:i', strtotime($evento['fecha']));
                                            } else {
                                                echo 'Pendiente';
                                            }
                                        ?>
                                    </p>
                                </div>
                                <div class="timeline-body">
                                    <p class="mb-1"><?php echo View::e($evento['descripcion']); ?></p>
                                    
                                    <?php if (isset($evento['usuario']) && $evento['usuario']): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            Por: <?php echo View::e($evento['usuario']); ?>
                                        </small>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($evento['asignado_a']) && $evento['asignado_a'] && (!isset($evento['usuario']) || !$evento['usuario'])): ?>
                                        <small class="text-info d-block mt-1">
                                            <i class="fas fa-arrow-right me-1"></i>
                                            Asignado a: <?php echo View::e($evento['asignado_a']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Items de la Requisición -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Items Solicitados
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Descripción</th>
                                    <th>Cantidad</th>
                                    <th class="text-end">Precio Unit.</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $i => $item): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo View::e($item['descripcion']); ?></td>
                                    <td><?php echo number_format($item['cantidad']); ?></td>
                                    <td class="text-end"><?php echo View::money($item['precio_unitario'], $monedaOrden, 3); ?></td>
                                    <td class="text-end">
                                        <strong><?php echo View::money($item['total'], $monedaOrden); ?></strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Distribución de Gastos -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>
                        Distribución de Gastos
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Unidad de Negocio</th>
                                    <th>Cuenta Contable</th>
                                    <th class="text-end">%</th>
                                    <th class="text-end">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($distribucion as $dist): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo View::e($dist['centro_nombre'] ?? 'N/A'); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo View::e($dist['cuenta_nombre'] ?? 'N/A'); ?>
                                        <br><small class="text-muted"><?php echo View::e($dist['cuenta_codigo'] ?? ''); ?></small>
                                        <?php if ($flujo['requiere_autorizacion_especial_cuenta'] ?? false): ?>
                                            <br><span class="badge bg-info">Requiere Autorización Especial</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?php echo number_format($dist['porcentaje'], 2); ?>%</td>
                                    <td class="text-end">
                                        <strong><?php echo View::money($dist['cantidad'], $monedaOrden); ?></strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar con Información Adicional -->
        <div class="col-lg-4">
            <!-- Estadísticas del Flujo -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Estadísticas del Flujo
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-primary"><?php echo $estadisticas['tiempo_total'] ?? 0; ?></h4>
                            <small class="text-muted">Días Total</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-warning"><?php echo $estadisticas['tiempo_revision'] ?? 0; ?></h4>
                            <small class="text-muted">Días Revisión</small>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="text-muted mb-2">Autorizaciones Especiales</h6>
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="text-warning"><?php echo $estadisticas['autorizaciones_especiales']['pendientes']; ?></div>
                            <small class="text-muted">Pendientes</small>
                        </div>
                        <div class="col-4">
                            <div class="text-success"><?php echo $estadisticas['autorizaciones_especiales']['autorizadas']; ?></div>
                            <small class="text-muted">Autorizadas</small>
                        </div>
                        <div class="col-4">
                            <div class="text-danger"><?php echo $estadisticas['autorizaciones_especiales']['rechazadas']; ?></div>
                            <small class="text-muted">Rechazadas</small>
                        </div>
                    </div>
                    
                    <h6 class="text-muted mb-2">Autorizaciones por Unidad de Negocio</h6>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="text-warning"><?php echo $estadisticas['autorizaciones_centros']['pendientes']; ?></div>
                            <small class="text-muted">Pendientes</small>
                        </div>
                        <div class="col-4">
                            <div class="text-success"><?php echo $estadisticas['autorizaciones_centros']['autorizadas']; ?></div>
                            <small class="text-muted">Autorizadas</small>
                        </div>
                        <div class="col-4">
                            <div class="text-danger"><?php echo $estadisticas['autorizaciones_centros']['rechazadas']; ?></div>
                            <small class="text-muted">Rechazadas</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Autorizaciones Especiales Detalle -->
            <?php if (!empty($autorizaciones_especiales)): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-star me-2"></i>
                        Autorizaciones Especiales
                    </h6>
                </div>
                <div class="card-body">
                    <?php foreach ($autorizaciones_especiales as $auth): ?>
                    <?php
                        $metadata = json_decode($auth['metadata'], true);
                        $esRespaldo = $metadata['es_respaldo'] ?? false;
                    ?>
                    <div class="border-start border-3 border-<?php echo $auth['tipo'] === 'forma_pago' ? 'success' : 'info'; ?> ps-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    <?php if ($auth['tipo'] === 'forma_pago'): ?>
                                        <i class="fas fa-credit-card text-success me-1"></i>Forma de Pago
                                    <?php else: ?>
                                        <i class="fas fa-calculator text-info me-1"></i>Cuenta Contable
                                    <?php endif; ?>
                                    
                                    <?php if ($esRespaldo): ?>
                                        <span class="badge bg-secondary text-white">Respaldo</span>
                                    <?php endif; ?>
                                </h6>
                                <p class="mb-1 text-muted small">
                                    <?php 
                                        if ($auth['tipo'] === 'forma_pago') {
                                            echo View::e($metadata['forma_pago'] ?? 'N/A');
                                        } else {
                                            echo View::e($metadata['cuenta_nombre'] ?? 'N/A');
                                        }
                                    ?>
                                </p>
                                <p class="mb-0 text-muted small">
                                    <i class="fas fa-user me-1"></i><?php echo View::e($auth['autorizador_email']); ?>
                                </p>
                            </div>
                            <span class="badge bg-<?php echo $auth['estado'] === 'autorizada' ? 'success' : ($auth['estado'] === 'rechazada' ? 'danger' : 'warning'); ?>">
                                <?php echo ucfirst($auth['estado']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Autorizaciones por Unidad de Negocio Detalle -->
            <?php if (!empty($autorizaciones_centros)): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-building me-2"></i>
                        Autorizaciones por Unidad de Negocio
                    </h6>
                </div>
                <div class="card-body">
                    <?php foreach ($autorizaciones_centros as $auth): ?>
                    <?php
                        $metadata = json_decode($auth['metadata'] ?? '{}', true);
                        $esRespaldo = $metadata['es_respaldo'] ?? false;
                    ?>
                    <div class="border-start border-3 border-warning ps-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    <i class="fas fa-building text-warning me-1"></i>
                                    <?php echo View::e($auth['centro_nombre']); ?>
                                    
                                    <?php if ($esRespaldo): ?>
                                        <span class="badge bg-secondary text-white">Respaldo</span>
                                    <?php endif; ?>
                                </h6>
                                <p class="mb-1 text-muted small">
                                    Porcentaje: <?php echo $auth['porcentaje']; ?>%
                                </p>
                                <p class="mb-0 text-muted small">
                                    <i class="fas fa-user me-1"></i><?php echo View::e($auth['autorizador_email']); ?>
                                </p>
                            </div>
                            <span class="badge bg-<?php echo $auth['estado'] === 'autorizado' ? 'success' : ($auth['estado'] === 'rechazado' ? 'danger' : 'warning'); ?>">
                                <?php echo ucfirst($auth['estado']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Respaldos Activos -->
            <?php if (!empty($respaldos)): ?>
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user-shield me-2"></i>
                        Autorizadores de Respaldo Activos
                    </h6>
                </div>
                <div class="card-body">
                    <?php foreach ($respaldos as $respaldo): ?>
                    <div class="border-start border-3 border-secondary ps-3 mb-3">
                        <h6 class="mb-1"><?php echo View::e($respaldo['centro_nombre']); ?></h6>
                        <p class="mb-1 text-muted small">
                            <strong>Principal:</strong> <?php echo View::e($respaldo['autorizador_principal_email']); ?>
                        </p>
                        <p class="mb-1 text-muted small">
                            <strong>Respaldo:</strong> <?php echo View::e($respaldo['autorizador_respaldo_email']); ?>
                        </p>
                        <p class="mb-0 text-muted small">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('d/m/Y', strtotime($respaldo['fecha_inicio'])); ?> - 
                            <?php echo date('d/m/Y', strtotime($respaldo['fecha_fin'])); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- CSS para Timeline -->
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    width: 20px;
    height: 20px;
}

.timeline-marker-icon {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 10px;
}

.timeline-content {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    position: relative;
}

.timeline-content::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 15px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 8px 8px 8px 0;
    border-color: transparent #dee2e6 transparent transparent;
}

.timeline-content::after {
    content: '';
    position: absolute;
    left: -7px;
    top: 15px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 8px 8px 8px 0;
    border-color: transparent #f8f9fa transparent transparent;
}

.timeline-header {
    margin-bottom: 10px;
}

.timeline-title {
    margin-bottom: 5px;
    color: #495057;
}

.timeline-date {
    margin: 0;
    color: #6c757d;
    font-size: 0.875rem;
}

.timeline-body {
    color: #6c757d;
}

.timeline::before {
    content: '';
    position: absolute;
    left: -20px;
    top: 10px;
    bottom: 10px;
    width: 2px;
    background: #dee2e6;
}
</style>

<?php
View::endSection();

View::startSection('scripts');
?>
<script>
// Los efectos se aplican automáticamente por el CSS y JS global
// Aquí puedes agregar funcionalidad específica para esta vista si es necesario
</script>
<?php View::endSection(); ?>