<?php
// Uso: php scripts/diagnostico_autorizadores_oc.php <orden_id>

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\AutorizacionFlujo;
use App\Models\DistribucionGasto;
use App\Models\PersonaAutorizada;
use App\Models\AutorizacionCentroCosto;
use App\Models\OrdenCompra;

if ($argc < 2) {
	echo "Uso: php scripts/diagnostico_autorizadores_oc.php <orden_id>\n";
	exit(1);
}

$ordenId = intval($argv[1]);

echo "Diagnóstico de autorizadores para OC {$ordenId}\n";

$orden = OrdenCompra::find($ordenId);
if (!$orden) {
	echo "- Orden no existe\n";
	exit(1);
}

echo "- Proveedor: " . ($orden->nombre_razon_social ?? '') . ", fecha: " . ($orden->fecha ?? '') . "\n";

// Flujo
$flujoRow = AutorizacionFlujo::porOrdenCompra($ordenId);
if (!$flujoRow) {
	echo "- Flujo: NO encontrado\n";
} else {
	echo "- Flujo: ID {$flujoRow['id']}, estado: {$flujoRow['estado']}\n";
}

// Distribución y centros
$dist = DistribucionGasto::porOrdenCompra($ordenId);
$centros = [];
foreach ($dist as $d) {
	$centros[$d['centro_costo_id']] = $d['centro_nombre'] ?? ('Centro #' . $d['centro_costo_id']);
}
if (empty($centros)) {
	echo "- Distribución: SIN centros\n";
} else {
	echo "- Centros en distribución: " . implode(', ', array_map(fn($id,$n)=>"{$id} ({$n})", array_keys($centros), array_values($centros))) . "\n";
}

// Autorizadores definidos
if (!empty($centros)) {
	foreach ($centros as $centroId => $nombre) {
		$pa = PersonaAutorizada::principalPorCentro($centroId);
		if ($pa) {
			echo "  · Centro {$centroId} {$nombre}: autorizador=" . ($pa['email'] ?? 'SIN EMAIL') . "\n";
		} else {
			echo "  · Centro {$centroId} {$nombre}: SIN persona_autorizada\n";
		}
	}
}

// Autorizaciones creadas
if ($flujoRow) {
	$acc = AutorizacionCentroCosto::where(['autorizacion_flujo_id' => $flujoRow['id']]);
	echo "- Autorizaciones de centro creadas: " . count($acc) . "\n";
	foreach ($acc as $a) {
		$row = is_object($a) ? $a->toArray() : $a;
		echo "  · ACC#{$row['id']} centro={$row['centro_costo_id']} estado={$row['estado']} email={$row['autorizador_email']}\n";
	}
}

echo "Listo.\n";






