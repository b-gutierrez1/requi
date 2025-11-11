<?php
/**
 * Diagnóstico específico para la requisición 33
 * Para entender por qué se marcó como autorizada con solo una autorización
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\OrdenCompra;
use App\Models\AutorizacionFlujo;
use App\Models\AutorizacionCentroCosto;
use App\Models\DistribucionGastos;

$ordenId = 33;

echo "=== DIAGNÓSTICO REQUISICIÓN #$ordenId ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Información básica de la orden
    echo "1. INFORMACIÓN BÁSICA:\n";
    $orden = OrdenCompra::find($ordenId);
    if (!$orden) {
        echo "❌ Orden #$ordenId no encontrada\n";
        exit(1);
    }
    
    $ordenData = is_object($orden) ? $orden->toArray() : $orden;
    echo "   - ID: {$ordenData['id']}\n";
    echo "   - Proveedor: {$ordenData['nombre_razon_social']}\n";
    echo "   - Monto Total: Q " . number_format($ordenData['monto_total'], 2) . "\n";
    echo "   - Usuario: {$ordenData['usuario_id']}\n";
    echo "   - Fecha: {$ordenData['fecha']}\n\n";

    // 2. Flujo de autorización
    echo "2. FLUJO DE AUTORIZACIÓN:\n";
    $flujo = AutorizacionFlujo::porOrdenCompra($ordenId);
    if (!$flujo) {
        echo "❌ No se encontró flujo de autorización\n";
        exit(1);
    }
    
    echo "   - Flujo ID: {$flujo['id']}\n";
    echo "   - Estado: {$flujo['estado']}\n";
    echo "   - Fecha Creación: {$flujo['fecha_creacion']}\n";
    echo "   - Fecha Completado: " . ($flujo['fecha_completado'] ?? 'N/A') . "\n\n";

    // 3. Distribución de gastos (centros de costo involucrados)
    echo "3. DISTRIBUCIÓN DE GASTOS:\n";
    $conn = OrdenCompra::getConnection();
    $stmt = $conn->prepare("
        SELECT 
            dg.*,
            cc.nombre as centro_nombre
        FROM distribucion_gasto dg
        LEFT JOIN centro_de_costo cc ON dg.centro_costo_id = cc.id
        WHERE dg.orden_compra_id = ?
        ORDER BY dg.id
    ");
    $stmt->execute([$ordenId]);
    $distribucion = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($distribucion)) {
        echo "   ❌ No se encontró distribución de gastos\n";
    } else {
        foreach ($distribucion as $dist) {
            echo "   - Centro: {$dist['centro_nombre']} (ID: {$dist['centro_costo_id']})\n";
            echo "     Porcentaje: {$dist['porcentaje']}%\n";
            echo "     Cantidad: Q " . number_format($dist['cantidad'], 2) . "\n";
        }
    }
    echo "\n";

    // 4. Autorizaciones por centro de costo
    echo "4. AUTORIZACIONES POR CENTRO DE COSTO:\n";
    $autorizaciones = AutorizacionCentroCosto::porFlujo($flujo['id']);
    
    if (empty($autorizaciones)) {
        echo "   ❌ No se encontraron autorizaciones de centro de costo\n";
    } else {
        $totalAutorizaciones = count($autorizaciones);
        $pendientes = 0;
        $autorizadas = 0;
        $rechazadas = 0;
        
        foreach ($autorizaciones as $auth) {
            echo "   - ACC ID: {$auth['id']}\n";
            echo "     Centro: {$auth['centro_costo_id']}\n";
            echo "     Autorizador: {$auth['autorizador_email']}\n";
            echo "     Estado: {$auth['estado']}\n";
            echo "     Porcentaje: {$auth['porcentaje']}%\n";
            echo "     Fecha Autorización: " . ($auth['autorizador_fecha'] ?? 'N/A') . "\n";
            echo "     Comentario: " . ($auth['autorizador_comentario'] ?? 'N/A') . "\n";
            echo "\n";
            
            switch ($auth['estado']) {
                case 'pendiente':
                    $pendientes++;
                    break;
                case 'autorizado':
                    $autorizadas++;
                    break;
                case 'rechazado':
                    $rechazadas++;
                    break;
            }
        }
        
        echo "   RESUMEN:\n";
        echo "   - Total: $totalAutorizaciones\n";
        echo "   - Pendientes: $pendientes\n";
        echo "   - Autorizadas: $autorizadas\n";
        echo "   - Rechazadas: $rechazadas\n\n";
    }

    // 5. Verificar lógica de "todasAutorizadas"
    echo "5. VERIFICACIÓN DE LÓGICA:\n";
    $todasAutorizadas = AutorizacionCentroCosto::todasAutorizadas($flujo['id']);
    echo "   - todasAutorizadas(): " . ($todasAutorizadas ? 'SÍ' : 'NO') . "\n";
    
    $algunaRechazada = AutorizacionCentroCosto::algunaRechazada($flujo['id']);
    echo "   - algunaRechazada(): " . ($algunaRechazada ? 'SÍ' : 'NO') . "\n";
    
    // Ejecutar la misma consulta que usa todasAutorizadas para debug
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado = 'autorizado' THEN 1 ELSE 0 END) as autorizadas,
            SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as rechazadas
        FROM autorizacion_centro_costo
        WHERE autorizacion_flujo_id = ?
    ");
    $stmt->execute([$flujo['id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n   CONSULTA DIRECTA:\n";
    echo "   - Total filas: {$stats['total']}\n";
    echo "   - Pendientes: {$stats['pendientes']}\n";
    echo "   - Autorizadas: {$stats['autorizadas']}\n";
    echo "   - Rechazadas: {$stats['rechazadas']}\n";
    
    $condicion1 = $stats['total'] > 0;
    $condicion2 = $stats['pendientes'] == 0;
    $condicion3 = $stats['autorizadas'] == $stats['total'];
    
    echo "\n   CONDICIONES PARA 'TODAS AUTORIZADAS':\n";
    echo "   - Total > 0: " . ($condicion1 ? 'SÍ' : 'NO') . "\n";
    echo "   - Pendientes == 0: " . ($condicion2 ? 'SÍ' : 'NO') . "\n";
    echo "   - Autorizadas == Total: " . ($condicion3 ? 'SÍ' : 'NO') . "\n";
    echo "   - RESULTADO: " . ($condicion1 && $condicion2 && $condicion3 ? 'TODAS AUTORIZADAS' : 'NO TODAS AUTORIZADAS') . "\n\n";

    // 6. Historial de cambios
    echo "6. HISTORIAL DE CAMBIOS:\n";
    $stmt = $conn->prepare("
        SELECT *
        FROM historial_requisicion
        WHERE orden_compra_id = ?
        ORDER BY fecha DESC
        LIMIT 10
    ");
    $stmt->execute([$ordenId]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($historial)) {
        echo "   ❌ No se encontró historial\n";
    } else {
        foreach ($historial as $evento) {
            echo "   - {$evento['fecha']}: {$evento['tipo_evento']}\n";
            echo "     Descripción: {$evento['descripcion']}\n";
            echo "     Usuario: {$evento['usuario_id']}\n\n";
        }
    }

    // 7. Conclusión
    echo "7. CONCLUSIÓN:\n";
    if ($flujo['estado'] === 'autorizado' && $stats['pendientes'] > 0) {
        echo "   🚨 PROBLEMA DETECTADO: El flujo está marcado como 'autorizado' pero aún hay {$stats['pendientes']} autorización(es) pendiente(s)\n";
        echo "   📋 ACCIÓN REQUERIDA: Revisar la lógica de verificación del flujo\n";
    } elseif ($flujo['estado'] === 'autorizado' && $stats['autorizadas'] < $stats['total']) {
        echo "   🚨 PROBLEMA DETECTADO: El flujo está marcado como 'autorizado' pero solo {$stats['autorizadas']} de {$stats['total']} están autorizadas\n";
        echo "   📋 ACCIÓN REQUERIDA: Revisar la lógica de verificación del flujo\n";
    } else {
        echo "   ✅ El estado del flujo parece consistente con las autorizaciones\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
