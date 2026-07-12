<?php
/**
 * Script de reparación: flujos atascados por respaldos pendientes.
 *
 * Uso: php scripts/fix_flujo_atascado.php [requisicion_id]
 * Si no se pasa id, lista todos los flujos atascados.
 *
 * Problema: cuando el autorizador principal aprueba forma_pago o cuenta_contable,
 * las autorizaciones de respaldo quedan en 'pendiente', bloqueando la transición.
 * Este script las marca como 'omitida' y fuerza la re-evaluación del flujo.
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$pdo = new PDO(
    'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'bd_prueba') . ';charset=utf8',
    $_ENV['DB_USERNAME'] ?? 'root',
    $_ENV['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$requisicionId = isset($argv[1]) ? (int)$argv[1] : null;

// ---- Listar flujos atascados ----
function listarFlujosAtascados(PDO $pdo): void
{
    $stmt = $pdo->query("
        SELECT af.requisicion_id, af.estado,
               SUM(CASE WHEN a.estado = 'aprobada' THEN 1 ELSE 0 END) AS aprobadas,
               SUM(CASE WHEN a.estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
               GROUP_CONCAT(CONCAT(a.tipo, ':', a.autorizador_email, '=', a.estado) ORDER BY a.id SEPARATOR ' | ') AS detalle
        FROM autorizacion_flujo af
        JOIN autorizaciones a ON af.requisicion_id = a.requisicion_id
        WHERE af.estado IN ('pendiente_autorizacion_pago', 'pendiente_autorizacion_cuenta')
          AND a.tipo IN ('forma_pago', 'cuenta_contable')
        GROUP BY af.requisicion_id, af.estado
        HAVING aprobadas > 0 AND pendientes > 0
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo "✅ No hay flujos atascados.\n";
        return;
    }

    echo "Flujos atascados encontrados:\n";
    foreach ($rows as $row) {
        echo "  - Req #{$row['requisicion_id']} [{$row['estado']}] aprobadas={$row['aprobadas']} pendientes={$row['pendientes']}\n";
        echo "    {$row['detalle']}\n";
    }
    echo "\nUsa: php fix_flujo_atascado.php <requisicion_id> para reparar uno.\n";
}

// ---- Reparar un flujo ----
function repararFlujo(PDO $pdo, int $requisicionId): void
{
    echo "=== Reparando requisición #$requisicionId ===\n";

    // Mostrar estado actual
    $stmt = $pdo->prepare("SELECT id, tipo, autorizador_email, estado FROM autorizaciones WHERE requisicion_id = ? ORDER BY tipo, id");
    $stmt->execute([$requisicionId]);
    $autorizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Estado actual de autorizaciones:\n";
    foreach ($autorizaciones as $a) {
        echo "  #{$a['id']} [{$a['tipo']}] {$a['autorizador_email']} = {$a['estado']}\n";
    }

    // Para cada tipo, si hay al menos una aprobada, omitir las pendientes del mismo (tipo + cuenta_contable_id)
    // forma_pago: agrupar por requisicion_id (todas son del mismo método de pago)
    $stmt = $pdo->prepare("
        SELECT tipo, cuenta_contable_id, COUNT(*) as total,
               SUM(CASE WHEN estado = 'aprobada' THEN 1 ELSE 0 END) AS aprobadas
        FROM autorizaciones
        WHERE requisicion_id = ?
          AND tipo IN ('forma_pago', 'cuenta_contable')
        GROUP BY tipo, cuenta_contable_id
        HAVING aprobadas > 0
    ");
    $stmt->execute([$requisicionId]);
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $omitidas = 0;
    foreach ($grupos as $grupo) {
        if ($grupo['tipo'] === 'forma_pago') {
            $stmtOmitir = $pdo->prepare("
                UPDATE autorizaciones
                SET estado = 'omitida',
                    comentarios = CONCAT(IFNULL(comentarios, ''), ' [Reparación manual: ya autorizada por principal]'),
                    fecha_respuesta = NOW()
                WHERE requisicion_id = ?
                  AND tipo = 'forma_pago'
                  AND estado = 'pendiente'
            ");
            $stmtOmitir->execute([$requisicionId]);
            $omitidas += $stmtOmitir->rowCount();
        } elseif ($grupo['tipo'] === 'cuenta_contable' && $grupo['cuenta_contable_id']) {
            $stmtOmitir = $pdo->prepare("
                UPDATE autorizaciones
                SET estado = 'omitida',
                    comentarios = CONCAT(IFNULL(comentarios, ''), ' [Reparación manual: ya autorizada por principal]'),
                    fecha_respuesta = NOW()
                WHERE requisicion_id = ?
                  AND tipo = 'cuenta_contable'
                  AND cuenta_contable_id = ?
                  AND estado = 'pendiente'
            ");
            $stmtOmitir->execute([$requisicionId, $grupo['cuenta_contable_id']]);
            $omitidas += $stmtOmitir->rowCount();
        }
    }

    echo "Registros omitidos: $omitidas\n";

    // Forzar la transición del flujo
    $stmt = $pdo->prepare("SELECT id, estado FROM autorizacion_flujo WHERE requisicion_id = ?");
    $stmt->execute([$requisicionId]);
    $flujo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$flujo) {
        echo "⚠️ No se encontró flujo para requisición #$requisicionId\n";
        return;
    }

    echo "Flujo actual: estado={$flujo['estado']}\n";

    // Contar pendientes ahora
    $stmt = $pdo->prepare("
        SELECT tipo, COUNT(*) as pendientes
        FROM autorizaciones
        WHERE requisicion_id = ? AND tipo IN ('forma_pago', 'cuenta_contable') AND estado = 'pendiente'
        GROUP BY tipo
    ");
    $stmt->execute([$requisicionId]);
    $pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hayPendientes = !empty($pendientes);

    if (!$hayPendientes) {
        // Transicionar a pendiente_autorizacion_centros
        $stmt = $pdo->prepare("UPDATE autorizacion_flujo SET estado = 'pendiente_autorizacion_centros' WHERE id = ?");
        $stmt->execute([$flujo['id']]);
        echo "✅ Flujo transicionado a 'pendiente_autorizacion_centros'\n";
        echo "El sistema creará las autorizaciones de centros de costo en la próxima acción.\n";
    } else {
        echo "⚠️ Aún hay autorizaciones especiales pendientes:\n";
        foreach ($pendientes as $p) {
            echo "   {$p['tipo']}: {$p['pendientes']} pendientes\n";
        }
    }

    echo "=== Fin reparación ===\n";
}

// ---- Main ----
if ($requisicionId) {
    repararFlujo($pdo, $requisicionId);
} else {
    listarFlujosAtascados($pdo);
}
