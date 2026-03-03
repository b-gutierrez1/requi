<?php
use App\Helpers\View;
use App\Middlewares\CsrfMiddleware;

View::startSection('content');
?>

<div class="container py-4" style="max-width: 1200px;">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">
                <i class="fas fa-eye me-2"></i>
                Requisiciones Pendientes de Revisión
            </h1>
            <p class="text-muted mb-0">
                Como revisor, puedes aprobar o rechazar requisiciones antes de que pasen a autorización
                <span class="badge bg-warning text-dark ms-2">
                    <?php echo count($requisiciones); ?> pendiente<?php echo count($requisiciones) != 1 ? 's' : ''; ?>
                </span>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= url('/autorizaciones') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    <?php if (!empty($requisiciones)): ?>
        <?php foreach ($requisiciones as $req): ?>
        <div class="card mb-4 border-start border-warning border-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-file-invoice me-2"></i>
                            Requisición #<?php echo $req['requisicion_id'] ?? $req['orden_id'] ?? $req['id']; ?>
                        </h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>Proveedor:</strong><br>
                                    <?php echo View::e($req['nombre_razon_social']); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>Monto Total:</strong><br>
                                    <span class="h5 text-primary"><?php echo View::money($req['monto_total'], $req['moneda'] ?? 'GTQ'); ?></span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>Solicitante:</strong><br>
                                    <?php echo View::e($req['usuario_nombre'] ?? 'Usuario desconocido'); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <strong>Fecha:</strong><br>
                                    <?php echo View::formatDate($req['fecha_orden']); ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if (!empty($req['justificacion'])): ?>
                        <div class="mb-3">
                            <strong>Justificación:</strong><br>
                            <div class="text-muted">
                                <?php echo nl2br(View::e($req['justificacion'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4 text-end">
                        <div class="mb-3">
                            <small class="text-muted">Estado actual:</small><br>
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-clock me-1"></i>
                                Pendiente de Revisión
                            </span>
                        </div>
                        
                        <div class="btn-group-vertical d-grid gap-2">
                            <a href="<?= url('/requisiciones/' . $req['requisicion_id']) ?>" 
                               class="btn btn-outline-primary">
                                <i class="fas fa-eye me-2"></i>Ver Detalle
                            </a>
                            
                            <button type="button" 
                                    class="btn btn-success" 
                                    onclick="aprobarRevision(<?php echo $req['id']; ?>)">
                                <i class="fas fa-check me-2"></i>Aprobar
                            </button>
                            
                            <button type="button" 
                                    class="btn btn-danger" 
                                    onclick="rechazarRevision(<?php echo $req['id']; ?>)">
                                <i class="fas fa-times me-2"></i>Rechazar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-double fs-1 text-success mb-3"></i>
                <h4 class="text-muted">¡Excelente!</h4>
                <p class="text-muted">No hay requisiciones pendientes de revisión en este momento.</p>
                <a href="<?= url('/autorizaciones') ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Volver a Autorizaciones
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
View::endSection();

// Sección de modales (se renderiza fuera del contenedor principal)
View::startSection('modals');
?>
<!-- Modal Aprobar -->
<div class="modal fade" id="modalAprobar" tabindex="-1" aria-labelledby="modalAprobarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #10b981, #059669); border: none;">
                <h5 class="modal-title text-white" id="modalAprobarLabel">
                    <i class="fas fa-check-circle me-2"></i>
                    Aprobar Revisión
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formAprobar">
                <?php echo \App\Middlewares\CsrfMiddleware::field(); ?>
                <input type="hidden" id="revisar_aprobar_id" name="flujo_id">
                <div class="modal-body" style="padding: 1.5rem;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Comentario (opcional)</label>
                        <textarea class="form-control" name="comentario" rows="3" 
                                  placeholder="Agregar comentarios sobre la aprobación..."></textarea>
                    </div>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Al aprobar, la requisición pasará a la fase de autorización por centros de costo.
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e5e7eb; padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i>Aprobar Revisión
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Rechazar -->
<div class="modal fade" id="modalRechazar" tabindex="-1" aria-labelledby="modalRechazarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
            <div class="modal-header" style="background: linear-gradient(135deg, #ef4444, #dc2626); border: none;">
                <h5 class="modal-title text-white" id="modalRechazarLabel">
                    <i class="fas fa-times-circle me-2"></i>
                    Rechazar Revisión
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formRechazar">
                <?php echo \App\Middlewares\CsrfMiddleware::field(); ?>
                <input type="hidden" id="revisar_rechazar_id" name="flujo_id">
                <div class="modal-body" style="padding: 1.5rem;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo del Rechazo <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="motivo" rows="4" required
                                  placeholder="Explica por qué estás rechazando esta requisición..."></textarea>
                        <small class="text-muted">El motivo será enviado al solicitante para que pueda corregir la requisición.</small>
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
function aprobarRevision(flujoId) {
    document.getElementById('revisar_aprobar_id').value = flujoId;
    const modalEl = document.getElementById('modalAprobar');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function rechazarRevision(flujoId) {
    document.getElementById('revisar_rechazar_id').value = flujoId;
    const modalEl = document.getElementById('modalRechazar');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function cerrarModal(modalId) {
    const modalEl = document.getElementById(modalId);
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) {
        modal.hide();
    }
}

document.getElementById('formAprobar').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const flujoId = document.getElementById('revisar_aprobar_id').value;
    const formData = new FormData(this);
    
    fetch('<?= url('/autorizaciones/') ?>' + flujoId + '/aprobar-revision', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                // Cerrar el modal primero
                cerrarModal('modalAprobar');
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message || 'Requisición aprobada exitosamente',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    alert(data.message || 'Requisición aprobada exitosamente');
                    location.reload();
                }
            } else {
                // Cerrar modal en caso de error también
                cerrarModal('modalAprobar');
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || 'Error al aprobar la requisición'
                    });
                } else {
                    alert(data.error || 'Error al aprobar la requisición');
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
    
    const flujoId = document.getElementById('revisar_rechazar_id').value;
    const formData = new FormData(this);
    
    fetch('<?= url('/autorizaciones/') ?>' + flujoId + '/rechazar-revision', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                // Cerrar el modal primero
                cerrarModal('modalRechazar');
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Rechazada',
                        text: data.message || 'Requisición rechazada',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    alert(data.message || 'Requisición rechazada');
                    location.reload();
                }
            } else {
                // Cerrar modal en caso de error también
                cerrarModal('modalRechazar');
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || 'Error al rechazar la requisición'
                    });
                } else {
                    alert(data.error || 'Error al rechazar la requisición');
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