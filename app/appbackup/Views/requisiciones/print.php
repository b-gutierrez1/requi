<?php
use App\Helpers\View;

$moneda     = $orden->moneda ?? 'GTQ';
$simbolo    = $moneda === 'USD' ? '$' : ($moneda === 'EUR' ? '€' : 'Q');
$distribucion = $distribucion ?? [];
$items        = $items ?? [];

// Rellenar items hasta 15 filas vacías mínimo
$itemRows = max(15, count($items));
// Rellenar distribuciones hasta 14 filas vacías mínimo
$distRows = max(14, count($distribucion));

// Calcular totales por factura desde distribuciones
$facturas = [1 => ['pct' => 0, 'monto' => 0], 2 => ['pct' => 0, 'monto' => 0], 3 => ['pct' => 0, 'monto' => 0]];
foreach ($distribucion as $d) {
    $fk = (int)($d['factura'] ?? 1);
    if ($fk < 1 || $fk > 3) $fk = 1;
    $facturas[$fk]['pct']   += (float)($d['porcentaje'] ?? 0);
    $facturas[$fk]['monto'] += (float)($d['monto'] ?? 0);
}

$totalDistPct   = array_sum(array_column($facturas, 'pct'));
$totalDistMonto = array_sum(array_column($facturas, 'monto'));

$totalItems = 0;
foreach ($items as $it) {
    $totalItems += (float)($it['total'] ?? ($it['cantidad'] * $it['precio_unitario']));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Requisición #<?= $orden->id ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            background: white;
            color: #000;
        }

        .page {
            width: 210mm;
            margin: 0 auto;
            padding: 6mm 8mm;
        }

        /* ---- HEADER ---- */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .header-table td { border: 1px solid #000; padding: 2px 4px; vertical-align: middle; }
        .logo-cell { width: 55px; text-align: center; }
        .logo-box {
            border: 2px solid #000;
            width: 50px; height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 13px;
            color: #003087;
        }
        .title-cell { text-align: center; font-weight: bold; font-size: 10px; vertical-align: middle; }
        .meta-cell { font-size: 8px; }
        .meta-label { color: #0070c0; font-weight: bold; }
        .meta-field {
            display: inline-block;
            border-bottom: 1px solid #000;
            min-width: 80px;
            height: 11px;
        }

        /* ---- SECTION HEADERS ---- */
        .section-header {
            background: #000;
            color: #fff;
            font-weight: bold;
            text-align: center;
            padding: 3px;
            font-size: 9px;
            letter-spacing: 0.5px;
        }

        /* ---- GENERIC TABLE ---- */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 1px 3px;
            height: 14px;
        }
        .data-table th {
            background: #000;
            color: #fff;
            font-size: 8px;
            text-align: center;
            font-weight: bold;
        }
        .data-table td { font-size: 8px; }
        .data-table .total-row td {
            background: #000;
            color: #fff;
            font-weight: bold;
            text-align: center;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ---- COTIZACION HEADER ROW ---- */
        .cot-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .cot-header td { border: 1px solid #000; padding: 2px 4px; font-size: 8px; }
        .cot-label { color: #0070c0; font-weight: bold; }
        .cot-field {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 120px;
            height: 11px;
        }

        /* ---- BOTTOM SECTION ---- */
        .bottom-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }
        .bottom-wrap td { vertical-align: top; border: none; padding: 0; }

        .pago-section { width: 60%; padding-right: 4px; }
        .facturas-table {
            width: 100%;
            border-collapse: collapse;
            float: right;
            width: 38%;
        }
        .facturas-table th, .facturas-table td {
            border: 1px solid #000;
            padding: 1px 3px;
            font-size: 8px;
            text-align: center;
        }
        .facturas-table th { background: #000; color: #fff; font-weight: bold; }
        .facturas-table .total-row td { background: #000; color: #fff; font-weight: bold; }

        .field-row { margin: 3px 0; font-size: 8px; }
        .field-label { color: #0070c0; font-weight: bold; }
        .field-input {
            display: inline-block;
            border-bottom: 1px solid #000;
            min-width: 100px;
            height: 11px;
        }

        .text-area-box {
            border: 1px solid #000;
            width: 100%;
            height: 22px;
            margin: 1px 0 3px 0;
        }

        .signatures { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .signatures td { font-size: 8px; padding: 2px 4px; border: none; }
        .sig-line {
            display: inline-block;
            border-bottom: 1px solid #000;
            min-width: 110px;
            height: 11px;
        }

        /* Ingreso buttons (decorative, como en el original) */
        .ingreso-col { width: 18px; vertical-align: middle; }
        .ingreso-btn {
            background: #000;
            color: #fff;
            font-size: 7px;
            font-weight: bold;
            padding: 2px 1px;
            text-align: center;
            margin-bottom: 2px;
            writing-mode: vertical-rl;
            height: 40px;
        }

        .no-print { display: block; }

        @page { size: auto; margin: 1.5cm; }

        @media print {
            .no-print { display: none !important; }
            .page { padding: 4mm 6mm; width: 100%; }
            body { font-size: 8.5px; }
        }

        .print-btn {
            position: fixed;
            top: 10px; right: 10px;
            background: #007bff;
            color: #fff;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            z-index: 9999;
        }
    </style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">🖨️ Imprimir</button>

<div class="page">

    <!-- ===== HEADER ===== -->
    <table class="header-table">
        <tr>
            <td class="logo-cell" rowspan="2">
                <div class="logo-box">IGA</div>
            </td>
            <td class="title-cell" colspan="3">
                REQUISICION PARA COMPRA DE BIENES Y CONTRATACION DE SERVICIOS
            </td>
        </tr>
        <tr>
            <td style="width:45%">
                <span class="meta-label">Unidad Requirente:</span>
                <span class="meta-field"><?= View::e($orden->unidad_requirente_nombre ?? $orden->unidad_requirente ?? '') ?></span>
            </td>
            <td style="width:20%">
                <span class="meta-label">Fecha</span>
                <span class="meta-field"><?= View::e($orden->fecha_solicitud ?? '') ?></span>
            </td>
            <td style="width:35%">
                <span class="meta-label">Causal de Compra</span>
                <span class="meta-field" style="min-width:60px"><?= View::e($orden->causal_compra ?? '') ?></span>
            </td>
        </tr>
    </table>

    <!-- ===== DATOS DE COTIZACION ===== -->
    <div class="section-header">DATOS DE COTIZACION SELECCIONADA</div>

    <table class="cot-header">
        <tr>
            <td style="width:55%">
                <span class="cot-label">Nombre o Razón Social:</span>
                <span class="cot-field"><?= View::e($orden->proveedor_nombre ?? '') ?></span>
            </td>
            <td>
                <span class="cot-label">Moneda</span>
                <span class="cot-field" style="min-width:50px"><?= View::e($moneda) ?></span>
            </td>
        </tr>
    </table>

    <!-- Items -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:55px">Cantidad</th>
                <th>Descripción</th>
                <th style="width:75px">Precio Unitario</th>
                <th style="width:75px">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php for ($i = 0; $i < $itemRows; $i++):
                $it = $items[$i] ?? null;
            ?>
            <tr>
                <td class="text-center"><?= $it ? number_format((float)$it['cantidad'], 0) : '' ?></td>
                <td><?= $it ? View::e($it['descripcion']) : '' ?></td>
                <td class="text-right"><?= $it ? $simbolo . ' ' . number_format((float)$it['precio_unitario'], 2) : '' ?></td>
                <td class="text-right"><?= $it ? $simbolo . ' ' . number_format((float)$it['total'], 2) : '' ?></td>
            </tr>
            <?php endfor; ?>
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL</td>
                <td class="text-right"><?= number_format($totalItems, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- ===== DISTRIBUCION DEL GASTO ===== -->
    <div class="section-header">DISTRIBUCION DEL GASTO</div>

    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="border:none; padding:0; width:100%">
                <table class="data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Cuenta Contable</th>
                            <th>Centro de Costo</th>
                            <th>Ubicación</th>
                            <th>Unidad de Negocio</th>
                            <th style="width:35px">%</th>
                            <th style="width:60px">Cantidad</th>
                            <th style="width:30px">Fact.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < $distRows; $i++):
                            $d = $distribucion[$i] ?? null;
                            $cuentaLabel = '';
                            if ($d) {
                                $codigo = $d['cuenta_contable_codigo'] ?? '';
                                $nombre = $d['cuenta_nombre'] ?? '';
                                $cuentaLabel = $codigo ? $codigo . ' - ' . $nombre : $nombre;
                            }
                        ?>
                        <tr>
                            <td><?= View::e($cuentaLabel) ?></td>
                            <td><?= $d ? View::e($d['centro_nombre'] ?? '') : '' ?></td>
                            <td><?= $d ? View::e($d['ubicacion_nombre'] ?? '') : '' ?></td>
                            <td><?= $d ? View::e($d['unidad_negocio_nombre'] ?? '') : '' ?></td>
                            <td class="text-center"><?= $d ? number_format((float)$d['porcentaje'], 2) : '' ?></td>
                            <td class="text-right"><?= $d ? number_format((float)$d['cantidad'], 2) : '' ?></td>
                            <td class="text-center"><?= $d ? View::e($d['factura'] ?? '') : '' ?></td>
                        </tr>
                        <?php endfor; ?>
                        <tr class="total-row">
                            <td colspan="4" class="text-right">Total</td>
                            <td class="text-center"><?= number_format($totalDistPct, 2) ?>%</td>
                            <td class="text-right"><?= number_format($totalDistMonto, 2) ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- ===== FORMA DE PAGO + FACTURAS ===== -->
    <table style="width:100%; border-collapse:collapse; margin-top:3px;">
        <tr>
            <td style="border:none; padding:0; width:58%; vertical-align:top; padding-right:4px;">
                <div class="field-row">
                    <span class="field-label">Forma de Pago</span>
                    <span class="field-input"><?= View::e($orden->forma_pago ?? '') ?></span>
                    &nbsp;&nbsp;
                    <span class="field-label">Anticipo:</span>
                    <span class="field-input" style="min-width:50px"><?= $orden->anticipo ? $simbolo . ' ' . number_format((float)$orden->anticipo, 2) : '' ?></span>
                </div>
            </td>
            <td style="border:none; padding:0; width:42%; vertical-align:top;">
                <table class="facturas-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Facturas</th>
                            <th>%</th>
                            <th>Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ([1,2,3] as $fk): ?>
                        <tr>
                            <td><?= $fk ?></td>
                            <td><?= $facturas[$fk]['pct'] > 0 ? number_format($facturas[$fk]['pct'], 0) : '0' ?></td>
                            <td><?= $facturas[$fk]['monto'] > 0 ? $simbolo . ' ' . number_format($facturas[$fk]['monto'], 2) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td>Total</td>
                            <td><?= number_format($totalDistPct, 2) ?>%</td>
                            <td><?= $totalDistMonto > 0 ? $simbolo . ' ' . number_format($totalDistMonto, 2) : '-' ?></td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- ===== ESPECIFICACIONES ===== -->
    <div class="field-row" style="margin-top:4px;"><strong>Especificaciones y Datos del Proveedor</strong></div>
    <div class="text-area-box"><?= View::e($orden->observaciones ?? '') ?></div>

    <div class="field-row"><strong>Razón de selección de cotización:</strong></div>
    <div class="text-area-box"><?= View::e($orden->justificacion ?? '') ?></div>

    <!-- ===== FIRMAS ===== -->
    <table class="signatures">
        <tr>
            <td style="width:50%">
                <span class="field-label">Elaborado por:</span>
                <span class="sig-line"><?= View::e($orden->usuario_nombre ?? '') ?></span>
            </td>
            <td style="width:50%"></td>
        </tr>
        <tr>
            <td>
                <span class="field-label">Director Unidad Requirente</span>
                <span class="sig-line"></span>
            </td>
            <td></td>
        </tr>
    </table>

</div><!-- /page -->
</body>
</html>
