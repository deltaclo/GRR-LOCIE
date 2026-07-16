<?php
require_once __DIR__.'/lib/bootstrap.php';

$sessionOk = grr_suivi_bootstrap(true);

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Rights.php';

if (!$sessionOk) {
    http_response_code(403);
    exit('Acces refuse');
}

$login = function_exists('getUserName') ? (string) getUserName() : '';
if (!SuiviDemandesConfig::accountEnabled() || !SuiviDemandesRights::canAccessModule($login)) {
    http_response_code(403);
    exit('Acces refuse');
}

$filters = SuiviDemandesRepository::filtersFromRequest($_GET);
$demands = SuiviDemandesRepository::findVisibleForUser($login, SuiviDemandesRights::isAdmin($login), $filters);
$filename = 'suivi_demandes_'.date('Ymd_His').'.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
$header = array(
    'id',
    'titre',
    'statut',
    'priorite',
    'ressources',
    'createur',
    'cree_le',
    'mis_a_jour_le',
    'cloture_le',
);
if (SuiviDemandesConfig::categoriesEnabled()) {
    array_splice($header, 4, 0, array('categorie'));
}
fputcsv($output, $header, ';');

foreach ($demands as $demand) {
    $resources = SuiviDemandesRepository::resourcesForDemand((int) $demand['id']);
    $row = array(
        $demand['id'],
        $demand['titre'],
        SuiviDemandesConfig::statusLabel($demand['statut']),
        SuiviDemandesConfig::priorityLabel($demand['priorite']),
        implode(', ', $resources),
        $demand['createur'],
        suivi_demandes_export_date($demand['created_at']),
        suivi_demandes_export_date($demand['updated_at']),
        suivi_demandes_export_date(isset($demand['closed_at']) ? $demand['closed_at'] : 0),
    );
    if (SuiviDemandesConfig::categoriesEnabled()) {
        array_splice($row, 4, 0, array(SuiviDemandesConfig::categoryLabel(isset($demand['categorie']) ? $demand['categorie'] : '')));
    }
    fputcsv($output, suivi_demandes_export_row($row), ';');
}

fclose($output);
exit;

function suivi_demandes_export_row($values)
{
    $row = array();
    foreach ($values as $value) {
        $row[] = suivi_demandes_export_cell($value);
    }

    return $row;
}

function suivi_demandes_export_cell($value)
{
    $value = str_replace(array("\r\n", "\r", "\n"), ' ', (string) $value);
    $value = trim($value);
    if ($value !== '' && preg_match('/^[=+\-@]/', $value)) {
        return "'".$value;
    }

    return $value;
}

function suivi_demandes_export_date($timestamp)
{
    $timestamp = (int) $timestamp;
    if ($timestamp <= 0) {
        return '';
    }

    return date('Y-m-d H:i:s', $timestamp);
}
