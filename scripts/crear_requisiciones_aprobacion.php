<?php
// Uso: php scripts/crear_requisiciones_aprobacion.php [cantidad]

require_once __DIR__ . '/../vendor/autoload.php';
// No incluir public/index.php para evitar dispatch HTTP en CLI

use App\Services\RequisicionService;
use App\Services\AutorizacionService;
use App\Models\CentroCosto;
use App\Models\CuentaContable;
use App\Models\UnidadRequirente;
use App\Models\Usuario;
use App\Models\OrdenCompra;

// Iniciar sesión para que los servicios que leen $_SESSION funcionen
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

$cantidad = isset($argv[1]) ? max(1, intval($argv[1])) : 5;

echo "Creando {$cantidad} requisiciones y aprobándolas para pasar a autorización...\n";

// Obtener un usuario existente para asignar como creador y revisor
$usuarioId = 1;
$usuarioEmail = 'bgutierrez@sp.iga.edu';
try {
	$usuario = Usuario::first();
	if ($usuario) {
		$usuarioId = is_object($usuario) ? ($usuario->id ?? 1) : ($usuario['id'] ?? 1);
		$usuarioEmail = is_object($usuario) ? ($usuario->azure_email ?? $usuario->email ?? $usuarioEmail) : ($usuario['azure_email'] ?? $usuario['email'] ?? $usuarioEmail);
	}
} catch (Exception $e) {
	// usar defaults
}

// Colocar banderas de revisor en sesión para aprobar
$_SESSION['user_id'] = $usuarioId;
$_SESSION['user_email'] = $usuarioEmail;
$_SESSION['is_revisor'] = true;
$_SESSION['is_admin'] = true; // por si el chequeo require admin/revisor

// Obtener un centro de costo y una cuenta contable válidos
$centroId = null; $cuentaId = null; $unidadReqId = null;
try {
	$cc = CentroCosto::first();
	if ($cc) { $centroId = is_object($cc) ? $cc->id : $cc['id']; }
} catch (Exception $e) {}
try {
	$cta = CuentaContable::first();
	if ($cta) { $cuentaId = is_object($cta) ? $cta->id : $cta['id']; }
} catch (Exception $e) {}
try {
	$ur = UnidadRequirente::first();
	if ($ur) { $unidadReqId = is_object($ur) ? $ur->id : $ur['id']; }
} catch (Exception $e) {}

// Crear mínimos si no existen
if (!$centroId) {
	try {
		$ccNuevo = CentroCosto::create([
			'nombre' => 'Centro Demo',
		]);
		$centroId = $ccNuevo->id;
		echo "Creado centro de costo demo ID {$centroId}\n";
	} catch (Exception $e) {
		echo "No se pudo crear centro de costo: {$e->getMessage()}\n";
	}
}
if (!$cuentaId) {
	try {
		$ctaNueva = CuentaContable::create([
			'codigo' => '1000-00',
			'descripcion' => 'Cuenta Demo',
		]);
		$cuentaId = $ctaNueva->id;
		echo "Creada cuenta contable demo ID {$cuentaId}\n";
	} catch (Exception $e) {
		echo "No se pudo crear cuenta contable: {$e->getMessage()}\n";
	}
}
if (!$unidadReqId) {
	try {
		$urNueva = UnidadRequirente::create([
			'nombre' => 'Unidad Demo',
			'activo' => 1,
		]);
		$unidadReqId = $urNueva->id;
		echo "Creada unidad requirente demo ID {$unidadReqId}\n";
	} catch (Exception $e) {
		echo "No se pudo crear unidad requirente: {$e->getMessage()}\n";
	}
}

if (!$centroId || !$cuentaId || !$unidadReqId) {
	echo "Falta centro de costo, cuenta contable o unidad requirente. Aborta.\n";
	exit(1);
}

$reqService = new RequisicionService();
$autoService = new AutorizacionService();

$creadas = [];
for ($i = 1; $i <= $cantidad; $i++) {
	$data = [
		'nombre_razon_social' => 'Proveedor Demo ' . date('Ymd') . '-' . $i,
		'fecha' => date('Y-m-d'),
		'causal_compra' => 'otro',
		'moneda' => 'GTQ',
		'forma_pago' => 'contado',
		'anticipo' => 0,
		'unidad_requirente' => $unidadReqId,
		'justificacion' => 'Seed automática ' . $i,
		'items' => [
			['descripcion' => 'Item demo ' . $i, 'cantidad' => 1, 'precio_unitario' => 100 + $i],
		],
		'distribucion' => [
			['centro_costo_id' => $centroId, 'cuenta_contable_id' => $cuentaId, 'porcentaje' => 100, 'cantidad' => 0, 'factura' => 1],
		],
	];

	$res = $reqService->crearRequisicion($data, $usuarioId);
	if (!$res['success']) {
		echo "Error creando requisición {$i}: {$res['error']}\n";
		continue;
	}

	$ordenId = $res['orden_id'];
	$ap = $autoService->aprobarRevision($ordenId, $usuarioId, 'Aprobación masiva script');
	if (!is_array($ap) || empty($ap['success'])) {
		echo "Creada OC {$ordenId} pero fallo al aprobar: " . (is_array($ap) ? ($ap['error'] ?? 'desconocido') : 'respuesta inválida') . "\n";
	} else {
		$creadas[] = $ordenId;
		echo "OC {$ordenId} creada y aprobada (pendiente_autorizacion).\n";
	}
}

// Mostrar estados resultantes
if (!empty($creadas)) {
	echo "\nResumen:\n";
	foreach ($creadas as $ocId) {
		$orden = OrdenCompra::find($ocId);
		$flujo = $orden ? $orden->autorizacionFlujo() : null;
		$estado = $flujo ? (is_object($flujo) ? $flujo->estado : $flujo['estado']) : 'sin_flujo';
		echo "OC {$ocId} -> estado flujo: {$estado}\n";
	}
}

echo "Listo.\n";
