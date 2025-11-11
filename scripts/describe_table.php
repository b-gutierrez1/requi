<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Helpers\Config;

$db = Config::get('database.connections.mysql');
$pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['database'], $db['username'], $db['password']);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$table = $argv[1] ?? 'autorizacion_flujo';
$stmt = $pdo->query('DESCRIBE ' . $table);
print_r($stmt->fetchAll());






