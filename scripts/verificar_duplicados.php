<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\CentroCosto;
use App\Models\PersonaAutorizada;

$conn = CentroCosto::getConnection();

$email = 'ana.ramirez@iga.edu';

echo "=== VERIFICANDO REGISTROS PARA: {$email} ===\n\n";

$sql = "SELECT 
            acc.id,
            acc.autorizador_id,
            acc.centro_costo_id,
            a.email,
            a.nombre,
            cc.nombre as centro_nombre,
            cc.id as centro_id
        FROM autorizador_centro_costo acc
        INNER JOIN autorizadores a ON a.id = acc.autorizador_id
        INNER JOIN centro_de_costo cc ON cc.id = acc.centro_costo_id
        WHERE a.email = ?
        AND acc.activo = 1
        ORDER BY acc.id";

$stmt = $conn->prepare($sql);
$stmt->execute([$email]);
$resultados = $stmt->fetchAll(\PDO::FETCH_ASSOC);

echo "Total de registros activos: " . count($resultados) . "\n\n";

foreach ($resultados as $r) {
    echo "ID: {$r['id']} | Autorizador ID: {$r['autorizador_id']} | Centro ID: {$r['centro_costo_id']} | Centro: {$r['centro_nombre']}\n";
}

echo "\n=== BUSCANDO DUPLICADOS REALES ===\n";

$sql = "SELECT 
            acc.autorizador_id,
            acc.centro_costo_id,
            COUNT(*) as total,
            GROUP_CONCAT(acc.id ORDER BY acc.id) as ids
        FROM autorizador_centro_costo acc
        INNER JOIN autorizadores a ON a.id = acc.autorizador_id
        WHERE a.email = ?
        AND acc.activo = 1
        GROUP BY acc.autorizador_id, acc.centro_costo_id
        HAVING COUNT(*) > 1";

$stmt = $conn->prepare($sql);
$stmt->execute([$email]);
$duplicados = $stmt->fetchAll(\PDO::FETCH_ASSOC);

if (empty($duplicados)) {
    echo "✓ No se encontraron duplicados reales\n";
} else {
    echo "✗ Se encontraron " . count($duplicados) . " grupos de duplicados:\n";
    foreach ($duplicados as $dup) {
        echo "  - Autorizador ID: {$dup['autorizador_id']}, Centro ID: {$dup['centro_costo_id']}, Total: {$dup['total']}, IDs: {$dup['ids']}\n";
    }
}

