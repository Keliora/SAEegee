<?php
require_once __DIR__ . "/../init.php";

if (empty($_SESSION['auth']) || $_SESSION['auth']['role'] !== 'ADMIN') {
    exit("Accès refusé");
}

$type = $_GET['type'] ?? '';

$tables = [
    'benevoles' => 'Benevole',
    'missions'  => 'Mission',
    'evenements'=> 'Evenement'
];

if (!isset($tables[$type])) {
    die("Export invalide");
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="'.$type.'.csv"');

$stmt = $pdo->query("SELECT * FROM ".$tables[$type]);
$out = fopen('php://output', 'w');

$first = true;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($first) {
        fputcsv($out, array_keys($row), ';');
        $first = false;
    }
    fputcsv($out, $row, ';');
}
fclose($out);
exit;
