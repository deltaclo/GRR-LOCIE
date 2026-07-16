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
if (!SuiviDemandesConfig::accountEnabled() || !SuiviDemandesRights::canAccessModule($login) || !SuiviDemandesRights::isAdmin($login)) {
    http_response_code(403);
    exit('Acces refuse');
}

$tcpdfPath = GRR_SUIVI_ROOT.'/vendor/tecnickcom/tcpdf/tcpdf.php';
if (!is_file($tcpdfPath)) {
    http_response_code(500);
    exit('TCPDF indisponible');
}
require_once $tcpdfPath;

$filters = SuiviDemandesRepository::statisticsFiltersFromRequest($_GET);
$stats = SuiviDemandesRepository::statisticsForAdmin($filters);
$resources = SuiviDemandesRepository::statisticsResourceOptions();

$pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('GRR');
$pdf->SetAuthor($login);
$pdf->SetTitle('Rapport statistiques suivi des demandes');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(12, 12, 12);
$pdf->SetAutoPageBreak(true, 12);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 9);
$pdf->writeHTML(suivi_demandes_stats_pdf_html($stats, $filters, $resources), true, false, true, false, '');
$pdf->lastPage();

if (ob_get_length()) {
    ob_end_clean();
}

$pdf->Output('suivi_demandes_statistiques_'.date('Ymd_His').'.pdf', 'I');
exit;

function suivi_demandes_stats_pdf_html($stats, $filters, $resources)
{
    $response = $stats['response_time'];
    $closure = $stats['closure_time'];

    $html = '<style>'
        .'h1{font-size:20px;margin-bottom:6px;}'
        .'h2{font-size:14px;margin:12px 0 6px;}'
        .'table{border-collapse:collapse;width:100%;}'
        .'th{background-color:#eeeeee;font-weight:bold;}'
        .'th,td{border:1px solid #cccccc;padding:5px;}'
        .'.muted{color:#555555;}'
        .'</style>';

    $html .= '<h1>Rapport statistiques - Suivi des demandes</h1>'
        .'<p class="muted">Genere le '.suivi_demandes_stats_pdf_h(date('d/m/Y H:i')).'</p>'
        .'<p><strong>Periode :</strong> '.suivi_demandes_stats_pdf_h(suivi_demandes_stats_pdf_period_label($filters)).'</p>'
        .'<p><strong>Filtres :</strong> '.suivi_demandes_stats_pdf_h(suivi_demandes_stats_pdf_filter_label($filters, $resources)).'</p>'
        .'<p class="muted">Les demandes supprimees ne sont pas prises en compte dans ces statistiques.</p>';

    $html .= '<h2>Synthese</h2>'
        .'<table><tbody>'
        .suivi_demandes_stats_pdf_row('Demandes', (int) $stats['total'])
        .suivi_demandes_stats_pdf_row(SuiviDemandesConfig::statusLabel('ouverte'), suivi_demandes_stats_pdf_count($stats['by_status'], 'ouverte'))
        .suivi_demandes_stats_pdf_row(SuiviDemandesConfig::statusLabel('en_cours'), suivi_demandes_stats_pdf_count($stats['by_status'], 'en_cours'))
        .suivi_demandes_stats_pdf_row(SuiviDemandesConfig::statusLabel('cloturee'), suivi_demandes_stats_pdf_count($stats['by_status'], 'cloturee'))
        .suivi_demandes_stats_pdf_row('Prise en charge moyenne', suivi_demandes_stats_pdf_duration($response['average']))
        .suivi_demandes_stats_pdf_row('Cloture moyenne', suivi_demandes_stats_pdf_duration($closure['average']))
        .suivi_demandes_stats_pdf_row('Demandes reouvertes', (int) $stats['reopened_demands'])
        .suivi_demandes_stats_pdf_row('Commentaires', (int) $stats['comments_total'])
        .suivi_demandes_stats_pdf_row('Pieces jointes', (int) $stats['attachments_total'])
        .suivi_demandes_stats_pdf_row('Reservations liees', (int) $stats['reservations_total'])
        .suivi_demandes_stats_pdf_row('Suiveurs', (int) $stats['followers_total'])
        .'</tbody></table>';

    $html .= '<h2>Par statut</h2>'.suivi_demandes_stats_pdf_count_table(suivi_demandes_stats_pdf_labelled_status_counts($stats['by_status']));
    $html .= '<h2>Par priorite</h2>'.suivi_demandes_stats_pdf_count_table(suivi_demandes_stats_pdf_labelled_priority_counts($stats['by_priority']));
    if (SuiviDemandesConfig::categoriesEnabled()) {
        $html .= '<h2>Par categorie</h2>'.suivi_demandes_stats_pdf_count_table($stats['by_category']);
    }
    $html .= '<h2>Temps</h2>'.suivi_demandes_stats_pdf_duration_table(array(
        'Prise en charge' => $stats['response_time'],
        'Cloture' => $stats['closure_time'],
    ));
    $html .= '<h2>Donnees associees</h2>'.suivi_demandes_stats_pdf_count_table(array(
        'Demandes avec commentaires' => (int) $stats['comments_demands'],
        'Demandes avec pieces jointes' => (int) $stats['attachments_demands'],
        'Demandes avec reservations' => (int) $stats['reservations_demands'],
        'Demandes avec suiveurs' => (int) $stats['followers_demands'],
        'Evenements de reouverture' => (int) $stats['reopen_events'],
    ));
    $html .= '<h2>Principaux createurs</h2>'.suivi_demandes_stats_pdf_count_table(array_slice($stats['by_creator'], 0, 10, true));
    $html .= '<h2>Ressources les plus concernees</h2>'.suivi_demandes_stats_pdf_resource_table(array_slice($stats['by_resource'], 0, 10, true));

    return $html;
}

function suivi_demandes_stats_pdf_row($label, $value)
{
    return '<tr><td>'.suivi_demandes_stats_pdf_h($label).'</td><td>'.suivi_demandes_stats_pdf_h($value).'</td></tr>';
}

function suivi_demandes_stats_pdf_count($counts, $key)
{
    return isset($counts[$key]) ? (int) $counts[$key] : 0;
}

function suivi_demandes_stats_pdf_labelled_status_counts($counts)
{
    $labels = array();
    foreach ($counts as $status => $count) {
        $labels[SuiviDemandesConfig::statusLabel($status)] = (int) $count;
    }

    return $labels;
}

function suivi_demandes_stats_pdf_labelled_priority_counts($counts)
{
    $labels = array();
    foreach ($counts as $priority => $count) {
        $labels[SuiviDemandesConfig::priorityLabel($priority)] = (int) $count;
    }

    return $labels;
}

function suivi_demandes_stats_pdf_count_table($counts)
{
    if (count($counts) === 0) {
        return '<p class="muted">Aucune donnee.</p>';
    }

    $html = '<table><thead><tr><th>Element</th><th>Total</th></tr></thead><tbody>';
    foreach ($counts as $label => $count) {
        $html .= suivi_demandes_stats_pdf_row($label, (int) $count);
    }

    return $html.'</tbody></table>';
}

function suivi_demandes_stats_pdf_resource_table($resources)
{
    if (count($resources) === 0) {
        return '<p class="muted">Aucune donnee.</p>';
    }

    $html = '<table><thead><tr><th>Ressource</th><th>Demandes</th></tr></thead><tbody>';
    foreach ($resources as $resource) {
        $html .= '<tr><td>'.suivi_demandes_stats_pdf_h($resource['label']).'</td><td>'.suivi_demandes_stats_pdf_h((int) $resource['count']).'</td></tr>';
    }

    return $html.'</tbody></table>';
}

function suivi_demandes_stats_pdf_duration_table($durations)
{
    $html = '<table><thead><tr><th>Indicateur</th><th>Demandes</th><th>Moyenne</th><th>Minimum</th><th>Maximum</th></tr></thead><tbody>';
    foreach ($durations as $label => $duration) {
        $html .= '<tr>'
            .'<td>'.suivi_demandes_stats_pdf_h($label).'</td>'
            .'<td>'.suivi_demandes_stats_pdf_h((int) $duration['count']).'</td>'
            .'<td>'.suivi_demandes_stats_pdf_h(suivi_demandes_stats_pdf_duration($duration['average'])).'</td>'
            .'<td>'.suivi_demandes_stats_pdf_h(suivi_demandes_stats_pdf_duration($duration['min'])).'</td>'
            .'<td>'.suivi_demandes_stats_pdf_h(suivi_demandes_stats_pdf_duration($duration['max'])).'</td>'
            .'</tr>';
    }

    return $html.'</tbody></table>';
}

function suivi_demandes_stats_pdf_period_label($filters)
{
    $from = isset($filters['from']) ? (string) $filters['from'] : '';
    $to = isset($filters['to']) ? (string) $filters['to'] : '';
    if ($from === '' && $to === '') {
        return 'Toutes les demandes existantes';
    }
    if ($from !== '' && $to !== '') {
        return 'du '.suivi_demandes_stats_pdf_date($from).' au '.suivi_demandes_stats_pdf_date($to);
    }
    if ($from !== '') {
        return 'depuis le '.suivi_demandes_stats_pdf_date($from);
    }

    return 'jusqu au '.suivi_demandes_stats_pdf_date($to);
}

function suivi_demandes_stats_pdf_filter_label($filters, $resources)
{
    $labels = array();
    if ($filters['status'] !== '') {
        $labels[] = 'statut '.SuiviDemandesConfig::statusLabel($filters['status']);
    }
    if ($filters['priority'] !== '') {
        $labels[] = 'priorite '.SuiviDemandesConfig::priorityLabel($filters['priority']);
    }
    if (SuiviDemandesConfig::categoriesEnabled() && $filters['category'] !== '') {
        $labels[] = 'categorie '.($filters['category'] === '__none__' ? 'Sans categorie' : $filters['category']);
    }
    if ((int) $filters['room_id'] > 0) {
        $labels[] = 'ressource '.suivi_demandes_stats_pdf_resource_label($resources, (int) $filters['room_id']);
    }
    if ($filters['creator'] !== '') {
        $labels[] = 'createur '.$filters['creator'];
    }

    return count($labels) === 0 ? 'Aucun filtre complementaire' : implode(', ', $labels);
}

function suivi_demandes_stats_pdf_resource_label($resources, $roomId)
{
    foreach ($resources as $resource) {
        if ((int) $resource['id'] === (int) $roomId) {
            return (string) $resource['label'];
        }
    }

    return '#'.(int) $roomId;
}

function suivi_demandes_stats_pdf_date($date)
{
    $time = strtotime((string) $date);
    return $time ? date('d/m/Y', $time) : (string) $date;
}

function suivi_demandes_stats_pdf_duration($seconds)
{
    $seconds = (int) $seconds;
    if ($seconds <= 0) {
        return '-';
    }

    $days = (int) floor($seconds / 86400);
    $seconds -= $days * 86400;
    $hours = (int) floor($seconds / 3600);
    $seconds -= $hours * 3600;
    $minutes = (int) floor($seconds / 60);
    $parts = array();
    if ($days > 0) {
        $parts[] = $days.' j';
    }
    if ($hours > 0 || $days > 0) {
        $parts[] = $hours.' h';
    }
    $parts[] = $minutes.' min';

    return implode(' ', $parts);
}

function suivi_demandes_stats_pdf_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
