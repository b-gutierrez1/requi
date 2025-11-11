<?php
/**
 * Script para corregir las autorizaciones faltantes de la requisición 33
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\OrdenCompra;
use App\Models\AutorizacionFlujo;
use App\Models\AutorizacionCentroCosto;
use App\Models\DistribucionGasto;
use App\Models\PersonaAutorizada;

$ordenId = 33;

echo "=== CORRECCIÓN DE AUTORIZACIONES REQUISICIÓN #$ordenId ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // 1. Obtener información básica
    $flujo = AutorizacionFlujo::porOrdenCompra($ordenId);
    if (!$flujo) {
        echo "❌ No se encontró flujo para la orden $ordenId\n";
        exit(1);
    }
    
    $flujoId = $flujo['id'];
    echo "1. Flujo encontrado: ID $flujoId, Estado: {$flujo['estado']}\n\n";
    
    // 2. Obtener distribución de gastos
    echo "2. Analizando distribución de gastos:\n";
    $distribuciones = DistribucionGasto::porOrdenCompra($ordenId);
    
    if (empty($distribuciones)) {
        echo "❌ No se encontró distribución de gastos\n";
        exit(1);
    }
    
    $centrosIds = array_unique(array_column($distribuciones, 'centro_costo_id'));
    echo "   Centros de costo involucrados: " . implode(', ', $centrosIds) . "\n";
    
    foreach ($distribuciones as $dist) {
        echo "   - Centro {$dist['centro_costo_id']}: {$dist['porcentaje']}% (Q {$dist['cantidad']})\n";
    }
    echo "\n";
    
    // 3. Verificar autorizaciones existentes
    echo "3. Autorizaciones existentes:\n";
    $autorizacionesExistentes = AutorizacionCentroCosto::porFlujo($flujoId);
    $centrosConAutorizacion = array_column($autorizacionesExistentes, 'centro_costo_id');
    
    echo "   Centros con autorización: " . implode(', ', $centrosConAutorizacion) . "\n";
    
    foreach ($autorizacionesExistentes as $auth) {
        echo "   - Centro {$auth['centro_costo_id']}: {$auth['estado']} ({$auth['autorizador_email']})\n";
    }
    echo "\n";
    
    // 4. Identificar centros faltantes
    echo "4. Centros faltantes:\n";
    $centrosFaltantes = array_diff($centrosIds, $centrosConAutorizacion);
    
    if (empty($centrosFaltantes)) {
        echo "   ✅ No hay centros faltantes\n";
    } else {
        echo "   ❌ Centros sin autorización: " . implode(', ', $centrosFaltantes) . "\n";
        
        // 5. Crear autorizaciones faltantes
        echo "\n5. Creando autorizaciones faltantes:\n";
        
        foreach ($centrosFaltantes as $centroId) {
            echo "   Procesando centro $centroId...\n";
            
            // Buscar autorizador
            $persona = PersonaAutorizada::principalPorCentro($centroId);
            
            if (!$persona) {
                echo "   ❌ No se encontró autorizador para centro $centroId\n";
                continue;
            }
            
            echo "   ✓ Autorizador encontrado: {$persona['nombre']} ({$persona['email']})\n";
            
            // Calcular porcentaje
            $porcentajeTotal = 0;
            foreach ($distribuciones as $dist) {
                if ($dist['centro_costo_id'] == $centroId) {
                    $porcentajeTotal += floatval($dist['porcentaje']);
                }
            }
            
            echo "   ✓ Porcentaje calculado: $porcentajeTotal%\n";
            
            // Crear autorización
            try {
                $autorizacion = AutorizacionCentroCosto::create([
                    'autorizacion_flujo_id' => $flujoId,
                    'centro_costo_id' => $centroId,
                    'autorizador_email' => $persona['email'],
                    'estado' => 'pendiente',
                    'porcentaje' => $porcentajeTotal
                ]);
                
                $autorizacionId = is_object($autorizacion) ? $autorizacion->id : $autorizacion['id'];
                echo "   ✅ Autorización creada con ID: $autorizacionId\n";
            } catch (Exception $e) {
                echo "   ❌ Error creando autorización: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // 6. Corregir estado del flujo
    echo "\n6. Verificando estado del flujo:\n";
    
    // Recargar autorizaciones
    $autorizacionesActualizadas = AutorizacionCentroCosto::porFlujo($flujoId);
    $totalAutorizaciones = count($autorizacionesActualizadas);
    $pendientes = 0;
    $autorizadas = 0;
    
    foreach ($autorizacionesActualizadas as $auth) {
        if ($auth['estado'] === 'pendiente') $pendientes++;
        if ($auth['estado'] === 'autorizado') $autorizadas++;
    }
    
    echo "   Total autorizaciones: $totalAutorizaciones\n";
    echo "   Pendientes: $pendientes\n";
    echo "   Autorizadas: $autorizadas\n";
    
    // Determinar estado correcto
    if ($pendientes > 0) {
        echo "   ✓ Estado correcto: pendiente_autorizacion (hay $pendientes pendientes)\n";
        
        // Actualizar estado si está incorrecto
        if ($flujo['estado'] === 'autorizado') {
            echo "   🔄 Corrigiendo estado del flujo de 'autorizado' a 'pendiente_autorizacion'\n";
            
            $conn = AutorizacionFlujo::getConnection();
            $stmt = $conn->prepare("UPDATE autorizacion_flujo SET estado = 'pendiente_autorizacion', fecha_completado = NULL WHERE id = ?");
            $result = $stmt->execute([$flujoId]);
            
            if ($result) {
                echo "   ✅ Estado del flujo corregido\n";
            } else {
                echo "   ❌ Error corrigiendo estado del flujo\n";
            }
        }
    } else {
        echo "   ✓ Estado correcto: autorizado (todas las autorizaciones están completas)\n";
    }
    
    echo "\n=== CORRECCIÓN COMPLETADA ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
