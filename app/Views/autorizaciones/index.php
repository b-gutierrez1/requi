<?php
use App\Helpers\View;

View::startSection('content');
?>


<div class="container py-4" style="max-width: 1200px;">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">
                <i class="fas fa-tasks me-2"></i>
                Panel de Autorizaciones
            </h1>
            <p class="text-muted mb-0">
                Requisiciones que requieren tu atención
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= url('/autorizaciones/historial') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-history me-2"></i>Ver Historial
            </a>
        </div>
    </div>

    <!-- Estilos para tarjetas de estadísticas -->
    <style>
        .stat-card-revision {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important;
            border: none !important;
        }
        .stat-card-autorizacion {
            background: linear-gradient(135deg, #ec4899 0%, #db2777 100%) !important;
            border: none !important;
        }
        .stat-card-revision *, .stat-card-autorizacion * {
            color: #ffffff !important;
        }
        .stat-card-revision .stat-icon, .stat-card-autorizacion .stat-icon {
            color: rgba(255,255,255,0.7) !important;
        }
        .stat-card-revision .stat-label, .stat-card-autorizacion .stat-label {
            color: rgba(255,255,255,0.85) !important;
        }
        .header-revision {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important;
        }
        .header-autorizacion {
            background: linear-gradient(135deg, #ec4899 0%, #db2777 100%) !important;
        }
        .header-revision *, .header-autorizacion * {
            color: #ffffff !important;
        }
    </style>

    <!-- Resumen de pendientes -->
    <?php 
    $esRevisor = $es_revisor ?? false;
    $countRevision = $esRevisor ? count($requisiciones_pendientes_revision ?? []) : 0;
    $countAutorizacion = count($todas_autorizaciones ?? []);
    ?>
    <?php if ($countRevision > 0 || $countAutorizacion > 0): ?>
    <div class="row mb-4 g-3">
        <?php if ($esRevisor && $countRevision > 0): ?>
        <div class="col-md-6">
            <div class="card stat-card-revision h-100 shadow">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="me-4 stat-icon">
                        <i class="fas fa-clipboard-list fa-3x"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 fw-bold"><?php echo $countRevision; ?></h2>
                        <span class="stat-label">Pendientes de Revisión</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($countAutorizacion > 0): ?>
        <div class="col-md-<?php echo ($esRevisor && $countRevision > 0) ? '6' : '12'; ?>">
            <div class="card stat-card-autorizacion h-100 shadow">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="me-4 stat-icon">
                        <i class="fas fa-file-signature fa-3x"></i>
                    </div>
                    <div>
                        <h2 class="mb-0 fw-bold"><?php echo $countAutorizacion; ?></h2>
                        <span class="stat-label">Pendientes de Autorización</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Requisiciones Pendientes de Revisión (solo para revisores) -->
    <?php if ($esRevisor && !empty($requisiciones_pendientes_revision)): ?>
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header header-revision border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-clipboard-list me-2"></i>
                Pendientes de Revisión
            </h5>
            <span class="badge" style="background-color: rgba(255,255,255,0.25) !important;">
                <i class="fas fa-user-edit me-1"></i>Rol: Revisor
            </span>
        </div>
        <div class="card-body">
            <?php foreach ($requisiciones_pendientes_revision as $req): ?>
            <div class="card mb-3 border-0 shadow-sm" style="border-left: 4px solid #8b5cf6 !important;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge rounded-pill me-2" style="background-color: #8b5cf6;">Revisión</span>
                                <small class="text-muted">Fecha: <?php echo date('d/m/Y', strtotime($req['fecha_orden'])); ?></small>
                            </div>
                            <h6 class="mb-2 fw-semibold">
                                Requisición #<?php echo $req['orden_id'] ?? $req['requisicion_id'] ?? $req['id']; ?>
                            </h6>
                            <p class="text-muted mb-1 small">
                                <i class="fas fa-user me-1"></i> <?php echo View::e($req['usuario_nombre'] ?? 'Usuario desconocido'); ?>
                            </p>
                            <p class="text-muted mb-1 small">
                                <i class="fas fa-building me-1"></i> <?php echo View::e($req['nombre_razon_social']); ?>
                            </p>
                            <p class="mb-0 fw-semibold" style="color: #374151;">
                                <i class="fas fa-coins me-1"></i> Q<?php echo number_format($req['monto_total'], 2); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="<?= url('/autorizaciones/' . ($req['requisicion_id'] ?? $req['id'])) ?>"
                                   class="btn btn-sm fw-semibold px-3 rounded-pill" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none;">
                                    <i class="fas fa-eye me-1"></i>
                                    Ver Detalle
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
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header header-autorizacion border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-file-signature me-2"></i>
                Pendientes de Autorización
            </h5>
            <span class="badge" style="background-color: rgba(255,255,255,0.25) !important;">
                <i class="fas fa-user-check me-1"></i>Rol: Autorizador
                <?php if ($es_autorizador_respaldo ?? false): ?>
                    (Respaldo)
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body">
            <?php foreach ($todas_autorizaciones as $auth): ?>
            <div class="card mb-3 border-0 shadow-sm" style="border-left: 4px solid <?php 
                    echo match($auth['tipo'] ?? 'centro_costo') {
                        'forma_pago' => '#10b981',
                        'cuenta_contable' => '#06b6d4', 
                        'revision' => '#8b5cf6',
                        default => '#f59e0b'
                    };
                ?> !important;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <!-- Encabezado según el tipo -->
                            <div class="d-flex align-items-center mb-2">
                                <?php if ($auth['tipo'] === 'forma_pago'): ?>
                                    <span class="badge rounded-pill me-2" style="background-color: #10b981;">Forma de Pago</span>
                                <?php elseif ($auth['tipo'] === 'cuenta_contable'): ?>
                                    <span class="badge rounded-pill me-2" style="background-color: #06b6d4;">Cuenta Contable</span>
                                <?php elseif ($auth['tipo'] === 'revision'): ?>
                                    <span class="badge rounded-pill me-2" style="background-color: #8b5cf6;">Revisión</span>
                                <?php else: ?>
                                    <span class="badge rounded-pill text-dark me-2" style="background-color: #fbbf24;">Centro de Costo</span>
                                <?php endif; ?>
                                
                                <!-- Badge de respaldo si aplica -->
                                <?php if (isset($auth['metadata']) && $auth['metadata']): 
                                    $metadata = is_string($auth['metadata']) ? json_decode($auth['metadata'], true) : $auth['metadata'];
                                    if ($metadata['es_respaldo'] ?? false):
                                ?>
                                    <span class="badge rounded-pill" style="background-color: #6b7280;">
                                        <i class="fas fa-user-shield me-1"></i>Respaldo
                                    </span>
                                <?php 
                                    endif; 
                                endif; 
                                ?>
                                <small class="text-muted ms-auto">
                                    <?php echo date('d/m/Y', strtotime($auth['fecha_orden'] ?? $auth['created_at'])); ?>
                                </small>
                            </div>
                            
                            <h6 class="mb-2 fw-semibold">
                                Requisición #<?php echo $auth['orden_id'] ?? $auth['requisicion_id'] ?? $auth['id']; ?>
                            </h6>
                            <p class="text-muted mb-1 small">
                                <i class="fas fa-building me-1"></i> <?php echo View::e($auth['nombre_razon_social']); ?>
                            </p>
                            
                            <!-- Información específica por tipo -->
                            <?php if ($auth['tipo'] === 'centro_costo' && isset($auth['centro_nombre'])): ?>
                                <p class="text-muted mb-1 small">
                                    <i class="fas fa-sitemap me-1"></i> <?php echo View::e($auth['centro_nombre']); ?>
                                    <?php if (isset($auth['porcentaje'])): ?>
                                        <span class="badge bg-light text-dark ms-1"><?php echo $auth['porcentaje']; ?>%</span>
                                    <?php endif; ?>
                                </p>
                            <?php elseif ($auth['tipo'] === 'forma_pago' && isset($auth['metadata'])): 
                                $metadata = is_string($auth['metadata']) ? json_decode($auth['metadata'], true) : $auth['metadata'];
                            ?>
                                <p class="text-muted mb-1 small">
                                    <i class="fas fa-credit-card me-1"></i> <?php echo View::e($metadata['forma_pago'] ?? 'No especificada'); ?>
                                </p>
                            <?php elseif ($auth['tipo'] === 'cuenta_contable' && isset($auth['metadata'])): 
                                $metadata = is_string($auth['metadata']) ? json_decode($auth['metadata'], true) : $auth['metadata'];
                            ?>
                                <p class="text-muted mb-1 small">
                                    <i class="fas fa-calculator me-1"></i> <?php echo View::e($metadata['cuenta_nombre'] ?? 'No especificada'); ?>
                                </p>
                            <?php endif; ?>
                            
                            <p class="mb-0 fw-semibold" style="color: #374151;">
                                <i class="fas fa-coins me-1"></i> Q<?php echo number_format($auth['monto_total'], 2); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="d-flex gap-2">
                                <?php 
                                // Para autorizaciones especiales, usar el ID de la autorización
                                // Para revisión y centros de costo, usar el ID de la requisición
                                $urlId = ($auth['tipo'] === 'forma_pago' || $auth['tipo'] === 'cuenta_contable') 
                                    ? $auth['id'] 
                                    : ($auth['requisicion_id'] ?? $auth['id']);
                                ?>
                                <a href="<?= url('/autorizaciones/' . $urlId) ?>" 
                                   class="btn btn-sm fw-semibold px-3 rounded-pill" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none;">
                                    <i class="fas fa-eye me-1"></i>
                                    Ver Detalle
                                </a>
                                <button type="button" class="btn btn-sm rounded-pill px-3" 
                                        style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none;"
                                        onclick="rechazarAutorizacion(<?php echo $urlId; ?>, '<?php echo $auth['tipo']; ?>')">
                                    <i class="fas fa-times me-1"></i>
                                    Rechazar
                                </button>
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
                                <strong>Requisición:</strong> #<?php echo $auth['orden_id'] ?? $auth['requisicion_id'] ?? $auth['id']; ?> - <?php echo View::e($auth['nombre_razon_social']); ?>
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
                                <a href="<?= url('/autorizaciones/' . ($auth['requisicion_id'] ?? $auth['id'])) ?>" class="btn btn-warning btn-sm">
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
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <div class="mb-4">
                <i class="fas fa-check-circle fa-4x" style="color: #10b981;"></i>
            </div>
            <h4 class="fw-semibold mb-2" style="color: #374151;">¡Todo al día!</h4>
            <p class="text-muted mb-0">No tienes autorizaciones pendientes en este momento.</p>
        </div>
    </div>
    <?php endif; ?>
</div>


<?php
View::endSection();

View::startSection('scripts');
?>
<script>
// Función para rechazar requisición (para revisores)
function rechazarRequisicion(requisicionId) {
    // Crear modal dinámico para el motivo del rechazo
    const modalHtml = `
        <div class="modal fade" id="rechazarModal" tabindex="-1">
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
                        <form id="formRechazar">
                            <div class="mb-3">
                                <label for="motivoRechazo" class="form-label">
                                    <i class="fas fa-comment me-1"></i>
                                    Motivo del rechazo *
                                </label>
                                <textarea class="form-control" id="motivoRechazo" name="motivo" rows="4" 
                                          placeholder="Explica detalladamente por qué se rechaza esta requisición..." required></textarea>
                                <div class="form-text">El motivo será enviado al solicitante.</div>
                            </div>
                            <input type="hidden" name="_token" value="${getCsrfToken()}">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left me-1"></i>
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-danger" onclick="confirmarRechazoRequisicion(${requisicionId})">
                            <i class="fas fa-times me-1"></i>
                            Confirmar Rechazo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal existente si existe
    const existingModal = document.getElementById('rechazarModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('rechazarModal'));
    modal.show();
}

// Función para confirmar el rechazo de requisición
function confirmarRechazoRequisicion(requisicionId) {
    const motivo = document.getElementById('motivoRechazo').value.trim();
    
    if (!motivo) {
        alert('Debes especificar el motivo del rechazo.');
        return;
    }
    
    // Obtener el token CSRF del formulario
    const csrfToken = document.querySelector('#formRechazar input[name="_token"]').value;
    
    // Realizar petición AJAX
    fetch(`<?= url('/autorizaciones/revision/') ?>${requisicionId}/rechazar`, {
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
            bootstrap.Modal.getInstance(document.getElementById('rechazarModal')).hide();
            
            // Mostrar mensaje de éxito
            showSuccess('Requisición rechazada exitosamente');
            
            // Recargar página después de un momento
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            alert('Error: ' + (data.error || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Inténtalo de nuevo.');
    });
}

// Función para rechazar autorización (para autorizadores)
function rechazarAutorizacion(authId, tipo) {
    const tipoTexto = tipo === 'forma_pago' ? 'Forma de Pago' : 
                     tipo === 'cuenta_contable' ? 'Cuenta Contable' : 
                     tipo === 'revision' ? 'Revisión' : 'Centro de Costo';
                     
    // Crear modal dinámico para el motivo del rechazo
    const modalHtml = `
        <div class="modal fade" id="rechazarAuthModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-times me-2"></i>
                            Rechazar Autorización de ${tipoTexto}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formRechazarAuth">
                            <div class="mb-3">
                                <label for="motivoRechazoAuth" class="form-label">
                                    <i class="fas fa-comment me-1"></i>
                                    Motivo del rechazo *
                                </label>
                                <textarea class="form-control" id="motivoRechazoAuth" name="motivo" rows="4" 
                                          placeholder="Explica detalladamente por qué se rechaza esta autorización..." required></textarea>
                                <div class="form-text">El motivo será registrado en el historial.</div>
                            </div>
                            <input type="hidden" name="_token" value="${getCsrfToken()}">
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left me-1"></i>
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-danger" onclick="confirmarRechazoAutorizacion(${authId}, '${tipo}')">
                            <i class="fas fa-times me-1"></i>
                            Confirmar Rechazo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remover modal existente si existe
    const existingModal = document.getElementById('rechazarAuthModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('rechazarAuthModal'));
    modal.show();
}

// Función para confirmar el rechazo de autorización
function confirmarRechazoAutorizacion(authId, tipo) {
    const motivo = document.getElementById('motivoRechazoAuth').value.trim();
    
    if (!motivo) {
        alert('Debes especificar el motivo del rechazo.');
        return;
    }
    
    // Obtener el token CSRF del formulario
    const csrfToken = document.querySelector('#formRechazarAuth input[name="_token"]').value;
    
    // Determinar la URL según el tipo
    let url;
    if (tipo === 'forma_pago') {
        url = `<?= url('/autorizaciones/pago/') ?>${authId}/rechazar`;
    } else if (tipo === 'cuenta_contable') {
        url = `<?= url('/autorizaciones/cuenta/') ?>${authId}/rechazar`;
    } else if (tipo === 'revision') {
        url = `<?= url('/autorizaciones/revision/') ?>${authId}/rechazar`;
    } else {
        url = `<?= url('/autorizaciones/centro/') ?>${authId}/rechazar`;
    }
    
    // Realizar petición AJAX
    fetch(url, {
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
            bootstrap.Modal.getInstance(document.getElementById('rechazarAuthModal')).hide();
            
            // Mostrar mensaje de éxito
            showSuccess('Autorización rechazada exitosamente');
            
            // Recargar página después de un momento
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            alert('Error: ' + (data.error || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Inténtalo de nuevo.');
    });
}

// Función para mostrar mensajes de éxito
function showSuccess(message) {
    const alertHtml = `
        <div class="alert alert-success alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="fas fa-check-circle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', alertHtml);
    
    // Auto-remover después de 3 segundos
    setTimeout(() => {
        const alert = document.querySelector('.alert-success');
        if (alert) {
            alert.remove();
        }
    }, 3000);
}

// Función para obtener el token CSRF
function getCsrfToken() {
    // Intentar obtener el token de un meta tag si existe
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) {
        return metaToken.getAttribute('content');
    }
    
    // Si no existe meta tag, usar el token de un formulario existente
    const existingToken = document.querySelector('input[name="_token"]');
    if (existingToken) {
        return existingToken.value;
    }
    
    // Como fallback, obtener el token de la sesión
    return '<?php echo \App\Middlewares\CsrfMiddleware::getToken(); ?>';
}
</script>
<?php View::endSection(); ?>