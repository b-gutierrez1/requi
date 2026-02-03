<?php
// Vista parcial para mostrar el detalle completo de una requisición en el modal
use App\Helpers\EstadoHelper;

// Helper para obtener valor de objeto o array
if (!function_exists('getData')) {
    function getData($data, $key, $default = '') {
        if (is_object($data)) {
            return $data->$key ?? $default;
        } elseif (is_array($data)) {
            return $data[$key] ?? $default;
        }
        return $default;
    }
}

$estadoReal = getData($orden, 'id') ? EstadoHelper::getEstado(getData($orden, 'id')) : 'borrador';
$badge = EstadoHelper::getBadge($estadoReal);
?>

<style>
    .detalle-section {
        margin-bottom: 25px;
        border: 1px solid #e3e3e3;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .detalle-section-header {
        background: #f8f9fa;
        padding: 12px 20px;
        border-bottom: 1px solid #e3e3e3;
        font-weight: 600;
        font-size: 14px;
    }
    
    .detalle-section-content {
        padding: 20px;
    }
    
    .info-row {
        margin-bottom: 15px;
    }
    
    .info-label {
        font-weight: 600;
        font-size: 13px;
        color: #555;
        margin-bottom: 5px;
    }
    
    .info-value {
        font-size: 14px;
        color: #333;
    }
    
    .table-sm th {
        font-size: 12px;
        font-weight: 600;
        background: #f8f9fa;
        border-color: #e3e3e3;
        padding: 8px;
    }
    
    .table-sm td {
        font-size: 13px;
        padding: 8px;
        border-color: #e3e3e3;
    }
</style>

<!-- Estado de la requisición -->
<div class="row mb-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Estado actual:</h6>
            <span class="badge <?= $badge['class'] ?> px-3 py-2"><?= $badge['text'] ?></span>
        </div>
    </div>
</div>

<!-- Información General -->
<div class="detalle-section">
    <div class="detalle-section-header">
        <i class="fas fa-info-circle me-2"></i>Información General
    </div>
    <div class="detalle-section-content">
        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Proveedor / Razón Social</div>
                    <div class="info-value"><?= htmlspecialchars(getData($orden, 'proveedor_nombre') ?: getData($orden, 'nombre_razon_social') ?: 'No especificado') ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha de Solicitud</div>
                    <div class="info-value"><?= getData($orden, 'fecha_solicitud') ?: getData($orden, 'fecha') ?: 'No especificada' ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Moneda</div>
                    <div class="info-value"><?= getData($orden, 'moneda') ?: 'GTQ' ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Forma de Pago</div>
                    <div class="info-value"><?= ucfirst(str_replace('_', ' ', getData($orden, 'forma_pago') ?: 'No especificada')) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Anticipo</div>
                    <div class="info-value">Q <?= number_format(floatval(getData($orden, 'anticipo', 0)), 2) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Monto Total</div>
                    <div class="info-value"><strong>Q <?= number_format(floatval(getData($orden, 'monto_total', 0)), 2) ?></strong></div>
                </div>
            </div>
        </div>
        
        <?php if (getData($orden, 'justificacion')): ?>
        <div class="info-row mt-3">
            <div class="info-label">Justificación</div>
            <div class="info-value"><?= nl2br(htmlspecialchars(getData($orden, 'justificacion'))) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if (getData($orden, 'observaciones')): ?>
        <div class="info-row">
            <div class="info-label">Observaciones</div>
            <div class="info-value"><?= nl2br(htmlspecialchars(getData($orden, 'observaciones'))) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Items de la Requisición -->
<?php if (!empty($items)): ?>
<div class="detalle-section">
    <div class="detalle-section-header">
        <i class="fas fa-list me-2"></i>Items de la Requisición
    </div>
    <div class="detalle-section-content">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-end">Precio Unit.</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalGeneral = 0;
                    foreach ($items as $item): 
                        $cantidad = floatval(getData($item, 'cantidad', 0));
                        $precio = floatval(getData($item, 'precio_unitario', 0));
                        $total = floatval(getData($item, 'total', 0));
                        $totalGeneral += $total;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars(getData($item, 'descripcion')) ?></td>
                        <td class="text-center"><?= number_format($cantidad, 0) ?></td>
                        <td class="text-end">Q <?= number_format($precio, 2) ?></td>
                        <td class="text-end">Q <?= number_format($total, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-active">
                        <th colspan="3" class="text-end">Total General:</th>
                        <th class="text-end">Q <?= number_format($totalGeneral, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Distribución por Centro de Costo -->
<?php if (!empty($distribucion)): ?>
<div class="detalle-section">
    <div class="detalle-section-header">
        <i class="fas fa-chart-pie me-2"></i>Distribución por Centro de Costo
    </div>
    <div class="detalle-section-content">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Centro de Costo</th>
                        <th>Cuenta Contable</th>
                        <th class="text-center">%</th>
                        <th class="text-end">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totalDistribucion = 0;
                    foreach ($distribucion as $dist):
                        $porcentaje = floatval(getData($dist, 'porcentaje', 0));
                        $cantidad = floatval(getData($dist, 'cantidad', 0));
                        $totalDistribucion += $cantidad;
                        
                        // Buscar nombres en catálogos
                        $centroCostoNombre = 'Centro ' . getData($dist, 'centro_costo_id');
                        $cuentaContableNombre = 'Cuenta ' . getData($dist, 'cuenta_contable_id');
                        
                        if (!empty($catalogos['centros_costo'])) {
                            foreach ($catalogos['centros_costo'] as $centro) {
                                if (getData($centro, 'id') == getData($dist, 'centro_costo_id')) {
                                    $centroCostoNombre = getData($centro, 'nombre') ?: getData($centro, 'descripcion');
                                    break;
                                }
                            }
                        }
                        
                        if (!empty($catalogos['cuentas_contables'])) {
                            foreach ($catalogos['cuentas_contables'] as $cuenta) {
                                if (getData($cuenta, 'id') == getData($dist, 'cuenta_contable_id')) {
                                    $cuentaContableNombre = getData($cuenta, 'codigo') . ' - ' . getData($cuenta, 'descripcion');
                                    break;
                                }
                            }
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($centroCostoNombre) ?></td>
                        <td><?= htmlspecialchars($cuentaContableNombre) ?></td>
                        <td class="text-center"><?= number_format($porcentaje, 1) ?>%</td>
                        <td class="text-end">Q <?= number_format($cantidad, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-active">
                        <th colspan="3" class="text-end">Total Distribuido:</th>
                        <th class="text-end">Q <?= number_format($totalDistribucion, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Facturas -->
<?php if (!empty($distribucion)): ?>
<div class="detalle-section">
    <div class="detalle-section-header">
        <i class="fas fa-receipt me-2"></i>Resumen de Facturas
    </div>
    <div class="detalle-section-content">
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 25%">Forma de Pago</th>
                        <th style="width: 15%">Anticipo</th>
                        <th style="width: 15%">Facturas</th>
                        <th style="width: 20%">Porcentaje</th>
                        <th style="width: 25%">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Calcular facturas
                    $facturas = [
                        1 => ['porcentaje' => 0, 'monto' => 0],
                        2 => ['porcentaje' => 0, 'monto' => 0],
                        3 => ['porcentaje' => 0, 'monto' => 0]
                    ];
                    
                    foreach ($distribucion as $dist) {
                        $factura = getData($dist, 'factura', 'Factura 1');
                        $porcentaje = floatval(getData($dist, 'porcentaje', 0));
                        $monto = floatval(getData($dist, 'cantidad', 0));
                        
                        if (preg_match('/Factura\s*1/i', $factura)) {
                            $facturas[1]['porcentaje'] += $porcentaje;
                            $facturas[1]['monto'] += $monto;
                        } elseif (preg_match('/Factura\s*2/i', $factura)) {
                            $facturas[2]['porcentaje'] += $porcentaje;
                            $facturas[2]['monto'] += $monto;
                        } elseif (preg_match('/Factura\s*3/i', $factura)) {
                            $facturas[3]['porcentaje'] += $porcentaje;
                            $facturas[3]['monto'] += $monto;
                        }
                    }
                    
                    $formaPago = getData($orden, 'forma_pago', '');
                    $formasPago = [
                        'contado' => 'Contado',
                        'tarjeta_credito_lic_milton' => 'Tarjeta de Crédito (Lic. Milton)',
                        'cheque' => 'Cheque',
                        'transferencia' => 'Transferencia',
                        'credito' => 'Crédito'
                    ];
                    
                    $anticipoTexto = (getData($orden, 'anticipo', 0) == 1) ? 'Sí' : 'No';
                    ?>
                    
                    <tr>
                        <td rowspan="4" class="align-middle">
                            <strong><?= htmlspecialchars($formasPago[$formaPago] ?? $formaPago ?: 'N/A') ?></strong>
                        </td>
                        <td rowspan="4" class="align-middle text-center">
                            <strong><?= $anticipoTexto ?></strong>
                        </td>
                        <td><strong>Factura 1</strong></td>
                        <td class="text-end"><?= number_format($facturas[1]['porcentaje'], 2) ?>%</td>
                        <td class="text-end"><strong>Q <?= number_format($facturas[1]['monto'], 2) ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Factura 2</strong></td>
                        <td class="text-end"><?= number_format($facturas[2]['porcentaje'], 2) ?>%</td>
                        <td class="text-end"><strong>Q <?= number_format($facturas[2]['monto'], 2) ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Factura 3</strong></td>
                        <td class="text-end"><?= number_format($facturas[3]['porcentaje'], 2) ?>%</td>
                        <td class="text-end"><strong>Q <?= number_format($facturas[3]['monto'], 2) ?></strong></td>
                    </tr>
                    <tr class="table-active">
                        <td><strong>TOTAL</strong></td>
                        <td class="text-end"><strong><?= number_format($facturas[1]['porcentaje'] + $facturas[2]['porcentaje'] + $facturas[3]['porcentaje'], 2) ?>%</strong></td>
                        <td class="text-end"><strong>Q <?= number_format($facturas[1]['monto'] + $facturas[2]['monto'] + $facturas[3]['monto'], 2) ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Información del Flujo de Autorización -->
<?php if (!empty($flujo)): ?>
<div class="detalle-section">
    <div class="detalle-section-header">
        <i class="fas fa-route me-2"></i>Flujo de Autorización
    </div>
    <div class="detalle-section-content">
        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Estado del Flujo</div>
                    <div class="info-value"><?= ucfirst(str_replace('_', ' ', getData($flujo, 'estado'))) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Fecha de Inicio</div>
                    <div class="info-value"><?= getData($flujo, 'fecha_creacion') ?: 'No disponible' ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label">Requiere Autorización Especial de Pago</div>
                    <div class="info-value">
                        <?= getData($flujo, 'requiere_autorizacion_especial_pago') ? 
                            '<span class="badge bg-warning text-dark">Sí</span>' : 
                            '<span class="badge bg-secondary">No</span>' ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Requiere Autorización Especial de Cuenta</div>
                    <div class="info-value">
                        <?= getData($flujo, 'requiere_autorizacion_especial_cuenta') ? 
                            '<span class="badge bg-warning text-dark">Sí</span>' : 
                            '<span class="badge bg-secondary">No</span>' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>