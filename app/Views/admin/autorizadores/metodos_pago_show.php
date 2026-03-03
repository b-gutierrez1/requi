<?php 
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Detalles del Autorizador de Método de Pago';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; padding: 2rem 0; margin-bottom: 2rem; border-radius: 0 0 15px 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
    .detail-container { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); padding: 2rem; border: 1px solid #e9ecef; }
    .detail-section { margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e9ecef; }
    .detail-section:last-child { border-bottom: none; margin-bottom: 0; }
    .section-title { font-size: 1.2rem; font-weight: 600; color: #17a2b8; margin-bottom: 1rem; display: flex; align-items: center; }
    .section-title i { margin-right: 10px; }
    .status-card { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-left: 4px solid #17a2b8; padding: 1.5rem; border-radius: 0 8px 8px 0; margin-bottom: 1.5rem; }
    .status-active { border-left-color: #28a745; }
    .status-inactive { border-left-color: #dc3545; }
    .metodo-badge { background: #e3f2fd; color: #1565c0; padding: 6px 15px; border-radius: 15px; font-size: 0.9rem; font-weight: 500; margin: 3px; }
    .info-item { display: flex; align-items: center; margin-bottom: 1rem; }
    .info-item i { color: #17a2b8; margin-right: 10px; font-size: 1.1rem; }
    .btn-back { background: linear-gradient(135deg, #17a2b8, #138496); border: none; border-radius: 8px; padding: 10px 25px; color: white; font-weight: 600; transition: all 0.3s ease; }
    .btn-back:hover { transform: translateY(-2px); color: white; }
</style>

<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0"><i class="fas fa-credit-card me-3"></i><?= View::e($title) ?></h1>
                <p class="mb-0 opacity-75">Información detallada del autorizador por método de pago</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?= url('/admin/autorizadores/metodos-pago') ?>" class="btn btn-light">
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
                    'nombre' => 'María González',
                    'email' => 'maria.gonzalez@empresa.com',
                    'cargo' => 'Gerente de Finanzas',
                    'metodos_pago' => 'efectivo,transferencia,cheque',
                    'activo' => true,
                    'fecha_inicio' => '2024-01-01',
                    'fecha_fin' => '2024-12-31',
                    'observaciones' => 'Autorización para métodos de pago en el departamento de finanzas',
                    'centros_costo_count' => 3
                ];
                
                $activo = $autorizador->activo ?? true;
                
                $estado = 'activo';
                $estadoTexto = 'Activo';
                $estadoClass = 'status-active';
                
                if (!$activo) {
                    $estado = 'inactivo';
                    $estadoTexto = 'Inactivo';
                    $estadoClass = 'status-inactive';
                }
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
                    <h3 class="section-title"><i class="fas fa-credit-card"></i>Métodos de Pago Autorizados</h3>
                    <div class="mb-3">
                        <?php if (!empty($autorizador->metodos_pago)): ?>
                            <?php 
                            $metodos = is_string($autorizador->metodos_pago) 
                                ? explode(',', $autorizador->metodos_pago) 
                                : $autorizador->metodos_pago;
                            ?>
                            <?php foreach ($metodos as $metodo): ?>
                                <span class="metodo-badge">
                                    <?php
                                    $metodosTexto = [
                                        'efectivo' => 'Efectivo',
                                        'transferencia' => 'Transferencia Bancaria',
                                        'cheque' => 'Cheque',
                                        'tarjeta' => 'Tarjeta de Crédito',
                                        'deposito' => 'Depósito Bancario'
                                    ];
                                    echo View::e($metodosTexto[trim($metodo)] ?? trim($metodo));
                                    ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No hay métodos de pago específicos asignados</p>
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
                        <a href="<?= url('/admin/autorizadores/metodos-pago') ?>" class="btn btn-back me-3">
                            <i class="fas fa-arrow-left me-2"></i>Volver a la Lista
                        </a>
                        <a href="<?= url('/admin/autorizadores/metodos-pago/' . View::e($autorizador->id) . '/edit') ?>" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        <a href="<?= url('/admin/autorizadores/metodos-pago/create') ?>" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Nuevo Autorizador
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php View::endSection(); ?>