<?php
// Uso: php scripts/cargar_autorizadores_y_generar.php <orden_id>

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Model;
use App\Models\AutorizacionFlujo;
use App\Models\AutorizacionCentroCosto;

if ($argc < 2) {
	echo "Uso: php scripts/cargar_autorizadores_y_generar.php <orden_id>\n";
	exit(1);
}

$ordenId = intval($argv[1]);
$sqlFile = __DIR__ . '/../cargar_autorizadores_excel.sql';

$pdo = Model::getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1) Cargar autorizadores desde el SQL si existe
if (file_exists($sqlFile)) {
	echo "Cargando autorizadores desde: {$sqlFile}\n";
	$sql = file_get_contents($sqlFile);
	$statements = array_filter(array_map('trim', explode(';', $sql)));
	foreach ($statements as $stmt) {
		if ($stmt === '' || stripos($stmt, 'DELIMITER') !== false) { continue; }
		try {
			$first = strtoupper(substr(ltrim($stmt), 0, 6));
			if ($first === 'SELECT') {
				$q = $pdo->query($stmt);
				$q->fetchAll(PDO::FETCH_ASSOC); // consumir resultados
				$q->closeCursor();
			} else {
				$pdo->exec($stmt);
			}
		} catch (Exception $e) {
			echo "Aviso al ejecutar sentencia: " . $e->getMessage() . "\n";
		}
	}
	echo "Autorizadores cargados (o ya existentes).\n";
} else {
	echo "Archivo SQL no encontrado: {$sqlFile}. Continuo con generación.\n";
}

// 2) Buscar flujo de la orden
$flujo = AutorizacionFlujo::porOrdenCompra($ordenId);
if (!$flujo) {
	echo "No existe flujo para la OC {$ordenId}.\n";
	exit(1);
}

// 3) Generar autorizaciones por centro
echo "Generando autorizaciones por centro para flujo {$flujo['id']}...\n";
$res = AutorizacionCentroCosto::crearParaFlujo($flujo['id'], $ordenId);

if ($res) {
	$count = Model::query("SELECT COUNT(*) c FROM autorizacion_centro_costo WHERE autorizacion_flujo_id = ?", [$flujo['id']])->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;
	echo "Listo. Autorizaciones creadas: {$count}.\n";
} else {
	echo "Error creando autorizaciones (ver error_log).\n";
}
