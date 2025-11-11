<?php
require __DIR__ . '/vendor/autoload.php';

use App\Helpers\Config;

$db = Config::get('database.connections.mysql');
$pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['database'], $db['username'], $db['password']);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$ordenId = 23;

echo "=== DIAGNÓSTICO REQUISICIÓN #$ordenId ===\n";

$stmt = $pdo->prepare('SELECT * FROM orden_compra WHERE id = ?');
$stmt->execute([$ordenId]);
$orden = $stmt->fetch();
echo "\nOrden de compra:\n";
print_r($orden);

$stmt = $pdo->prepare('SELECT * FROM autorizacion_flujo WHERE orden_compra_id = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$ordenId]);
$flujo = $stmt->fetch();
echo "\nFlujo de autorización:\n";
print_r($flujo);

$stmt = $pdo->prepare('SELECT tipo_evento, fecha, descripcion FROM historial_requisicion WHERE orden_compra_id = ? ORDER BY fecha DESC LIMIT 5');
$stmt->execute([$ordenId]);
$historial = $stmt->fetchAll();
echo "\nHistorial reciente:\n";
print_r($historial);






