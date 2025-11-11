<?php
// Uso: php scripts/generar_autorizaciones_centro.php <orden_id>

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\AutorizacionFlujo;
use App\Models\AutorizacionCentroCosto;

if ($argc < 2) {
	echo "Uso: php scripts/generar_autorizaciones_centro.php <orden_id>\n";
	exit(1);
}

$ordenId = intval($argv[1]);

$flujo = AutorizacionFlujo::porOrdenCompra($ordenId);
if (!$flujo) {
	echo "No existe flujo para la OC {$ordenId}.\n";
	exit(1);
}

echo "Generando autorizaciones por centro para flujo {$flujo['id']} (OC {$ordenId})...\n";
$res = AutorizacionCentroCosto::crearParaFlujo($flujo['id'], $ordenId);

if ($res) {
	echo "Listo. Verificando autorizaciones creadas...\n";
	$acc = AutorizacionCentroCosto::where(['autorizacion_flujo_id' => $flujo['id']]);
	echo "Total autorizaciones: " . count($acc) . "\n";
	foreach ($acc as $a) {
		$row = is_object($a) ? $a->toArray() : $a;
		echo "  · ACC#{$row['id']} centro={$row['centro_costo_id']} estado={$row['estado']} email={$row['autorizador_email']}\n";
	}
} else {
	echo "Error creando autorizaciones (ver error_log).\n";
}






