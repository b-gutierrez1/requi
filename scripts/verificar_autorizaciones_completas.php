<?php
/**
 * Script para verificar que todas las requisiciones tengan 
 * autorizaciones completas según su distribución de gastos
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\OrdenCompra;
use App\Models\AutorizacionFlujo;
use App\Models\AutorizacionCentroCosto;
use App\Models\DistribucionGasto;

echo "=== VERIFICACIÓN DE AUTORIZACIONES COMPLETAS ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Obtener todos los flujos en estado pendiente_autorizacion o autorizado
    $conn = AutorizacionFlujo::getConnection();
    $stmt = $conn->query("
        SELECT af.*, oc.id as orden_id, oc.nombre_razon_social
        FROM autorizacion_flujo af
        INNER JOIN orden_compra oc ON af.orden_compra_id = oc.id
        WHERE af.estado IN ('pendiente_autorizacion', 'autorizado')
        ORDER BY af.id DESC
        LIMIT 20
    ");
    
    $flujos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Verificando " . count($flujos) . " flujos recientes...\n\n";
    
    $problemasEncontrados = 0;
    
    foreach ($flujos as $flujo) {
        $flujoId = $flujo['id'];
        $ordenId = $flujo['orden_id'];
        $estado = $flujo['estado'];
        
        echo "Flujo $flujoId (OC $ordenId) - Estado: $estado\n";
        
        // Obtener distribución de gastos
        $distribuciones = DistribucionGasto::porOrdenCompra($ordenId);
        $centrosEsperados = array_unique(array_column($distribuciones, 'centro_costo_id'));
        
        // Obtener autorizaciones existentes
        $autorizaciones = AutorizacionCentroCosto::porFlujo($flujoId);
        $centrosConAutorizacion = array_column($autorizaciones, 'centro_costo_id');
        
        // Verificar completitud
        $centrosFaltantes = array_diff($centrosEsperados, $centrosConAutorizacion);
        
        echo "  Centros esperados: " . implode(', ', $centrosEsperados) . "\n";
        echo "  Centros con autorización: " . implode(', ', $centrosConAutorizacion) . "\n";
        
        if (!empty($centrosFaltantes)) {
            echo "  ❌ PROBLEMA: Faltan autorizaciones para centros: " . implode(', ', $centrosFaltantes) . "\n";
            $problemasEncontrados++;
        } else {
            // Verificar consistencia del estado
            $pendientes = 0;
            $autorizadas = 0;
            
            foreach ($autorizaciones as $auth) {
                if ($auth['estado'] === 'pendiente') $pendientes++;
                if ($auth['estado'] === 'autorizado') $autorizadas++;
            }
            
            if ($estado === 'autorizado' && $pendientes > 0) {
                echo "  ❌ PROBLEMA: Estado 'autorizado' pero hay $pendientes pendientes\n";
                $problemasEncontrados++;
            } elseif ($estado === 'pendiente_autorizacion' && $pendientes === 0) {
                echo "  ⚠️  ADVERTENCIA: Estado 'pendiente_autorizacion' pero no hay pendientes\n";
            } else {
                echo "  ✅ OK\n";
            }
        }
        
        echo "\n";
    }
    
    echo "=== RESUMEN ===\n";
    echo "Flujos verificados: " . count($flujos) . "\n";
    echo "Problemas encontrados: $problemasEncontrados\n";
    
    if ($problemasEncontrados > 0) {
        echo "\n🔧 RECOMENDACIÓN: Ejecutar scripts de corrección para los flujos problemáticos\n";
    } else {
        echo "\n✅ Todos los flujos verificados están correctos\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}






