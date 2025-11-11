<?php 
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Detalles del Autorizador de Cuenta Contable';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header { background: linear-gradient(135deg, #6f42c1 0%, #5a2d91 100%); color: white; padding: 2rem 0; margin-bottom: 2rem; border-radius: 0 0 15px 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
    .detail-container { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); padding: 2rem; border: 1px solid #e9ecef; }
    .detail-section { margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e9ecef; }
    .detail-section:last-child { border-bottom: none; margin-bottom: 0; }
    .section-title { font-size: 1.2rem; font-weight: 600; color: #6f42c1; margin-bottom: 1rem; display: flex; align-items: center; }
    .section-title i { margin-right: 10px; }
    .status-card { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-left: 4px solid #6f42c1; padding: 1.5rem; border-radius: 0 8px 8px 0; margin-bottom: 1.5rem; }
    .status-active { border-left-color: #28a745; }
    .status-inactive { border-left-color: #dc3545; }
    .cuenta-item { background: #f3e5f5; border: 2px solid #e1bee7; border-radius: 8px; padding: 1rem; margin-bottom: 0.5rem; }
    .codigo-badge { background: #e8f5e8; color: #2e7d32; padding: 6px 12px; border-radius: 8px; font-family: 'Courier New', monospace; font-weight: 600; font-size: 0.9rem; }
    .info-item { display: flex; align-items: center; margin-bottom: 1rem; }
    .info-item i { color: #6f42c1; margin-right: 10px; font-size: 1.1rem; }
    .btn-back { background: linear-gradient(135deg, #6f42c1, #5a2d91); border: none; border-radius: 8px; padding: 10px 25px; color: white; font-weight: 600; transition: all 0.3s ease; }
    .btn-back:hover { transform: translateY(-2px); color: white; }
</style>

<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0"><i class="fas fa-calculator me-3"></i><?= View::e($title) ?></h1>
                <p class="mb-0 opacity-75">Información detallada del autorizador por cuenta contable</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="/admin/autorizadores/cuentas-contables" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>Volver a la Lista
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="detail-container">
                <?php 
                // Datos de ejemplo para demostración
                $autorizador = (object)[
                    'id' => 1,
                    'nombre' => 'Carlos Mendoza',
                    'email' => 'carlos.mendoza@empresa.com',
                    'cargo' => 'Contador General',
                    'cuentas_contables' => json_encode([
                        ['codigo' => '1101', 'nombre' => 'Caja General'],
                        ['codigo' => '1102', 'nombre' => 'Bancos Nacionales'],
                        ['codigo' => '5101', 'nombre' => 'Gastos de Oficina']
                    ]),
                    'activo' => true,
                    'fecha_inicio' => '2024-01-01',
                    'fecha_fin' => '2024-12-31',
                    'observaciones' => 'Autorización para cuentas contables del área financiera',
                    'centros_costo_count' => 2
                ];
                
                $activo = $autorizador->activo ?? true;
                
                $estado = 'activo';
                $estadoTexto = 'Activo';
                $estadoClass = 'status-active';
                
                if (!$activo) {
                    $estado = 'inactivo';
                    $estadoTexto = 'Inactivo';
                    $estadoClass = 'status-inactive';
                ?>
                
                <div class="status-card <?= $estadoClass ?>">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-1"><?= View::e($autorizador->nombre) ?></h4>
                            <p class="mb-0 text-muted"><?= View::e($autorizador->email) ?></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge <?= $estado === 'activo' ? 'bg-success' : ($estado === 'inactivo' ? 'bg-danger' : 'bg-warning') ?> fs-6 px-3 py-2">
                                <?= $estadoTexto ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3 class="section-title"><i class="fas fa-user"></i>Información del Autorizador</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <i class="fas fa-user"></i>
                                <div>
                                    <strong>Nombre Completo:</strong><br>
                                    <span><?= View::e($autorizador->nombre) ?></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <strong>Email:</strong><br>
                                    <span><?= View::e($autorizador->email) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <i class="fas fa-briefcase"></i>
                                <div>
                                    <strong>Cargo:</strong><br>
                                    <span><?= View::e($autorizador->cargo ?? 'No especificado') ?></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-id-badge"></i>
                                <div>
                                    <strong>ID del Autorizador:</strong><br>
                                    <span>#<?= View::e($autorizador->id) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3 class="section-title"><i class="fas fa-list-alt"></i>Cuentas Contables Autorizadas</h3>
                    <div class="row">
                        <?php if (!empty($autorizador->cuentas_contables)): ?>
                            <?php 
                            $cuentas = is_array($autorizador->cuentas_contables) 
                                ? $autorizador->cuentas_contables 
                                : json_decode($autorizador->cuentas_contables, true);
                            if (!$cuentas) {
                                $cuentas = [$autorizador->cuentas_contables]; // Fallback si no es JSON
                            }
                            ?>
                            <?php foreach ($cuentas as $cuenta): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="cuenta-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <?php if (is_array($cuenta)): ?>
                                                    <span class="codigo-badge"><?= View::e($cuenta['codigo'] ?? 'Sin código') ?></span>
                                                    <div class="mt-2">
                                                        <strong><?= View::e($cuenta['nombre'] ?? $cuenta['descripcion'] ?? 'Sin nombre') ?></strong>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="codigo-badge"><?= View::e($cuenta) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-muted">No hay cuentas contables asignadas</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3 class="section-title"><i class="fas fa-building"></i>Información Adicional</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <i class="fas fa-infinity"></i>
                                <div>
                                    <strong>Tipo de Autorización:</strong><br>
                                    <span class="text-success fw-bold">Sin límite de monto</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <i class="fas fa-building"></i>
                                <div>
                                    <strong>Centros de Costo:</strong><br>
                                    <span><?= $autorizador->centros_costo_count ?? 0 ?> centro(s) asignado(s)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3 class="section-title"><i class="fas fa-calendar-alt"></i>Período de Vigencia</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item">
                                <i class="fas fa-play-circle"></i>
                                <div>
                                    <strong>Fecha de Inicio:</strong><br>
                                    <span><?= !empty($autorizador->fecha_inicio) ? date('d/m/Y', strtotime($autorizador->fecha_inicio)) : 'No especificada' ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <i class="fas fa-stop-circle"></i>
                                <div>
                                    <strong>Fecha de Fin:</strong><br>
                                    <span><?= !empty($autorizador->fecha_fin) ? date('d/m/Y', strtotime($autorizador->fecha_fin)) : 'Permanente' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($autorizador->fecha_inicio) && !empty($autorizador->fecha_fin)): ?>
                        <div class="mt-3 p-3 bg-light rounded">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                <?php 
                                $dias = ceil((strtotime($autorizador->fecha_fin) - strtotime($autorizador->fecha_inicio)) / (60 * 60 * 24));
                                echo "Duración total: $dias día" . ($dias != 1 ? 's' : '');
                                ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($autorizador->observaciones)): ?>
                <div class="detail-section">
                    <h3 class="section-title"><i class="fas fa-sticky-note"></i>Observaciones</h3>
                    <div class="p-3 bg-light rounded">
                        <p class="mb-0"><?= View::e($autorizador->observaciones) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-12 text-center">
                        <a href="/admin/autorizadores/cuentas-contables" class="btn btn-back me-3">
                            <i class="fas fa-arrow-left me-2"></i>Volver a la Lista
                        </a>
                        <a href="/admin/autorizadores/cuentas-contables/<?= View::e($autorizador->id) ?>/edit" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        <a href="/admin/autorizadores/cuentas-contables/create" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Nuevo Autorizador
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); ?>