<?php
// Uso: php scripts/debug_verificacion_flujo.php <orden_id>

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\AutorizacionFlujo;
use App\Models\AutorizacionCentroCosto;
use App\Services\AutorizacionService;

if ($argc < 2) {
	echo "Uso: php scripts/debug_verificacion_flujo.php <orden_id>\n";
	exit(1);
}

$ordenId = intval($argv[1]);

$flujo = AutorizacionFlujo::porOrdenCompra($ordenId);
if (!$flujo) {
	echo "No existe flujo para la OC {$ordenId}.\n";
	exit(1);
}

$flujoId = $flujo['id'];
echo "=== DEBUG VERIFICACIÓN FLUJO {$flujoId} (OC {$ordenId}) ===\n";
echo "Estado actual: {$flujo['estado']}\n\n";

// Obtener todas las autorizaciones de centro
$autorizaciones = AutorizacionCentroCosto::where(['autorizacion_flujo_id' => $flujoId]);
echo "Autorizaciones de centro:\n";
$total = 0; $pendientes = 0; $autorizadas = 0; $rechazadas = 0;

foreach ($autorizaciones as $auth) {
	$row = is_object($auth) ? $auth->toArray() : $auth;
	echo "  - Centro {$row['centro_costo_id']}: {$row['estado']} (email: {$row['autorizador_email']})\n";
	$total++;
	if ($row['estado'] === 'pendiente') $pendientes++;
	if ($row['estado'] === 'autorizado') $autorizadas++;
	if ($row['estado'] === 'rechazado') $rechazadas++;
}

echo "\nResumen:\n";
echo "- Total: {$total}\n";
echo "- Pendientes: {$pendientes}\n";
echo "- Autorizadas: {$autorizadas}\n";
echo "- Rechazadas: {$rechazadas}\n";

// Probar método todasAutorizadas
$todasAutorizadas = AutorizacionCentroCosto::todasAutorizadas($flujoId);
echo "- todasAutorizadas(): " . ($todasAutorizadas ? 'SÍ' : 'NO') . "\n";

$algunaRechazada = AutorizacionCentroCosto::algunaRechazada($flujoId);
echo "- algunaRechazada(): " . ($algunaRechazada ? 'SÍ' : 'NO') . "\n";

// Forzar verificación
echo "\n=== FORZANDO VERIFICACIÓN ===\n";
$service = new AutorizacionService();
$resultado = $service->verificarYCompletarFlujo($flujoId);
echo "Resultado: " . ($resultado ? 'ÉXITO' : 'FALLO') . "\n";

// Estado final
$flujoFinal = AutorizacionFlujo::find($flujoId);
$estadoFinal = is_object($flujoFinal) ? $flujoFinal->estado : $flujoFinal['estado'];
echo "Estado final: {$estadoFinal}\n";






