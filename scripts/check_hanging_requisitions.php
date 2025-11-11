<?php
/**
 * Script para detectar requisiciones que se quedaron colgadas durante la creación
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\OrdenCompra;
use App\Models\AutorizacionFlujo;

echo "=== DETECCIÓN DE REQUISICIONES COLGADAS ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $conn = OrdenCompra::getConnection();
    
    // Buscar órdenes creadas recientemente (por ID) sin flujo de autorización
    $stmt = $conn->query("
        SELECT oc.*, 
               af.id as flujo_id,
               af.estado as flujo_estado,
               af.fecha_creacion as flujo_fecha_creacion
        FROM orden_compra oc
        LEFT JOIN autorizacion_flujo af ON oc.id = af.orden_compra_id
        WHERE oc.id >= (SELECT MAX(id) - 10 FROM orden_compra)
        ORDER BY oc.id DESC
        LIMIT 10
    ");
    
    $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ordenes)) {
        echo "✅ No se encontraron órdenes recientes\n";
        exit(0);
    }
    
    echo "Órdenes recientes:\n\n";
    
    $problemasEncontrados = 0;
    
    foreach ($ordenes as $orden) {
        $ordenId = $orden['id'];
        $flujoId = $orden['flujo_id'];
        $flujoEstado = $orden['flujo_estado'];
        $flujoFecha = $orden['flujo_fecha_creacion'];
        
        echo "OC #$ordenId - Fecha: {$orden['fecha']}\n";
        echo "  Proveedor: {$orden['nombre_razon_social']}\n";
        echo "  Monto: Q " . number_format($orden['monto_total'], 2) . "\n";
        
        if (!$flujoId) {
            echo "  ❌ PROBLEMA: No tiene flujo de autorización\n";
            echo "  🔧 ACCIÓN: Necesita iniciar flujo manualmente\n";
            $problemasEncontrados++;
        } else {
            echo "  ✅ Flujo: ID $flujoId, Estado: $flujoEstado\n";
        }
        
        echo "\n";
    }
    
    echo "=== RESUMEN ===\n";
    echo "Órdenes verificadas: " . count($ordenes) . "\n";
    echo "Problemas encontrados: $problemasEncontrados\n";
    
    if ($problemasEncontrados > 0) {
        echo "\n🔧 Para corregir órdenes sin flujo, ejecuta:\n";
        echo "php scripts/fix_hanging_requisitions.php\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
