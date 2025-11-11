<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Helpers\Config;

$db = Config::get('database.connections.mysql');
$pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['database'], $db['username'], $db['password']);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$stmt = $pdo->query('SELECT id, nombre_razon_social, fecha FROM orden_compra ORDER BY id DESC LIMIT 5');
print_r($stmt->fetchAll());






