<?php
/**
 * Script para eliminar automáticamente logs de debug del código
 * 
 * USAR CON CUIDADO: Este script modifica archivos de código fuente
 * Hacer backup antes de ejecutar
 * 
 * Uso: php remove-debug-logs.php
 */

echo "===========================================\n";
echo "  REMOVEDOR DE LOGS DE DEBUG - PROD READY\n";
echo "===========================================\n\n";

// Archivos y líneas específicas a modificar
$debugLogs = [
    'app/Controllers/AutorizacionController.php' => [
        79 => 'error_log("=== DEBUG REVISIÓN ===");',
        1470 => 'error_log("=== DEBUG PERMISOS AUTORIZACIÓN ESPECIAL ===");'
    ],
    'app/Controllers/DashboardController.php' => [
        93 => 'error_log("Dashboard Debug - Usuario ID: $usuarioId");',
        94 => 'error_log("Dashboard Debug - Estadísticas: " . json_encode($estadisticas));',
        95 => 'error_log("Dashboard Debug - Requisiciones recientes: " . count($requisiciones_recientes));',
        96 => 'error_log("Dashboard Debug - Resumen mensual: " . json_encode($resumen_mensual));'
    ],
    'app/Controllers/RequisicionController.php' => [
        97 => 'error_log("DEBUG: show() llamado con ID: " . $id);'
    ],
    'app/Controllers/Controller.php' => [
        198 => 'error_log("=== SENDAJAXRESPONSE DEBUG ===");'
    ],
    'app/Controllers/AdminController.php' => [
        2579 => 'error_log("DEBUG deleteRespaldo: Intentando eliminar respaldo ID: $id");',
        2584 => 'error_log("DEBUG deleteRespaldo: Respaldo ID $id no encontrado");',
        2591 => 'error_log("DEBUG deleteRespaldo: Respaldo encontrado, intentando eliminar...");',
        2599 => 'error_log("DEBUG deleteRespaldo: Resultado de eliminación: " . ($eliminado ? \'true\' : \'false\') . ", Filas afectadas: $rowsAffected");',
        2602 => 'error_log("DEBUG deleteRespaldo: Respaldo eliminado exitosamente");',
        2607 => 'error_log("DEBUG deleteRespaldo: No se pudo eliminar el respaldo");'
    ]
];

$totalArchivos = count($debugLogs);
$archivosModificados = 0;
$lineasComentadas = 0;

echo "Archivos a procesar: $totalArchivos\n\n";

// Crear directorio de backup
$backupDir = 'deploy/backup-debug-removal';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    echo "[INFO] Directorio de backup creado: $backupDir\n\n";
}

foreach ($debugLogs as $archivo => $lineas) {
    echo "Procesando: $archivo\n";
    
    if (!file_exists($archivo)) {
        echo "  ❌ Archivo no encontrado: $archivo\n";
        continue;
    }
    
    // Crear backup del archivo
    $backupFile = $backupDir . '/' . basename($archivo) . '.backup.' . date('Y-m-d_H-i-s');
    copy($archivo, $backupFile);
    echo "  💾 Backup creado: $backupFile\n";
    
    // Leer contenido del archivo
    $contenido = file($archivo, FILE_IGNORE_NEW_LINES);
    $modificado = false;
    
    foreach ($lineas as $numeroLinea => $textoEsperado) {
        // Ajustar índice (las líneas empiezan en 1, los arrays en 0)
        $indice = $numeroLinea - 1;
        
        if (isset($contenido[$indice])) {
            $lineaActual = trim($contenido[$indice]);
            $textoEsperadoTrim = trim($textoEsperado);
            
            // Verificar si la línea contiene el debug log (puede tener espacios/indentación diferentes)
            if (strpos($lineaActual, 'error_log') !== false && 
                (strpos($lineaActual, 'DEBUG') !== false || strpos($lineaActual, 'Debug') !== false)) {
                
                // Comentar la línea manteniendo la indentación original
                $indentacion = strlen($contenido[$indice]) - strlen(ltrim($contenido[$indice]));
                $espacios = str_repeat(' ', $indentacion);
                $contenido[$indice] = $espacios . '// ' . ltrim($contenido[$indice]) . ' // DEBUG - Comentado para producción';
                
                echo "  ✅ Línea $numeroLinea comentada\n";
                $modificado = true;
                $lineasComentadas++;
            } else {
                echo "  ⚠️  Línea $numeroLinea no coincide o ya está modificada\n";
            }
        } else {
            echo "  ❌ Línea $numeroLinea no existe en el archivo\n";
        }
    }
    
    if ($modificado) {
        // Guardar el archivo modificado
        file_put_contents($archivo, implode("\n", $contenido) . "\n");
        echo "  ✅ Archivo guardado con cambios\n";
        $archivosModificados++;
    } else {
        echo "  ℹ️  No se realizaron cambios en este archivo\n";
    }
    
    echo "\n";
}

echo "===========================================\n";
echo "          RESUMEN DE CAMBIOS\n";
echo "===========================================\n";
echo "📁 Archivos procesados: $totalArchivos\n";
echo "✏️  Archivos modificados: $archivosModificados\n";
echo "💬 Líneas comentadas: $lineasComentadas\n";
echo "💾 Backups creados en: $backupDir\n";
echo "\n";

if ($archivosModificados > 0) {
    echo "✅ PROCESO COMPLETADO EXITOSAMENTE\n";
    echo "\nLos archivos han sido modificados y los logs de debug comentados.\n";
    echo "Los backups están disponibles en caso de necesitar revertir cambios.\n";
} else {
    echo "ℹ️  NO SE REALIZARON CAMBIOS\n";
    echo "Posibles razones:\n";
    echo "- Los archivos ya fueron modificados anteriormente\n";
    echo "- Las líneas de debug ya están comentadas\n";
    echo "- Los números de línea han cambiado desde la última actualización\n";
}

echo "\n===========================================\n";
echo "SIGUIENTE PASO: Verificar manualmente\n";
echo "===========================================\n";
echo "1. Revisar los archivos modificados\n";
echo "2. Probar que la aplicación sigue funcionando\n";
echo "3. Buscar logs de debug adicionales con:\n";
echo "   grep -r \"error_log.*DEBUG\" app/\n";
echo "   grep -r \"error_log.*Debug\" app/\n";
echo "\n";

echo "Presiona Enter para continuar...";
$handle = fopen("php://stdin", "r");
fgets($handle);
fclose($handle);
?>