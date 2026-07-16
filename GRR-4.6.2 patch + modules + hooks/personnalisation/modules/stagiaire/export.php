<?php

require_once __DIR__.'/lib/bootstrap.php';

$sessionOk = grr_stagiaire_bootstrap(true);

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';

if (!$sessionOk) {
    header('Location: ../../../index.php');
    exit;
}

$login = function_exists('getUserName') ? (string) getUserName() : '';
if ($login === '' || SecuAccess::UserLevel($login, -1) < 6) {
    http_response_code(403);
    exit('Acces refuse.');
}

$filters = StagiaireRepository::reservationFiltersFromRequest($_GET);
$reservations = StagiaireRepository::stagiaireReservations($filters, true);

$filename = 'stagiaire_reservations_'.date('Ymd_His').'.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, array(
    'ID reservation',
    'Titre',
    'Debut',
    'Fin',
    'Domaine',
    'Ressource',
    'Nom',
    'Prenom',
    'Email',
    'Encadrant',
    'Compte GRR',
    'Statut',
), ';');

foreach ($reservations as $reservation) {
    fputcsv($output, array(
        isset($reservation['entry_id']) ? (int) $reservation['entry_id'] : 0,
        isset($reservation['name']) ? $reservation['name'] : '',
        stagiaire_export_date(isset($reservation['start_time']) ? (int) $reservation['start_time'] : 0),
        stagiaire_export_date(isset($reservation['end_time']) ? (int) $reservation['end_time'] : 0),
        isset($reservation['area_name']) ? $reservation['area_name'] : '',
        isset($reservation['room_name']) ? $reservation['room_name'] : '',
        isset($reservation['nom']) ? $reservation['nom'] : '',
        isset($reservation['prenom']) ? $reservation['prenom'] : '',
        isset($reservation['email']) ? $reservation['email'] : '',
        isset($reservation['encadrant']) ? $reservation['encadrant'] : '',
        isset($reservation['created_by']) ? $reservation['created_by'] : '',
        stagiaire_export_status(isset($reservation['moderate']) ? (int) $reservation['moderate'] : 0),
    ), ';');
}

fclose($output);
exit;

function stagiaire_export_date($timestamp)
{
    $timestamp = (int) $timestamp;
    if ($timestamp <= 0) {
        return '';
    }

    return date('d/m/Y H:i', $timestamp);
}

function stagiaire_export_status($moderate)
{
    if ((int) $moderate === 1) {
        return 'En attente';
    }
    if ((int) $moderate === 2) {
        return 'Acceptee';
    }
    if ((int) $moderate === 3) {
        return 'Refusee';
    }

    return 'Validee';
}
