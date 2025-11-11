<?php
/**
 * Script de diagnóstico para Requisición #22
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
$ordenId = 22;

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

    echo "=== DIAGNÓSTICO COMPLETO DE REQUISICIÓN #$ordenId ===\n\n";

    // 1. Estado de la orden
    echo "\n1. ESTADO DE LA ORDEN DE COMPRA\n";
    echo str_repeat("=", 60) . "\n";
    $stmt = $pdo->prepare("SELECT id, nombre_razon_social, monto_total, fecha, usuario_id, forma_pago FROM orden_compra WHERE id = ?");
    $stmt->execute([$ordenId]);
    $orden = $stmt->fetch();
    if ($orden) {
        foreach ($orden as $key => $value) {
            echo "  $key: $value\n";
        }
    } else {
        echo "  ✗ Orden #$ordenId no encontrada\n";
        exit;
    }
    echo "\n";

    // 2. Estado del flujo
    echo "\n2. ESTADO DEL FLUJO DE AUTORIZACIÓN\n";
    echo str_repeat("=", 60) . "\n";
    $stmt = $pdo->prepare("SELECT id, orden_compra_id, estado, fecha_creacion FROM autorizacion_flujo WHERE orden_compra_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$ordenId]);
    $flujo = $stmt->fetch();
    if ($flujo) {
        foreach ($flujo as $key => $value) {
            echo "  $key: $value\n";
        }
    } else {
        echo "  ✗ No se encontró flujo para orden #$ordenId\n";
    }
    echo "\n";

    // 3. Historial reciente
    echo "\n3. HISTORIAL RECIENTE\n";
    echo str_repeat("=", 60) . "\n";
    $stmt = $pdo->prepare("SELECT tipo_evento, fecha, descripcion FROM historial_requisicion WHERE orden_compra_id = ? ORDER BY fecha DESC LIMIT 5");
    $stmt->execute([$ordenId]);
    $historial = $stmt->fetchAll();

    if (empty($historial)) {
        echo "  ✗ No hay historial para esta requisición.\n";
    } else {
        foreach ($historial as $h) {
            echo "  [{$h['fecha']}] {$h['tipo_evento']}: {$h['descripcion']}\n";
        }
    }
    echo "\n";

    echo "\n=== FIN DEL DIAGNÓSTICO ===\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}






