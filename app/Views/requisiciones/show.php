<?php
use App\Helpers\View;
use App\Helpers\EstadoHelper;

View::startSection('content');

// Función auxiliar para acceder a datos que pueden ser objetos o arrays
function getData($data, $key, $default = '') {
    if (is_object($data)) {
        return $data->$key ?? $default;
    } elseif (is_array($data)) {
        return $data[$key] ?? $default;
    }
    return $default;
}
?>

<style>
    .section-header {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: #fff;
        padding: 15px 25px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .form-label {
        font-weight: 600;
        font-size: 13px;
        color: #333;
        margin-bottom: 8px;
    }
    
    .table-dark-custom {
        background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%) !important;
        color: #fff !important;
    }
    
    .table-dark-custom th {
        font-weight: 600;
        font-size: 13px;
        padding: 15px 12px;
        border: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .card-form {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        padding: 35px;
        margin-bottom: 25px;
        margin-left: auto;
        margin-right: auto;
        border: 1px solid #f1f3f4;
        transition: all 0.3s ease;
    }
    
    .card-form:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        transform: translateY(-2px);
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
        background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
        color: #fff;
        padding: 12px 24px;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-right: 10px;
        box-shadow: 0 2px 8px rgba(52, 152, 219, 0.3);
        transition: all 0.3s ease;
    }
    
    .btn-action:hover {
        background: linear-gradient(135deg, #2980b9 0%, #1f639a 100%);
        color: #fff;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.4);
    }
    
    .btn-secondary-custom {
        background: #6c757d;
        color: #fff;
    }
    
    .btn-secondary-custom:hover {
        background: #5a6268;
        color: #fff;
    }
    
    .field-display {
        background: #f8f9fa;
        padding: 14px 18px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        min-height: 42px;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
        font-size: 14px;
    }
    
    .field-display:hover {
        background: #e9ecef;
        border-color: #dee2e6;
    }
    
    .estado-badge {
        font-size: 14px;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .estado-pendiente { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #8b4513; border: 1px solid #ffd1dc; box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2); }
    .estado-pendiente-revision { background: linear-gradient(135deg, #e2e3e5 0%, #c6c8ca 100%); color: #495057; border: 1px solid #ced4da; box-shadow: 0 2px 8px rgba(108, 117, 125, 0.2); }
    .estado-aprobada { background: linear-gradient(135deg, #d4edda 0%, #a3d9a4 100%); color: #155724; border: 1px solid #c3e6cb; box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2); }
    .estado-autorizada { background: linear-gradient(135deg, #cff4fc 0%, #9fddff 100%); color: #055160; border: 1px solid #b6effb; box-shadow: 0 2px 8px rgba(13, 202, 240, 0.2); }
    .estado-rechazada { background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); color: #721c24; border: 1px solid #f5c6cb; box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2); }
    .estado-completada { background: linear-gradient(135deg, #d1ecf1 0%, #b8daff 100%); color: #0c5460; border: 1px solid #bee5eb; box-shadow: 0 2px 8px rgba(13, 202, 240, 0.2); }
    
    .info-section {
        margin-bottom: 25px;
    }
    
    .info-section h3 {
        font-size: 16px;
        font-weight: 600;
        color: #000;
        border-bottom: 2px solid #000;
        padding-bottom: 8px;
        margin-bottom: 20px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .card-form {
            padding: 20px 15px;
            margin-bottom: 15px;
        }
        
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .table th, .table td {
            padding: 8px 6px;
            font-size: 0.8rem;
        }
    }
    
    @media (min-width: 1200px) {
        .container {
            max-width: 1140px !important;
            margin: 0 auto;
        }
    }
    
    .actions-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    /* Estilos para archivos adjuntos */
    .archivo-item {
        transition: all 0.3s ease;
        border: 1px solid #e9ecef !important;
    }
    
    .archivo-item:hover {
        border-color: #3498db !important;
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
        transform: translateY(-2px);
    }
    
    /* Mejoras en las tablas */
    .table {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .table tbody tr {
        transition: background-color 0.2s ease;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .table td {
        padding: 12px 15px;
        vertical-align: middle;
    }
    
    /* Badges mejorados */
    .badge {
        font-size: 0.8rem;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
    }
    
    /* Iconos mejorados */
    .section-header i {
        font-size: 1.2rem;
    }
    
    /* Total display mejorado */
    .total-display {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 20px;
        border-radius: 10px;
        text-align: right;
        font-weight: 700;
        font-size: 18px;
        margin-top: 20px;
        border: 2px solid #dee2e6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    /* Mejorar espaciado */
    .row.mb-4 {
        margin-bottom: 2rem !important;
    }
    
    /* Animaciones suaves */
    * {
        transition: all 0.2s ease;
    }
    
    /* Mensaje cuando no hay datos */
    .no-data-message {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
        font-style: italic;
    }
</style>

<div class="container py-4" style="max-width: 1200px;">
    <div class="card-form">
        <!-- Logo y Título -->
        <div class="logo-header">
            <img src="/assets/images/logo-iga.png" alt="IGA" onerror="this.style.display='none'">
        </div>
        
        <h1 class="form-title">
            Requisición para compra de bienes y contratación de servicios
        </h1>
        
        <!-- Acciones principales -->
        <div class="actions-header">
            <a href="/requisiciones" class="btn-action btn-secondary-custom">
                <i class="fas fa-arrow-left"></i>Volver
            </a>
            <a href="/requisiciones/<?php echo getData($orden, 'id'); ?>/imprimir" 
               class="btn-action" target="_blank">
                <i class="fas fa-print"></i>Imprimir
            </a>
            <?php if (getData($orden, 'id')): ?>
                <a href="/requisiciones/<?php echo getData($orden, 'id'); ?>/editar" class="btn-action">
                    <i class="fas fa-edit"></i>Editar
                </a>
            <?php endif; ?>
        </div>
        
        <!-- INFORMACIÓN GENERAL -->
        <div class="info-section">
            <h3>Información General</h3>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Requisición N°</label>
                    <div class="field-display">
                        <strong>#<?php echo getData($orden, 'requisicion_numero') ?: getData($orden, 'id', 'N/A'); ?></strong>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <div class="field-display">
                        <?php 
                        // ✅ USAR SISTEMA CENTRALIZADO DE ESTADOS
                        
                        // Obtener estado real desde la orden de compra
                        $estadoReal = $orden->getEstadoReal();
                        $badge = EstadoHelper::getBadge($estadoReal);
                        $estadoClass = 'estado-' . str_replace('_', '-', $estadoReal);
                        
                        // Mantener timestamp para debug
                        $timestamp = date('H:i:s');
                        ?>
                        <span class="estado-badge <?php echo $estadoClass; ?> <?php echo $badge['class']; ?>">
                            <?php echo $badge['text']; ?>
                            <small class="text-muted ms-2" title="Última actualización"><?php echo $timestamp; ?></small>
                        </span>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Fecha de Creación</label>
                    <div class="field-display">
                        <?php echo date('d/m/Y', strtotime(getData($orden, 'fecha_creacion', 'now'))); ?>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Nombre o Razón Social</label>
                    <div class="field-display">
                        <?php echo View::e(getData($orden, 'nombre_razon_social')); ?>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Unidad Requirente</label>
                    <div class="field-display">
                        <?php 
                        $unidadRequiriente = getData($orden, 'unidad_requirente');
                        // Si es un número, mostrar un texto más amigable
                        if (is_numeric($unidadRequiriente)) {
                            echo "Unidad ID: " . $unidadRequiriente;
                        } else {
                            echo View::e($unidadRequiriente ?: 'No especificada');
                        }
                        ?>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Fecha</label>
                    <div class="field-display">
                        <?php echo date('d/m/Y', strtotime(getData($orden, 'fecha') ?: getData($orden, 'fecha_creacion', 'now'))); ?>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Causal de Compra</label>
                    <div class="field-display">
                        <?php echo View::e(getCausalCompraLabel(getData($orden, 'causal_compra'))); ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Moneda</label>
                    <div class="field-display">
                        <?php 
                        $moneda = getData($orden, 'moneda', 'GTQ');
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
            
            <?php if (!empty(getData($orden, 'direccion'))): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <label class="form-label">Dirección</label>
                    <div class="field-display">
                        <?php echo View::e(getData($orden, 'direccion')); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- ITEMS/DESCRIPCIÓN -->
    <div class="card-form">
        <div class="section-header">
            <i class="fas fa-shopping-cart"></i>
            DESCRIPCIÓN DE BIENES Y SERVICIOS
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark-custom">
                    <tr>
                        <th style="width: 15%">Cantidad</th>
                        <th style="width: 55%">Descripción</th>
                        <th style="width: 15%">Precio Unitario</th>
                        <th style="width: 15%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="text-center">
                                <strong><?php echo number_format(getData($item, 'cantidad', 0), 2); ?></strong>
                            </td>
                            <td><?php echo View::e(getData($item, 'descripcion')); ?></td>
                            <td class="text-end">
                                <?php echo View::money(getData($item, 'precio_unitario', 0)); ?>
                            </td>
                            <td class="text-end">
                                <strong><?php echo View::money(getData($item, 'cantidad', 0) * getData($item, 'precio_unitario', 0)); ?></strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <!-- Fila de total -->
                        <tr class="table-light">
                            <td colspan="2" class="text-end"><strong>TOTAL GENERAL:</strong></td>
                            <td class="text-end">
                                <strong style="font-size: 1.1em;">
                                    <?php 
                                    $total = 0;
                                    foreach ($items as $item) {
                                        $total += getData($item, 'cantidad', 0) * getData($item, 'precio_unitario', 0);
                                    }
                                    echo View::money($total);
                                    ?>
                                </strong>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-data-message">
                                <i class="fas fa-inbox fa-2x mb-2 text-muted"></i><br>
                                <em>No hay items registrados en esta requisición</em>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="total-display">
            Total: <strong>
                <?php 
                $total = 0;
                if (!empty($items)) {
                    foreach ($items as $item) {
                        $total += getData($item, 'cantidad', 0) * getData($item, 'precio_unitario', 0);
                    }
                }
                echo View::money($total);
                ?>
            </strong>
        </div>
    </div>
    
    <!-- DISTRIBUCIÓN DE GASTO -->
    <?php if (!empty($distribucion)): ?>
    <div class="card-form">
        <div class="section-header">
            <i class="fas fa-chart-pie"></i>
            DISTRIBUCIÓN DE GASTO
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark-custom">
                    <tr>
                        <th>Cuenta Contable</th>
                        <th>Centro de Costo</th>
                        <th>Ubicación</th>
                        <th>Porcentaje</th>
                        <th>Cantidad</th>
                        <th>N° Factura</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($distribucion as $dist): ?>
                    <tr>
                        <td><?php echo View::e(getData($dist, 'cuenta_contable')); ?></td>
                        <td><?php echo View::e(getData($dist, 'centro_costo')); ?></td>
                        <td><?php echo View::e(getData($dist, 'ubicacion')); ?></td>
                        <td class="text-center"><?php echo number_format(getData($dist, 'porcentaje', 0), 2); ?>%</td>
                        <td class="text-end"><?php echo View::money(getData($dist, 'cantidad', 0)); ?></td>
                        <td class="text-center">
                            <?php 
                            $numeroFactura = getData($dist, 'numero_factura') ?: getData($dist, 'factura');
                            echo $numeroFactura ? View::e($numeroFactura) : '-';
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- FACTURAS -->
    <div class="card-form">
        <div class="section-header">
            <i class="fas fa-file-invoice"></i>
            FACTURAS
        </div>
        
        <?php if (!empty($facturas)): ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark-custom">
                    <tr>
                        <th>N° Factura</th>
                        <th>Proveedor</th>
                        <th>Fecha</th>
                        <th>Monto</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facturas as $factura): ?>
                    <tr>
                        <td class="text-center">
                            <strong><?php echo View::e(getData($factura, 'numero_factura')); ?></strong>
                        </td>
                        <td><?php echo View::e(getData($factura, 'proveedor')); ?></td>
                        <td class="text-center">
                            <?php 
                            $fecha = getData($factura, 'fecha_factura');
                            echo $fecha ? date('d/m/Y', strtotime($fecha)) : '-';
                            ?>
                        </td>
                        <td class="text-end">
                            <?php echo View::money(getData($factura, 'monto', 0)); ?>
                        </td>
                        <td class="text-center">
                            <?php 
                            $estado = getData($factura, 'estado', 'pendiente');
                            $badgeClass = $estado === 'pagada' ? 'bg-success' : 'bg-warning';
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>">
                                <?php echo ucfirst($estado); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="no-data-message">
            <i class="fas fa-file-invoice fa-3x mb-3 text-muted"></i><br>
            <h5 class="text-muted">No hay facturas asociadas</h5>
            <p class="text-muted mb-0">Las facturas aparecerán aquí una vez que sean procesadas</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- ARCHIVOS ADJUNTOS -->
    <div class="card-form">
        <div class="section-header">
            <i class="fas fa-paperclip"></i>
            ARCHIVOS ADJUNTOS
        </div>
        
        <?php if (!empty($archivos)): ?>
        <div class="row">
            <?php foreach ($archivos as $archivo): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="archivo-item p-3 border rounded bg-light">
                    <div class="d-flex align-items-center">
                        <div class="archivo-icon me-3">
                            <?php 
                            $extension = strtolower(pathinfo(getData($archivo, 'nombre_archivo', ''), PATHINFO_EXTENSION));
                            $iconClass = match($extension) {
                                'pdf' => 'fas fa-file-pdf text-danger',
                                'doc', 'docx' => 'fas fa-file-word text-primary',
                                'xls', 'xlsx' => 'fas fa-file-excel text-success',
                                'jpg', 'jpeg', 'png', 'gif' => 'fas fa-file-image text-info',
                                default => 'fas fa-file text-secondary'
                            };
                            ?>
                            <i class="<?php echo $iconClass; ?>" style="font-size: 2rem;"></i>
                        </div>
                        <div class="archivo-info flex-grow-1">
                            <h6 class="mb-1">
                                <?php echo View::e(getData($archivo, 'nombre_original') ?: getData($archivo, 'nombre_archivo')); ?>
                            </h6>
                            <small class="text-muted">
                                <?php 
                                $tamaño = getData($archivo, 'tamaño');
                                if ($tamaño) {
                                    echo number_format($tamaño / 1024, 1) . ' KB';
                                }
                                ?>
                            </small>
                        </div>
                        <div class="archivo-actions">
                            <a href="/archivos/<?php echo getData($archivo, 'id'); ?>/descargar" 
                               class="btn btn-sm btn-outline-primary" target="_blank">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="no-data-message">
            <i class="fas fa-paperclip fa-3x mb-3 text-muted"></i><br>
            <h5 class="text-muted">No hay archivos adjuntos</h5>
            <p class="text-muted mb-0">Los documentos de soporte aparecerán aquí cuando sean agregados</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- ESPECIFICACIONES TÉCNICAS Y JUSTIFICACIÓN -->
    <div class="card-form">
        <div class="section-header">
            <i class="fas fa-clipboard-list"></i>
            ESPECIFICACIONES TÉCNICAS Y JUSTIFICACIÓN
        </div>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <label class="form-label">Justificación de la Requisición</label>
                <div class="field-display" style="min-height: 80px; align-items: flex-start;">
                    <?php 
                    $justificacion = getData($orden, 'justificacion') ?: getData($orden, 'observaciones');
                    if ($justificacion): 
                        echo nl2br(View::e($justificacion));
                    else:
                        echo '<em class="text-muted">No se proporcionó justificación específica.</em>';
                    endif;
                    ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty(getData($orden, 'especificaciones_tecnicas'))): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <label class="form-label">Especificaciones Técnicas</label>
                <div class="field-display" style="min-height: 60px; align-items: flex-start;">
                    <?php echo nl2br(View::e(getData($orden, 'especificaciones_tecnicas'))); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label">Tipo de Adquisición</label>
                <div class="field-display">
                    <?php 
                    $tipo = getData($orden, 'tipo_adquisicion', 'Compra de bienes');
                    echo View::e($tipo);
                    ?>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Prioridad</label>
                <div class="field-display">
                    <?php 
                    $prioridad = getData($orden, 'prioridad', 'Normal');
                    $prioridadClass = match(strtolower($prioridad)) {
                        'urgente' => 'text-danger fw-bold',
                        'alta' => 'text-warning fw-bold',
                        default => 'text-info'
                    };
                    ?>
                    <span class="<?php echo $prioridadClass; ?>">
                        <?php echo View::e($prioridad); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- INFORMACIÓN ADICIONAL -->
    <?php if (!empty(getData($orden, 'observaciones')) || !empty($flujo)): ?>
    <div class="card-form">
        <div class="section-header">
            <i class="fas fa-info-circle"></i>
            INFORMACIÓN ADICIONAL
        </div>
        
        <?php if (!empty(getData($orden, 'observaciones'))): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <label class="form-label">Observaciones</label>
                <div class="field-display" style="min-height: 60px; align-items: flex-start;">
                    <?php echo nl2br(View::e(getData($orden, 'observaciones'))); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($flujo)): ?>
        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label">Fecha de Última Actualización</label>
                <div class="field-display">
                    <?php echo date('d/m/Y H:i', strtotime(getData($flujo, 'fecha_actualizacion') ?: getData($flujo, 'fecha_creacion', 'now'))); ?>
                </div>
            </div>
            
            <?php if (!empty(getData($flujo, 'comentario_rechazo'))): ?>
            <div class="col-md-6">
                <label class="form-label">Comentario de Rechazo</label>
                <div class="field-display">
                    <?php echo View::e(getData($flujo, 'comentario_rechazo')); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- HISTORIAL DE AUTORIZACIONES -->
    <?php if (!empty($autorizaciones)): ?>
    <div class="card-form">
        <div class="section-header">
            <i class="fas fa-history"></i>
            HISTORIAL DE AUTORIZACIONES
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark-custom">
                    <tr>
                        <th>Centro de Costo</th>
                        <th>Autorizador</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Comentario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($autorizaciones as $auth): ?>
                    <tr>
                        <td><?php echo View::e(getData($auth, 'centro_costo')); ?></td>
                        <td><?php echo View::e(getData($auth, 'autorizador_email')); ?></td>
                        <td class="text-center">
                            <?php if (getData($auth, 'autorizada')): ?>
                                <span class="badge bg-success">Autorizada</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Pendiente</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo getData($auth, 'fecha_autorizacion') ? date('d/m/Y H:i', strtotime(getData($auth, 'fecha_autorizacion'))) : '-'; ?></td>
                        <td><?php echo View::e(getData($auth, 'comentario')); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php View::endSection(); ?>

<?php View::startSection('scripts'); ?>
<script>
// Auto-refrescar si se viene de una autorización
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si hay parámetro de refresco en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const refresh = urlParams.get('refresh');
    
    if (refresh === 'autorizado') {
        // Remover el parámetro de la URL para evitar refrescos infinitos
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
        
        // Mostrar mensaje de éxito si hay
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: '¡Autorización Completada!',
                text: 'La requisición ha sido autorizada exitosamente',
                timer: 3000,
                showConfirmButton: false
            });
        }
    }
    
    // Verificar actualizaciones cada 30 segundos si está pendiente
    const estadoBadge = document.querySelector('.estado-badge');
    if (estadoBadge && (estadoBadge.textContent.includes('Pendiente') || estadoBadge.textContent.includes('pendiente'))) {
        setInterval(function() {
            // Solo verificar si la página está visible
            if (!document.hidden) {
                checkEstadoUpdate();
            }
        }, 30000); // 30 segundos
    }
});

function checkEstadoUpdate() {
    // Obtener ID de la requisición desde la URL
    const path = window.location.pathname;
    const match = path.match(/\/requisiciones\/(\d+)/);
    if (!match) return;
    
    const requisicionId = match[1];
    
    // Hacer petición AJAX para verificar estado actual
    fetch(`/api/requisicion/${requisicionId}/estado`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.estado) {
            const estadoBadge = document.querySelector('.estado-badge');
            if (estadoBadge) {
                const estadoActual = estadoBadge.textContent.trim();
                const estadoNuevo = data.estado_texto || data.estado;
                
                // Si el estado cambió, refrescar la página
                if (estadoActual !== estadoNuevo && !estadoActual.includes(estadoNuevo)) {
                    window.location.reload();
                }
            }
        }
    })
    .catch(error => {
        console.log('Error verificando estado:', error);
    });
}
</script>
<?php View::endSection(); ?>