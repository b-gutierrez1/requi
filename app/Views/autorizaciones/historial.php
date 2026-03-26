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
                <?php if ($es_admin ?? false): ?>
                    Registro de todas las autorizaciones procesadas por cada autorizador
                <?php else: ?>
                    Registro de todas las autorizaciones que has procesado
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= url('/autorizaciones') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= url('/autorizaciones/historial') ?>" class="row g-3">
                <?php if ($es_admin ?? false): ?>
                <div class="col-md-3">
                    <label class="form-label">Autorizador</label>
                    <input type="text" name="autorizador" class="form-control"
                           placeholder="Email del autorizador"
                           value="<?= View::e($filtros['autorizador'] ?? '') ?>">
                </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label class="form-label">Tipo de Acción</label>
                    <select name="accion" class="form-select">
                        <option value="">Todas</option>
                        <option value="revision_aprobada"  <?= ($filtros['accion'] ?? '') === 'revision_aprobada'  ? 'selected' : '' ?>>Revisiones Aprobadas</option>
                        <option value="revision_rechazada" <?= ($filtros['accion'] ?? '') === 'revision_rechazada' ? 'selected' : '' ?>>Revisiones Rechazadas</option>
                        <option value="centro_autorizado"  <?= ($filtros['accion'] ?? '') === 'centro_autorizado'  ? 'selected' : '' ?>>Centros Autorizados</option>
                        <option value="centro_rechazado"   <?= ($filtros['accion'] ?? '') === 'centro_rechazado'   ? 'selected' : '' ?>>Centros Rechazados</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control"
                           value="<?= View::e($filtros['fecha_desde'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control"
                           value="<?= View::e($filtros['fecha_hasta'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="busqueda" class="form-control"
                           placeholder="Requisición, proveedor..."
                           value="<?= View::e($filtros['busqueda'] ?? '') ?>">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-2"></i>Filtrar
                    </button>
                    <a href="<?= url('/autorizaciones/historial') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($es_admin ?? false): ?>
    <!-- ===================== VISTA ADMIN: POR AUTORIZADOR ===================== -->
    <?php $historial = $historialPorAutorizador ?? []; ?>

    <?php if (!empty($historial)): ?>

        <!-- Resumen general -->
        <?php
        $totalGlobal    = array_sum(array_column($historial, 'total'));
        $aprobadasGlobal= array_sum(array_column($historial, 'aprobadas'));
        $rechazadasGlobal = array_sum(array_column($historial, 'rechazadas'));
        ?>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center border-0 bg-light">
                    <div class="card-body py-3">
                        <h4 class="mb-0 text-primary"><?= count($historial) ?></h4>
                        <small class="text-muted">Autorizadores activos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center border-0 bg-light">
                    <div class="card-body py-3">
                        <h4 class="mb-0 text-success"><?= $aprobadasGlobal ?></h4>
                        <small class="text-muted">Total aprobadas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center border-0 bg-light">
                    <div class="card-body py-3">
                        <h4 class="mb-0 text-danger"><?= $rechazadasGlobal ?></h4>
                        <small class="text-muted">Total rechazadas</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acordeón por autorizador -->
        <div class="accordion" id="acordeonAutorizadores">
            <?php foreach ($historial as $i => $autorizador): ?>
            <div class="accordion-item border mb-3 rounded">
                <h2 class="accordion-header">
                    <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?> rounded"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#autorizador-<?= $i ?>">
                        <div class="d-flex align-items-center gap-3 w-100 me-3">
                            <div class="flex-grow-1">
                                <strong><?= View::e($autorizador['nombre']) ?></strong>
                                <br>
                                <small class="text-muted"><?= View::e($autorizador['email']) ?></small>
                            </div>
                            <div class="d-flex gap-2 flex-shrink-0">
                                <span class="badge bg-secondary"><?= $autorizador['total'] ?> total</span>
                                <span class="badge bg-success"><?= $autorizador['aprobadas'] ?> aprobadas</span>
                                <?php if ($autorizador['rechazadas'] > 0): ?>
                                <span class="badge bg-danger"><?= $autorizador['rechazadas'] ?> rechazadas</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </button>
                </h2>
                <div id="autorizador-<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>">
                    <div class="accordion-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Requisición</th>
                                        <th>Proveedor</th>
                                        <th>Acción</th>
                                        <th>Comentario</th>
                                        <th class="text-end">Monto</th>
                                        <th class="text-center">Estado Actual</th>
                                        <th class="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($autorizador['registros'] as $reg): ?>
                                    <?php
                                    $accionTexto = match($reg['tipo_accion']) {
                                        'revision_aprobada'  => 'Revisión Aprobada',
                                        'revision_rechazada' => 'Revisión Rechazada',
                                        'centro_autorizado'  => 'Centro Autorizado',
                                        'centro_rechazado'   => 'Centro Rechazado',
                                        default => 'Desconocida'
                                    };
                                    $badgeAccion = in_array($reg['tipo_accion'], ['revision_aprobada','centro_autorizado']) ? 'bg-success' : 'bg-danger';
                                    $estadoTexto = match($reg['estado_actual']) {
                                        'pendiente_revision'      => 'En Revisión',
                                        'rechazado_revision'      => 'Rechazada',
                                        'pendiente_autorizacion'  => 'En Autorización',
                                        'rechazado_autorizacion'  => 'Rechazada',
                                        'autorizado'              => 'Autorizada',
                                        'rechazado'               => 'Rechazada',
                                        default => 'Pendiente'
                                    };
                                    $badgeEstado = match($reg['estado_actual']) {
                                        'autorizado' => 'bg-success',
                                        'rechazado','rechazado_revision','rechazado_autorizacion' => 'bg-danger',
                                        'pendiente_revision','pendiente_autorizacion' => 'bg-warning text-dark',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <tr>
                                        <td><small><?= View::formatDate($reg['fecha_autorizacion']) ?></small></td>
                                        <td><strong>#<?= $reg['orden_id'] ?></strong></td>
                                        <td>
                                            <div class="text-truncate" style="max-width:180px;">
                                                <?= View::e($reg['nombre_razon_social']) ?>
                                            </div>
                                        </td>
                                        <td><span class="badge <?= $badgeAccion ?>"><?= $accionTexto ?></span></td>
                                        <td>
                                            <div class="text-truncate text-muted" style="max-width:180px;">
                                                <?= View::e($reg['comentario'] ?: $reg['motivo'] ?: '-') ?>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <strong><?= View::money($reg['monto_total'], 'GTQ') ?></strong>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $badgeEstado ?>"><?= $estadoTexto ?></span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= url('/requisiciones/' . $reg['orden_id']) ?>"
                                               class="btn btn-sm btn-outline-primary" title="Ver requisición">
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
            </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-history fs-1 text-muted mb-3"></i>
                <h4 class="text-muted">No hay historial disponible</h4>
                <p class="text-muted">
                    <?php if (!empty($filtros['accion']) || !empty($filtros['busqueda']) || !empty($filtros['autorizador'])): ?>
                        No se encontraron registros con los filtros aplicados.<br>
                        <a href="<?= url('/autorizaciones/historial') ?>" class="btn btn-outline-primary mt-2">
                            <i class="fas fa-times me-2"></i>Limpiar Filtros
                        </a>
                    <?php else: ?>
                        Aún no se han procesado autorizaciones.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- ===================== VISTA AUTORIZADOR: HISTORIAL PERSONAL ===================== -->
    <?php $autorizaciones = $autorizaciones ?? []; ?>

    <?php if (!empty($autorizaciones)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Historial de Acciones
                    <span class="badge bg-secondary ms-2"><?= count($autorizaciones) ?></span>
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
                                <th class="text-end">Monto</th>
                                <th class="text-center">Estado Actual</th>
                                <th class="text-center"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($autorizaciones as $auth): ?>
                            <?php
                            $accionTexto = match($auth['tipo_accion']) {
                                'revision_aprobada'  => 'Revisión Aprobada',
                                'revision_rechazada' => 'Revisión Rechazada',
                                'centro_autorizado'  => 'Centro Autorizado',
                                'centro_rechazado'   => 'Centro Rechazado',
                                default => 'Acción Desconocida'
                            };
                            $badgeAccion = in_array($auth['tipo_accion'], ['revision_aprobada','centro_autorizado']) ? 'bg-success' : 'bg-danger';
                            $estadoActual = $auth['estado_actual'] ?? 'pendiente';
                            $estadoTexto = match($estadoActual) {
                                'pendiente_revision'     => 'En Revisión',
                                'rechazado_revision'     => 'Rechazada en Revisión',
                                'pendiente_autorizacion' => 'En Autorización',
                                'rechazado_autorizacion' => 'Rechazada',
                                'autorizado'             => 'Autorizada',
                                'rechazado'              => 'Rechazada',
                                default => 'Pendiente'
                            };
                            $badgeEstado = match($estadoActual) {
                                'autorizado' => 'bg-success',
                                'rechazado','rechazado_revision','rechazado_autorizacion' => 'bg-danger',
                                'pendiente_revision','pendiente_autorizacion' => 'bg-warning text-dark',
                                default => 'bg-secondary'
                            };
                            ?>
                            <tr>
                                <td><small><?= View::formatDate($auth['fecha_autorizacion']) ?></small></td>
                                <td><strong>#<?= $auth['orden_id'] ?></strong></td>
                                <td>
                                    <div class="text-truncate" style="max-width:200px;">
                                        <?= View::e($auth['nombre_razon_social']) ?>
                                    </div>
                                </td>
                                <td><span class="badge <?= $badgeAccion ?>"><?= $accionTexto ?></span></td>
                                <td>
                                    <div class="text-truncate" style="max-width:220px;">
                                        <?= View::e($auth['comentario'] ?: $auth['motivo'] ?: '-') ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <strong><?= View::money($auth['monto_total'], $auth['moneda'] ?? 'GTQ') ?></strong>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $badgeEstado ?>"><?= $estadoTexto ?></span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= url('/requisiciones/' . $auth['orden_id']) ?>"
                                       class="btn btn-sm btn-outline-primary" title="Ver requisición">
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

        <!-- Estadísticas -->
        <?php
        $conteos = array_count_values(array_column($autorizaciones, 'tipo_accion'));
        ?>
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-check-circle fs-2 text-success mb-2"></i>
                        <h5><?= $conteos['revision_aprobada'] ?? 0 ?></h5>
                        <small class="text-muted">Revisiones Aprobadas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-times-circle fs-2 text-danger mb-2"></i>
                        <h5><?= $conteos['revision_rechazada'] ?? 0 ?></h5>
                        <small class="text-muted">Revisiones Rechazadas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-building fs-2 text-info mb-2"></i>
                        <h5><?= $conteos['centro_autorizado'] ?? 0 ?></h5>
                        <small class="text-muted">Centros Autorizados</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-ban fs-2 text-warning mb-2"></i>
                        <h5><?= $conteos['centro_rechazado'] ?? 0 ?></h5>
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
                    <?php if (!empty($filtros['accion']) || !empty($filtros['busqueda'])): ?>
                        No se encontraron registros con los filtros aplicados.<br>
                        <a href="<?= url('/autorizaciones/historial') ?>" class="btn btn-outline-primary mt-2">
                            <i class="fas fa-times me-2"></i>Limpiar Filtros
                        </a>
                    <?php else: ?>
                        Aún no has procesado ninguna autorización.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
    <?php endif; ?>

</div>

<?php View::endSection(); ?>
