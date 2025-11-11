<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Models\CentroCosto;

$conn = CentroCosto::getConnection();

echo "=== ESTRUCTURA DE centro_de_costo ===\n";
$stmt = $conn->query('DESCRIBE centro_de_costo');
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "{$c['Field']} - {$c['Type']}\n";
}

echo "\n=== ESTRUCTURA DE unidad_de_negocio ===\n";
try {
    $stmt = $conn->query('DESCRIBE unidad_de_negocio');
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "{$c['Field']} - {$c['Type']}\n";
    }
} catch (\Exception $e) {
    echo "Tabla no existe: " . $e->getMessage() . "\n";
}

echo "\n=== CENTROS DE COSTO EXISTENTES ===\n";
$stmt = $conn->query('SELECT id, nombre FROM centro_de_costo ORDER BY nombre');
$centros = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($centros as $c) {
    echo "ID: {$c['id']} - {$c['nombre']}\n";
}



