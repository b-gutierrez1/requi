<?php
require_once __DIR__ . '/../vendor/autoload.php';
use App\Models\CentroCosto;

$conn = CentroCosto::getConnection();

echo "=== AUTORIZADORES EXISTENTES ===\n";
$sql = "SELECT id, nombre, email FROM autorizadores WHERE activo = 1 ORDER BY nombre";
$stmt = $conn->prepare($sql);
$stmt->execute();
$autorizadores = $stmt->fetchAll(\PDO::FETCH_ASSOC);

foreach ($autorizadores as $a) {
    echo "ID: {$a['id']} | {$a['nombre']} | {$a['email']}\n";
}

echo "\n=== RELACIONES AUTORIZADOR-CENTRO ===\n";
$sql = "SELECT acc.id, a.nombre as autorizador, a.email, cc.nombre as centro
        FROM autorizador_centro_costo acc
        INNER JOIN autorizadores a ON a.id = acc.autorizador_id
        INNER JOIN centro_de_costo cc ON cc.id = acc.centro_costo_id
        WHERE acc.activo = 1
        ORDER BY a.nombre, cc.nombre";
$stmt = $conn->prepare($sql);
$stmt->execute();
$relaciones = $stmt->fetchAll(\PDO::FETCH_ASSOC);

foreach ($relaciones as $r) {
    echo "{$r['autorizador']} ({$r['email']}) -> {$r['centro']}\n";
}

echo "\nTotal relaciones: " . count($relaciones) . "\n";



