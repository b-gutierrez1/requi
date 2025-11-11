<?php 
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Detalles del Autorizador';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 15px 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .detail-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        padding: 2rem;
        border: 1px solid #e9ecef;
        margin-bottom: 2rem;
    }
    
    .section-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 3px solid #667eea;
        display: flex;
        align-items: center;
    }
    
    .section-title i {
        margin-right: 12px;
        color: #667eea;
    }
    
    .detail-row {
        padding: 1rem 0;
        border-bottom: 1px solid #f1f3f5;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .detail-value {
        color: #212529;
        font-size: 1.1rem;
    }
    
    .badge-status {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }
    
    .badge-active {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }
    
    .badge-inactive {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }
    
    .permission-badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        margin: 5px;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    .permission-active {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .permission-inactive {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .btn-edit {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .btn-edit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }
    
    .btn-back {
        background: #6c757d;
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-back:hover {
        background: #5a6268;
        color: white;
    }
    
    .info-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        padding: 1.5rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .info-card h3 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }
    
    .info-card p {
        margin: 0;
        opacity: 0.9;
    }
    
    .special-authorization {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 4px;
    }
    
    .no-data {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
        font-style: italic;
    }
    
    .table-responsive {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .table-sm th {
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .table-hover tbody tr:hover {
        background: #f1f3f5;
        cursor: pointer;
    }
    
    .centro-badge {
        display: inline-flex;
        align-items: center;
        padding: 8px 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px;
        margin: 4px;
        font-size: 0.85rem;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
    }
    
    .centro-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .centro-badge i {
        margin-right: 6px;
    }
    
    .centros-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }
</style>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0">
                    <i class="fas fa-user-shield me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Información completa del autorizador</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/admin/autorizadores" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver a la Lista
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Tarjeta de Información Principal -->
    <div class="row">
        <div class="col-12">
            <div class="info-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3><i class="fas fa-user me-2"></i><?= View::e($autorizador['nombre'] ?? 'Sin nombre') ?></h3>
                        <p><i class="fas fa-envelope me-2"></i><?= View::e($autorizador['email'] ?? 'Sin email') ?></p>
                        <p><i class="fas fa-briefcase me-2"></i><?= View::e($autorizador['cargo'] ?? 'Sin cargo asignado') ?></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="mb-3">
                            <?php if ($autorizador['activo'] ?? true): ?>
                                <span class="badge badge-status badge-active">
                                    <i class="fas fa-check-circle me-2"></i>ACTIVO
                                </span>
                            <?php else: ?>
                                <span class="badge badge-status badge-inactive">
                                    <i class="fas fa-times-circle me-2"></i>INACTIVO
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="mb-2">
                            <strong>ID:</strong> #<?= View::e($autorizador['id'] ?? 'N/A') ?>
                        </div>
                        <div>
                            <i class="fas fa-building me-2"></i>
                            <strong><?= count($centrosCosto) ?></strong> Centro<?= count($centrosCosto) != 1 ? 's' : '' ?> de Costo
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columna Izquierda: Información General -->
        <div class="col-lg-6">
            <div class="detail-container">
                <h4 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Información General
                </h4>
                
                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-building me-2"></i>
                        Centros de Costo Asignados (<?= count($centrosCosto) ?>)
                    </div>
                    <div class="detail-value">
                        <?php if (!empty($centrosCosto)): ?>
                            <!-- Vista de Badges (más visual) -->
                            <div class="centros-grid">
                                <?php foreach ($centrosCosto as $index => $centro): ?>
                                    <div class="centro-badge" title="<?= View::e($centro['centro_nombre'] ?? $centro['nombre'] ?? 'Sin nombre') ?>">
                                        <i class="fas fa-building"></i>
                                        <span>
                                            <?php 
                                            // Mostrar ID si no hay código
                                            $codigo = $centro['centro_codigo'] ?? $centro['codigo'] ?? ('ID: ' . ($centro['centro_id'] ?? $centro['centro_costo_id'] ?? 'N/A'));
                                            echo View::e($codigo);
                                            ?>
                                            - 
                                            <?php 
                                            $nombre = $centro['centro_nombre'] ?? $centro['nombre'] ?? 'Sin nombre';
                                            echo View::e(strlen($nombre) > 25 ? substr($nombre, 0, 25) . '...' : $nombre);
                                            ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Vista de Tabla (alternativa colapsada) -->
                            <?php if (count($centrosCosto) > 5): ?>
                            <div class="mt-3">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#tablaCentros">
                                    <i class="fas fa-table me-2"></i>Ver como tabla
                                </button>
                            </div>
                            <div class="collapse mt-2" id="tablaCentros">
                                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Código</th>
                                                <th>Nombre</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($centrosCosto as $index => $centro): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?php 
                                                        $codigo = $centro['centro_codigo'] ?? $centro['codigo'] ?? ('ID: ' . ($centro['centro_id'] ?? $centro['centro_costo_id'] ?? 'N/A'));
                                                        echo View::e($codigo);
                                                        ?>
                                                    </span>
                                                </td>
                                                <td><?= View::e($centro['centro_nombre'] ?? $centro['nombre'] ?? 'Sin nombre') ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-warning mt-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No tiene centros de costo asignados
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Prioridad</div>
                    <div class="detail-value">
                        <?php 
                        $prioridad = $autorizador['prioridad'] ?? 1;
                        $prioridadTexto = ['1' => 'Alta', '2' => 'Media', '3' => 'Baja'];
                        $prioridadColor = ['1' => 'danger', '2' => 'warning', '3' => 'info'];
                        ?>
                        <span class="badge bg-<?= $prioridadColor[$prioridad] ?? 'secondary' ?>">
                            <?= $prioridadTexto[$prioridad] ?? 'No definida' ?>
                        </span>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Límite de Autorización</div>
                    <div class="detail-value">
                        <?php if (isset($autorizador['monto_limite']) && $autorizador['monto_limite'] > 0): ?>
                            <i class="fas fa-money-bill-wave text-success me-2"></i>
                            <strong>Q <?= number_format($autorizador['monto_limite'], 2) ?></strong>
                        <?php else: ?>
                            <span class="text-muted">Sin límite definido</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Fecha de Inicio</div>
                    <div class="detail-value">
                        <?php if (!empty($autorizador['fecha_inicio'])): ?>
                            <i class="fas fa-calendar-alt text-info me-2"></i>
                            <?= date('d/m/Y', strtotime($autorizador['fecha_inicio'])) ?>
                        <?php else: ?>
                            <span class="text-muted">No especificada</span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($autorizador['fecha_creacion'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Fecha de Creación</div>
                    <div class="detail-value">
                        <i class="fas fa-clock text-secondary me-2"></i>
                        <?= date('d/m/Y H:i', strtotime($autorizador['fecha_creacion'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Columna Derecha: Permisos -->
        <div class="col-lg-6">
            <div class="detail-container">
                <h4 class="section-title">
                    <i class="fas fa-shield-alt"></i>
                    Permisos de Autorización
                </h4>
                
                <div class="detail-row">
                    <div class="detail-label">Permisos Asignados</div>
                    <div class="detail-value">
                        <div>
                            <?php 
                            $permisos = [
                                'puede_autorizar_centro_costo' => 'Centro de Costo',
                                'puede_autorizar_flujo' => 'Flujo de Trabajo',
                                'puede_autorizar_cuenta_contable' => 'Cuenta Contable',
                                'puede_autorizar_metodo_pago' => 'Método de Pago',
                                'puede_autorizar_respaldo' => 'Respaldo'
                            ];
                            
                            $tienePermisos = false;
                            foreach ($permisos as $key => $label):
                                if ($autorizador[$key] ?? false):
                                    $tienePermisos = true;
                            ?>
                                <span class="permission-badge permission-active">
                                    <i class="fas fa-check me-1"></i><?= $label ?>
                                </span>
                            <?php 
                                endif;
                            endforeach;
                            
                            if (!$tienePermisos):
                            ?>
                                <div class="no-data">
                                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                                    <p>No tiene permisos especiales asignados</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Autorizaciones Especiales -->
            <?php if (!empty($autorizacionesEspeciales)): ?>
            <div class="detail-container mt-3">
                <h4 class="section-title">
                    <i class="fas fa-star"></i>
                    Autorizaciones Especiales
                </h4>
                
                <?php if (!empty($autorizacionesEspeciales['metodo_pago'])): ?>
                <div class="special-authorization">
                    <strong><i class="fas fa-credit-card me-2"></i>Métodos de Pago</strong>
                    <p class="mb-0 mt-2">Tiene <?= count($autorizacionesEspeciales['metodo_pago']) ?> autorización(es) para métodos de pago específicos</p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($autorizacionesEspeciales['cuenta_contable'])): ?>
                <div class="special-authorization">
                    <strong><i class="fas fa-calculator me-2"></i>Cuentas Contables</strong>
                    <p class="mb-0 mt-2">Tiene <?= count($autorizacionesEspeciales['cuenta_contable']) ?> autorización(es) para cuentas contables específicas</p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($autorizacionesEspeciales['respaldo'])): ?>
                <div class="special-authorization">
                    <strong><i class="fas fa-hands-helping me-2"></i>Respaldos</strong>
                    <p class="mb-0 mt-2">Actúa como respaldo en <?= count($autorizacionesEspeciales['respaldo']) ?> caso(s)</p>
                </div>
                <?php endif; ?>
                
                <?php if (empty($autorizacionesEspeciales['metodo_pago']) && 
                          empty($autorizacionesEspeciales['cuenta_contable']) && 
                          empty($autorizacionesEspeciales['respaldo'])): ?>
                <div class="no-data">
                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                    <p>No tiene autorizaciones especiales asignadas</p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="/admin/autorizadores/<?= View::e($autorizador['id'] ?? '') ?>/edit" class="btn btn-edit me-3">
                <i class="fas fa-edit me-2"></i>
                Editar Autorizador
            </a>
            <a href="/admin/autorizadores" class="btn btn-back">
                <i class="fas fa-arrow-left me-2"></i>
                Volver a la Lista
            </a>
        </div>
    </div>
</div>

<?php View::endSection(); ?>

