<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Services\AutorizacionService;

$ordenId = $argv[1] ?? null;
if (!$ordenId) {
    echo "Uso: php scripts/iniciar_flujo.php <orden_id>" . PHP_EOL;
    exit(1);
}

$service = new AutorizacionService();
$resultado = $service->iniciarFlujoAutorizacion($ordenId);
print_r($resultado);






