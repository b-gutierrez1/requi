<?php
use App\Helpers\View;

$moneda     = $orden->moneda ?? 'GTQ';
$simbolo    = $moneda === 'USD' ? '$' : ($moneda === 'EUR' ? '€' : 'Q');
$distribucion = $distribucion ?? [];
$directorUnidad = $director_unidad ?? '';
$autorizacionesAprobadas = $autorizaciones_aprobadas ?? [];
$items        = $items ?? [];

$labelTipo = [
    'revision'        => 'Revisión',
    'forma_pago'      => 'Forma de Pago',
    'cuenta_contable' => 'Cuenta Contable',
    'centro_costo'    => 'Centro de Costo',
];

$labelFormaPago = [
    'contado'                    => 'Contado',
    'tarjeta_credito_lic_milton' => 'Tarjeta de Crédito (Lic. Milton)',
    'cheque'                     => 'Cheque',
    'transferencia'              => 'Transferencia',
    'credito'                    => 'Crédito',
];
$formaPagoLabel = $labelFormaPago[$orden->forma_pago ?? ''] ?? ($orden->forma_pago ?? '');

$itemRows = max(15, count($items));
$distRows = max(14, count($distribucion));

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

$navy   = '#111111';
$blue   = '#111111';
$light  = '#f0f0f0';
$border = '#c0c0c0';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Requisición #<?= $orden->id ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 8.5px;
            background: #f5f7fa;
            color: #1a1a2e;
        }

        .page {
            width: 210mm;
            margin: 0 auto;
            background: #fff;
            padding: 7mm 9mm;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
        }

        /* ── HEADER ── */
        .doc-header {
            display: flex;
            align-items: stretch;
            border: 1.5px solid <?= $navy ?>;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 6px;
        }
        .logo-block {
            background: #fff;
            width: 90px;
            min-width: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            border-right: 1.5px solid <?= $navy ?>;
        }
        .logo-block img {
            width: 78px;
            height: auto;
            display: block;
        }
        .header-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .doc-title {
            background: <?= $light ?>;
            border-bottom: 1px solid <?= $border ?>;
            text-align: center;
            padding: 5px 8px;
            font-size: 9.5px;
            font-weight: 700;
            color: <?= $navy ?>;
            letter-spacing: 0.4px;
        }
        .doc-meta {
            display: flex;
            flex: 1;
        }
        .doc-meta-item {
            flex: 1;
            padding: 4px 8px;
            border-right: 1px solid <?= $border ?>;
            font-size: 8px;
        }
        .doc-meta-item:last-child { border-right: none; }
        .meta-lbl { color: <?= $blue ?>; font-weight: 700; display: block; font-size: 7px; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 1px; }
        .meta-val { color: #111; border-bottom: 1px solid #aab; display: inline-block; min-width: 70px; padding-bottom: 1px; }

        /* ── SECTION HEADER ── */
        .sec-hdr {
            background: <?= $navy ?>;
            color: #fff;
            font-weight: 700;
            text-align: center;
            padding: 3px 6px;
            font-size: 8px;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        /* ── COTIZACION META ── */
        .cot-meta {
            width: 100%;
            border-collapse: collapse;
        }
        .cot-meta td {
            border: 1px solid <?= $border ?>;
            padding: 3px 7px;
            font-size: 8px;
            background: <?= $light ?>;
        }
        .cot-lbl { color: <?= $blue ?>; font-weight: 700; }
        .cot-val { border-bottom: 1px solid #99a; display: inline-block; min-width: 130px; padding-bottom: 1px; }

        /* ── DATA TABLES ── */
        .dt {
            width: 100%;
            border-collapse: collapse;
        }
        .dt th {
            background: <?= $navy ?>;
            color: #fff;
            font-size: 7.5px;
            font-weight: 600;
            text-align: center;
            padding: 3px 4px;
            border: 1px solid <?= $navy ?>;
            letter-spacing: 0.2px;
        }
        .dt td {
            font-size: 8px;
            padding: 2px 4px;
            height: 13px;
            border: 1px solid <?= $border ?>;
            color: #222;
        }
        .dt tbody tr:nth-child(even) td { background: #f7f9fc; }
        .dt .tr-total td {
            background: #e8e8e8;
            color: #111;
            font-weight: 700;
            text-align: center;
            border-color: #bbb;
        }
        .tr { text-align: right; }
        .tc { text-align: center; }

        /* ── FACTURAS TABLE ── */
        .fact-tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        .fact-tbl th {
            background: #111;
            color: #fff;
            padding: 3px 4px;
            text-align: center;
            border: 1px solid #111;
            font-size: 7.5px;
        }
        .fact-tbl td {
            border: 1px solid <?= $border ?>;
            padding: 2px 4px;
            text-align: center;
        }
        .fact-tbl tbody tr:nth-child(even) td { background: #f7f9fc; }
        .fact-tbl .tr-total td {
            background: #e8e8e8;
            color: #111;
            font-weight: 700;
            border-color: #bbb;
        }

        /* ── PAGO / OBSERVACIONES ── */
        .field-row { margin: 3px 0; font-size: 8px; }
        .f-lbl { color: <?= $blue ?>; font-weight: 700; }
        .f-val {
            display: inline-block;
            border-bottom: 1px solid #99a;
            min-width: 100px;
            padding-bottom: 1px;
        }
        .obs-box {
            border: 1px solid <?= $border ?>;
            border-radius: 2px;
            width: 100%;
            min-height: 20px;
            padding: 2px 5px;
            margin: 2px 0 4px;
            font-size: 8px;
            color: #333;
            background: #fafcff;
        }
        .obs-lbl {
            font-size: 7.5px;
            font-weight: 700;
            color: <?= $navy ?>;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-top: 3px;
        }

        /* ── FIRMAS ── */
        .sig-section {
            margin-top: 7px;
            display: flex;
            gap: 14px;
            align-items: flex-start;
        }
        .sig-left { flex: 1; }
        .sig-right { flex: 1; }
        .sig-row { margin-bottom: 6px; font-size: 8px; }
        .sig-lbl { color: <?= $blue ?>; font-weight: 700; display: block; font-size: 7px; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 2px; }
        .sig-line {
            display: block;
            border-bottom: 1px solid #333;
            min-width: 140px;
            height: 12px;
            font-size: 8.5px;
            color: #111;
        }

        /* ── AUTORIZACIONES BOX ── */
        .auth-box {
            border: 1.5px solid <?= $border ?>;
            border-radius: 4px;
            overflow: hidden;
        }
        .auth-box-hdr {
            background: <?= $navy ?>;
            color: #fff;
            padding: 3px 8px;
            font-size: 7px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .auth-tbl { width: 100%; border-collapse: collapse; }
        .auth-tbl th {
            background: <?= $light ?>;
            color: <?= $navy ?>;
            font-size: 7px;
            font-weight: 700;
            padding: 2px 5px;
            text-align: left;
            border-bottom: 1px solid <?= $border ?>;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .auth-tbl td {
            font-size: 7.5px;
            padding: 2px 5px;
            border-bottom: 1px solid #e8edf3;
            color: #222;
        }
        .auth-tbl tr:nth-child(even) td { background: #f7f9fc; }
        .auth-tbl tr:last-child td { border-bottom: none; }
        .auth-tipo { color: <?= $navy ?>; font-weight: 600; }
        .auth-fecha { color: #6b7280; text-align: center; white-space: nowrap; }

        /* ── PRINT BUTTON ── */
        .print-btn {
            position: fixed;
            top: 12px; right: 14px;
            background: <?= $navy ?>;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            z-index: 9999;
            letter-spacing: 0.3px;
        }
        .print-btn:hover { background: #162d4a; }
        .no-print { display: block; }

        @page { size: A4; margin: 1.2cm; }
        @media print {
            body { background: #fff; font-size: 8px; }
            .no-print { display: none !important; }
            .page { box-shadow: none; padding: 0; width: 100%; }
        }
    </style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">🖨 Imprimir</button>


<div class="page">

    <!-- ═══ HEADER ═══ -->
    <div class="doc-header">
        <div class="logo-block">
            <img src="<?= View::asset('img/logo.png') ?>" alt="IGA">
        </div>
        <div class="header-main">
            <div class="doc-title">REQUISICIÓN PARA COMPRA DE BIENES Y CONTRATACIÓN DE SERVICIOS</div>
            <div class="doc-meta">
                <div class="doc-meta-item">
                    <span class="meta-lbl">Unidad Requirente</span>
                    <span class="meta-val"><?= View::e($orden->unidad_requirente_nombre ?? $orden->unidad_requirente ?? '') ?></span>
                </div>
                <div class="doc-meta-item">
                    <span class="meta-lbl">Fecha</span>
                    <span class="meta-val"><?= View::e($orden->fecha_solicitud ?? '') ?></span>
                </div>
                <div class="doc-meta-item">
                    <span class="meta-lbl">Causal de Compra</span>
                    <span class="meta-val"><?= View::e($orden->causal_compra ?? '') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ COTIZACION ═══ -->
    <div class="sec-hdr">Datos de Cotización Seleccionada</div>
    <table class="cot-meta">
        <tr>
            <td style="width:60%">
                <span class="cot-lbl">Nombre o Razón Social:</span>&nbsp;
                <span class="cot-val"><?= View::e($orden->proveedor_nombre ?? '') ?></span>
            </td>
            <td>
                <span class="cot-lbl">Moneda:</span>&nbsp;
                <span class="cot-val" style="min-width:50px"><?= View::e($moneda) ?></span>
            </td>
        </tr>
    </table>

    <!-- Items -->
    <table class="dt" style="margin-top:1px;">
        <thead>
            <tr>
                <th style="width:50px">Cantidad</th>
                <th>Descripción</th>
                <th style="width:80px">Precio Unitario</th>
                <th style="width:80px">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php for ($i = 0; $i < $itemRows; $i++):
                $it = $items[$i] ?? null;
            ?>
            <tr>
                <td class="tc"><?= $it ? number_format((float)$it['cantidad'], 0) : '' ?></td>
                <td><?= $it ? View::e($it['descripcion']) : '' ?></td>
                <td class="tr"><?= $it ? $simbolo . ' ' . number_format((float)$it['precio_unitario'], 2) : '' ?></td>
                <td class="tr"><?= $it ? $simbolo . ' ' . number_format((float)$it['total'], 2) : '' ?></td>
            </tr>
            <?php endfor; ?>
            <tr class="tr-total">
                <td colspan="3" style="text-align:right; padding-right:6px;">TOTAL</td>
                <td class="tr"><?= number_format($totalItems, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- ═══ DISTRIBUCION ═══ -->
    <div class="sec-hdr" style="margin-top:5px;">Distribución del Gasto</div>
    <table class="dt" style="margin-top:1px;">
        <thead>
            <tr>
                <th>Cuenta Contable</th>
                <th>Centro de Costo</th>
                <th>Ubicación</th>
                <th>Unidad de Negocio</th>
                <th style="width:36px">%</th>
                <th style="width:62px">Cantidad</th>
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
                <td class="tc"><?= $d ? number_format((float)$d['porcentaje'], 2) : '' ?></td>
                <td class="tr"><?= $d ? number_format((float)$d['cantidad'], 2) : '' ?></td>
                <td class="tc"><?= $d ? View::e($d['factura'] ?? '') : '' ?></td>
            </tr>
            <?php endfor; ?>
            <tr class="tr-total">
                <td colspan="4" style="text-align:right; padding-right:6px;">Total</td>
                <td class="tc"><?= number_format($totalDistPct, 2) ?>%</td>
                <td class="tr"><?= number_format($totalDistMonto, 2) ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- ═══ PAGO + FACTURAS ═══ -->
    <table style="width:100%; border-collapse:collapse; margin-top:4px;">
        <tr>
            <td style="width:56%; vertical-align:middle; border:none; padding-right:8px;">
                <span class="f-lbl">Forma de Pago</span>&nbsp;
                <span class="f-val"><?= View::e($formaPagoLabel) ?></span>
                &nbsp;&nbsp;&nbsp;
                <span class="f-lbl">Anticipo:</span>&nbsp;
                <span class="f-val" style="min-width:55px"><?= ($orden->anticipo && (float)$orden->anticipo > 0) ? 'Sí' : 'No' ?></span>
            </td>
            <td style="width:44%; vertical-align:top; border:none;">
                <table class="fact-tbl">
                    <thead>
                        <tr>
                            <th>Factura</th>
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
                        <tr class="tr-total">
                            <td>Total</td>
                            <td><?= number_format($totalDistPct, 2) ?>%</td>
                            <td><?= $totalDistMonto > 0 ? $simbolo . ' ' . number_format($totalDistMonto, 2) : '-' ?></td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- ═══ OBSERVACIONES ═══ -->
    <p class="obs-lbl" style="margin-top:5px;">Especificaciones y Datos del Proveedor</p>
    <div class="obs-box"><?= View::e($orden->observaciones ?? '') ?></div>

    <p class="obs-lbl">Razón de selección de cotización</p>
    <div class="obs-box"><?= View::e($orden->justificacion ?? '') ?></div>

    <!-- ═══ FIRMAS + AUTORIZACIONES ═══ -->
    <div class="sig-section">
        <div class="sig-left">
            <div class="sig-row">
                <span class="sig-lbl">Elaborado por</span>
                <span class="sig-line"><?= View::e($orden->usuario_nombre ?? '') ?></span>
            </div>
            <div class="sig-row">
                <span class="sig-lbl">Director Unidad Requirente</span>
                <span class="sig-line"><?= View::e($directorUnidad) ?></span>
            </div>
        </div>

        <div class="sig-right">
            <div class="auth-box">
                <div class="auth-box-hdr">Autorizaciones</div>
                <table class="auth-tbl">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Autorizador</th>
                            <th style="text-align:center;">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($autorizacionesAprobadas as $aut): ?>
                        <tr>
                            <td class="auth-tipo"><?= View::e($labelTipo[$aut['tipo']] ?? $aut['tipo']) ?></td>
                            <td>
                                <?= View::e($aut['nombre']) ?>
                                <?php if (!empty($aut['cargo'])): ?>
                                    <br><span style="font-size:6.5px;color:#666;"><?= View::e($aut['cargo']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="auth-fecha"><?= $aut['fecha_respuesta'] ? date('d/m/Y', strtotime($aut['fecha_respuesta'])) : '' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($autorizacionesAprobadas)): ?>
                        <tr><td colspan="3" style="text-align:center; color:#999; padding:4px;">Sin autorizaciones</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /page -->
</body>
</html>
