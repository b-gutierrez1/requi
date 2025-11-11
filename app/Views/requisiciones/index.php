<?php
use App\Helpers\View;

View::startSection('content');
?>


<div class="container py-4" style="max-width: 1200px;">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">
                <i class="fas fa-file-alt me-2"></i>
                Mis Requisiciones
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item active">Requisiciones</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-4 text-end">
            <a href="/requisiciones/crear" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                Nueva Requisición
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="/requisiciones" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos</option>
                        <option value="pendiente_revision" <?php echo ($filtros['estado'] ?? '') === 'pendiente_revision' ? 'selected' : ''; ?>>
                            En Revisión
                        </option>
                        <option value="pendiente_autorizacion" <?php echo ($filtros['estado'] ?? '') === 'pendiente_autorizacion' ? 'selected' : ''; ?>>
                            En Autorización
                        </option>
                        <option value="autorizado" <?php echo ($filtros['estado'] ?? '') === 'autorizado' ? 'selected' : ''; ?>>
                            Autorizada
                        </option>
                        <option value="rechazado" <?php echo ($filtros['estado'] ?? '') === 'rechazado' ? 'selected' : ''; ?>>
                            Rechazada
                        </option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" 
                           value="<?php echo View::e($filtros['fecha_desde'] ?? ''); ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" 
                           value="<?php echo View::e($filtros['fecha_hasta'] ?? ''); ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <div class="input-group">
                        <input type="text" name="busqueda" class="form-control" 
                               placeholder="Proveedor, justificación..."
                               value="<?php echo View::e($filtros['busqueda'] ?? ''); ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>
                        Filtrar
                    </button>
                    <a href="/requisiciones" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Requisiciones -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Listado de Requisiciones
                <span class="badge bg-secondary ms-2"><?php echo count($requisiciones); ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($requisiciones)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox fs-1 text-muted mb-3"></i>
                <h5 class="text-muted">No se encontraron requisiciones</h5>
                <p class="text-muted mb-4">
                    <?php if (!empty($filtros['busqueda']) || !empty($filtros['estado'])): ?>
                        Intenta cambiar los filtros de búsqueda
                    <?php else: ?>
                        Comienza creando tu primera requisición
                    <?php endif; ?>
                </p>
                <a href="/requisiciones/crear" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Nueva Requisición
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 80px;">ID</th>
                            <th>Proveedor</th>
                            <th>Creado por</th>
                            <th>Justificación</th>
                            <th class="text-center">Fecha</th>
                            <th class="text-end">Monto</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requisiciones as $req): ?>
                        <tr>
                            <td class="text-center">
                                <strong>#<?php echo $req->id; ?></strong>
                            </td>
                            <td>
                                <div class="fw-bold"><?php echo View::e($req->nombre_razon_social); ?></div>
                                <?php if (!empty($req->referencia)): ?>
                                <small class="text-muted">Ref: <?php echo View::e($req->referencia); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?php echo View::e($req->usuario_nombre ?? 'N/A'); ?></div>
                                <?php if (!empty($req->usuario_email)): ?>
                                <small class="text-muted"><?php echo View::e($req->usuario_email); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 300px;">
                                    <?php echo View::e($req->justificacion ?? ''); ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <small><?php echo View::formatDate($req->fecha); ?></small>
                            </td>
                            <td class="text-end">
                                <strong><?php echo View::money($req->monto_total); ?></strong>
                            </td>
                            <td class="text-center">
                                <?php
                                $flujo = $req->autorizacionFlujo();
                                if ($flujo):
                                    $badgeClass = match($flujo->estado) {
                                        'pendiente_revision' => 'bg-warning',
                                        'rechazado_revision' => 'bg-danger',
                                        'pendiente_autorizacion' => 'bg-info',
                                        'rechazado_autorizacion' => 'bg-danger',
                                        'autorizado' => 'bg-success',
                                        'rechazado' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    $estadoText = match($flujo->estado) {
                                        'pendiente_revision' => 'En Revisión',
                                        'rechazado_revision' => 'Rechazada en Revisión',
                                        'pendiente_autorizacion' => 'En Autorización',
                                        'rechazado_autorizacion' => 'Rechazada en Autorización',
                                        'autorizado' => 'Autorizada',
                                        'rechazado' => 'Rechazada',
                                        default => 'Pendiente'
                                    };
                                    $icon = match($flujo->estado) {
                                        'pendiente_revision' => 'clock',
                                        'rechazado_revision' => 'times-circle',
                                        'pendiente_autorizacion' => 'hourglass-half',
                                        'rechazado_autorizacion' => 'times-circle',
                                        'autorizado' => 'check-circle',
                                        'rechazado' => 'times-circle',
                                        default => 'question-circle'
                                    };
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>">
                                    <i class="fas fa-<?php echo $icon; ?> me-1"></i>
                                    <?php echo $estadoText; ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <!-- DEBUG: ID = <?php echo $req->id ?? 'NULL'; ?> -->
                                    <a href="/requisiciones/<?php echo $req->id; ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Ver detalle"
                                       onclick="console.log('Navegando a ID:', <?php echo $req->id; ?>); return true;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php 
                                    // Solo permitir editar si está en estado pendiente_revision o rechazado_revision
                                    if ($flujo && in_array($flujo->estado, ['pendiente_revision', 'rechazado_revision'])): 
                                    ?>
                                    <a href="/requisiciones/<?php echo $req->id; ?>/editar" 
                                       class="btn btn-sm btn-outline-secondary" 
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <a href="/requisiciones/<?php echo $req->id; ?>/imprimir" 
                                       class="btn btn-sm btn-outline-info" 
                                       target="_blank"
                                       title="Imprimir">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    
                                    <?php 
                                    // Solo permitir eliminar si está en estado pendiente_revision
                                    if ($flujo && $flujo->estado === 'pendiente_revision'): 
                                    ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger" 
                                            onclick="eliminarRequisicion(<?php echo $req->id; ?>)"
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
View::endSection();

View::startSection('scripts');
?>
<script>
// Los efectos se aplican automáticamente por el CSS y JS global

function eliminarRequisicion(id) {
    if (!confirm('¿Estás seguro de eliminar esta requisición? Esta acción no se puede deshacer.')) {
        return;
    }
    
    fetch(`/requisiciones/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Requisición eliminada exitosamente');
            window.location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al eliminar la requisición');
    });
}
</script>
<?php
View::endSection();
?>
