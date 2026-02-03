<?php 
use App\Helpers\View;
use App\Helpers\Session;

$title = 'Detalle del Autorizador de Respaldo';
?>

<?php View::startSection('content'); ?>
<style>
    .main-header {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
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
    
    .status-card {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        border-radius: 12px;
        padding: 1.5rem;
        color: white;
        margin-bottom: 2rem;
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
    }
    
    .status-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-block;
    }
    
    .badge-activo {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }
    
    .badge-vencido {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }
    
    .badge-proximo {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        color: white;
    }
    
    .badge-inactivo {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
    }
    
    .section-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 3px solid #ff6b6b;
        display: flex;
        align-items: center;
    }
    
    .section-title i {
        margin-right: 12px;
        color: #ff6b6b;
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
    
    .person-card {
        background: #f8f9fa;
        border-left: 4px solid #ff6b6b;
        padding: 1.5rem;
        border-radius: 0 8px 8px 0;
        margin: 1rem 0;
    }
    
    .person-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #ff6b6b;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 600;
        margin-right: 20px;
    }
    
    .btn-edit {
        background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
    }
    
    .btn-edit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
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
    
    .btn-delete {
        background: #dc3545;
        border: none;
        border-radius: 8px;
        padding: 12px 30px;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-delete:hover {
        background: #c82333;
        color: white;
    }
    
    .timeline-item {
        padding: 1rem;
        border-left: 3px solid #ff6b6b;
        margin-left: 1rem;
        margin-bottom: 1rem;
        background: #f8f9fa;
        border-radius: 0 8px 8px 0;
    }
    
    .progress-bar {
        height: 8px;
        border-radius: 4px;
        background: #e9ecef;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #ff6b6b, #ee5a24);
        transition: width 0.3s ease;
    }
</style>

<!-- Header Principal -->
<div class="main-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0">
                    <i class="fas fa-hands-helping me-3"></i>
                    <?= View::e($title) ?>
                </h1>
                <p class="mb-0 opacity-75">Información completa del autorizador de respaldo</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?= url('/admin/autorizadores/respaldos') ?>" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver a la Lista
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Tarjeta de Estado -->
    <div class="row">
        <div class="col-12">
            <div class="status-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3><i class="fas fa-hands-helping me-2"></i>Respaldo ID: #<?= View::e($respaldo['id'] ?? 'N/A') ?></h3>
                        <p class="mb-0">Centro de Costo: <?= View::e($respaldo['centro_nombre'] ?? 'No especificado') ?></p>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php
                        // Determinar el estado del respaldo
                        $fechaInicio = $respaldo['fecha_inicio'] ?? '';
                        $fechaFin = $respaldo['fecha_fin'] ?? '';
                        $fechaHoy = date('Y-m-d');
                        
                        $estado = 'inactivo';
                        $estadoTexto = 'Inactivo';
                        $estadoClass = 'badge-inactivo';
                        
                        if ($fechaInicio && $fechaFin) {
                            if ($fechaHoy >= $fechaInicio && $fechaHoy <= $fechaFin) {
                                $estado = 'activo';
                                $estadoTexto = 'Activo';
                                $estadoClass = 'badge-activo';
                            } elseif ($fechaHoy > $fechaFin) {
                                $estado = 'vencido';
                                $estadoTexto = 'Vencido';
                                $estadoClass = 'badge-vencido';
                            } elseif ($fechaHoy < $fechaInicio) {
                                $diasHasta = ceil((strtotime($fechaInicio) - strtotime($fechaHoy)) / (60 * 60 * 24));
                                if ($diasHasta <= 7) {
                                    $estado = 'proximo';
                                    $estadoTexto = "Inicia en $diasHasta día" . ($diasHasta != 1 ? 's' : '');
                                    $estadoClass = 'badge-proximo';
                                }
                            }
                        }
                        ?>
                        <span class="status-badge <?= $estadoClass ?>"><?= $estadoTexto ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Columna Izquierda: Información de Personas -->
        <div class="col-lg-6">
            <div class="detail-container">
                <h4 class="section-title">
                    <i class="fas fa-users"></i>
                    Autorizadores Involucrados
                </h4>
                
                <!-- Autorizador Principal -->
                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-user me-2"></i>
                        Autorizador Principal
                    </div>
                    <div class="person-card">
                        <div class="d-flex align-items-center">
                            <div class="person-avatar">
                                <?= View::e(substr($respaldo['autorizador_principal_nombre'] ?? 'P', 0, 1)) ?>
                            </div>
                            <div>
                                <h5 class="mb-1"><?= View::e($respaldo['autorizador_principal_nombre'] ?? 'Sin nombre') ?></h5>
                                <p class="mb-0 text-muted">
                                    <i class="fas fa-envelope me-1"></i>
                                    <?= View::e($respaldo['autorizador_principal_email'] ?? 'Sin email') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Autorizador de Respaldo -->
                <div class="detail-row">
                    <div class="detail-label">
                        <i class="fas fa-user-plus me-2"></i>
                        Autorizador de Respaldo
                    </div>
                    <div class="person-card">
                        <div class="d-flex align-items-center">
                            <div class="person-avatar">
                                <?= View::e(substr($respaldo['autorizador_respaldo_nombre'] ?? 'R', 0, 1)) ?>
                            </div>
                            <div>
                                <h5 class="mb-1"><?= View::e($respaldo['autorizador_respaldo_nombre'] ?? 'Sin nombre') ?></h5>
                                <p class="mb-0 text-muted">
                                    <i class="fas fa-envelope me-1"></i>
                                    <?= View::e($respaldo['autorizador_respaldo_email'] ?? 'Sin email') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Detalles del Respaldo -->
        <div class="col-lg-6">
            <div class="detail-container">
                <h4 class="section-title">
                    <i class="fas fa-info-circle"></i>
                    Detalles del Respaldo
                </h4>
                
                <div class="detail-row">
                    <div class="detail-label">Período de Vigencia</div>
                    <div class="detail-value">
                        <?php if ($fechaInicio && $fechaFin): ?>
                            <i class="fas fa-calendar-alt text-info me-2"></i>
                            <strong>Desde:</strong> <?= date('d/m/Y', strtotime($fechaInicio)) ?><br>
                            <strong>Hasta:</strong> <?= date('d/m/Y', strtotime($fechaFin)) ?>
                            <?php 
                            $dias = ceil((strtotime($fechaFin) - strtotime($fechaInicio)) / (60 * 60 * 24));
                            ?>
                            <div class="mt-2">
                                <small class="text-muted">Duración: <?= $dias ?> día<?= $dias != 1 ? 's' : '' ?></small>
                            </div>
                            
                            <!-- Barra de progreso -->
                            <?php if ($estado === 'activo'): ?>
                                <?php 
                                $totalDias = ceil((strtotime($fechaFin) - strtotime($fechaInicio)) / (60 * 60 * 24));
                                $diasTranscurridos = ceil((strtotime($fechaHoy) - strtotime($fechaInicio)) / (60 * 60 * 24));
                                $progreso = min(100, max(0, ($diasTranscurridos / $totalDias) * 100));
                                ?>
                                <div class="mt-2">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= $progreso ?>%;"></div>
                                    </div>
                                    <small class="text-muted">Progreso: <?= round($progreso) ?>%</small>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">Fechas no especificadas</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Motivo del Respaldo</div>
                    <div class="detail-value">
                        <?php if (!empty($respaldo['motivo'])): ?>
                            <i class="fas fa-comment text-warning me-2"></i>
                            <?= View::e($respaldo['motivo']) ?>
                        <?php else: ?>
                            <span class="text-muted">No especificado</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Centro de Costo</div>
                    <div class="detail-value">
                        <?php if (!empty($respaldo['centro_nombre'])): ?>
                            <i class="fas fa-building text-primary me-2"></i>
                            <?= View::e($respaldo['centro_nombre']) ?>
                            <?php if (!empty($respaldo['centro_codigo'])): ?>
                                <small class="text-muted">(<?= View::e($respaldo['centro_codigo']) ?>)</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">No especificado</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($respaldo['descripcion'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Descripción Adicional</div>
                    <div class="detail-value">
                        <div class="timeline-item">
                            <i class="fas fa-sticky-note me-2"></i>
                            <?= View::e($respaldo['descripcion']) ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($respaldo['fecha_creacion'])): ?>
                <div class="detail-row">
                    <div class="detail-label">Fecha de Creación</div>
                    <div class="detail-value">
                        <i class="fas fa-clock text-secondary me-2"></i>
                        <?= date('d/m/Y H:i', strtotime($respaldo['fecha_creacion'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="<?= url('/admin/autorizadores/respaldos/' . View::e($respaldo['id'] ?? '') . '/edit') ?>" class="btn btn-edit me-3">
                <i class="fas fa-edit me-2"></i>
                Editar Respaldo
            </a>
            <button type="button" class="btn btn-delete me-3" onclick="confirmarEliminacion()">
                <i class="fas fa-trash me-2"></i>
                Eliminar Respaldo
            </button>
            <a href="<?= url('/admin/autorizadores/respaldos') ?>" class="btn btn-back">
                <i class="fas fa-arrow-left me-2"></i>
                Volver a la Lista
            </a>
        </div>
    </div>
</div>

<script>
function confirmarEliminacion() {
    if (confirm('¿Estás seguro de que quieres eliminar este respaldo?\n\nEsta acción no se puede deshacer.')) {
        // Crear formulario para enviar DELETE request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/admin/autorizadores/respaldos/<?= View::e($respaldo['id'] ?? '') ?>';
        
        // Agregar method override para DELETE
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'DELETE';
        form.appendChild(methodInput);
        
        // Agregar CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = '<?= \App\Middlewares\CsrfMiddleware::getToken() ?>';
        form.appendChild(csrfInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Actualizar automáticamente la barra de progreso si está activa
document.addEventListener('DOMContentLoaded', function() {
    const progressFill = document.querySelector('.progress-fill');
    if (progressFill) {
        // Animar la barra de progreso
        setTimeout(() => {
            progressFill.style.transition = 'width 2s ease-in-out';
        }, 500);
    }
});
</script>
<?php View::endSection(); ?>