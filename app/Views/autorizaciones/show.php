<?php
use App\Helpers\View;
use App\Middlewares\CsrfMiddleware;

// Helper para obtener valor de objeto o array
function getValue($data, $key, $default = null) {
    if (is_object($data)) {
        // Intentar acceso directo a propiedad
        if (isset($data->$key)) {
            return $data->$key;
        }
        // Si es un modelo, intentar acceder a attributes
        if (method_exists($data, 'getAttribute') || property_exists($data, 'attributes')) {
            $attributes = $data->attributes ?? [];
            if (isset($attributes[$key])) {
                return $attributes[$key];
            }
        }
        return $default;
    } elseif (is_array($data)) {
        return isset($data[$key]) ? $data[$key] : $default;
    }
    return $default;
}

$flujoEstado = 'pendiente_revision';
if (isset($flujo)) {
    if (is_object($flujo)) {
        $flujoEstado = $flujo->estado ?? $flujoEstado;
    } elseif (is_array($flujo)) {
        $flujoEstado = $flujo['estado'] ?? $flujoEstado;
    }
}

$estadoLabels = [
    'borrador' => ['class' => 'bg-secondary text-white', 'label' => 'Borrador'],
    'pendiente_revision' => ['class' => 'bg-warning text-dark', 'label' => 'Pendiente de Revisión'],
    'pendiente_autorizacion' => ['class' => 'bg-warning text-dark', 'label' => 'Pendiente de Autorización'],
    'pendiente_autorizacion_centros' => ['class' => 'bg-warning text-dark', 'label' => 'Pendiente Autorización Centros'],
    'autorizado' => ['class' => 'bg-success text-white', 'label' => 'Autorizado'],
    'autorizada' => ['class' => 'bg-success text-white', 'label' => 'Autorizada'],
    'rechazado' => ['class' => 'bg-danger text-white', 'label' => 'Rechazado'],
    'rechazada' => ['class' => 'bg-danger text-white', 'label' => 'Rechazada'],
];
$estadoBadge = $estadoLabels[$flujoEstado] ?? ['class' => 'bg-secondary text-white', 'label' => ucfirst(str_replace('_', ' ', $flujoEstado))];

// Obtener moneda de la orden
$moneda = getValue($orden, 'moneda', 'GTQ');

$centrosDistrib = [];
if (!empty($distribucion)) {
    foreach ($distribucion as $distItem) {
        if (isset($distItem['centro_costo_id'])) {
            $centrosDistrib[$distItem['centro_costo_id']] = $distItem;
        }
    }
}
$centrosAutorizaciones = $autorizaciones_centro ?? [];
$puedeRevisar = $puede_revisar ?? false;
$ordenIdVista = $orden_id ?? getValue($orden, 'id');
$montoTotal = getValue($orden, 'monto_total', 0);

View::startSection('content');
?>

<style>
.btn-detalle-super {
    background: linear-gradient(135deg, #28a745 0%, #20c997 50%, #17a2b8 100%) !important;
    border: none !important;
    color: white !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    position: relative !important;
    overflow: hidden !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    border-radius: 12px !important;
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3) !important;
}

.btn-detalle-super:hover {
    transform: translateY(-3px) scale(1.05) !important;
    box-shadow: 0 15px 35px rgba(40, 167, 69, 0.4) !important;
    color: white !important;
}

.btn-detalle-super:active {
    transform: translateY(-1px) scale(1.02) !important;
}

.btn-detalle-super::before {
    content: '' !important;
    position: absolute !important;
    top: 0 !important;
    left: -100% !important;
    width: 100% !important;
    height: 100% !important;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent) !important;
    transition: left 0.5s !important;
}

.btn-detalle-super:hover::before {
    left: 100% !important;
}

.btn-detalle-super i {
    font-size: 1.2em !important;
    margin-right: 8px !important;
}

@keyframes pulseGlow {
    0% {
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
    }
    50% {
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.5);
    }
    100% {
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
    }
}

.btn-detalle-super {
    animation: pulseGlow 2s ease-in-out infinite !important;
}
</style>

<div class="container py-4" style="max-width: 1200px;">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">
                <i class="fas fa-check-circle me-2"></i>
                Autorizar Requisición #<?php echo getValue($orden, 'id'); ?>
            </h1>
            <p class="text-muted mb-0">Revisa la información y autoriza los centros de costo</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= url('/requisiciones/' . getValue($orden, 'id')) ?>" class="btn btn-detalle-super btn-lg me-2 fw-bold px-4 py-3">
                <i class="fas fa-file-contract"></i>
                Ver Detalle Completo
            </a>
            <a href="<?= url('/autorizaciones') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    <?php if ($flujoEstado === 'pendiente_revision' && $puedeRevisar): ?>
    <div class="alert alert-info d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-3">
        <div>
            <h5 class="mb-1"><i class="fas fa-user-check me-2"></i>Revisión pendiente</h5>
            <p class="mb-0 small">
                Esta requisición espera tu aprobación como revisor. Puedes aprobarla desde aquí o ir al panel de revisión para ver el detalle completo.
            </p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <form method="POST" action="<?= url('/autorizaciones/' . $ordenIdVista . '/aprobar-revision') ?>" class="d-flex gap-2 align-items-center" id="formAprobarRevision">
                <?php echo CsrfMiddleware::field(); ?>
                <input type="hidden" name="comentario" value="">
                <button type="submit" class="btn btn-success btn-authorize">
                    <i class="fas fa-check me-2"></i>Aprobar revisión
                </button>
            </form>
            <button type="button" class="btn btn-danger" onclick="rechazarRequisicionDetalle(<?php echo $ordenIdVista; ?>)">
                <i class="fas fa-times me-2"></i>Rechazar
            </button>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Información de la Requisición -->
        <div class="col-md-8">
            <!-- Información de Autorización -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-check me-2"></i>
                        Información de Autorización
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Proveedor:</strong></p>
                            <p>
                                <i class="fas fa-store me-2 text-primary"></i>
                                <?php echo View::e(getValue($orden, 'nombre_razon_social')) ?: View::e(getValue($orden, 'proveedor_nombre')) ?: 'No especificado'; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Estado de Revisión:</strong></p>
                            <p>
                                <?php if ($flujoEstado === 'pendiente_revision'): ?>
                                    <i class="fas fa-hourglass-half me-2 text-warning"></i>
                                    <span class="text-warning">Pendiente de Revisión</span>
                                <?php elseif ($flujoEstado === 'pendiente_autorizacion'): ?>
                                    <i class="fas fa-user-check me-2 text-info"></i>
                                    <span class="text-info">Lista para Autorización</span>
                                <?php elseif ($flujoEstado === 'autorizado'): ?>
                                    <i class="fas fa-check-circle me-2 text-success"></i>
                                    <span class="text-success">Completamente Autorizada</span>
                                <?php elseif ($flujoEstado === 'rechazado'): ?>
                                    <i class="fas fa-times-circle me-2 text-danger"></i>
                                    <span class="text-danger">Rechazada</span>
                                <?php else: ?>
                                    <i class="fas fa-question-circle me-2 text-muted"></i>
                                    <span class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $flujoEstado)); ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Items de la Requisición
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">#</th>
                                    <th>Descripción</th>
                                    <th width="100">Cantidad</th>
                                    <th width="150" class="text-end">Precio Unit.</th>
                                    <th width="150" class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $i => $item): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo View::e($item['descripcion']); ?></td>
                                    <td><?php echo number_format($item['cantidad'], 2); ?></td>
                                    <td class="text-end"><?php echo View::money($item['precio_unitario'], $moneda); ?></td>
                                    <td class="text-end">
                                        <strong><?php echo View::money($item['cantidad'] * $item['precio_unitario'], $moneda); ?></strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-primary">
                                    <td colspan="4" class="text-end"><h5 class="mb-0">TOTAL:</h5></td>
                                    <td class="text-end">
                                        <h4 class="mb-0 text-primary">
                                            <?php echo View::money(getValue($orden, 'monto_total'), $moneda); ?>
                                        </h4>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Justificación -->
            <?php if (!empty(getValue($orden, 'justificacion'))): ?>
            <div class="alert alert-info">
                <h5><i class="fas fa-comment me-2"></i>Justificación:</h5>
                <p class="mb-0"><?php echo nl2br(View::e(getValue($orden, 'justificacion'))); ?></p>
            </div>
            <?php endif; ?>

            <!-- Autorizaciones Especiales -->
            <?php if (!empty($autorizaciones_especiales)): ?>
            <div class="card mb-3 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-star me-2"></i>
                        Autorizaciones Especiales Pendientes
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tipo</th>
                                    <th>Detalle</th>
                                    <th width="120">Autorizador</th>
                                    <th width="200" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($autorizaciones_especiales as $auth): ?>
                                <tr id="especial-<?php echo $auth['id']; ?>">
                                    <td>
                                        <?php if ($auth['tipo'] === 'forma_pago'): ?>
                                            <span class="badge bg-success me-2">
                                                <i class="fas fa-credit-card me-1"></i>Forma Pago
                                            </span>
                                        <?php elseif ($auth['tipo'] === 'cuenta_contable'): ?>
                                            <span class="badge bg-info me-2">
                                                <i class="fas fa-calculator me-1"></i>Cuenta Contable
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php 
                                            $metadata = is_string($auth['metadata']) ? json_decode($auth['metadata'], true) : $auth['metadata'];
                                            if ($metadata['es_respaldo'] ?? false):
                                        ?>
                                            <span class="badge bg-secondary text-white">
                                                <i class="fas fa-user-shield me-1"></i>Respaldo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $metadata = is_string($auth['metadata']) ? json_decode($auth['metadata'], true) : $auth['metadata'];
                                            if ($auth['tipo'] === 'forma_pago'): 
                                                echo View::e($metadata['forma_pago'] ?? 'No especificado');
                                            elseif ($auth['tipo'] === 'cuenta_contable'): 
                                                echo View::e($metadata['cuenta_nombre'] ?? 'No especificada');
                                            endif; 
                                        ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo View::e($auth['autorizador_email']); ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php $estadoAuth = $auth['estado'] ?? 'pendiente'; ?>
                                        <?php if ($estadoAuth === 'pendiente'): ?>
                                        <button class="btn btn-sm btn-success btn-authorize me-1" 
                                                onclick="autorizarEspecial(<?php echo $auth['id']; ?>, '<?php echo $auth['tipo']; ?>')">
                                            <i class="fas fa-check me-1"></i>Autorizar
                                        </button>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="rechazarEspecial(<?php echo $auth['id']; ?>, '<?php echo $auth['tipo']; ?>')">
                                            <i class="fas fa-times me-1"></i>Rechazar
                                        </button>
                                        <?php elseif ($estadoAuth === 'autorizada'): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Autorizado
                                        </span>
                                        <?php elseif ($estadoAuth === 'rechazada'): ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times me-1"></i>Rechazado
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Centros de Costo a Autorizar -->
            <div class="card mb-3 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks me-2"></i>
                        Centros de Costo Pendientes de Autorización
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($centrosAutorizaciones)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Centro de Costo</th>
                                    <th>Cuenta Contable</th>
                                    <th>Autorizador</th>
                                    <th width="100" class="text-end">%</th>
                                    <th width="150" class="text-end">Monto</th>
                                    <th width="200" class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($centrosAutorizaciones as $centro): ?>
                                <?php
                                    $metadataCentro = is_string($centro['metadata'] ?? null)
                                        ? json_decode($centro['metadata'], true)
                                        : ($centro['metadata'] ?? []);
                                    $distInfo = [];
                                    $centroCostoId = $centro['centro_costo_id'] ?? null;
                                    if ($centroCostoId && isset($centrosDistrib[$centroCostoId])) {
                                        $distInfo = $centrosDistrib[$centroCostoId];
                                    }
                                    $porcentajeCentro = $centro['porcentaje'] ?? ($metadataCentro['porcentaje'] ?? ($distInfo['porcentaje'] ?? 0));
                                    $montoCentro = $distInfo['monto_distribuido'] ?? ($distInfo['monto'] ?? 0);
                                ?>
                                <tr id="centro-<?php echo $centro['id']; ?>">
                                    <td>
                                        <strong><?php echo View::e($centro['centro_nombre'] ?? 'N/A'); ?></strong>
                                        <?php if ($metadataCentro['es_respaldo'] ?? false): ?>
                                            <br><span class="badge bg-secondary text-white">
                                                <i class="fas fa-user-shield me-1"></i>Autorizador de Respaldo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo View::e($distInfo['cuenta_nombre'] ?? 'N/A'); ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo View::e($centro['autorizador_email'] ?? 'N/A'); ?>
                                        </small>
                                    </td>
                                    <td class="text-end"><?php echo number_format($porcentajeCentro, 2); ?>%</td>
                                    <td class="text-end">
                                        <strong class="text-primary">
                                            <?php echo View::money($montoCentro, $moneda); ?>
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        <?php $estadoCentro = $centro['estado'] ?? 'pendiente'; ?>
                                        <?php 
                                        // Verificar si el usuario actual es el autorizador de este centro
                                        $autorizadorCentro = $centro['autorizador_email'] ?? '';
                                        $usuarioActual = $usuario['email'] ?? ($currentUser['email'] ?? '');
                                        $puedeAutorizar = ($autorizadorCentro === $usuarioActual && !empty($usuarioActual));
                                        ?>
                                        <?php if ($estadoCentro === 'pendiente'): ?>
                                            <?php if ($puedeAutorizar): ?>
                                            <button class="btn btn-sm btn-success btn-authorize me-1" 
                                                    onclick="autorizarCentro(<?php echo $centro['id']; ?>)">
                                                <i class="fas fa-check me-1"></i>Autorizar
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="rechazarCentro(<?php echo $centro['id']; ?>)">
                                                <i class="fas fa-times me-1"></i>Rechazar
                                            </button>
                                            <?php else: ?>
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-clock me-1"></i>Pendiente
                                                <br><small>Asignado a: <?php echo View::e($autorizadorCentro); ?></small>
                                            </span>
                                            <?php endif; ?>
                                        <?php elseif ($estadoCentro === 'autorizado'): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Autorizado
                                        </span>
                                        <?php elseif ($estadoCentro === 'rechazado'): ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times me-1"></i>Rechazado
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-info me-1"></i><?php echo View::e($estadoCentro); ?>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="p-3 text-center text-muted">
                        <?php if ($flujoEstado === 'pendiente_revision'): ?>
                            En espera de la aprobación de revisión para generar las autorizaciones de centros de costo.
                        <?php else: ?>
                            No hay centros de costo pendientes de autorización.
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Estado -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Estado Actual
                    </h6>
                </div>
                <div class="card-body text-center">
                    <span class="badge <?php echo $estadoBadge['class']; ?> fs-5 p-3">
                        <i class="fas fa-hourglass-half me-2"></i>
                        <?php echo View::e($estadoBadge['label']); ?>
                    </span>
                </div>
            </div>

            <!-- Análisis de la Requisición -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Análisis de la Requisición
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Forma de Pago:</strong>
                        <div class="mt-1">
                            <i class="fas fa-credit-card me-2 text-primary"></i>
                            <?php echo View::e(getValue($orden, 'forma_pago')); ?>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <strong>Tiempo de Proceso:</strong>
                        <div class="mt-1">
                            <?php 
                            // Intentar obtener la fecha de creación desde diferentes campos posibles
                            $fechaCreacion = getValue($orden, 'fecha_solicitud');
                            if (!$fechaCreacion) {
                                $fechaCreacion = getValue($orden, 'created_at');
                            }
                            if (!$fechaCreacion) {
                                $fechaCreacion = getValue($orden, 'fecha');
                            }
                            
                            if ($fechaCreacion) {
                                // Convertir a timestamp
                                if (is_numeric($fechaCreacion)) {
                                    $timestamp = $fechaCreacion;
                                } else {
                                    $timestamp = strtotime($fechaCreacion);
                                }
                                
                                if ($timestamp !== false && $timestamp > 0) {
                                    $diferencia = time() - $timestamp;
                                    $dias = max(0, floor($diferencia / (60 * 60 * 24)));
                                    $tiempoColor = $dias > 5 ? 'text-warning' : 'text-success';
                                    $tiempoIcon = $dias > 5 ? 'fa-exclamation-triangle' : 'fa-clock';
                                } else {
                                    $dias = 0;
                                    $tiempoColor = 'text-muted';
                                    $tiempoIcon = 'fa-question';
                                }
                            } else {
                                $dias = 0;
                                $tiempoColor = 'text-muted';
                                $tiempoIcon = 'fa-question';
                            }
                            ?>
                            <i class="fas <?php echo $tiempoIcon; ?> me-2 <?php echo $tiempoColor; ?>"></i>
                            <span class="<?php echo $tiempoColor; ?>"><?php echo $dias; ?> días transcurridos</span>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <strong>Progreso de Autorización:</strong>
                        <div class="mt-1">
                            <?php
                            $totalAutorizaciones = count($centrosAutorizaciones ?? []) + count($autorizaciones_especiales ?? []);
                            $autorizacionesCompletas = 0;
                            
                            foreach ($centrosAutorizaciones ?? [] as $centro) {
                                if (($centro['estado'] ?? 'pendiente') === 'autorizado') {
                                    $autorizacionesCompletas++;
                                }
                            }
                            foreach ($autorizaciones_especiales ?? [] as $auth) {
                                if (($auth['estado'] ?? 'pendiente') === 'autorizada') {
                                    $autorizacionesCompletas++;
                                }
                            }
                            
                            $progreso = $totalAutorizaciones > 0 ? round(($autorizacionesCompletas / $totalAutorizaciones) * 100) : 0;
                            $progresoColor = $progreso >= 80 ? 'text-success' : ($progreso >= 50 ? 'text-warning' : 'text-info');
                            ?>
                            <i class="fas fa-percentage me-2 <?php echo $progresoColor; ?>"></i>
                            <span class="<?php echo $progresoColor; ?>"><?php echo $progreso; ?>% completado</span>
                        </div>
                        <div class="progress mt-2" style="height: 8px;">
                            <div class="progress-bar bg-<?php echo $progreso >= 80 ? 'success' : ($progreso >= 50 ? 'warning' : 'info'); ?>" 
                                 style="width: <?php echo $progreso; ?>%"></div>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <strong>Solicitante:</strong>
                        <div class="mt-1">
                            <i class="fas fa-user me-2 text-info"></i>
                            <?php echo View::e(getValue($orden, 'created_by', 'Sistema')); ?>
                        </div>
                    </div>
                    <div class="mb-0">
                        <strong>ID de Requisición:</strong>
                        <div class="mt-1">
                            <i class="fas fa-hashtag me-2 text-muted"></i>
                            <?php echo getValue($orden, 'id'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas Inteligentes -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-bell me-2"></i>
                        Alertas del Sistema
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Alerta de monto alto -->
                    <?php if ($montoTotal > 10000000): ?>
                    <div class="alert alert-warning alert-sm mb-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Monto Alto:</strong> Esta requisición supera los $10M.
                    </div>
                    <?php endif; ?>

                    <!-- Alerta de tiempo -->
                    <?php if (isset($dias) && $dias > 5): ?>
                    <div class="alert alert-info alert-sm mb-2">
                        <i class="fas fa-clock me-2"></i>
                        <strong>Tiempo Extendido:</strong> <?php echo $dias; ?> días pendiente.
                    </div>
                    <?php endif; ?>

                    <!-- Alerta de autorizaciones especiales -->
                    <?php if (!empty($autorizaciones_especiales)): ?>
                    <div class="alert alert-secondary alert-sm mb-2">
                        <i class="fas fa-star me-2"></i>
                        <strong>Especiales:</strong> <?php echo count($autorizaciones_especiales); ?> autorizaciones adicionales.
                    </div>
                    <?php endif; ?>

                    <!-- Estado general -->
                    <?php if (empty($autorizaciones_especiales) && $montoTotal <= 10000000 && (!isset($dias) || $dias <= 5)): ?>
                    <div class="alert alert-success alert-sm mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Todo en Orden:</strong> Requisición lista para autorizar.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Instrucciones -->
            <div class="alert alert-warning">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Importante:</h6>
                <ul class="mb-0 small">
                    <li>Revisa cuidadosamente todos los datos</li>
                    <li>Verifica que los montos sean correctos</li>
                    <li>Confirma que hay presupuesto disponible</li>
                    <li>Al rechazar debes indicar el motivo</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rechazar -->
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-times-circle me-2"></i>
                    Rechazar Centro de Costo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formRechazar">
                <?php echo CsrfMiddleware::field(); ?>
                <input type="hidden" id="centro_rechazar_id" name="centro_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Motivo del Rechazo *</label>
                        <textarea class="form-control" name="motivo" rows="3" required></textarea>
                        <small class="text-muted">Explica por qué estás rechazando esta autorización</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Rechazar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
View::endSection();

View::startSection('scripts');
?>
<!-- Authorization Effects System -->
<script src="<?= url('/js/authorization-effects.js') ?>"></script>
<script>
function autorizarCentro(centroId) {
    if (!confirm('¿Estás seguro de autorizar este centro de costo?')) {
        return;
    }
    
    // Encontrar el botón y la fila
    const button = document.querySelector(`button[onclick*="autorizarCentro(${centroId})"]`);
    const row = button ? button.closest('tr') : null;
    
    // Iniciar animación del botón
    if (window.AuthEffects) {
        window.AuthEffects.animateButton(button, 'authorizing');
        if (row) window.AuthEffects.animateCard(row, 'authorizing');
    }
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('comentario', '');
    
    fetch('<?= url('/autorizaciones/centro/') ?>' + centroId + '/autorizar', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Efectos de celebración
            if (window.AuthEffects) {
                window.AuthEffects.animateButton(button, 'authorized');
                if (row) window.AuthEffects.animateCard(row, 'authorized');
                
                // Celebración principal
                window.AuthEffects.celebrate(
                    'Centro de costo autorizado exitosamente',
                    'authorization'
                );
            }
            
            // Recargar después de los efectos
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            // Restaurar botón en caso de error
            if (window.AuthEffects && button) {
                button.disabled = false;
                button.innerHTML = button.dataset.originalText || '<i class="fas fa-check me-1"></i>Autorizar';
                button.classList.remove('authorizing');
            }
            if (row) row.classList.remove('authorizing');
            
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Restaurar estado en caso de error
        if (window.AuthEffects && button) {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || '<i class="fas fa-check me-1"></i>Autorizar';
            button.classList.remove('authorizing');
        }
        if (row) row.classList.remove('authorizing');
        
        alert('Error al procesar la solicitud');
    });
}

function rechazarCentro(centroId) {
    document.getElementById('centro_rechazar_id').value = centroId;
    new bootstrap.Modal(document.getElementById('modalRechazar')).show();
}

document.getElementById('formRechazar').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const centroId = document.getElementById('centro_rechazar_id').value;
    const formData = new FormData(this);
    
    fetch('<?= url('/autorizaciones/centro/') ?>' + centroId + '/rechazar', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Centro de costo rechazado');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
});

// Funciones para autorizaciones especiales
function autorizarEspecial(authId, tipo) {
    if (!confirm('¿Estás seguro de autorizar esta autorización especial de ' + tipo + '?')) {
        return;
    }
    
    // Encontrar el botón y la fila
    const button = document.querySelector(`button[onclick*="autorizarEspecial(${authId}"]`);
    const row = button ? button.closest('tr') : null;
    
    // Iniciar animación del botón
    if (window.AuthEffects) {
        window.AuthEffects.animateButton(button, 'authorizing');
        if (row) window.AuthEffects.animateCard(row, 'authorizing');
    }
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('comentario', '');
    
    fetch('<?= url('/autorizaciones/especial/') ?>' + authId + '/autorizar', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Efectos especiales para autorización especial
            if (window.AuthEffects) {
                window.AuthEffects.animateButton(button, 'authorized');
                if (row) window.AuthEffects.animateCard(row, 'authorized');
                
                // Celebración especial con fuegos artificiales
                window.AuthEffects.celebrate(
                    `¡Autorización especial de ${tipo} aprobada exitosamente!`,
                    'special'
                );
                
                // Agregar fuegos artificiales para autorizaciones especiales
                setTimeout(() => {
                    window.AuthEffects.createFireworks();
                }, 500);
            }
            
            // Recargar después de los efectos
            setTimeout(() => {
                location.reload();
            }, 2500);
        } else {
            // Restaurar botón en caso de error
            if (window.AuthEffects && button) {
                button.disabled = false;
                button.innerHTML = button.dataset.originalText || '<i class="fas fa-check me-1"></i>Autorizar';
                button.classList.remove('authorizing');
            }
            if (row) row.classList.remove('authorizing');
            
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Restaurar estado en caso de error
        if (window.AuthEffects && button) {
            button.disabled = false;
            button.innerHTML = button.dataset.originalText || '<i class="fas fa-check me-1"></i>Autorizar';
            button.classList.remove('authorizing');
        }
        if (row) row.classList.remove('authorizing');
        
        alert('Error al procesar la solicitud');
    });
}

function rechazarEspecial(authId, tipo) {
    const motivo = prompt('Ingresa el motivo del rechazo para la autorización especial de ' + tipo + ':');
    if (!motivo) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('motivo', motivo);
    
    fetch('<?= url('/autorizaciones/especial/') ?>' + authId + '/rechazar', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Autorización especial de ' + tipo + ' rechazada');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
}

// Manejar formulario de aprobación de revisión con efectos
document.addEventListener('DOMContentLoaded', function() {
    const formRevision = document.getElementById('formAprobarRevision');
    if (formRevision) {
        formRevision.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const button = this.querySelector('button[type="submit"]');
            const alertContainer = this.closest('.alert');
            
            // Iniciar animación
            if (window.AuthEffects) {
                window.AuthEffects.animateButton(button, 'authorizing');
                if (alertContainer) window.AuthEffects.animateCard(alertContainer, 'authorizing');
            }
            
            // Enviar formulario
            fetch(this.action, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Efectos de celebración para revisión
                    if (window.AuthEffects) {
                        window.AuthEffects.animateButton(button, 'authorized');
                        if (alertContainer) window.AuthEffects.animateCard(alertContainer, 'authorized');
                        
                        // Celebración especial para revisión
                        window.AuthEffects.celebrate(
                            '¡Revisión aprobada exitosamente!',
                            'revision'
                        );
                    }
                    
                    // Recargar después de los efectos
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    // Restaurar estado en caso de error
                    if (window.AuthEffects && button) {
                        button.disabled = false;
                        button.innerHTML = button.dataset.originalText || '<i class="fas fa-check me-2"></i>Aprobar revisión';
                        button.classList.remove('authorizing');
                    }
                    if (alertContainer) alertContainer.classList.remove('authorizing');
                    
                    alert('Error: ' + (data.error || 'Error al procesar la solicitud'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Restaurar estado en caso de error
                if (window.AuthEffects && button) {
                    button.disabled = false;
                    button.innerHTML = button.dataset.originalText || '<i class="fas fa-check me-2"></i>Aprobar revisión';
                    button.classList.remove('authorizing');
                }
                if (alertContainer) alertContainer.classList.remove('authorizing');
                
                alert('Error de conexión');
            });
        });
    }
});
</script>

<!-- Enhanced Modal para mostrar detalle completo de la requisición -->
<div class="modal fade modern-modal" id="modalDetalleRequisicion" tabindex="-1" aria-labelledby="modalDetalleRequisicionLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title" id="modalDetalleRequisicionLabel">
                    <div class="title-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="title-text">
                        <div>Requisición #<?php echo getValue($orden, 'id'); ?></div>
                        <div class="title-meta">Información detallada y completa</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="contenidoDetalleRequisicion">
                    <!-- Loading state mejorado -->
                    <div class="modal-loading">
                        <div class="loading-animation">
                            <div class="loading-spinner"></div>
                            <div class="loading-dots">
                                <div class="loading-dot"></div>
                                <div class="loading-dot"></div>
                                <div class="loading-dot"></div>
                            </div>
                        </div>
                        <h5 class="loading-text">Cargando detalles</h5>
                        <p class="loading-subtext">Obteniendo información completa de la requisición...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="modal-nav-controls">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="prevSection">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    <span class="nav-indicator">
                        <span class="current-section">1</span> / <span class="total-sections">5</span>
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="nextSection">
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cerrar
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-share me-2"></i>Exportar
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= url('/requisiciones/' . getValue($orden, 'id') . '/imprimir') ?>" target="_blank">
                                <i class="fas fa-print me-2"></i>Imprimir PDF
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="shareModal()">
                                <i class="fas fa-share-alt me-2"></i>Compartir enlace
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= url('/requisiciones/' . getValue($orden, 'id')) ?>" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>Abrir en nueva pestaña
                            </a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="toggleFullscreen()">
                        <i class="fas fa-expand me-2"></i>Pantalla completa
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Enhanced modal content loading with better UX
document.getElementById('modalDetalleRequisicion').addEventListener('show.bs.modal', function () {
    const contenido = document.getElementById('contenidoDetalleRequisicion');
    const requisicionId = <?php echo getValue($orden, 'id'); ?>;
    
    // Reset to loading state with improved animation
    contenido.innerHTML = `
        <div class="modal-loading">
            <div class="loading-animation">
                <div class="loading-spinner"></div>
                <div class="loading-dots">
                    <div class="loading-dot"></div>
                    <div class="loading-dot"></div>
                    <div class="loading-dot"></div>
                </div>
            </div>
            <h5 class="loading-text">Cargando detalles</h5>
            <p class="loading-subtext">Obteniendo información completa de la requisición...</p>
        </div>
    `;
    
    // Simulate minimum loading time for better UX
    const startTime = Date.now();
    const minLoadTime = 800; // Minimum 800ms for smooth transition
    
    // En lugar de cargar via AJAX, crear contenido completo directamente
    setTimeout(() => {
        const elapsed = Date.now() - startTime;
        const remainingTime = Math.max(0, minLoadTime - elapsed);
        
        // Create complete requisition detail content
        const completeContent = createCompleteRequisitionDetail();
        
        setTimeout(() => {
            contenido.innerHTML = completeContent;
            
            // Add entrance animation
            contenido.style.opacity = '0';
            contenido.style.transform = 'translateY(20px)';
            
            // Trigger animation
            requestAnimationFrame(() => {
                contenido.style.transition = 'all 0.3s ease';
                contenido.style.opacity = '1';
                contenido.style.transform = 'translateY(0)';
                
                // Initialize any additional modal-specific functionality
                initializeModalContent();
            });
        }, remainingTime);
        
    }, 100); // Small delay to show loading animation
});

// Create complete requisition detail with all information and styling
function createCompleteRequisitionDetail() {
    return `
        <div class="modal-requisition-detail">
            <!-- Compact Header with Status and Main Info -->
            <div class="modal-header-compact">
                <div class="row align-items-center mb-3">
                    <div class="col-8">
                        <h4 class="modal-req-title mb-1">
                            <i class="fas fa-file-invoice me-2"></i>
                            Requisición #<?php echo getValue($orden, 'id'); ?>
                        </h4>
                        <div class="modal-meta">
                            <span class="badge <?php echo $estadoBadge['class']; ?> badge-sm me-2">
                                <?php echo View::e($estadoBadge['label']); ?>
                            </span>
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i>
                                <?php echo View::formatDate(getValue($orden, 'fecha_solicitud')); ?>
                            </small>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="modal-amount">
                            <small class="amount-label-sm">Monto Total</small>
                            <div class="amount-value-sm">
                                <?php echo View::money(getValue($orden, 'monto_total')); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compact Content Layout -->
            <div class="modal-content-compact">
                        
                        <!-- Información de Autorización -->
                        <div class="modal-card mb-3">
                            <div class="modal-card-header">
                                <h6 class="modal-card-title">
                                    <i class="fas fa-clipboard-check me-1"></i>
                                    Información de Autorización
                                </h6>
                            </div>
                            <div class="modal-card-body">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="modal-info-field">
                                            <label class="modal-info-label">Proveedor</label>
                                            <div class="modal-info-value">
                                                <i class="fas fa-store me-1"></i>
                                                <?php echo View::e(getValue($orden, 'proveedor_nombre')) ?: 'No especificado'; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="modal-info-field">
                                            <label class="modal-info-label">Req. Documentos</label>
                                            <div class="modal-info-value">
                                                <?php 
                                                $reqDocs = $montoTotal > 5000000 ? 'Sí' : 'No';
                                                $docsClass = $montoTotal > 5000000 ? 'text-warning' : 'text-success';
                                                $docsIcon = $montoTotal > 5000000 ? 'fa-file-contract' : 'fa-check';
                                                ?>
                                                <i class="fas <?php echo $docsIcon; ?> me-1 <?php echo $docsClass; ?>"></i>
                                                <span class="<?php echo $docsClass; ?>"><?php echo $reqDocs; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Compact Items Table -->
                        <div class="modal-card mb-3">
                            <div class="modal-card-header">
                                <h6 class="modal-card-title">
                                    <i class="fas fa-list me-1"></i>
                                    Items <span class="badge bg-secondary badge-sm ms-1"><?php echo count($items); ?></span>
                                </h6>
                            </div>
                            <div class="modal-card-body p-0">
                                <div class="modal-table-container">
                                    <table class="modal-table">
                                        <thead>
                                            <tr>
                                                <th width="30">#</th>
                                                <th>Descripción</th>
                                                <th width="60">Cant.</th>
                                                <th width="90" class="text-end">P. Unit.</th>
                                                <th width="90" class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($items as $i => $item): ?>
                                                <?php $itemTotal = $item['cantidad'] * $item['precio_unitario']; ?>
                                            <tr>
                                                <td class="text-center">
                                                    <span class="modal-item-number"><?php echo $i + 1; ?></span>
                                                </td>
                                                <td>
                                                    <div class="modal-item-desc">
                                                        <?php echo View::e($item['descripcion']); ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <small class="modal-qty">
                                                        <?php echo number_format($item['cantidad'], 0); ?>
                                                    </small>
                                                </td>
                                                <td class="text-end">
                                                    <small class="modal-price">
                                                        <?php echo View::money($item['precio_unitario'], $moneda); ?>
                                                    </small>
                                                </td>
                                                <td class="text-end">
                                                    <strong class="modal-total">
                                                        <?php echo View::money($itemTotal, $moneda); ?>
                                                    </strong>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="modal-total-row">
                                                <td colspan="4" class="text-end">
                                                    <strong>TOTAL:</strong>
                                                </td>
                                                <td class="text-end">
                                                    <strong class="modal-grand-total">
                                                        <?php echo View::money(getValue($orden, 'monto_total')); ?>
                                                    </strong>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Extended Info Grid -->
                        <div class="row g-2 mb-3">
                            <div class="col-6 col-md-3">
                                <div class="modal-info-card">
                                    <small class="modal-info-label">Estado</small>
                                    <div class="badge <?php echo $estadoBadge['class']; ?> badge-sm w-100">
                                        <?php echo View::e($estadoBadge['label']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="modal-info-card">
                                    <small class="modal-info-label">Forma de Pago</small>
                                    <div class="modal-info-value-sm">
                                        <i class="fas fa-credit-card me-1"></i>
                                        <?php echo View::e(getValue($orden, 'forma_pago')) ?: 'N/A'; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="modal-info-card">
                                    <small class="modal-info-label">Prioridad</small>
                                    <div class="modal-info-value-sm">
                                        <?php 
                                        $prioridad = getValue($orden, 'prioridad', 'Normal');
                                        $prioridadColor = $prioridad === 'Alta' ? 'text-danger' : ($prioridad === 'Baja' ? 'text-muted' : 'text-warning');
                                        ?>
                                        <i class="fas fa-flag me-1 <?php echo $prioridadColor; ?>"></i>
                                        <?php echo $prioridad; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="modal-info-card">
                                    <small class="modal-info-label">Items</small>
                                    <div class="modal-info-value-sm">
                                        <i class="fas fa-list me-1"></i>
                                        <?php echo count($items); ?> productos
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Solicitante y Departamento -->
                        <div class="modal-card mb-3">
                            <div class="modal-card-header">
                                <h6 class="modal-card-title">
                                    <i class="fas fa-user me-1"></i>
                                    Información del Solicitante
                                </h6>
                            </div>
                            <div class="modal-card-body">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="modal-info-field">
                                            <label class="modal-info-label">Solicitante</label>
                                            <div class="modal-info-value">
                                                <i class="fas fa-user-circle me-1"></i>
                                                <?php echo View::e(getValue($orden, 'solicitante')) ?: getValue($orden, 'created_by', 'No especificado'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="modal-info-field">
                                            <label class="modal-info-label">Departamento</label>
                                            <div class="modal-info-value">
                                                <i class="fas fa-building me-1"></i>
                                                <?php echo View::e(getValue($orden, 'departamento')) ?: 'No especificado'; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="modal-info-field">
                                            <label class="modal-info-label">Email</label>
                                            <div class="modal-info-value">
                                                <i class="fas fa-envelope me-1"></i>
                                                <?php echo View::e(getValue($orden, 'email_solicitante')) ?: 'No especificado'; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="modal-info-field">
                                            <label class="modal-info-label">Fecha Necesaria</label>
                                            <div class="modal-info-value">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo getValue($orden, 'fecha_necesaria') ? View::formatDate(getValue($orden, 'fecha_necesaria')) : 'No especificada'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Compact Justification -->
                        <?php if (!empty(getValue($orden, 'justificacion'))): ?>
                        <div class="modal-card mb-3">
                            <div class="modal-card-header">
                                <h6 class="modal-card-title">
                                    <i class="fas fa-comment-alt me-1"></i>
                                    Justificación
                                </h6>
                            </div>
                            <div class="modal-card-body">
                                <div class="modal-justification">
                                    <?php echo nl2br(View::e(getValue($orden, 'justificacion'))); ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Compact Special Authorizations -->
                        <?php if (!empty($autorizaciones_especiales)): ?>
                        <div class="modal-card mb-3 border-warning">
                            <div class="modal-card-header bg-warning text-dark">
                                <h6 class="modal-card-title mb-0">
                                    <i class="fas fa-star me-1"></i>
                                    Autorizaciones Especiales <span class="badge bg-dark badge-sm ms-1"><?php echo count($autorizaciones_especiales); ?></span>
                                </h6>
                            </div>
                            <div class="modal-card-body p-0">
                                <div class="modal-auth-list">
                                    <?php foreach ($autorizaciones_especiales as $auth): ?>
                                        <?php 
                                            $metadata = is_string($auth['metadata']) ? json_decode($auth['metadata'], true) : $auth['metadata'];
                                            $estadoAuth = $auth['estado'] ?? 'pendiente';
                                        ?>
                                    <div class="modal-auth-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="modal-auth-type mb-1">
                                                    <?php if ($auth['tipo'] === 'forma_pago'): ?>
                                                        <span class="badge bg-success badge-sm me-1">Forma Pago</span>
                                                    <?php elseif ($auth['tipo'] === 'cuenta_contable'): ?>
                                                        <span class="badge bg-info badge-sm me-1">Cuenta</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($metadata['es_respaldo'] ?? false): ?>
                                                        <span class="badge bg-secondary badge-sm">Respaldo</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-auth-detail">
                                                    <?php 
                                                        if ($auth['tipo'] === 'forma_pago'): 
                                                            echo View::e($metadata['forma_pago'] ?? 'No especificado');
                                                        elseif ($auth['tipo'] === 'cuenta_contable'): 
                                                            echo View::e($metadata['cuenta_nombre'] ?? 'No especificada');
                                                        endif; 
                                                    ?>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-user-tie me-1"></i>
                                                    <?php echo View::e($auth['autorizador_email']); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <?php if ($estadoAuth === 'pendiente'): ?>
                                                <span class="badge bg-warning text-dark badge-sm">
                                                    <i class="fas fa-clock me-1"></i>Pendiente
                                                </span>
                                                <?php elseif ($estadoAuth === 'autorizada'): ?>
                                                <span class="badge bg-success badge-sm">
                                                    <i class="fas fa-check me-1"></i>Autorizado
                                                </span>
                                                <?php elseif ($estadoAuth === 'rechazada'): ?>
                                                <span class="badge bg-danger badge-sm">
                                                    <i class="fas fa-times me-1"></i>Rechazado
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Distribución de Centros de Costo -->
                        <?php if (!empty($centrosAutorizaciones) || !empty($distribucion)): ?>
                        <div class="modal-card mb-3">
                            <div class="modal-card-header">
                                <h6 class="modal-card-title">
                                    <i class="fas fa-chart-pie me-1"></i>
                                    Distribución de Centros de Costo
                                </h6>
                            </div>
                            <div class="modal-card-body">
                                <?php if (!empty($centrosAutorizaciones)): ?>
                                    <div class="modal-distribution-list">
                                        <?php foreach ($centrosAutorizaciones as $centro): ?>
                                            <?php
                                                $metadataCentro = is_string($centro['metadata'] ?? null)
                                                    ? json_decode($centro['metadata'], true)
                                                    : ($centro['metadata'] ?? []);
                                                $distInfo = [];
                                                $centroCostoId = $centro['centro_costo_id'] ?? null;
                                                if ($centroCostoId && isset($centrosDistrib[$centroCostoId])) {
                                                    $distInfo = $centrosDistrib[$centroCostoId];
                                                }
                                                $porcentajeCentro = $centro['porcentaje'] ?? ($metadataCentro['porcentaje'] ?? ($distInfo['porcentaje'] ?? 0));
                                                $montoCentro = $distInfo['monto_distribuido'] ?? ($distInfo['monto'] ?? 0);
                                                $estadoCentro = $centro['estado'] ?? 'pendiente';
                                            ?>
                                            <div class="modal-distribution-item">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div class="flex-grow-1">
                                                        <div class="fw-bold"><?php echo View::e($centro['centro_nombre'] ?? 'N/A'); ?></div>
                                                        <small class="text-muted">
                                                            <i class="fas fa-calculator me-1"></i>
                                                            <?php echo View::e($distInfo['cuenta_nombre'] ?? 'N/A'); ?>
                                                        </small>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="fw-bold text-primary"><?php echo View::money($montoCentro, $moneda); ?></div>
                                                        <small class="text-muted"><?php echo number_format($porcentajeCentro, 1); ?>%</small>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user-tie me-1"></i>
                                                        <?php echo View::e($centro['autorizador_email'] ?? 'N/A'); ?>
                                                    </small>
                                                    <?php if ($estadoCentro === 'pendiente'): ?>
                                                        <span class="badge bg-warning text-dark badge-sm">Pendiente</span>
                                                    <?php elseif ($estadoCentro === 'autorizado'): ?>
                                                        <span class="badge bg-success badge-sm">Autorizado</span>
                                                    <?php elseif ($estadoCentro === 'rechazado'): ?>
                                                        <span class="badge bg-danger badge-sm">Rechazado</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-info-circle mb-2"></i>
                                        <div>No hay distribución de centros de costo configurada</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Timeline de Actividad -->
                        <div class="modal-card mb-3">
                            <div class="modal-card-header">
                                <h6 class="modal-card-title">
                                    <i class="fas fa-history me-1"></i>
                                    Actividad Reciente
                                </h6>
                            </div>
                            <div class="modal-card-body">
                                <div class="modal-timeline">
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-primary"></div>
                                        <div class="timeline-content">
                                            <div class="timeline-time">
                                                <i class="fas fa-plus me-1"></i>
                                                <?php echo View::formatDate(getValue($orden, 'fecha_solicitud')); ?>
                                            </div>
                                            <div class="timeline-desc">Requisición creada por <?php echo View::e(getValue($orden, 'created_by', 'Sistema')); ?></div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($flujoEstado !== 'borrador'): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-info"></div>
                                        <div class="timeline-content">
                                            <div class="timeline-time">
                                                <i class="fas fa-paper-plane me-1"></i>
                                                Enviado
                                            </div>
                                            <div class="timeline-desc">Requisición enviada para revisión</div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($flujoEstado === 'pendiente_autorizacion' || $flujoEstado === 'autorizado' || $flujoEstado === 'rechazado'): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-warning"></div>
                                        <div class="timeline-content">
                                            <div class="timeline-time">
                                                <i class="fas fa-eye me-1"></i>
                                                En Revisión
                                            </div>
                                            <div class="timeline-desc">Requisición aprobada para autorización</div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($flujoEstado === 'autorizado'): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-success"></div>
                                        <div class="timeline-content">
                                            <div class="timeline-time">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Completado
                                            </div>
                                            <div class="timeline-desc">Requisición completamente autorizada</div>
                                        </div>
                                    </div>
                                    <?php elseif ($flujoEstado === 'rechazado'): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-danger"></div>
                                        <div class="timeline-content">
                                            <div class="timeline-time">
                                                <i class="fas fa-times-circle me-1"></i>
                                                Rechazado
                                            </div>
                                            <div class="timeline-desc">Requisición rechazada, requiere ajustes</div>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="timeline-item current">
                                        <div class="timeline-marker bg-primary"></div>
                                        <div class="timeline-content">
                                            <div class="timeline-time">
                                                <i class="fas fa-clock me-1"></i>
                                                Actual
                                            </div>
                                            <div class="timeline-desc">Esperando autorización</div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Estadísticas y Alertas -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="modal-stat-card">
                                    <div class="stat-icon bg-primary">
                                        <i class="fas fa-calendar-day"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-value">
                                            <?php 
                                            $fechaCreacion = getValue($orden, 'fecha_solicitud');
                                            if (!$fechaCreacion) {
                                                $fechaCreacion = getValue($orden, 'created_at');
                                            }
                                            if (!$fechaCreacion) {
                                                $fechaCreacion = getValue($orden, 'fecha');
                                            }
                                            
                                            if ($fechaCreacion) {
                                                if (is_numeric($fechaCreacion)) {
                                                    $timestamp = $fechaCreacion;
                                                } else {
                                                    $timestamp = strtotime($fechaCreacion);
                                                }
                                                
                                                if ($timestamp !== false && $timestamp > 0) {
                                                    $diferencia = time() - $timestamp;
                                                    $dias = max(0, floor($diferencia / (60 * 60 * 24)));
                                                    echo $dias;
                                                } else {
                                                    echo 'N/A';
                                                }
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </div>
                                        <div class="stat-label">Días transcurridos</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="modal-stat-card">
                                    <div class="stat-icon bg-success">
                                        <i class="fas fa-percentage"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-value">
                                            <?php
                                            $totalAutorizaciones = count($centrosAutorizaciones ?? []) + count($autorizaciones_especiales ?? []);
                                            $autorizacionesCompletas = 0;
                                            
                                            foreach ($centrosAutorizaciones ?? [] as $centro) {
                                                if (($centro['estado'] ?? 'pendiente') === 'autorizado') {
                                                    $autorizacionesCompletas++;
                                                }
                                            }
                                            foreach ($autorizaciones_especiales ?? [] as $auth) {
                                                if (($auth['estado'] ?? 'pendiente') === 'autorizada') {
                                                    $autorizacionesCompletas++;
                                                }
                                            }
                                            
                                            echo $totalAutorizaciones > 0 ? round(($autorizacionesCompletas / $totalAutorizaciones) * 100) : 0;
                                            ?>%
                                        </div>
                                        <div class="stat-label">Progreso</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alertas y Recomendaciones -->
                        <div class="modal-card mb-3">
                            <div class="modal-card-header">
                                <h6 class="modal-card-title">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    Alertas y Recomendaciones
                                </h6>
                            </div>
                            <div class="modal-card-body">
                                <div class="alert-list">
                                    <?php $montoTotal = getValue($orden, 'monto_total', 0); ?>
                                    
                                    <!-- Alerta de monto alto -->
                                    <?php if ($montoTotal > 10000000): ?>
                                    <div class="modal-alert alert-warning">
                                        <div class="alert-icon">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <div class="alert-content">
                                            <div class="alert-title">Monto Significativo</div>
                                            <div class="alert-desc">Esta requisición supera los $10M. Revisar justificación cuidadosamente.</div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Alerta de días pendientes -->
                                    <?php if (isset($dias) && $dias > 5): ?>
                                    <div class="modal-alert alert-info">
                                        <div class="alert-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="alert-content">
                                            <div class="alert-title">Tiempo Extendido</div>
                                            <div class="alert-desc">Requisición pendiente por <?php echo $dias; ?> días. Considerar priorizar.</div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Recomendación de autorización múltiple -->
                                    <?php if (!empty($autorizaciones_especiales)): ?>
                                    <div class="modal-alert alert-special">
                                        <div class="alert-icon">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div class="alert-content">
                                            <div class="alert-title">Autorizaciones Especiales</div>
                                            <div class="alert-desc">Requiere <?php echo count($autorizaciones_especiales); ?> autorizaciones especiales adicionales.</div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Recomendación general -->
                                    <div class="modal-alert alert-success">
                                        <div class="alert-icon">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div class="alert-content">
                                            <div class="alert-title">Lista para Autorizar</div>
                                            <div class="alert-desc">Toda la información está completa y verificada.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Presupuesto -->
                        <div class="modal-card mb-3">
                            <div class="modal-card-header">
                                <h6 class="modal-card-title">
                                    <i class="fas fa-chart-line me-1"></i>
                                    Análisis Presupuestal
                                </h6>
                            </div>
                            <div class="modal-card-body">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="modal-info-field">
                                            <label class="modal-info-label">Impacto Presupuestal</label>
                                            <div class="modal-info-value">
                                                <?php 
                                                $impacto = 'Bajo';
                                                $impactoClass = 'text-success';
                                                $impactoIcon = 'fa-arrow-down';
                                                
                                                if ($montoTotal > 50000000) {
                                                    $impacto = 'Crítico';
                                                    $impactoClass = 'text-danger';
                                                    $impactoIcon = 'fa-arrow-up';
                                                } elseif ($montoTotal > 20000000) {
                                                    $impacto = 'Alto';
                                                    $impactoClass = 'text-warning';
                                                    $impactoIcon = 'fa-arrow-up';
                                                } elseif ($montoTotal > 5000000) {
                                                    $impacto = 'Moderado';
                                                    $impactoClass = 'text-info';
                                                    $impactoIcon = 'fa-minus';
                                                }
                                                ?>
                                                <i class="fas <?php echo $impactoIcon; ?> me-1 <?php echo $impactoClass; ?>"></i>
                                                <span class="<?php echo $impactoClass; ?>"><?php echo $impacto; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="modal-info-field">
                                            <label class="modal-info-label">Nivel de Aprobación</label>
                                            <div class="modal-info-value">
                                                <?php 
                                                $nivel = 'Estándar';
                                                if ($montoTotal > 50000000) {
                                                    $nivel = 'Directivo';
                                                } elseif ($montoTotal > 20000000) {
                                                    $nivel = 'Gerencial';
                                                } elseif ($montoTotal > 10000000) {
                                                    $nivel = 'Supervisión';
                                                }
                                                ?>
                                                <i class="fas fa-layer-group me-1"></i>
                                                <?php echo $nivel; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
            </div>
        </div>
    `;
}

// Enhanced content creation with modern design
function createEnhancedContent(data) {
    try {
        // Parse data if it's a string
        const parsedData = typeof data === 'string' ? JSON.parse(data) : data;
        
        if (!parsedData || !parsedData.requisicion) {
            return createFallbackContent(data);
        }
        
        const req = parsedData.requisicion;
        
        return `
            <div class="modal-content-enhanced">
                ${createHeaderSection(req)}
                ${createInfoGrid(req, parsedData)}
                ${parsedData.items ? createItemsSection(parsedData.items) : ''}
                ${req.observaciones ? createObservationsSection(req.observaciones) : ''}
                ${parsedData.autorizaciones ? createAuthSection(parsedData.autorizaciones) : ''}
                ${createTimelineSection(parsedData)}
            </div>
        `;
    } catch (error) {
        console.error('Error parsing data:', error);
        return createFallbackContent(data);
    }
}

// Create header section with modern design
function createHeaderSection(req) {
    return `
        <div class="modal-detail-header">
            <div class="detail-title-group">
                <h5 class="detail-title">
                    <span class="title-icon">
                        <i class="fas fa-file-invoice"></i>
                    </span>
                    Requisición #${req.numero || req.id || 'N/A'}
                </h5>
                <div class="status-badge-modern ${getStatusClass(req.estado)}">
                    <i class="fas ${getStatusIcon(req.estado)}"></i>
                    <span>${getStatusText(req.estado)}</span>
                </div>
            </div>
            <div class="detail-meta">
                <div class="meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>${formatDate(req.fecha)}</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-user"></i>
                    <span>${req.solicitante || 'No especificado'}</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-dollar-sign"></i>
                    <span class="amount">${formatCurrency(req.monto_total)}</span>
                </div>
            </div>
        </div>
    `;
}

// Create information grid
function createInfoGrid(req, data) {
    return `
        <div class="info-grid">
            ${createInfoCard('Información General', [
                {label: 'Descripción', value: req.descripcion, icon: 'fas fa-align-left'},
                {label: 'Centro de Costo', value: req.centro_costo, icon: 'fas fa-building'},
                {label: 'Proveedor', value: req.proveedor_nombre, icon: 'fas fa-store'},
                {label: 'Forma de Pago', value: req.forma_pago, icon: 'fas fa-credit-card'}
            ])}
            ${createInfoCard('Datos del Proveedor', [
                {label: 'Razón Social', value: req.proveedor_nombre, icon: 'fas fa-building'},
                {label: 'NIT', value: req.proveedor_nit, icon: 'fas fa-id-card'},
                {label: 'Dirección', value: req.proveedor_direccion, icon: 'fas fa-map-marker-alt'},
                {label: 'Teléfono', value: req.proveedor_telefono, icon: 'fas fa-phone'}
            ])}
        </div>
    `;
}

// Create info card
function createInfoCard(title, items) {
    const validItems = items.filter(item => item.value && item.value !== 'N/A');
    
    if (validItems.length === 0) return '';
    
    return `
        <div class="info-card">
            <div class="info-card-header">
                <h6 class="info-card-title">${title}</h6>
            </div>
            <div class="info-card-body">
                ${validItems.map(item => `
                    <div class="info-item">
                        <div class="info-label">
                            <i class="${item.icon}"></i>
                            ${item.label}
                        </div>
                        <div class="info-value">${item.value}</div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

// Create items section
function createItemsSection(items) {
    if (!items || items.length === 0) return '';
    
    return `
        <div class="items-section">
            <div class="section-header">
                <h6 class="section-title">
                    <i class="fas fa-list me-2"></i>
                    Items de la Requisición
                </h6>
            </div>
            <div class="items-table-container">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Descripción</th>
                            <th width="100">Cantidad</th>
                            <th width="120">Precio Unit.</th>
                            <th width="120">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${items.map((item, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${item.descripcion}</td>
                                <td>${formatNumber(item.cantidad)}</td>
                                <td>${formatCurrency(item.precio_unitario)}</td>
                                <td class="amount">${formatCurrency(item.cantidad * item.precio_unitario)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

// Create observations section
function createObservationsSection(observaciones) {
    return `
        <div class="observations-section">
            <div class="section-header">
                <h6 class="section-title">
                    <i class="fas fa-comment-alt me-2"></i>
                    Observaciones
                </h6>
            </div>
            <div class="observations-content">
                ${observaciones.split('\n').map(line => `<p>${line}</p>`).join('')}
            </div>
        </div>
    `;
}

// Create authorization section
function createAuthSection(autorizaciones) {
    if (!autorizaciones || autorizaciones.length === 0) return '';
    
    return `
        <div class="auth-section">
            <div class="section-header">
                <h6 class="section-title">
                    <i class="fas fa-check-circle me-2"></i>
                    Autorizaciones
                </h6>
            </div>
            <div class="auth-list">
                ${autorizaciones.map(auth => `
                    <div class="auth-item">
                        <div class="auth-info">
                            <div class="auth-type">${auth.tipo}</div>
                            <div class="auth-detail">${auth.detalle}</div>
                        </div>
                        <div class="auth-status ${auth.estado}">
                            <i class="fas ${getStatusIcon(auth.estado)}"></i>
                            ${getStatusText(auth.estado)}
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

// Create timeline section
function createTimelineSection(data) {
    return `
        <div class="timeline-section">
            <div class="section-header">
                <h6 class="section-title">
                    <i class="fas fa-history me-2"></i>
                    Línea de Tiempo
                </h6>
            </div>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker created"></div>
                    <div class="timeline-content">
                        <div class="timeline-time">Creado</div>
                        <div class="timeline-desc">Requisición creada en el sistema</div>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker pending"></div>
                    <div class="timeline-content">
                        <div class="timeline-time">En proceso</div>
                        <div class="timeline-desc">Pendiente de autorización</div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Utility functions
function getStatusClass(estado) {
    const statusMap = {
        'borrador': 'status-draft',
        'pendiente_revision': 'status-pending',
        'pendiente_autorizacion': 'status-pending',
        'autorizado': 'status-approved',
        'autorizada': 'status-approved',
        'rechazado': 'status-rejected',
        'rechazada': 'status-rejected'
    };
    return statusMap[estado] || 'status-unknown';
}

function getStatusIcon(estado) {
    const iconMap = {
        'borrador': 'fa-edit',
        'pendiente_revision': 'fa-clock',
        'pendiente_autorizacion': 'fa-hourglass-half',
        'autorizado': 'fa-check-circle',
        'autorizada': 'fa-check-circle',
        'rechazado': 'fa-times-circle',
        'rechazada': 'fa-times-circle'
    };
    return iconMap[estado] || 'fa-question-circle';
}

function getStatusText(estado) {
    const textMap = {
        'borrador': 'Borrador',
        'pendiente_revision': 'Pendiente de Revisión',
        'pendiente_autorizacion': 'Pendiente de Autorización',
        'autorizado': 'Autorizado',
        'autorizada': 'Autorizada',
        'rechazado': 'Rechazado',
        'rechazada': 'Rechazada'
    };
    return textMap[estado] || estado;
}

function formatDate(date) {
    if (!date) return 'No especificada';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatCurrency(amount) {
    if (!amount) return '$0.00';
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP'
    }).format(amount);
}

function formatNumber(num) {
    if (!num) return '0';
    return new Intl.NumberFormat('es-ES').format(num);
}

// Fallback content for when parsing fails
function createFallbackContent(rawData) {
    return `
        <div class="modal-content-section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <h6 class="section-title">Información de la Requisición</h6>
            </div>
            <div class="section-body">
                <div class="info-card">
                    <div class="info-card-header">
                        <div class="info-card-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h6 class="info-card-title">Detalles Completos</h6>
                    </div>
                    <div class="info-card-body">
                        ${typeof rawData === 'string' ? rawData : JSON.stringify(rawData, null, 2)}
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Modal navigation and interactive features
let currentSectionIndex = 0;
let totalSections = 0;
let isFullscreen = false;

// Initialize modal interactions
function initializeModalInteractions() {
    const modal = document.getElementById('modalDetalleRequisicion');
    if (!modal) return;
    
    // Initialize navigation
    const prevBtn = document.getElementById('prevSection');
    const nextBtn = document.getElementById('nextSection');
    
    if (prevBtn && nextBtn) {
        prevBtn.addEventListener('click', () => navigateSection(-1));
        nextBtn.addEventListener('click', () => navigateSection(1));
    }
    
    // Initialize keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (modal.classList.contains('show')) {
            switch(e.key) {
                case 'ArrowUp':
                    e.preventDefault();
                    navigateSection(-1);
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    navigateSection(1);
                    break;
                case 'Escape':
                    if (isFullscreen) {
                        toggleFullscreen();
                    }
                    break;
                case 'F11':
                    e.preventDefault();
                    toggleFullscreen();
                    break;
            }
        }
    });
    
    // Update sections count after content loads
    modal.addEventListener('shown.bs.modal', function() {
        updateSectionsCount();
    });
}

// Navigate between modal sections
function navigateSection(direction) {
    const sections = document.querySelectorAll('.modal-content-enhanced > div, .modal-content-section > div');
    totalSections = sections.length;
    
    if (totalSections === 0) return;
    
    // Update current section index
    currentSectionIndex = Math.max(0, Math.min(totalSections - 1, currentSectionIndex + direction));
    
    // Scroll to the current section
    if (sections[currentSectionIndex]) {
        sections[currentSectionIndex].scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
        
        // Highlight current section temporarily
        sections[currentSectionIndex].style.boxShadow = '0 0 10px rgba(231, 76, 60, 0.3)';
        setTimeout(() => {
            sections[currentSectionIndex].style.boxShadow = '';
        }, 1000);
    }
    
    // Update navigation indicators
    updateNavigationState();
}

// Update navigation state and indicators
function updateNavigationState() {
    const currentSpan = document.querySelector('.current-section');
    const totalSpan = document.querySelector('.total-sections');
    const prevBtn = document.getElementById('prevSection');
    const nextBtn = document.getElementById('nextSection');
    
    if (currentSpan) currentSpan.textContent = currentSectionIndex + 1;
    if (totalSpan) totalSpan.textContent = totalSections;
    
    if (prevBtn) prevBtn.disabled = currentSectionIndex === 0;
    if (nextBtn) nextBtn.disabled = currentSectionIndex === totalSections - 1;
}

// Update sections count
function updateSectionsCount() {
    const sections = document.querySelectorAll('.modal-content-enhanced > div, .modal-content-section > div');
    totalSections = sections.length;
    currentSectionIndex = 0;
    updateNavigationState();
}

// Toggle fullscreen mode
function toggleFullscreen() {
    const modal = document.getElementById('modalDetalleRequisicion');
    const modalDialog = modal.querySelector('.modal-dialog');
    const fullscreenBtn = document.querySelector('.btn[onclick="toggleFullscreen()"]');
    
    if (!isFullscreen) {
        // Enter fullscreen
        modalDialog.classList.add('modal-fullscreen');
        isFullscreen = true;
        if (fullscreenBtn) {
            fullscreenBtn.innerHTML = '<i class="fas fa-compress me-2"></i>Salir pantalla completa';
        }
        
        // Add fullscreen styles
        modal.style.zIndex = '9999';
        document.body.style.overflow = 'hidden';
        
    } else {
        // Exit fullscreen
        modalDialog.classList.remove('modal-fullscreen');
        isFullscreen = false;
        if (fullscreenBtn) {
            fullscreenBtn.innerHTML = '<i class="fas fa-expand me-2"></i>Pantalla completa';
        }
        
        // Remove fullscreen styles
        modal.style.zIndex = '';
        document.body.style.overflow = '';
    }
}

// Share modal functionality
function shareModal() {
    const requisicionId = <?php echo getValue($orden, 'id'); ?>;
    const shareUrl = window.location.origin + '<?= url('/requisiciones/') ?>' + requisicionId;
    
    if (navigator.share) {
        // Use native share API if available
        navigator.share({
            title: 'Requisición #' + requisicionId,
            text: 'Revisar requisición',
            url: shareUrl
        }).catch(err => {
            console.log('Error sharing:', err);
            fallbackShare(shareUrl);
        });
    } else {
        fallbackShare(shareUrl);
    }
}

// Fallback share functionality
function fallbackShare(url) {
    // Copy to clipboard
    navigator.clipboard.writeText(url).then(() => {
        // Show success notification
        if (window.AuthEffects) {
            window.AuthEffects.showSuccessNotification('Enlace copiado al portapapeles', 'special');
        } else {
            alert('Enlace copiado al portapapeles');
        }
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = url;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        if (window.AuthEffects) {
            window.AuthEffects.showSuccessNotification('Enlace copiado al portapapeles', 'special');
        } else {
            alert('Enlace copiado al portapapeles');
        }
    });
}

// Función para rechazar requisición desde la vista de detalle
function rechazarRequisicionDetalle(requisicionId) {
    // Crear modal dinámico para el motivo del rechazo
    const modalHtml = `
        <div class="modal fade" id="rechazarDetalleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-times me-2"></i>
                            Rechazar Requisición #${requisicionId}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Atención:</strong> Al rechazar esta requisición, se notificará al solicitante y no podrá continuar el proceso.
                        </div>
                        <form id="formRechazarDetalle">
                            <div class="mb-3">
                                <label for="motivoRechazoDetalle" class="form-label">
                                    <i class="fas fa-comment me-1"></i>
                                    Motivo del rechazo *
                                </label>
                                <textarea class="form-control" id="motivoRechazoDetalle" name="motivo" rows="4" 
                                          placeholder="Explica detalladamente por qué se rechaza esta requisición..." required></textarea>
                                <div class="form-text">El motivo será enviado al solicitante y quedará registrado en el historial.</div>
                            </div>
                            <input type="hidden" name="_token" value="${getCsrfTokenDetalle()}">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left me-1"></i>
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-danger" onclick="confirmarRechazoDetalle(${requisicionId})">
                            <i class="fas fa-times me-1"></i>
                            Confirmar Rechazo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal existente si existe
    const existingModal = document.getElementById('rechazarDetalleModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('rechazarDetalleModal'));
    modal.show();
}

// Función para confirmar el rechazo desde la vista de detalle
function confirmarRechazoDetalle(requisicionId) {
    const motivo = document.getElementById('motivoRechazoDetalle').value.trim();
    
    if (!motivo) {
        alert('Debes especificar el motivo del rechazo.');
        return;
    }
    
    // Obtener el token CSRF del formulario
    const csrfToken = document.querySelector('#formRechazarDetalle input[name="_token"]').value;
    
    // Deshabilitar botón para evitar doble envío
    const btnConfirmar = document.querySelector('#rechazarDetalleModal .btn-danger');
    btnConfirmar.disabled = true;
    btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Rechazando...';
    
    // Realizar petición AJAX
    fetch(`<?= url('/autorizaciones/') ?>${requisicionId}/rechazar-revision`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            'motivo': motivo,
            '_token': csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cerrar modal
            bootstrap.Modal.getInstance(document.getElementById('rechazarDetalleModal')).hide();
            
            // Mostrar efectos de éxito/rechazo
            if (window.AuthEffects) {
                window.AuthEffects.showSuccessNotification('Requisición rechazada exitosamente', 'special');
            }
            
            // Redirigir después de un momento
            setTimeout(() => {
                window.location.href = '<?= url('/autorizaciones') ?>';
            }, 1500);
        } else {
            // Rehabilitar botón
            btnConfirmar.disabled = false;
            btnConfirmar.innerHTML = '<i class="fas fa-times me-1"></i>Confirmar Rechazo';
            
            alert('Error: ' + (data.error || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Rehabilitar botón
        btnConfirmar.disabled = false;
        btnConfirmar.innerHTML = '<i class="fas fa-times me-1"></i>Confirmar Rechazo';
        
        alert('Error de conexión. Inténtalo de nuevo.');
    });
}

// Función para obtener el token CSRF específica para esta vista
function getCsrfTokenDetalle() {
    // Intentar obtener el token de un formulario existente en la página
    const existingToken = document.querySelector('input[name="_token"]');
    if (existingToken) {
        return existingToken.value;
    }
    
    // Como fallback, generar un token
    return '<?php echo \App\Middlewares\CsrfMiddleware::getToken(); ?>';
}

// Initialize modal content after loading
function initializeModalContent() {
    // Update sections count for navigation
    updateSectionsCount();
    
    // Initialize tooltips if any
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
    
    // Initialize any additional interactive elements
    const tables = document.querySelectorAll('.table-responsive');
    tables.forEach(table => {
        // Add smooth scrollbar styles
        table.style.scrollbarWidth = 'thin';
        table.style.scrollbarColor = '#e74c3c #f1f1f1';
    });
    
    // Add copy-to-clipboard functionality for requisition ID
    const requisitionId = document.querySelector('.modal-requisition-detail h3');
    if (requisitionId) {
        requisitionId.style.cursor = 'pointer';
        requisitionId.title = 'Click para copiar ID de requisición';
        
        requisitionId.addEventListener('click', function() {
            const id = this.textContent.match(/#(\d+)/);
            if (id && id[1]) {
                navigator.clipboard.writeText(id[1]).then(() => {
                    if (window.AuthEffects) {
                        window.AuthEffects.showSuccessNotification('ID copiado al portapapeles', 'special');
                    }
                });
            }
        });
    }
}

// Initialize interactions when DOM is ready
document.addEventListener('DOMContentLoaded', initializeModalInteractions);
</script>

<?php View::endSection(); ?>
