<?php
// Script para corregir flujos que deberían estar autorizados pero siguen pendientes

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\AutorizacionFlujo;
use App\Models\AutorizacionCentroCosto;
use App\Services\AutorizacionService;

echo "=== CORRECCIÓN MASIVA DE FLUJOS PENDIENTES ===\n";

// Buscar flujos en pendiente_autorizacion
$flujosPendientes = AutorizacionFlujo::porEstado('pendiente_autorizacion');
echo "Flujos en pendiente_autorizacion: " . count($flujosPendientes) . "\n\n";

$corregidos = 0;
$service = new AutorizacionService();

foreach ($flujosPendientes as $flujo) {
	$flujoId = $flujo['id'];
	$ordenId = $flujo['orden_compra_id'];
	
	echo "Revisando flujo {$flujoId} (OC {$ordenId}):\n";
	
	// Verificar autorizaciones
	$autorizaciones = AutorizacionCentroCosto::where(['autorizacion_flujo_id' => $flujoId]);
	$total = count($autorizaciones);
	$autorizadas = 0;
	$pendientes = 0;
	$rechazadas = 0;
	
	foreach ($autorizaciones as $auth) {
		$row = is_object($auth) ? $auth->toArray() : $auth;
		if ($row['estado'] === 'autorizado') $autorizadas++;
		if ($row['estado'] === 'pendiente') $pendientes++;
		if ($row['estado'] === 'rechazado') $rechazadas++;
	}
	
	echo "  - Autorizaciones: {$total} total, {$autorizadas} autorizadas, {$pendientes} pendientes, {$rechazadas} rechazadas\n";
	
	if ($total > 0 && $pendientes == 0 && $autorizadas > 0) {
		echo "  - ✅ Debería estar autorizado - corrigiendo...\n";
		$resultado = $service->verificarYCompletarFlujo($flujoId);
		if ($resultado) {
			$corregidos++;
			echo "  - ✅ CORREGIDO\n";
		} else {
			echo "  - ❌ ERROR al corregir\n";
		}
	} else {
		echo "  - ⏸️ Aún tiene pendientes - no se corrige\n";
	}
	echo "\n";
}

echo "=== RESUMEN ===\n";
echo "Flujos corregidos: {$corregidos}\n";
echo "Listo.\n";






