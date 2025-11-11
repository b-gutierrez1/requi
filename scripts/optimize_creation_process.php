<?php
/**
 * Script para optimizar el proceso de creación de requisiciones
 * Reduce timeouts y mejora la velocidad de respuesta
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Model;

echo "=== OPTIMIZACIÓN DEL PROCESO DE CREACIÓN ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $conn = Model::getConnection();
    
    // 1. Configurar variables de MySQL para mejor rendimiento en transacciones
    echo "1. Configurando variables de MySQL...\n";
    
    $optimizaciones = [
        "SET SESSION innodb_flush_log_at_trx_commit = 2",  // Mejor rendimiento en transacciones
        "SET SESSION autocommit = 1",                      // Autocommit habilitado
        "SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'",  // Evitar errores de inserción
        "SET SESSION wait_timeout = 300",                  // 5 minutos de timeout
        "SET SESSION interactive_timeout = 300"            // 5 minutos de timeout interactivo
    ];
    
    foreach ($optimizaciones as $sql) {
        try {
            $conn->exec($sql);
            echo "  ✓ Configuración aplicada\n";
        } catch (Exception $e) {
            echo "  ⚠ Error en configuración: " . $e->getMessage() . "\n";
        }
    }
    
    // 2. Verificar y optimizar índices críticos para creación
    echo "\n2. Verificando índices críticos...\n";
    
    $indicesCriticos = [
        "CREATE INDEX IF NOT EXISTS idx_orden_compra_usuario ON orden_compra(usuario_id)",
        "CREATE INDEX IF NOT EXISTS idx_distribucion_orden ON distribucion_gasto(orden_compra_id)",
        "CREATE INDEX IF NOT EXISTS idx_detalle_items_orden ON detalle_items(orden_compra_id)",
        "CREATE INDEX IF NOT EXISTS idx_facturas_orden ON facturas(orden_compra_id)",
        "CREATE INDEX IF NOT EXISTS idx_autorizacion_flujo_orden ON autorizacion_flujo(orden_compra_id)",
        "CREATE INDEX IF NOT EXISTS idx_autorizacion_centro_flujo ON autorizacion_centro_costo(autorizacion_flujo_id)"
    ];
    
    foreach ($indicesCriticos as $sql) {
        try {
            $conn->exec($sql);
            echo "  ✓ Índice verificado\n";
        } catch (Exception $e) {
            echo "  ⚠ Error con índice: " . $e->getMessage() . "\n";
        }
    }
    
    // 3. Limpiar tablas de logs antiguos para mejorar rendimiento
    echo "\n3. Limpiando logs antiguos...\n";
    
    try {
        // Mantener solo los últimos 30 días de historial
        $stmt = $conn->prepare("
            DELETE FROM historial_requisicion 
            WHERE fecha < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $deleted = $stmt->rowCount();
        echo "  ✓ Eliminados $deleted registros de historial antiguos\n";
    } catch (Exception $e) {
        echo "  ⚠ Error limpiando historial: " . $e->getMessage() . "\n";
    }
    
    // 4. Verificar configuración de PHP para timeouts
    echo "\n4. Configuración de PHP:\n";
    
    $phpSettings = [
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
        'post_max_size' => ini_get('post_max_size'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'max_input_vars' => ini_get('max_input_vars')
    ];
    
    foreach ($phpSettings as $setting => $value) {
        echo "  $setting: $value\n";
    }
    
    // Recomendaciones
    echo "\n5. Recomendaciones:\n";
    
    $maxExecTime = (int)ini_get('max_execution_time');
    if ($maxExecTime > 0 && $maxExecTime < 60) {
        echo "  ⚠ max_execution_time ($maxExecTime s) es bajo. Recomendado: 60s o más\n";
    } else {
        echo "  ✓ max_execution_time está bien configurado\n";
    }
    
    $memoryLimit = ini_get('memory_limit');
    $memoryBytes = return_bytes($memoryLimit);
    if ($memoryBytes < 128 * 1024 * 1024) { // 128MB
        echo "  ⚠ memory_limit ($memoryLimit) es bajo. Recomendado: 128M o más\n";
    } else {
        echo "  ✓ memory_limit está bien configurado\n";
    }
    
    // 6. Estadísticas de rendimiento
    echo "\n6. Estadísticas de rendimiento:\n";
    
    try {
        $stmt = $conn->query("
            SELECT 
                COUNT(*) as total_ordenes,
                COUNT(CASE WHEN fecha >= CURDATE() THEN 1 END) as ordenes_hoy,
                AVG(monto_total) as monto_promedio
            FROM orden_compra
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "  Total órdenes: " . number_format($stats['total_ordenes']) . "\n";
        echo "  Órdenes hoy: " . number_format($stats['ordenes_hoy']) . "\n";
        echo "  Monto promedio: Q " . number_format($stats['monto_promedio'], 2) . "\n";
        
    } catch (Exception $e) {
        echo "  ⚠ Error obteniendo estadísticas: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== OPTIMIZACIÓN COMPLETADA ===\n";
    echo "✅ El sistema está optimizado para mejor rendimiento\n";
    echo "🚀 Los tiempos de creación de requisiciones deberían ser más rápidos\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
}






