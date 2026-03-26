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

$orden = $requisicion['orden'] ?? null;
$autorizacion = $autorizacion_especial ?? null;
$tipoAuth = $tipo_autorizacion ?? 'forma_pago';
$itemsLista = $items ?? [];
$moneda = is_object($orden) ? ($orden->moneda ?? 'GTQ') : ($orden['moneda'] ?? 'GTQ');

if (!$orden || !$autorizacion) {
    echo '<div class="alert alert-danger">Error: Datos de autorización no disponibles</div>';
    return;
}

$estadoActual = getValue($autorizacion, 'estado', 'pendiente');
$tipoTexto = $tipoAuth === 'forma_pago' ? 'Forma de Pago' : 'Cuenta Contable';
$flujoEstado = is_object($flujo) ? ($flujo->estado ?? 'pendiente_revision') : ($flujo['estado'] ?? 'pendiente_revision');

View::startSection('content');
?>

<div class="container py-4" style="max-width: 1200px;">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">
                <?php if ($tipoAuth === 'forma_pago'): ?>
                    <i class="fas fa-credit-card me-2 text-success"></i>
                    Autorizar Forma de Pago
                <?php else: ?>
                    <i class="fas fa-calculator me-2 text-info"></i>
                    Autorizar Cuenta Contable
                <?php endif; ?>
            </h1>
            <p class="text-muted mb-0">
                Requisición #<?php echo View::e(getValue($orden, 'id')); ?>
                <span class="badge <?php echo $tipoAuth === 'forma_pago' ? 'bg-success' : 'bg-info'; ?> ms-2">
                    Autorización Especial - <?php echo $tipoTexto; ?>
                </span>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= url('/requisiciones/' . View::e(getValue($orden, 'id'))) ?>" class="btn btn-primary me-2">
                <i class="fas fa-eye me-2"></i>Ver detalle completo
            </a>
            <a href="<?= url('/autorizaciones') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    <?php if ($estadoActual === 'pendiente'): ?>
    <!-- Alert de autorización especial -->
    <div class="alert <?php echo $tipoAuth === 'forma_pago' ? 'alert-success' : 'alert-info'; ?> mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h5 class="alert-heading mb-1">
                    <?php if ($tipoAuth === 'forma_pago'): ?>
                        <i class="fas fa-credit-card me-2"></i>Autorización Especial Requerida - Forma de Pago
                    <?php else: ?>
                        <i class="fas fa-calculator me-2"></i>Autorización Especial Requerida - Cuenta Contable
                    <?php endif; ?>
                </h5>
                <p class="mb-0">
                    <?php if ($tipoAuth === 'forma_pago'): ?>
                        Esta requisición utiliza un método de pago que requiere autorización especial.
                    <?php else: ?>
                        Esta requisición utiliza una cuenta contable que requiere autorización especial.
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <button type="button" class="btn btn-<?php echo $tipoAuth === 'forma_pago' ? 'success' : 'info'; ?> me-2" 
                        onclick="aprobarAutorizacion()">
                    <i class="fas fa-check me-1"></i>Aprobar
                </button>
                <button type="button" class="btn btn-outline-danger" 
                        onclick="rechazarAutorizacion()">
                    <i class="fas fa-times me-1"></i>Rechazar
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Información Principal -->
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
                                <?php elseif (in_array($flujoEstado, ['autorizado', 'autorizada'])): ?>
                                    <i class="fas fa-check-circle me-2 text-success"></i>
                                    <span class="text-success">Completamente Autorizada</span>
                                <?php elseif (in_array($flujoEstado, ['rechazado', 'rechazada', 'rechazado_autorizacion', 'rechazado_revision'])): ?>
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

            <!-- Items de la Requisición -->
            <?php if (!empty($itemsLista)): ?>
            <div class="card mb-4">
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
                                    <th width="40">#</th>
                                    <th>Descripción</th>
                                    <th width="90">Cantidad</th>
                                    <th width="140" class="text-end">Precio Unit.</th>
                                    <th width="140" class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($itemsLista as $i => $item): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo View::e($item['descripcion']); ?></td>
                                    <td><?php echo number_format($item['cantidad'], 2); ?></td>
                                    <td class="text-end"><?php echo View::money($item['precio_unitario'], $moneda); ?></td>
                                    <td class="text-end"><strong><?php echo View::money($item['cantidad'] * $item['precio_unitario'], $moneda); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-primary">
                                    <td colspan="4" class="text-end"><h6 class="mb-0">TOTAL:</h6></td>
                                    <td class="text-end">
                                        <h5 class="mb-0 text-primary"><?php echo View::money(getValue($orden, 'monto_total', 0), $moneda); ?></h5>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Detalles de la Autorización Especial -->
            <div class="card mb-4">
                <div class="card-header <?php echo $tipoAuth === 'forma_pago' ? 'bg-success' : 'bg-info'; ?> text-white">
                    <h5 class="mb-0">
                        <?php if ($tipoAuth === 'forma_pago'): ?>
                            <i class="fas fa-credit-card me-2"></i>Detalles - Forma de Pago
                        <?php else: ?>
                            <i class="fas fa-calculator me-2"></i>Detalles - Cuenta Contable  
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($tipoAuth === 'forma_pago'): ?>
                        <p class="mb-2">
                            <strong>Forma de Pago:</strong><br>
                            <span class="text-muted"><?php echo View::e(getValue($orden, 'forma_pago')); ?></span>
                        </p>
                        <p class="mb-0">
                            <strong>Monto Total:</strong><br>
                            <span class="h5 text-success"><?php echo View::money(getValue($orden, 'monto_total', 0)); ?></span>
                        </p>
                    <?php else: ?>
                        <?php 
                        // Para cuenta contable, buscar los datos específicos
                        $metadata = getValue($autorizacion, 'metadata', '{}');
                        if (is_string($metadata)) {
                            $metadata = json_decode($metadata, true) ?: [];
                        }
                        ?>
                        <p class="mb-2">
                            <strong>Cuenta Contable:</strong><br>
                            <span class="text-muted"><?php echo View::e($metadata['cuenta_nombre'] ?? 'No especificada'); ?></span>
                        </p>
                        <p class="mb-0">
                            <strong>Monto Total:</strong><br>
                            <span class="h5 text-info"><?php echo View::money(getValue($orden, 'monto_total', 0)); ?></span>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Justificación si existe -->
            <?php if (!empty(getValue($orden, 'justificacion'))): ?>
            <div class="alert alert-info">
                <h5><i class="fas fa-comment me-2"></i>Justificación:</h5>
                <p class="mb-0"><?php echo nl2br(View::e(getValue($orden, 'justificacion'))); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Panel Lateral -->
        <div class="col-md-4">
            <!-- Estado Actual -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Estado Actual
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <?php if ($estadoActual === 'pendiente'): ?>
                            <span class="badge bg-warning text-dark fs-6 p-2">
                                <i class="fas fa-clock me-1"></i>
                                Pendiente de Autorización
                            </span>
                        <?php elseif ($estadoActual === 'aprobada'): ?>
                            <span class="badge bg-success fs-6 p-2">
                                <i class="fas fa-check me-1"></i>
                                Autorizada
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger fs-6 p-2">
                                <i class="fas fa-times me-1"></i>
                                Rechazada
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Datos Adicionales -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info me-2"></i>
                        Datos Adicionales
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Fecha de Creación:</strong><br>
                        <span class="text-muted"><?php echo View::formatDate(getValue($orden, 'fecha_solicitud')); ?></span>
                    </p>
                    <p class="mb-0">
                        <strong>Creado por:</strong><br>
                        <span class="text-muted"><?php echo View::e($autorizacion['usuario_nombre'] ?? 'N/A'); ?></span>
                    </p>
                </div>
            </div>

            <!-- Importante -->
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Importante
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Revisa cuidadosamente todos los datos</li>
                        <li>Verifica que los montos sean correctos</li>
                        <?php if ($tipoAuth === 'forma_pago'): ?>
                            <li>Confirma que la forma de pago es apropiada</li>
                        <?php else: ?>
                            <li>Confirma que la cuenta contable es correcta</li>
                        <?php endif; ?>
                        <li>Al rechazar debes indicar el motivo</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
View::endSection();

View::startSection('modals');
?>
<!-- Modal Aprobar -->
<div class="modal fade" id="modalAprobar" tabindex="-1" aria-labelledby="modalAprobarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, <?php echo $tipoAuth === 'forma_pago' ? '#10b981, #059669' : '#0ea5e9, #0284c7'; ?>); border: none; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title text-white" id="modalAprobarLabel">
                    <?php if ($tipoAuth === 'forma_pago'): ?>
                        <i class="fas fa-credit-card me-2"></i>Aprobar Forma de Pago
                    <?php else: ?>
                        <i class="fas fa-calculator me-2"></i>Aprobar Cuenta Contable
                    <?php endif; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formAprobar">
                <?php echo CsrfMiddleware::field(); ?>
                <input type="hidden" name="autorizacion_id" value="<?php echo $autorizacion['id']; ?>">
                <div class="modal-body" style="padding: 1.5rem;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Comentario (opcional)</label>
                        <textarea class="form-control" name="comentario" rows="3"
                                  placeholder="Agregar comentarios sobre la aprobación..."></textarea>
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Al aprobar, esta autorización especial será marcada como aprobada y el flujo continuará.
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e5e7eb; padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-<?php echo $tipoAuth === 'forma_pago' ? 'success' : 'info'; ?>">
                        <i class="fas fa-check me-1"></i>Aprobar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Rechazar -->
<div class="modal fade" id="modalRechazar" tabindex="-1" aria-labelledby="modalRechazarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #ef4444, #dc2626); border: none; border-radius: 16px 16px 0 0;">
                <h5 class="modal-title text-white" id="modalRechazarLabel">
                    <i class="fas fa-times-circle me-2"></i>
                    Rechazar <?php echo $tipoTexto; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formRechazar">
                <?php echo CsrfMiddleware::field(); ?>
                <input type="hidden" name="autorizacion_id" value="<?php echo $autorizacion['id']; ?>">
                <div class="modal-body" style="padding: 1.5rem;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo del Rechazo <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="motivo" rows="4" required
                                  placeholder="Explica por qué estás rechazando esta autorización..."></textarea>
                        <small class="text-muted">El motivo será registrado en el historial.</small>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e5e7eb; padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-left me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i>Rechazar
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
function aprobarAutorizacion() {
    document.activeElement.blur();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAprobar')).show();
}

function rechazarAutorizacion() {
    document.activeElement.blur();
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRechazar')).show();
}

document.getElementById('formAprobar').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const autorizacionId = <?php echo $autorizacion['id']; ?>;
    const tipoAuth = '<?php echo $tipoAuth; ?>';
    
    const url = tipoAuth === 'forma_pago' 
        ? '<?= url('/autorizaciones/pago/') ?>' + autorizacionId + '/aprobar'
        : '<?= url('/autorizaciones/cuenta/') ?>' + autorizacionId + '/aprobar';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message || 'Autorización aprobada exitosamente',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '<?= url('/autorizaciones') ?>';
                    });
                } else {
                    alert(data.message || 'Autorización aprobada exitosamente');
                    window.location.href = '<?= url('/autorizaciones') ?>';
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || 'Error al aprobar la autorización'
                    });
                } else {
                    alert(data.error || 'Error al aprobar la autorización');
                }
            }
        } catch (e) {
            console.error('Error parsing JSON:', e);
            console.error('Response text:', text);
            alert('Error al procesar la respuesta del servidor');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
});

document.getElementById('formRechazar').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const autorizacionId = <?php echo $autorizacion['id']; ?>;
    const tipoAuth = '<?php echo $tipoAuth; ?>';
    
    const url = tipoAuth === 'forma_pago' 
        ? '<?= url('/autorizaciones/pago/') ?>' + autorizacionId + '/rechazar'
        : '<?= url('/autorizaciones/cuenta/') ?>' + autorizacionId + '/rechazar';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Rechazada',
                        text: data.message || 'Autorización rechazada',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '<?= url('/autorizaciones') ?>';
                    });
                } else {
                    alert(data.message || 'Autorización rechazada');
                    window.location.href = '<?= url('/autorizaciones') ?>';
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || 'Error al rechazar la autorización'
                    });
                } else {
                    alert(data.error || 'Error al rechazar la autorización');
                }
            }
        } catch (e) {
            console.error('Error parsing JSON:', e);
            console.error('Response text:', text);
            alert('Error al procesar la respuesta del servidor');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
});
</script>
<?php View::endSection(); ?>