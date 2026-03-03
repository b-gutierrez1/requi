<?php
use App\Helpers\View;

View::startSection('content');
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-2">
                <i class="fas fa-file-alt me-2"></i>
                Mis Requisiciones
            </h1>
            <p class="text-muted mb-0">Gestiona tus órdenes de compra y requisiciones</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= url('/requisiciones/crear') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                Nueva Requisición
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?= url('/requisiciones') ?>" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="pendiente_revision">En Revisión</option>
                        <option value="pendiente_autorizacion">En Autorización</option>
                        <option value="autorizado">Autorizada</option>
                        <option value="rechazado">Rechazada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="busqueda" class="form-control" 
                           placeholder="Proveedor, justificación...">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Filtrar
                    </button>
                    <a href="<?= url('/requisiciones') ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Requisiciones 
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>Fecha</th>
                            <th>Centro</th>
                            <th class="text-end">Monto</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requisiciones)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-inbox fs-1 text-muted mb-3"></i>
                                <h5 class="text-muted">No hay requisiciones</h5>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($requisiciones as $req): ?>
                            <tr>
                                <td><strong>#<?php echo $req['id']; ?></strong></td>
                                <td><?php echo View::e($req['nombre_razon_social']); ?></td>
                                <td><?php echo View::formatDate($req['fecha']); ?></td>
                                <td>Centro</td>
                                <td class="text-end"><?php echo View::money($req['monto_total'], $req['moneda'] ?? 'GTQ'); ?></td>
                                <td><span class="badge bg-warning">Pendiente</span></td>
                                <td class="text-center">
                                    <a href="<?= url('/requisiciones/' . $req['id']) ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php View::endSection(); ?>
