<?php
use App\Helpers\View;
use App\Middlewares\CsrfMiddleware;

// Helper para obtener valor de objeto o array
function getValue($data, $key, $default = null) {
    if (is_object($data)) {
        return isset($data->$key) ? $data->$key : $default;
    } elseif (is_array($data)) {
        return isset($data[$key]) ? $data[$key] : $default;
    }
    return $default;
}

View::startSection('content');
?>

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
            <a href="/autorizaciones" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Información de la Requisición -->
        <div class="col-md-8">
            <!-- Datos del Proveedor -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-building me-2"></i>
                        Información del Proveedor
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Nombre/Razón Social:</strong></p>
                            <p><?php echo View::e(getValue($orden, 'nombre_razon_social')); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>NIT:</strong></p>
                            <p><?php echo View::e(getValue($orden, 'nit')); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Dirección:</strong></p>
                            <p><?php echo View::e(getValue($orden, 'direccion')); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Teléfono:</strong></p>
                            <p><?php echo View::e(getValue($orden, 'telefono')); ?></p>
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
                                    <td class="text-end"><?php echo View::money($item['precio_unitario']); ?></td>
                                    <td class="text-end">
                                        <strong><?php echo View::money($item['cantidad'] * $item['precio_unitario']); ?></strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-primary">
                                    <td colspan="4" class="text-end"><h5 class="mb-0">TOTAL:</h5></td>
                                    <td class="text-end">
                                        <h4 class="mb-0 text-primary">
                                            <?php echo View::money(getValue($orden, 'monto_total')); ?>
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
                                        <button class="btn btn-sm btn-success me-1" 
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
                    <?php if (!empty($distribucion)): ?>
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
                                <?php foreach ($distribucion as $dist): ?>
                                <tr id="centro-<?php echo $dist['id']; ?>">
                                    <td>
                                        <strong><?php echo View::e($dist['centro_nombre'] ?? 'N/A'); ?></strong>
                                        <?php 
                                            if (isset($dist['metadata'])) {
                                                $metadata = is_string($dist['metadata']) ? json_decode($dist['metadata'], true) : $dist['metadata'];
                                                if ($metadata['es_respaldo'] ?? false):
                                        ?>
                                            <br><span class="badge bg-secondary text-white">
                                                <i class="fas fa-user-shield me-1"></i>Autorizador de Respaldo
                                            </span>
                                        <?php 
                                                endif;
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo View::e($dist['cuenta_nombre'] ?? 'N/A'); ?></td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo View::e($dist['autorizador_email'] ?? 'N/A'); ?>
                                        </small>
                                    </td>
                                    <td class="text-end"><?php echo number_format($dist['porcentaje'], 2); ?>%</td>
                                    <td class="text-end">
                                        <strong class="text-primary">
                                            <?php echo View::money($dist['monto_distribuido'] ?? ($dist['monto'] ?? 0)); ?>
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        <?php $estadoCentro = $dist['estado'] ?? 'pendiente'; ?>
                                        <?php if ($estadoCentro === 'pendiente'): ?>
                                        <button class="btn btn-sm btn-success me-1" 
                                                onclick="autorizarCentro(<?php echo $dist['id']; ?>)">
                                            <i class="fas fa-check me-1"></i>Autorizar
                                        </button>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="rechazarCentro(<?php echo $dist['id']; ?>)">
                                            <i class="fas fa-times me-1"></i>Rechazar
                                        </button>
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
                        No hay centros de costo pendientes de autorización
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
                    <span class="badge bg-warning text-dark fs-5 p-3">
                        <i class="fas fa-hourglass-half me-2"></i>
                        Pendiente de Autorización
                    </span>
                </div>
            </div>

            <!-- Información Adicional -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info me-2"></i>
                        Datos Adicionales
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Forma de Pago:</strong></p>
                    <p><?php echo View::e(getValue($orden, 'forma_pago')); ?></p>
                    <hr>
                    <p class="mb-2"><strong>Fecha de Creación:</strong></p>
                    <p><?php echo View::formatDate(getValue($orden, 'fecha')); ?></p>
                    <hr>
                    <p class="mb-2"><strong>Creado por:</strong></p>
                    <p><?php echo View::e(getValue($orden, 'usuario_nombre', 'N/A')); ?></p>
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
<script>
function autorizarCentro(centroId) {
    if (!confirm('¿Estás seguro de autorizar este centro de costo?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('comentario', '');
    
    fetch('/autorizaciones/centro/' + centroId + '/autorizar', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Centro de costo autorizado exitosamente');
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

function rechazarCentro(centroId) {
    document.getElementById('centro_rechazar_id').value = centroId;
    new bootstrap.Modal(document.getElementById('modalRechazar')).show();
}

document.getElementById('formRechazar').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const centroId = document.getElementById('centro_rechazar_id').value;
    const formData = new FormData(this);
    
    fetch('/autorizaciones/centro/' + centroId + '/rechazar', {
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
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('comentario', '');
    
    fetch('/autorizaciones/especial/' + authId + '/autorizar', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Autorización especial de ' + tipo + ' aprobada exitosamente');
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

function rechazarEspecial(authId, tipo) {
    const motivo = prompt('Ingresa el motivo del rechazo para la autorización especial de ' + tipo + ':');
    if (!motivo) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    formData.append('motivo', motivo);
    
    fetch('/autorizaciones/especial/' + authId + '/rechazar', {
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
</script>
<?php View::endSection(); ?>
