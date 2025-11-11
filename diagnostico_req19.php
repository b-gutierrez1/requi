<?php
/**
 * Script de diagnóstico para Requisición #19
 * Ejecuta todas las consultas de diagnóstico y muestra los resultados
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Helpers\Config;

// Configuración de base de datos
$dbConfig = Config::get('database.connections.mysql');
$host = $dbConfig['host'] ?? 'localhost';
$dbname = $dbConfig['database'] ?? 'bd_prueba';
$username = $dbConfig['username'] ?? 'root';
$password = $dbConfig['password'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "=== DIAGNÓSTICO COMPLETO DE REQUISICIÓN #19 ===\n\n";

    // 1. Estructura de tablas
    echo "1. ESTRUCTURA DE TABLAS\n";
    echo str_repeat("=", 60) . "\n\n";
    
    echo "Estructura de autorizacion_centro_costo:\n";
    $stmt = $pdo->query("DESCRIBE autorizacion_centro_costo");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";

    echo "Estructura de autorizacion_flujo:\n";
    $stmt = $pdo->query("DESCRIBE autorizacion_flujo");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";

    // 2. Estado de la orden
    echo "\n2. ESTADO DE LA ORDEN DE COMPRA\n";
    echo str_repeat("=", 60) . "\n";
    $stmt = $pdo->prepare("SELECT id, nombre_razon_social, monto_total, fecha, usuario_id, forma_pago FROM orden_compra WHERE id = 19");
    $stmt->execute();
    $orden = $stmt->fetch();
    if ($orden) {
        foreach ($orden as $key => $value) {
            echo "  $key: $value\n";
        }
    } else {
        echo "  ✗ Orden #19 no encontrada\n";
    }
    echo "\n";

    // 3. Estado del flujo
    echo "\n3. ESTADO DEL FLUJO DE AUTORIZACIÓN\n";
    echo str_repeat("=", 60) . "\n";
    $stmt = $pdo->prepare("SELECT id, orden_compra_id, estado, fecha_creacion, fecha_completado, revisor_email, monto_total FROM autorizacion_flujo WHERE orden_compra_id = 19 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $flujo = $stmt->fetch();
    if ($flujo) {
        foreach ($flujo as $key => $value) {
            echo "  $key: $value\n";
        }
        $flujoId = $flujo['id'];
    } else {
        echo "  ✗ No se encontró flujo para orden #19\n";
        exit;
    }
    echo "\n";

    // 4. Estado de autorizaciones
    echo "\n4. ESTADO DE AUTORIZACIONES DE CENTRO DE COSTO\n";
    echo str_repeat("=", 60) . "\n";
    $stmt = $pdo->prepare("
        SELECT 
            acc.id,
            acc.autorizacion_flujo_id,
            acc.centro_costo_id,
            cc.nombre as centro_nombre,
            acc.autorizador_email,
            acc.estado as estado_autorizacion,
            acc.porcentaje,
            acc.autorizador_fecha,
            acc.autorizador_comentario
        FROM autorizacion_centro_costo acc
        INNER JOIN centro_de_costo cc ON acc.centro_costo_id = cc.id
        WHERE acc.autorizacion_flujo_id = ?
        ORDER BY acc.id
    ");
    $stmt->execute([$flujoId]);
    $autorizaciones = $stmt->fetchAll();
    
    if (empty($autorizaciones)) {
        echo "  ✗ No se encontraron autorizaciones\n";
    } else {
        foreach ($autorizaciones as $auth) {
            echo "\n  Autorización ID: {$auth['id']}\n";
            echo "    Centro: {$auth['centro_nombre']}\n";
            echo "    Autorizador: {$auth['autorizador_email']}\n";
            echo "    Estado: {$auth['estado_autorizacion']}\n";
            echo "    Porcentaje: {$auth['porcentaje']}%\n";
            echo "    Fecha: " . ($auth['autorizador_fecha'] ?? 'N/A') . "\n";
        }
    }
    echo "\n";

    // 5. Verificación de todas autorizadas
    echo "\n5. VERIFICACIÓN DE ESTADO\n";
    echo str_repeat("=", 60) . "\n";
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado = 'autorizado' THEN 1 ELSE 0 END) as autorizadas,
            SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as rechazadas
        FROM autorizacion_centro_costo
        WHERE autorizacion_flujo_id = ?
    ");
    $stmt->execute([$flujoId]);
    $resumen = $stmt->fetch();
    
    echo "  Total: {$resumen['total']}\n";
    echo "  Pendientes: {$resumen['pendientes']}\n";
    echo "  Autorizadas: {$resumen['autorizadas']}\n";
    echo "  Rechazadas: {$resumen['rechazadas']}\n";
    echo "\n";
    
    $todasAutorizadas = ($resumen['total'] > 0 && $resumen['pendientes'] == 0 && $resumen['autorizadas'] > 0);
    echo "  Resultado: " . ($todasAutorizadas ? "✓ TODAS AUTORIZADAS" : "✗ AÚN HAY PENDIENTES") . "\n";
    echo "\n";

    // 6. Diagnóstico de inconsistencias
    echo "\n6. DIAGNÓSTICO DE INCONSISTENCIAS\n";
    echo str_repeat("=", 60) . "\n";
    $estadoFlujo = $flujo['estado'];
    
    if ($estadoFlujo === 'pendiente_autorizacion' && $todasAutorizadas) {
        echo "  ⚠ INCONSISTENCIA DETECTADA:\n";
        echo "     - El flujo dice: 'pendiente_autorizacion'\n";
        echo "     - Pero todas las autorizaciones están: 'autorizado'\n";
        echo "     - El flujo DEBERÍA estar en estado: 'autorizado'\n";
        echo "\n";
        echo "  SOLUCIÓN: Ejecutar UPDATE para corregir el estado\n";
        echo "    UPDATE autorizacion_flujo \n";
        echo "    SET estado = 'autorizado', fecha_completado = NOW()\n";
        echo "    WHERE id = $flujoId;\n";
    } elseif ($estadoFlujo === 'autorizado' && $resumen['pendientes'] > 0) {
        echo "  ⚠ INCONSISTENCIA DETECTADA:\n";
        echo "     - El flujo dice: 'autorizado'\n";
        echo "     - Pero hay {$resumen['pendientes']} autorizaciones pendientes\n";
    } else {
        echo "  ✓ Estado consistente\n";
    }
    echo "\n";

    // 7. Historial reciente
    echo "\n7. HISTORIAL RECIENTE\n";
    echo str_repeat("=", 60) . "\n";
    $stmt = $pdo->prepare("SELECT tipo_evento, fecha, descripcion FROM historial_requisicion WHERE orden_compra_id = 19 ORDER BY fecha DESC LIMIT 5");
    $stmt->execute();
    $historial = $stmt->fetchAll();
    foreach ($historial as $h) {
        echo "  [{$h['fecha']}] {$h['tipo_evento']}: {$h['descripcion']}\n";
    }
    echo "\n";

    echo "\n=== FIN DEL DIAGNÓSTICO ===\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}







