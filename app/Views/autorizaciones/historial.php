<?php
use App\Helpers\View;

View::startSection('content');
?>

<div class="container py-4" style="max-width: 1200px;">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">
                <i class="fas fa-history me-2"></i>
                Historial de Autorizaciones
            </h1>
            <p class="text-muted mb-0">
                Registro de todas las autorizaciones que has procesado
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="/autorizaciones" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="/autorizaciones/historial" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tipo de Acción</label>
                    <select name="accion" class="form-select">
                        <option value="">Todas</option>
                        <option value="revision_aprobada" <?php echo ($_GET['accion'] ?? '') === 'revision_aprobada' ? 'selected' : ''; ?>>
                            Revisiones Aprobadas
                        </option>
                        <option value="revision_rechazada" <?php echo ($_GET['accion'] ?? '') === 'revision_rechazada' ? 'selected' : ''; ?>>
                            Revisiones Rechazadas
                        </option>
                        <option value="centro_autorizado" <?php echo ($_GET['accion'] ?? '') === 'centro_autorizado' ? 'selected' : ''; ?>>
                            Centros Autorizados
                        </option>
                        <option value="centro_rechazado" <?php echo ($_GET['accion'] ?? '') === 'centro_rechazado' ? 'selected' : ''; ?>>
                            Centros Rechazados
                        </option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" 
                           value="<?php echo View::e($_GET['fecha_desde'] ?? ''); ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" 
                           value="<?php echo View::e($_GET['fecha_hasta'] ?? ''); ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <div class="input-group">
                        <input type="text" name="busqueda" class="form-control" 
                               placeholder="Requisición, proveedor..."
                               value="<?php echo View::e($_GET['busqueda'] ?? ''); ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Filtrar
                    </button>
                    <a href="/autorizaciones/historial" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Historial -->
    <?php if (!empty($autorizaciones)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Historial de Acciones
                    <span class="badge bg-secondary ms-2"><?php echo count($autorizaciones); ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Requisición</th>
                                <th>Proveedor</th>
                                <th>Acción</th>
                                <th>Comentario/Motivo</th>
                                <th>Monto</th>
                                <th class="text-center">Estado Actual</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($autorizaciones as $auth): ?>
                            <tr>
                                <td>
                                    <small><?php echo View::formatDate($auth['fecha_autorizacion']); ?></small>
                                </td>
                                <td>
                                    <strong>#<?php echo $auth['orden_id']; ?></strong>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;">
                                        <?php echo View::e($auth['nombre_razon_social']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $accionTexto = match($auth['tipo_accion']) {
                                        'revision_aprobada' => 'Revisión Aprobada',
                                        'revision_rechazada' => 'Revisión Rechazada',
                                        'centro_autorizado' => 'Centro Autorizado',
                                        'centro_rechazado' => 'Centro Rechazado',
                                        default => 'Acción Desconocida'
                                    };
                                    
                                    $badgeClass = match($auth['tipo_accion']) {
                                        'revision_aprobada', 'centro_autorizado' => 'bg-success',
                                        'revision_rechazada', 'centro_rechazado' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo $accionTexto; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 250px;">
                                        <?php echo View::e($auth['comentario'] ?? $auth['motivo'] ?? '-'); ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo View::money($auth['monto_total']); ?></strong>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $estadoActual = $auth['estado_actual'] ?? 'pendiente';
                                    $estadoBadge = match($estadoActual) {
                                        'pendiente_revision' => 'bg-warning',
                                        'rechazado_revision' => 'bg-danger',
                                        'pendiente_autorizacion' => 'bg-info',
                                        'rechazado_autorizacion' => 'bg-danger',
                                        'autorizado' => 'bg-success',
                                        'rechazado' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    $estadoTexto = match($estadoActual) {
                                        'pendiente_revision' => 'En Revisión',
                                        'rechazado_revision' => 'Rechazada en Revisión',
                                        'pendiente_autorizacion' => 'En Autorización',
                                        'rechazado_autorizacion' => 'Rechazada en Autorización',
                                        'autorizado' => 'Autorizada',
                                        'rechazado' => 'Rechazada',
                                        default => 'Pendiente'
                                    };
                                    ?>
                                    <span class="badge <?php echo $estadoBadge; ?>">
                                        <?php echo $estadoTexto; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="/requisiciones/<?php echo $auth['orden_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Ver requisición">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas del historial -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fs-2 text-success mb-2"></i>
                        <h5><?php echo array_count_values(array_column($autorizaciones, 'tipo_accion'))['revision_aprobada'] ?? 0; ?></h5>
                        <small class="text-muted">Revisiones Aprobadas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-times-circle fs-2 text-danger mb-2"></i>
                        <h5><?php echo array_count_values(array_column($autorizaciones, 'tipo_accion'))['revision_rechazada'] ?? 0; ?></h5>
                        <small class="text-muted">Revisiones Rechazadas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-building fs-2 text-info mb-2"></i>
                        <h5><?php echo array_count_values(array_column($autorizaciones, 'tipo_accion'))['centro_autorizado'] ?? 0; ?></h5>
                        <small class="text-muted">Centros Autorizados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-ban fs-2 text-warning mb-2"></i>
                        <h5><?php echo array_count_values(array_column($autorizaciones, 'tipo_accion'))['centro_rechazado'] ?? 0; ?></h5>
                        <small class="text-muted">Centros Rechazados</small>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-history fs-1 text-muted mb-3"></i>
                <h4 class="text-muted">No hay historial disponible</h4>
                <p class="text-muted">
                    <?php if (!empty($_GET['accion']) || !empty($_GET['busqueda'])): ?>
                        No se encontraron registros con los filtros aplicados.<br>
                        <a href="/autorizaciones/historial" class="btn btn-outline-primary mt-2">
                            <i class="fas fa-times me-2"></i>Limpiar Filtros
                        </a>
                    <?php else: ?>
                        Aún no has procesado ninguna autorización.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php View::endSection(); ?>