<?php
/**
 * Script para corregir el estado del flujo de autorización #40
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Helpers\Config;

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
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $flujoId = 40;
    
    echo "=== CORRIGIENDO ESTADO DEL FLUJO #$flujoId ===\n\n";
    
    // Verificar estado actual
    $stmt = $pdo->prepare("SELECT estado FROM autorizacion_flujo WHERE id = ?");
    $stmt->execute([$flujoId]);
    $estadoActual = $stmt->fetchColumn();
    echo "Estado actual: $estadoActual\n";
    
    // Verificar autorizaciones
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado = 'autorizado' THEN 1 ELSE 0 END) as autorizadas
        FROM autorizacion_centro_costo
        WHERE autorizacion_flujo_id = ?
    ");
    $stmt->execute([$flujoId]);
    $resumen = $stmt->fetch();
    
    echo "Total autorizaciones: {$resumen['total']}\n";
    echo "Pendientes: {$resumen['pendientes']}\n";
    echo "Autorizadas: {$resumen['autorizadas']}\n\n";
    
    // Actualizar si todas están autorizadas
    if ($resumen['pendientes'] == 0 && $resumen['autorizadas'] > 0 && $estadoActual === 'pendiente_autorizacion') {
        $stmt = $pdo->prepare("
            UPDATE autorizacion_flujo 
            SET estado = 'autorizado', fecha_completado = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$flujoId]);
        
        echo "✓ Estado actualizado correctamente a 'autorizado'\n";
        echo "✓ Fecha de completado establecida\n\n";
        
        // Verificar cambio
        $stmt = $pdo->prepare("SELECT estado, fecha_completado FROM autorizacion_flujo WHERE id = ?");
        $stmt->execute([$flujoId]);
        $nuevoEstado = $stmt->fetch();
        echo "Nuevo estado: {$nuevoEstado['estado']}\n";
        echo "Fecha completado: {$nuevoEstado['fecha_completado']}\n";
    } else {
        echo "⚠ No se puede actualizar:\n";
        if ($resumen['pendientes'] > 0) {
            echo "  - Aún hay autorizaciones pendientes\n";
        }
        if ($resumen['autorizadas'] == 0) {
            echo "  - No hay autorizaciones autorizadas\n";
        }
        if ($estadoActual !== 'pendiente_autorizacion') {
            echo "  - El estado actual no es 'pendiente_autorizacion'\n";
        }
    }
    
    echo "\n=== FIN ===\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}







