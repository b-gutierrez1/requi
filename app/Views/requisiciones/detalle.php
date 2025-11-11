<?php
use App\Helpers\View;

View::startSection('content');
?>

<style>
    .section-header {
        background: #000;
        color: #fff;
        padding: 12px 20px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .form-label {
        font-weight: 600;
        font-size: 13px;
        color: #333;
        margin-bottom: 8px;
    }
    
    .table-dark-custom {
        background: #000 !important;
        color: #fff !important;
    }
    
    .table-dark-custom th {
        font-weight: 600;
        font-size: 13px;
        padding: 12px 8px;
        border: none;
    }
    
    .card-form {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        padding: 30px;
        margin-bottom: 20px;
    }
    
    .logo-header {
        text-align: center;
        margin-bottom: 20px;
    }
    
    .logo-header img {
        max-width: 150px;
        height: auto;
    }
    
    .form-title {
        text-align: center;
        font-size: 18px;
        font-weight: 700;
        color: #000;
        margin-bottom: 30px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .total-display {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 4px;
        text-align: right;
        font-weight: 700;
        font-size: 16px;
        margin-top: 15px;
    }
    
    .btn-action {
        background: #000;
        color: #fff;
        padding: 12px 24px;
        font-weight: 600;
        border: none;
        border-radius: 4px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-right: 10px;
        transition: all 0.3s;
    }
    
    .btn-action:hover {
        background: #333;
        color: #fff;
        text-decoration: none;
        transform: translateY(-2px);
    }

    .btn-secondary-custom {
        background: #6c757d;
    }
    
    .btn-secondary-custom:hover {
        background: #5a6268;
    }
    
    .field-display {
        background-color: #f8f9fa;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        border: 1px solid #e9ecef;
        min-height: 40px;
        display: flex;
        align-items: center;
        font-size: 14px;
        color: #333;
    }

    .textarea-display {
        white-space: pre-wrap;
        word-wrap: break-word;
        min-height: 80px;
    }
    
    .estado-badge {
        font-size: 13px;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: 1px solid transparent;
    }
    
    .estado-pendiente { background: #fff3cd; color: #856404; border-color: #ffeeba; }
    .estado-pendiente-revision { background: #e2e3e5; color: #495057; border-color: #d6d8db; }
    .estado-aprobada { background: #d4edda; color: #155724; border-color: #c3e6cb; }
    .estado-autorizada { background: #cff4fc; color: #055160; border-color: #b6effb; }
    .estado-rechazada { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    .estado-completada { background: #d1ecf1; color: #0c5460; border-color: #bee5eb; }

    .info-section h3 {
        font-size: 16px;
        font-weight: 600;
        color: #000;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    
    .actions-header {
        text-align: right;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .card-form {
            padding: 20px 15px;
        }
        .actions-header {
            text-align: center;
        }
        .btn-action {
            width: 100%;
            margin-bottom: 10px;
        }
    }
</style>

<div class="container py-4" style="max-width: 1200px;">
    
    <!-- Acciones principales -->
    <div class="actions-header">
        <a href="/requisiciones" class="btn-action btn-secondary-custom">
            <i class="fas fa-arrow-left me-2"></i>Volver al Listado
        </a>
        <a href="/requisiciones/<?php echo $orden['id']; ?>/imprimir" class="btn-action" target="_blank">
            <i class="fas fa-print me-2"></i>Imprimir
        </a>
        <?php if (isset($orden['id'])): ?>
            <a href="/requisiciones/<?php echo $orden['id']; ?>/editar" class="btn-action">
                <i class="fas fa-edit me-2"></i>Editar
            </a>
        <?php endif; ?>
    </div>

    <div class="card-form">
        <!-- Logo y Título -->
        <div class="logo-header">
            <img src="/assets/images/logo-iga.png" alt="IGA" onerror="this.style.display='none'">
        </div>
        
        <h1 class="form-title">
            Detalle de Requisición
        </h1>
        
        <!-- INFORMACIÓN GENERAL -->
        <div class="info-section">
            <h2 class="h5 mb-4" style="border-bottom: 2px solid #000; padding-bottom: 10px;">
                Información General
            </h2>
            
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label">Requisición N°</label>
                    <div class="field-display">
                        <strong>#<?php echo $orden['requisicion_numero'] ?? $orden['id'] ?? 'N/A'; ?></strong>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <div class="field-display">
                        <?php 
                        $estado = $flujo['estado'] ?? 'pendiente';
                        $estadoClass = 'estado-' . str_replace('_', '-', $estado);
                        $estadoTextos = [
                            'pendiente' => 'Pendiente',
                            'pendiente_revision' => 'En Revisión',
                            'aprobada' => 'Aprobada',
                            'rechazada' => 'Rechazada',
                            'completada' => 'Completada',
                            'autorizada' => 'Autorizada'
                        ];
                        ?>
                        <span class="estado-badge <?php echo $estadoClass; ?>">
                            <?php echo $estadoTextos[$estado] ?? ucfirst($estado); ?>
                        </span>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Fecha de Creación</label>
                    <div class="field-display">
                        <?php echo date('d/m/Y', strtotime($orden['fecha_creacion'] ?? 'now')); ?>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Fecha de Solicitud</label>
                    <div class="field-display">
                        <?php echo date('d/m/Y', strtotime($orden['fecha'] ?? $orden['fecha_creacion'] ?? 'now')); ?>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Nombre o Razón Social</label>
                    <div class="field-display">
                        <?php echo View::e($orden['nombre_razon_social'] ?? ''); ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Unidad Requirente</label>
                    <div class="field-display">
                        <?php echo View::e($orden['unidad_requirente'] ?? ''); ?>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">NIT</label>
                    <div class="field-display">
                        <?php echo View::e($orden['nit'] ?? 'N/A'); ?>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Causal de Compra</label>
                    <div class="field-display">
                        <?php echo View::e(getCausalCompraLabel($orden['causal_compra'] ?? '')); ?>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Moneda</label>
                    <div class="field-display">
                        <?php 
                        $moneda = $orden['moneda'] ?? 'GTQ';
                        $monedas = [
                            'GTQ' => 'Quetzales (GTQ)',
                            'USD' => 'Dólares (USD)',
                            'EUR' => 'Euros (EUR)'
                        ];
                        echo $monedas[$moneda] ?? $moneda;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ITEMS/DESCRIPCIÓN -->
    <div class="card-form">
        <div class="section-header">
            <i class="fas fa-list me-2"></i>
            ITEMS
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark-custom">
                    <tr>
                        <th style="width: 10%">Cantidad</th>
                        <th style="width: 50%">Descripción</th>
                        <th style="width: 20%">Precio Unitario</th>
                        <th style="width: 20%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalGeneral = 0;
                    if (!empty($items)): 
                        foreach ($items as $item): 
                            $totalItem = ($item['cantidad'] ?? 0) * ($item['precio_unitario'] ?? 0);
                            $totalGeneral += $totalItem;
                    ?>
                        <tr>
                            <td class="text-center"><?php echo number_format($item['cantidad'], 2); ?></td>
                            <td><?php echo View::e($item['descripcion']); ?></td>
                            <td class="text-end"><?php echo View::money($item['precio_unitario']); ?></td>
                            <td class="text-end"><strong><?php echo View::money($totalItem); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted"><em>No hay items registrados</em></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="total-display">
            Total General: <strong><?php echo View::money($totalGeneral); ?></strong>
        </div>
    </div>
    
    <!-- DISTRIBUCIÓN DE GASTO -->
    <?php if (!empty($distribucion)): ?>
    <div class="card-form">
        <div class="section-header">
            <i class="fas fa-chart-pie me-2"></i>
            DISTRIBUCIÓN DE GASTO
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark-custom">
                    <tr>
                        <th>Cuenta Contable</th>
                        <th>Centro de Costo</th>
                        <th>Ubicación</th>
                        <th>Unidad de Negocio</th>
                        <th class="text-center">Porcentaje</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-center">Factura</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($distribucion as $dist): ?>
                    <tr>
                        <td><?php echo View::e($dist['cuenta_contable'] ?? ''); ?></td>
                        <td><?php echo View::e($dist['centro_costo'] ?? ''); ?></td>
                        <td><?php echo View::e($dist['ubicacion'] ?? 'N/A'); ?></td>
                        <td><?php echo View::e($dist['unidad_negocio'] ?? 'N/A'); ?></td>
                        <td class="text-center"><?php echo number_format($dist['porcentaje'] ?? 0, 2); ?>%</td>
                        <td class="text-end"><?php echo View::money($dist['cantidad'] ?? 0); ?></td>
                        <td class="text-center"><?php echo View::e($dist['factura'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- DATOS ADJUNTOS -->
    <?php if (!empty($orden['archivos'])): ?>
    <div class="card-form">
        <div class="section-header"><i class="fas fa-paperclip me-2"></i>DATOS ADJUNTOS</div>
        <ul class="list-group">
            <?php foreach($orden['archivos'] as $archivo): ?>
                <li class="list-group-item">
                    <a href="/uploads/<?php echo View::e($archivo['ruta']); ?>" target="_blank">
                        <i class="fas fa-file-alt me-2"></i><?php echo View::e($archivo['nombre']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- ESPECIFICACIONES Y JUSTIFICACIÓN -->
    <div class="card-form">
        <div class="section-header">
            <i class="fas fa-cogs me-2"></i>ESPECIFICACIONES TÉCNICAS Y JUSTIFICACIÓN
        </div>
        <div class="mb-4">
            <label class="form-label">Especificaciones Técnicas y Detalles</label>
            <div class="field-display textarea-display">
                <?php echo !empty($orden['datos_proveedor']) ? nl2br(View::e($orden['datos_proveedor'])) : '<em>No especificado</em>'; ?>
            </div>
        </div>
        <div>
            <label class="form-label">Justificación y Razón de la Requisición</label>
            <div class="field-display textarea-display">
                <?php echo !empty($orden['razon_seleccion']) ? nl2br(View::e($orden['razon_seleccion'])) : '<em>No especificado</em>'; ?>
            </div>
        </div>
    </div>
    
    <!-- HISTORIAL DE AUTORIZACIONES -->
    <?php if (!empty($autorizaciones)): ?>
    <div class="card-form">
        <div class="section-header">
            <i class="fas fa-history me-2"></i>
            HISTORIAL DE AUTORIZACIONES
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark-custom">
                    <tr>
                        <th>Centro de Costo</th>
                        <th>Autorizador</th>
                        <th class="text-center">Estado</th>
                        <th>Fecha</th>
                        <th>Comentario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($autorizaciones as $auth): ?>
                    <tr>
                        <td><?php echo View::e($auth['centro_costo'] ?? ''); ?></td>
                        <td><?php echo View::e($auth['autorizador_email'] ?? ''); ?></td>
                        <td class="text-center">
                            <?php if ($auth['autorizada']): ?>
                                <span class="badge bg-success">Autorizada</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $auth['fecha_autorizacion'] ? date('d/m/Y H:i', strtotime($auth['fecha_autorizacion'])) : '-'; ?></td>
                        <td><?php echo View::e($auth['comentario'] ?? 'N/A'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php View::endSection(); ?>