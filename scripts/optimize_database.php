<?php
/**
 * Script para optimizar la base de datos y mejorar el rendimiento
 * de la creación de requisiciones
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Model;

try {
    $conn = Model::getConnection();
    
    echo "=== OPTIMIZACIÓN DE BASE DE DATOS ===\n";
    echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";
    
    // 1. Crear índices para mejorar el rendimiento
    echo "1. Creando índices optimizados...\n";
    
    $indices = [
        // Índices para orden_compra
        "CREATE INDEX IF NOT EXISTS idx_orden_compra_usuario_fecha ON orden_compra(usuario_id, fecha)",
        "CREATE INDEX IF NOT EXISTS idx_orden_compra_monto ON orden_compra(monto_total)",
        
        // Índices para autorizacion_flujo
        "CREATE INDEX IF NOT EXISTS idx_autorizacion_flujo_orden ON autorizacion_flujo(orden_compra_id)",
        "CREATE INDEX IF NOT EXISTS idx_autorizacion_flujo_estado ON autorizacion_flujo(estado)",
        "CREATE INDEX IF NOT EXISTS idx_autorizacion_flujo_fecha ON autorizacion_flujo(fecha_creacion)",
        
        // Índices para autorizacion_centro_costo
        "CREATE INDEX IF NOT EXISTS idx_autorizacion_centro_flujo ON autorizacion_centro_costo(autorizacion_flujo_id)",
        "CREATE INDEX IF NOT EXISTS idx_autorizacion_centro_estado ON autorizacion_centro_costo(estado)",
        "CREATE INDEX IF NOT EXISTS idx_autorizacion_centro_email ON autorizacion_centro_costo(autorizador_email)",
        
        // Índices para detalle_items
        "CREATE INDEX IF NOT EXISTS idx_detalle_items_orden ON detalle_items(orden_compra_id)",
        
        // Índices para distribucion_gastos
        "CREATE INDEX IF NOT EXISTS idx_distribucion_gastos_orden ON distribucion_gastos(orden_compra_id)",
        "CREATE INDEX IF NOT EXISTS idx_distribucion_gastos_centro ON distribucion_gastos(centro_costo_id)",
        
        // Índices para facturas
        "CREATE INDEX IF NOT EXISTS idx_facturas_orden ON facturas(orden_compra_id)",
        
        // Índices para historial_requisicion
        "CREATE INDEX IF NOT EXISTS idx_historial_requisicion_orden ON historial_requisicion(orden_compra_id)",
        "CREATE INDEX IF NOT EXISTS idx_historial_requisicion_fecha ON historial_requisicion(fecha)",
        
        // Índices para centro_de_costo
        "CREATE INDEX IF NOT EXISTS idx_centro_costo_nombre ON centro_de_costo(nombre)",
        
        // Índices para usuarios
        "CREATE INDEX IF NOT EXISTS idx_usuarios_email ON usuarios(azure_email)",
        "CREATE INDEX IF NOT EXISTS idx_usuarios_activo ON usuarios(activo)"
    ];
    
    foreach ($indices as $sql) {
        try {
            $conn->exec($sql);
            echo "  ✓ Índice creado\n";
        } catch (Exception $e) {
            echo "  ⚠ Error creando índice: " . $e->getMessage() . "\n";
        }
    }
    
    // 2. Optimizar tablas
    echo "\n2. Optimizando tablas...\n";
    
    $tablas = [
        'orden_compra',
        'autorizacion_flujo', 
        'autorizacion_centro_costo',
        'detalle_items',
        'distribucion_gastos',
        'facturas',
        'historial_requisicion',
        'centro_de_costo',
        'usuarios',
        'autorizadores',
        'autorizador_centro_costo'
    ];
    
    foreach ($tablas as $tabla) {
        try {
            $conn->exec("OPTIMIZE TABLE $tabla");
            echo "  ✓ Tabla $tabla optimizada\n";
        } catch (Exception $e) {
            echo "  ⚠ Error optimizando $tabla: " . $e->getMessage() . "\n";
        }
    }
    
    // 3. Analizar tablas para actualizar estadísticas
    echo "\n3. Analizando tablas...\n";
    
    foreach ($tablas as $tabla) {
        try {
            $conn->exec("ANALYZE TABLE $tabla");
            echo "  ✓ Tabla $tabla analizada\n";
        } catch (Exception $e) {
            echo "  ⚠ Error analizando $tabla: " . $e->getMessage() . "\n";
        }
    }
    
    // 4. Configurar variables de MySQL para mejor rendimiento
    echo "\n4. Configurando variables de rendimiento...\n";
    
    $configuraciones = [
        "SET SESSION innodb_buffer_pool_size = 134217728",  // 128MB
        "SET SESSION query_cache_size = 67108864",          // 64MB
        "SET SESSION tmp_table_size = 67108864",            // 64MB
        "SET SESSION max_heap_table_size = 67108864"        // 64MB
    ];
    
    foreach ($configuraciones as $sql) {
        try {
            $conn->exec($sql);
            echo "  ✓ Configuración aplicada\n";
        } catch (Exception $e) {
            echo "  ⚠ Error en configuración: " . $e->getMessage() . "\n";
        }
    }
    
    // 5. Mostrar estadísticas de las tablas principales
    echo "\n5. Estadísticas de tablas:\n";
    
    $estadisticas = $conn->query("
        SELECT 
            table_name as 'Tabla',
            table_rows as 'Filas',
            ROUND(((data_length + index_length) / 1024 / 1024), 2) as 'Tamaño (MB)',
            ROUND((index_length / 1024 / 1024), 2) as 'Índices (MB)'
        FROM information_schema.tables 
        WHERE table_schema = DATABASE() 
        AND table_name IN ('" . implode("','", $tablas) . "')
        ORDER BY (data_length + index_length) DESC
    ");
    
    printf("  %-25s %10s %12s %12s\n", "Tabla", "Filas", "Tamaño(MB)", "Índices(MB)");
    printf("  %s\n", str_repeat("-", 60));
    
    while ($row = $estadisticas->fetch(PDO::FETCH_ASSOC)) {
        printf("  %-25s %10s %12s %12s\n", 
            $row['Tabla'], 
            number_format($row['Filas']), 
            $row['Tamaño (MB)'], 
            $row['Índices (MB)']
        );
    }
    
    // 6. Verificar configuración de MySQL
    echo "\n6. Configuración actual de MySQL:\n";
    
    $configs = [
        'innodb_buffer_pool_size',
        'query_cache_size',
        'tmp_table_size',
        'max_heap_table_size',
        'innodb_log_file_size'
    ];
    
    foreach ($configs as $config) {
        try {
            $result = $conn->query("SHOW VARIABLES LIKE '$config'");
            $row = $result->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $value = $row['Value'];
                if (is_numeric($value) && $value > 1024) {
                    $value = round($value / 1024 / 1024, 2) . ' MB';
                }
                echo "  $config: $value\n";
            }
        } catch (Exception $e) {
            echo "  ⚠ Error consultando $config\n";
        }
    }
    
    echo "\n=== OPTIMIZACIÓN COMPLETADA ===\n";
    echo "La base de datos ha sido optimizada para mejor rendimiento.\n";
    echo "Se recomienda ejecutar este script periódicamente.\n\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}






