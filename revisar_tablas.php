<?php
$pdo = new PDO('mysql:host=localhost;dbname=bd_prueba', 'root', '');

echo "DETALLE_ITEMS:\n";
$stmt = $pdo->query('DESCRIBE detalle_items');
while($row = $stmt->fetch()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\nCUENTA_CONTABLE:\n";
$stmt = $pdo->query('DESCRIBE cuenta_contable');
while($row = $stmt->fetch()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\nDISTRIBUCION_GASTO:\n";
$stmt = $pdo->query('DESCRIBE distribucion_gasto');
while($row = $stmt->fetch()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>