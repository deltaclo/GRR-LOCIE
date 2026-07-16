<?php

require_once __DIR__.'/lib/bootstrap.php';
$sessionOk = grr_informatique_materiel_bootstrap(true);
require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Security.php';

if (!$sessionOk || !InformatiqueMaterielConfig::isEnabled() || !InformatiqueMaterielSecurity::canAccess()) {
    http_response_code(403);
    exit('Acces refuse');
}

$type = isset($_GET['type']) ? (string) $_GET['type'] : 'people';
$rows = array();
$headers = array();

function informatique_materiel_csv_cell($value)
{
    $value = (string) $value;
    return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
}

if ($type === 'categories') {
    $headers = array('Prefixe', 'Designation', 'Description', 'Dernier numero', 'Actif');
    foreach (InformatiqueMaterielRepository::categories(true) as $row) {
        $rows[] = array(
            $row['prefixe'],
            $row['designation'],
            $row['description'],
            $row['dernier_numero'],
            $row['actif'],
        );
    }
} elseif ($type === 'items') {
    $headers = array(
        'Identifiant',
        'Identifiant legacy',
        'Categorie',
        'Designation',
        'Precision',
        'MAC',
        'Marque',
        'Numero serie',
        'Code barre USMB',
        'OS',
        'Annee',
        'Localisation stockage',
        'Statut',
        'Pret multiple',
        'Commentaire',
        'Notes',
        'Actif',
    );
    foreach (InformatiqueMaterielRepository::items(true) as $row) {
        $rows[] = array(
            $row['identifiant'],
            $row['identifiant_legacy'],
            trim((string) $row['categorie_prefixe'].' - '.(string) $row['categorie_designation']),
            $row['designation'],
            $row['precision_materiel'],
            $row['mac'],
            $row['marque'],
            $row['numero_serie'],
            $row['code_barre_usmb'],
            $row['os'],
            $row['annee'],
            $row['localisation_stockage'],
            $row['statut'],
            (int) $row['pret_multiple'],
            $row['commentaire'],
            $row['notes'],
            $row['actif'],
        );
    }
} elseif ($type === 'loans') {
    $headers = array(
        'ID pret',
        'Statut',
        'Materiel',
        'Personne',
        'Localisation',
        'Date debut',
        'Date fin prevue',
        'Date retour effective',
        'Commentaire',
    );
    foreach (InformatiqueMaterielRepository::loans(true) as $row) {
        $rows[] = array(
            $row['id'],
            $row['statut'],
            trim((string) $row['item_identifiant'].' - '.(string) $row['item_designation']),
            trim((string) $row['personne_prenom'].' '.(string) $row['personne_nom']),
            $row['localisation'],
            $row['date_debut'],
            $row['date_fin_prevue'],
            $row['date_fin_effective'],
            $row['commentaire'],
        );
    }
} else {
    $type = 'people';
    $headers = array('ID source', 'Identifiant personnel', 'Prenom', 'Nom', 'Cadre usage', 'Date depart', 'Login GRR', 'Email', 'Notes', 'Actif');
    foreach (InformatiqueMaterielRepository::people(true) as $row) {
        $rows[] = array(
            $row['legacy_id'],
            $row['identifiant_legacy'],
            $row['prenom'],
            $row['nom'],
            $row['cadre_usage'],
            $row['date_depart'],
            $row['login_grr'],
            isset($row['email']) ? $row['email'] : '',
            $row['notes'],
            $row['actif'],
        );
    }
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="informatique_materiel_'.$type.'_'.date('Ymd-His').'.csv"');
echo "\xEF\xBB\xBF";
$output = fopen('php://output', 'w');
fputcsv($output, $headers, ';');
foreach ($rows as $row) {
    fputcsv($output, array_map('informatique_materiel_csv_cell', $row), ';');
}
fclose($output);
exit;
