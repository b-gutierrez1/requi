<?php
use App\Helpers\View;

View::startSection('content');

// Colores por factura
$coloresFactura = [
    1 => '#007bff',  // Azul - Factura 1
    2 => '#ffc107',  // Amarillo - Factura 2
    3 => '#28a745'   // Verde - Factura 3
];

// Agrupar centros de costo por unidad de negocio
$relaciones = [];
foreach ($centros_costo as $centro) {
    $unidadNombre = $centro['unidad_negocio_nombre'] ?? 'SIN ASIGNAR';
    $factura = $centro['factura_numero'] ?? 1;
    
    if (!isset($relaciones[$unidadNombre])) {
        $relaciones[$unidadNombre] = [
            'unidad_negocio' => $unidadNombre,
            'unidad_negocio_id' => $centro['rel_unidad_negocio_id'] ?? $centro['unidad_negocio_id'] ?? null,
            'factura' => $factura,
            'centros' => []
        ];
    }
    
    $relaciones[$unidadNombre]['centros'][] = $centro['nombre'];
}

// Ordenar por factura
uasort($relaciones, function($a, $b) {
    return $a['factura'] <=> $b['factura'];
});
?>

<style>
    .relacion-card {
        border-left: 5px solid;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .relacion-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
    .centro-badge {
        display: inline-block;
        padding: 6px 12px;
        margin: 4px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        transition: all 0.2s;
    }
    .centro-badge:hover {
        background: #e9ecef;
    }
    .factura-badge {
        font-size: 1rem;
        padding: 8px 16px;
        border-radius: 6px;
        color: white;
    }
    .factura-1 { background-color: #007bff; }
    .factura-2 { background-color: #ffc107; color: #333; }
    .factura-3 { background-color: #28a745; }
    .unidad-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .info-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 30px;
    }
    .diagram-container {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 30px;
        margin-bottom: 30px;
    }
    .diagram-flow {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    .diagram-box {
        background: white;
        border-radius: 8px;
        padding: 15px 25px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .diagram-arrow {
        font-size: 2rem;
        color: #007bff;
    }
    .leyenda-factura {
        display: inline-flex;
        align-items: center;
        margin-right: 20px;
        margin-bottom: 10px;
    }
    .leyenda-color {
        width: 30px;
        height: 20px;
        border-radius: 4px;
        margin-right: 8px;
    }
    .bd-indicator {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
        padding: 10px 15px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-project-diagram me-2"></i>
                Relaciones Centro de Costo - Unidad de Negocio
            </h1>
            <p class="text-muted mb-0">Mapeo automático desde la base de datos</p>
        </div>
        <div class="d-flex gap-2">
            <span class="bd-indicator">
                <i class="fas fa-database"></i>
                Datos desde BD
            </span>
            <a href="<?= url('/admin/catalogos') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Volver a Catálogos
            </a>
        </div>
    </div>

    <!-- Leyenda de Facturas -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="mb-3"><i class="fas fa-palette me-2"></i>Leyenda de Facturas</h5>
            <div class="d-flex flex-wrap">
                <div class="leyenda-factura">
                    <div class="leyenda-color" style="background-color: #007bff;"></div>
                    <span><strong>Factura 1:</strong> Comercial - Actividades Culturales</span>
                </div>
                <div class="leyenda-factura">
                    <div class="leyenda-color" style="background-color: #ffc107;"></div>
                    <span><strong>Factura 2:</strong> Colegio</span>
                </div>
                <div class="leyenda-factura">
                    <div class="leyenda-color" style="background-color: #28a745;"></div>
                    <span><strong>Factura 3:</strong> Administración - Cursos</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Box -->
    <div class="info-box">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4><i class="fas fa-info-circle me-2"></i>¿Cómo funciona?</h4>
                <p class="mb-0">
                    Cuando seleccionas un <strong>Centro de Costo</strong> en una requisición, el sistema 
                    automáticamente asigna la <strong>Unidad de Negocio</strong> y el tipo de <strong>Factura</strong> 
                    según las relaciones definidas en la base de datos. Los datos se leen directamente de las tablas
                    <code>centro_de_costo</code> y <code>unidad_de_negocio</code>.
                </p>
            </div>
            <div class="col-md-4 text-center">
                <i class="fas fa-database fa-3x opacity-75"></i>
            </div>
        </div>
    </div>

    <!-- Diagrama de Flujo -->
    <div class="diagram-container">
        <h5 class="text-center mb-4"><i class="fas fa-sitemap me-2"></i>Flujo de Asignación Automática</h5>
        <div class="diagram-flow">
            <div class="diagram-box">
                <i class="fas fa-building fa-2x text-primary mb-2"></i>
                <div class="fw-bold">Centro de Costo</div>
                <small class="text-muted">Seleccionado por usuario</small>
            </div>
            <div class="diagram-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
            <div class="diagram-box">
                <i class="fas fa-database fa-2x text-success mb-2"></i>
                <div class="fw-bold">Base de Datos</div>
                <small class="text-muted">Consulta directa</small>
            </div>
            <div class="diagram-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
            <div class="diagram-box">
                <i class="fas fa-tag fa-2x text-warning mb-2"></i>
                <div class="fw-bold">Unidad de Negocio</div>
                <small class="text-muted">Asignada automáticamente</small>
            </div>
            <div class="diagram-arrow">
                <i class="fas fa-plus"></i>
            </div>
            <div class="diagram-box">
                <i class="fas fa-file-invoice fa-2x text-info mb-2"></i>
                <div class="fw-bold">Tipo Factura</div>
                <small class="text-muted">Asignada automáticamente</small>
            </div>
        </div>
    </div>

    <!-- Relaciones -->
    <h4 class="mb-4"><i class="fas fa-list me-2"></i>Relaciones por Unidad de Negocio (<?= count($relaciones) ?> unidades)</h4>
    
    <div class="row">
        <?php foreach ($relaciones as $relacion): ?>
            <?php $colorFactura = $coloresFactura[$relacion['factura']] ?? '#6c757d'; ?>
            <div class="col-lg-6 mb-4">
                <div class="card relacion-card h-100" style="border-left-color: <?= $colorFactura ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div class="unidad-title" style="color: <?= $colorFactura ?>">
                                    <i class="fas fa-tag me-2"></i>
                                    <?= htmlspecialchars($relacion['unidad_negocio']) ?>
                                </div>
                                <?php if ($relacion['unidad_negocio_id']): ?>
                                    <small class="text-muted">ID: <?= $relacion['unidad_negocio_id'] ?></small>
                                <?php endif; ?>
                            </div>
                            <span class="badge factura-badge factura-<?= $relacion['factura'] ?>">
                                <i class="fas fa-file-invoice me-1"></i>
                                Factura <?= $relacion['factura'] ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block mb-2">
                                <i class="fas fa-building me-1"></i>
                                Centros de Costo asignados (<?= count($relacion['centros']) ?>):
                            </small>
                            <div>
                                <?php foreach ($relacion['centros'] as $centro): ?>
                                    <span class="centro-badge">
                                        <?= htmlspecialchars($centro) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Tabla Resumen Completa -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-table me-2"></i>
                Tabla Completa de Relaciones (<?= count($centros_costo) ?> centros de costo)
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Centro de Costo</th>
                            <th>Unidad de Negocio</th>
                            <th>Factura</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($centros_costo as $centro): ?>
                            <?php $factura = $centro['factura_numero'] ?? 1; ?>
                            <tr class="<?= $factura == 2 ? 'table-warning' : ($factura == 3 ? 'table-success' : '') ?>">
                                <td><?= $centro['id'] ?></td>
                                <td><strong><?= htmlspecialchars($centro['nombre']) ?></strong></td>
                                <td>
                                    <span class="badge" style="background-color: <?= $coloresFactura[$factura] ?? '#6c757d' ?>; <?= $factura == 2 ? 'color:#333' : '' ?>">
                                        <?= htmlspecialchars($centro['unidad_negocio_nombre'] ?? 'SIN ASIGNAR') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge factura-<?= $factura ?>">Factura <?= $factura ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Nota técnica -->
    <div class="alert alert-success mt-4">
        <h5><i class="fas fa-check-circle me-2"></i>Implementación con Base de Datos</h5>
        <p class="mb-2">
            Las relaciones ahora se almacenan directamente en la tabla <code>centro_de_costo</code> con las columnas:
        </p>
        <ul class="mb-2">
            <li><code>unidad_negocio_id</code> - Referencia a la tabla <code>unidad_de_negocio</code></li>
            <li><code>factura</code> - Número de factura (1, 2, o 3)</li>
        </ul>
        <p class="mb-0">
            <strong>Ventajas:</strong> Más simple, más mantenible, sin necesidad de modificar código cuando cambian las relaciones.
            <br>
            <strong>Última actualización:</strong> <?= date('d/m/Y H:i') ?>
        </p>
    </div>
</div>

<?php View::endSection(); ?>
