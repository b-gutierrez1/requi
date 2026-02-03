<?php
use App\Helpers\View;

// Obtener la moneda de la orden
$moneda = $orden->moneda ?? 'GTQ';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir Requisición #<?php echo $orden->id; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: white;
        }
        
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .document-title {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .requisition-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .info-section {
            flex: 1;
        }
        
        .info-section h3 {
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        
        .info-item {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pendiente { background-color: #fff3cd; color: #856404; }
        .status-autorizada { background-color: #d4edda; color: #155724; }
        .status-rechazada { background-color: #f8d7da; color: #721c24; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        
        .justification {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #f8f9fa;
        }
        
        .distribution-table {
            margin-top: 20px;
        }
        
        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 200px;
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 10px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .print-container {
                margin: 0;
                padding: 15px;
                max-width: none;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Imprimir</button>
    
    <div class="print-container">
        <!-- Header -->
        <div class="header">
            <div class="company-name">SISTEMA DE REQUISICIONES</div>
            <div class="document-title">ORDEN DE COMPRA</div>
        </div>
        
        <!-- Requisition Info -->
        <div class="requisition-info">
            <div class="info-section">
                <h3>Información General</h3>
                <div class="info-item">
                    <span class="info-label">Número:</span>
                    <?php echo $orden->id; ?>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha:</span>
                    <?php echo View::formatDate($orden->fecha); ?>
                </div>
                <div class="info-item">
                    <span class="info-label">Estado:</span>
                    <?php 
                    $estadoClass = match($flujo->estado) {
                        'pendiente' => 'status-pendiente',
                        'autorizada' => 'status-autorizada',
                        'rechazada' => 'status-rechazada',
                        default => 'status-pendiente'
                    };
                    $estadoTexto = match($flujo->estado) {
                        'pendiente' => 'Pendiente',
                        'autorizada' => 'Autorizada',
                        'rechazada' => 'Rechazada',
                        default => 'Pendiente'
                    };
                    ?>
                    <span class="status-badge <?php echo $estadoClass; ?>">
                        <?php echo $estadoTexto; ?>
                    </span>
                </div>
            </div>
            
            <div class="info-section">
                <h3>Datos del Requirente</h3>
                <div class="info-item">
                    <span class="info-label">Nombre:</span>
                    <?php echo View::e($orden->nombre_razon_social); ?>
                </div>
                <div class="info-item">
                    <span class="info-label">Forma de Pago:</span>
                    <?php echo View::e(getFormaPagoLabel($orden->forma_pago ?? '')); ?>
                </div>
                <?php if (!empty($orden->referencia)): ?>
                <div class="info-item">
                    <span class="info-label">Referencia:</span>
                    <?php echo View::e($orden->referencia); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Descripción</th>
                    <th class="text-center" style="width: 80px;">Cantidad</th>
                    <th class="text-right" style="width: 100px;">Precio Unit.</th>
                    <th class="text-right" style="width: 100px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = 0;
                foreach ($items as $index => $item): 
                    $subtotal = $item['cantidad'] * $item['precio_unitario'];
                    $total += $subtotal;
                ?>
                <tr>
                    <td class="text-center"><?php echo $index + 1; ?></td>
                    <td><?php echo View::e($item['descripcion']); ?></td>
                    <td class="text-center"><?php echo number_format($item['cantidad'], 0); ?></td>
                    <td class="text-right"><?php echo View::money($item['precio_unitario'], $moneda); ?></td>
                    <td class="text-right"><?php echo View::money($subtotal, $moneda); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
                    <td class="text-right"><strong><?php echo View::money($total, $moneda); ?></strong></td>
                </tr>
            </tbody>
        </table>
        
        <!-- Justification -->
        <?php if (!empty($orden->justificacion)): ?>
        <div class="justification">
            <h3>Justificación:</h3>
            <p><?php echo nl2br(View::e($orden->justificacion)); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Distribution of Expenses -->
        <?php if (!empty($distribucion)): ?>
        <table class="distribution-table">
            <thead>
                <tr>
                    <th>Centro de Costo</th>
                    <th>Cuenta Contable</th>
                    <th class="text-center">Porcentaje</th>
                    <th class="text-right">Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($distribucion as $dist): ?>
                <tr>
                    <td><?php echo View::e($dist['centro_nombre'] ?? 'N/A'); ?></td>
                    <td><?php echo View::e($dist['cuenta_contable_descripcion'] ?? 'N/A'); ?></td>
                    <td class="text-center"><?php echo $dist['porcentaje']; ?>%</td>
                    <td class="text-right">
                        <strong><?php echo View::money($dist['monto'] ?? 0, $moneda); ?></strong>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <!-- Signatures -->
        <div class="signatures">
            <div class="signature-box">
                <strong>Solicitante</strong><br>
                <br><br>
                _________________________<br>
                Firma y Sello
            </div>
            <div class="signature-box">
                <strong>Autorizador</strong><br>
                <br><br>
                _________________________<br>
                Firma y Sello
            </div>
        </div>
        
        <!-- Footer -->
        <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
            <p>Documento generado el <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
