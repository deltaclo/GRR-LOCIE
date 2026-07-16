<?php

require_once __DIR__.'/lib/bootstrap.php';
$sessionOk = grr_stock_chimique_bootstrap(true);
require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Security.php';

if (!$sessionOk || !StockChimiqueConfig::isEnabled() || !StockChimiqueSecurity::canAccess()) {
    http_response_code(403);
    exit('Accès refusé');
}

$type = isset($_GET['type']) ? (string) $_GET['type'] : 'products';
$rows = array();
$headers = array();

function stock_chimique_csv_cell($value)
{
    $value = (string) $value;
    return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
}
if ($type === 'containers') {
    $headers = array('Code', 'Produit', 'Lot', 'Quantité', 'Unité', 'Emplacement', 'Péremption', 'Statut');
    foreach (StockChimiqueRepository::containers(0, true, 5000) as $row) {
        $rows[] = array($row['code_interne'], $row['nom_commercial'], $row['numero_lot'], $row['quantite_courante'], $row['unite'], $row['emplacement_code'].' - '.$row['emplacement_nom'], $row['date_peremption'], $row['statut']);
    }
} elseif ($type === 'movements') {
    $headers = array('Date', 'Type', 'Contenant', 'Produit', 'Quantité', 'Avant', 'Après', 'Unité', 'Auteur', 'Motif');
    foreach (StockChimiqueRepository::movements(0, 5000) as $row) {
        $rows[] = array(date('Y-m-d H:i', (int) $row['date_effective']), $row['type_mouvement'], $row['code_interne'], $row['nom_commercial'], $row['quantite'], $row['quantite_avant'], $row['quantite_apres'], $row['unite'], $row['created_by'], $row['motif']);
    }
} else {
    $type = 'products';
    $headers = array('Référence', 'Produit', 'Fabricant', 'CAS', 'Catégorie', 'Stock', 'Unité', 'Seuil', 'Actif');
    foreach (StockChimiqueRepository::products(true, 2000) as $row) {
        $rows[] = array($row['reference_interne'], $row['nom_commercial'], $row['fabricant'], $row['numero_cas'], $row['categorie'], $row['stock_total'], $row['unite_stock'], $row['seuil_minimal'], $row['actif']);
    }
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="stock_chimique_'.$type.'_'.date('Ymd-His').'.csv"');
echo "\xEF\xBB\xBF";
$output = fopen('php://output', 'w');
fputcsv($output, $headers, ';');
foreach ($rows as $row) {
    fputcsv($output, array_map('stock_chimique_csv_cell', $row), ';');
}
fclose($output);
exit;
