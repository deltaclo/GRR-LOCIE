<?php

class SuiviDemandesRenderer
{
    public static function accountMenu()
    {
        $login = self::currentLogin();
        if (!SuiviDemandesConfig::accountEnabled() || !SuiviDemandesRights::canAccessModule($login)) {
            return '';
        }

        return '<br><br><a href="compte.php?pc=suivi_demandes" class="btn btn-primary col-lg-12 col-md-12 col-sm-12 col-xs-12 suivi-demandes-account-btn">'.self::html(SuiviDemandesConfig::displayName()).'</a>';
    }

    public static function statusSummaryLinks()
    {
        $login = self::currentLogin();
        if (!SuiviDemandesConfig::accountEnabled() || !SuiviDemandesRights::canAccessModule($login)) {
            return '';
        }

        $counts = SuiviDemandesRepository::visibleStatusCountsForUser($login, SuiviDemandesRights::isAdmin($login));
        $links = array();

        if ((int) $counts['ouverte'] > 0) {
            $count = (int) $counts['ouverte'];
            $links[] = '<a href="'.self::html(self::accountUrl(array('pc' => SuiviDemandesConfig::MODULE, 'suivi_statut' => 'ouverte'))).'" style="'.self::notificationLinkStyle('ouverte').'">'
                .self::html($count.' demande'.($count > 1 ? 's' : '').' ouverte'.($count > 1 ? 's' : '')).'</a>';
        }

        if ((int) $counts['en_cours'] > 0) {
            $count = (int) $counts['en_cours'];
            $links[] = '<a href="'.self::html(self::accountUrl(array('pc' => SuiviDemandesConfig::MODULE, 'suivi_statut' => 'en_cours'))).'" style="'.self::notificationLinkStyle('en_cours').'">'
                .self::html($count.' demande'.($count > 1 ? 's' : '').' en cours').'</a>';
        }

        if (count($links) === 0) {
            return '';
        }

        return '<p class="suivi-demandes-status" style="text-align:center;">'.implode('<br>', $links).'</p>';
    }

    private static function notificationLinkStyle($status)
    {
        return 'background-color:'.self::html(SuiviDemandesConfig::notificationLinkColor($status)).'; color:#fff; display:inline-block; padding:2px 6px; margin:2px 0; text-decoration:none;';
    }

    public static function accountPage()
    {
        $pc = isset($_GET['pc']) ? $_GET['pc'] : '';
        if ($pc !== SuiviDemandesConfig::MODULE) {
            return '';
        }

        $login = self::currentLogin();
        if (!SuiviDemandesConfig::accountEnabled() || !SuiviDemandesRights::canAccessModule($login)) {
            return '<div class="alert alert-warning">Acces refuse.</div>';
        }

        if (isset($_GET['admin']) && $_GET['admin'] === '1') {
            if (!SuiviDemandesRights::isAdmin($login)) {
                return '<div class="alert alert-warning">Acces refuse.</div>';
            }

            return self::renderEmbeddedAdminPage();
        }

        if (isset($_GET['stats']) && $_GET['stats'] === '1') {
            if (!SuiviDemandesRights::isAdmin($login)) {
                return '<div class="alert alert-warning">Acces refuse.</div>';
            }

            return self::renderStatisticsPage($login);
        }

        $detailId = self::currentDemandId();
        if ($detailId > 0) {
            return self::renderDetailPage($login, $detailId);
        }

        $canCreate = SuiviDemandesRights::canCreateDemand($login);
        $resources = $canCreate ? SuiviDemandesRepository::visibleResources($login) : array();
        $requesterUsers = $canCreate && SuiviDemandesRights::canCreateDemandForOtherUser($login)
            ? SuiviDemandesRepository::activeUsersAvailableAsRequesters($login)
            : array();
        $formState = self::handleCreate($login, $resources, $canCreate);
        $filters = SuiviDemandesRepository::filtersFromRequest($_GET);
        $dashboardCounts = SuiviDemandesRepository::dashboardCountsForUser($login, SuiviDemandesRights::isAdmin($login));
        $demands = SuiviDemandesRepository::findVisibleForUser($login, SuiviDemandesRights::isAdmin($login), $filters);
        $exportUrl = '../personnalisation/modules/suivi_demandes/export.php'.self::filterQueryString($filters);
        $adminLine = '';
        $adminUrl = 'compte.php?pc=suivi_demandes&admin=1';
        $statsUrl = 'compte.php?pc=suivi_demandes&stats=1';
        $viewMode = self::listViewFromRequest($_GET);
        $message = $formState['message'];

        if (SuiviDemandesRights::isAdmin($login)) {
            $adminLine = '<p>Total des demandes en base : <strong>'.self::html(SuiviDemandesRepository::countAll()).'</strong></p>';
        }

        if (isset($_GET['suivi_created'])) {
            $message = 'Demande creee.';
        }
        if (isset($_GET['suivi_deleted'])) {
            $message = 'Demande supprimee.';
        }

        return '<section id="suivi-demandes">'
            .self::assets()
            .'<h2>'.self::html(SuiviDemandesConfig::displayName()).'</h2>'
            .$adminLine
            .self::renderAccountActions($login, $canCreate, $adminUrl, $statsUrl)
            .self::renderCreateModal($formState['values'], $resources, $requesterUsers, $formState['errors'], $message, $canCreate)
            .self::renderDashboard($dashboardCounts)
            .self::renderDemandList($demands, $filters, $exportUrl, $viewMode)
            .self::renderCreateModalScript($formState['errors'])
            .self::renderModalScript(array())
            .'</section>';
    }

    private static function renderEmbeddedAdminPage()
    {
        ob_start();
        $suivi_demandes_admin_embedded = true;
        include __DIR__.'/../admin.php';
        $html = ob_get_clean();

        return '<section id="suivi-demandes">'.self::assets().$html.'</section>';
    }

    private static function renderStatisticsPage($login)
    {
        $filters = SuiviDemandesRepository::statisticsFiltersFromRequest($_GET);
        $stats = SuiviDemandesRepository::statisticsForAdmin($filters);
        $resources = SuiviDemandesRepository::statisticsResourceOptions();
        $creators = SuiviDemandesRepository::statisticsCreatorOptions();
        $pdfUrl = '../personnalisation/modules/suivi_demandes/stats_pdf.php'.self::statisticsQueryString($filters);

        return '<section id="suivi-demandes">'
            .self::assets()
            .'<p><a class="btn btn-default" href="compte.php?pc=suivi_demandes">Retour aux demandes</a></p>'
            .'<h2>Statistiques des demandes</h2>'
            .'<p class="text-muted">Page reservee aux administrateurs generaux.</p>'
            .self::renderStatisticsFilters($filters, $resources, $creators, $pdfUrl)
            .self::renderStatisticsSummary($stats)
            .self::renderStatisticsBreakdowns($stats)
            .'</section>';
    }

    private static function renderStatisticsFilters($filters, $resources, $creators, $pdfUrl)
    {
        $form = '<form method="get" action="compte.php" class="form-inline">'
            .'<input type="hidden" name="pc" value="suivi_demandes">'
            .'<input type="hidden" name="stats" value="1">'
            .'<div class="form-group">'
                .'<label for="suivi_stats_from">Du</label> '
                .'<input class="form-control" id="suivi_stats_from" type="date" name="suivi_stats_from" value="'.self::html($filters['from']).'">'
            .'</div> '
            .'<div class="form-group">'
                .'<label for="suivi_stats_to">Au</label> '
                .'<input class="form-control" id="suivi_stats_to" type="date" name="suivi_stats_to" value="'.self::html($filters['to']).'">'
            .'</div> '
            .'<div class="form-group">'
                .'<label for="suivi_stats_status">Statut</label> '
                .'<select class="form-control" id="suivi_stats_status" name="suivi_stats_status">'
                    .self::renderStatusFilterOptions($filters['status'])
                .'</select>'
            .'</div> '
            .'<div class="form-group">'
                .'<label for="suivi_stats_priority">Priorite</label> '
                .'<select class="form-control" id="suivi_stats_priority" name="suivi_stats_priority">'
                    .self::renderPriorityFilterOptions($filters['priority'])
                .'</select>'
            .'</div> '
            .(SuiviDemandesConfig::categoriesEnabled()
                ? '<div class="form-group">'
                    .'<label for="suivi_stats_category">Categorie</label> '
                    .'<select class="form-control" id="suivi_stats_category" name="suivi_stats_category">'
                        .self::renderCategoryFilterOptions($filters['category'])
                    .'</select>'
                .'</div> '
                : '')
            .'<div class="form-group">'
                .'<label for="suivi_stats_room">Ressource</label> '
                .'<select class="form-control" id="suivi_stats_room" name="suivi_stats_room">'
                    .self::renderStatisticsResourceOptions($resources, $filters['room_id'])
                .'</select>'
            .'</div> '
            .'<div class="form-group">'
                .'<label for="suivi_stats_creator">Createur</label> '
                .'<select class="form-control" id="suivi_stats_creator" name="suivi_stats_creator">'
                    .self::renderStatisticsCreatorOptions($creators, $filters['creator'])
                .'</select>'
            .'</div> '
            .'<button type="submit" class="btn btn-primary">Filtrer</button> '
            .'<a class="btn btn-default" href="compte.php?pc=suivi_demandes&amp;stats=1">Reinitialiser</a>'
            .'</form>';

        return '<section class="suivi-demandes-panel">'
            .'<h3>Filtres</h3>'
            .$form
            .'<p style="margin-top:10px;"><a class="btn btn-default" href="'.self::html($pdfUrl).'" target="_blank" rel="noopener">Rapport PDF</a></p>'
            .'</section>';
    }

    private static function renderStatisticsSummary($stats)
    {
        $response = $stats['response_time'];
        $closure = $stats['closure_time'];
        $cards = array(
            array('label' => 'Demandes', 'value' => (int) $stats['total']),
            array('label' => SuiviDemandesConfig::statusLabel('ouverte'), 'value' => isset($stats['by_status']['ouverte']) ? (int) $stats['by_status']['ouverte'] : 0),
            array('label' => SuiviDemandesConfig::statusLabel('en_cours'), 'value' => isset($stats['by_status']['en_cours']) ? (int) $stats['by_status']['en_cours'] : 0),
            array('label' => SuiviDemandesConfig::statusLabel('cloturee'), 'value' => isset($stats['by_status']['cloturee']) ? (int) $stats['by_status']['cloturee'] : 0),
            array('label' => 'Prise en charge moyenne', 'value' => self::formatDuration($response['average'])),
            array('label' => 'Cloture moyenne', 'value' => self::formatDuration($closure['average'])),
            array('label' => 'Demandes reouvertes', 'value' => (int) $stats['reopened_demands']),
            array('label' => 'Commentaires', 'value' => (int) $stats['comments_total']),
            array('label' => 'Pieces jointes', 'value' => (int) $stats['attachments_total']),
            array('label' => 'Reservations liees', 'value' => (int) $stats['reservations_total']),
            array('label' => 'Suiveurs', 'value' => (int) $stats['followers_total']),
        );

        $html = '<div class="suivi-demandes-stats-grid">';
        foreach ($cards as $card) {
            $html .= '<div class="suivi-demandes-stat-card">'
                .'<strong>'.self::html($card['value']).'</strong>'
                .'<span>'.self::html($card['label']).'</span>'
                .'</div>';
        }

        return $html.'</div>';
    }

    private static function renderStatisticsBreakdowns($stats)
    {
        $html = '<div class="suivi-demandes-detail-grid">'
            .'<section class="suivi-demandes-panel">'
                .'<h3>Par statut</h3>'
                .self::renderStatisticsCountTable(self::labelledStatusCounts($stats['by_status']))
            .'</section>'
            .'<section class="suivi-demandes-panel">'
                .'<h3>Par priorite</h3>'
                .self::renderStatisticsCountTable(self::labelledPriorityCounts($stats['by_priority']))
            .'</section>'
            .'</div>';

        if (SuiviDemandesConfig::categoriesEnabled()) {
            $html .= '<section class="suivi-demandes-panel">'
                .'<h3>Par categorie</h3>'
                .self::renderStatisticsCountTable($stats['by_category'])
                .'</section>';
        }

        $html .= '<div class="suivi-demandes-detail-grid">'
            .'<section class="suivi-demandes-panel">'
                .'<h3>Temps</h3>'
                .self::renderDurationStatisticsTable(array(
                    'Prise en charge' => $stats['response_time'],
                    'Cloture' => $stats['closure_time'],
                ))
            .'</section>'
            .'<section class="suivi-demandes-panel">'
                .'<h3>Donnees associees</h3>'
                .self::renderStatisticsCountTable(array(
                    'Demandes avec commentaires' => (int) $stats['comments_demands'],
                    'Demandes avec pieces jointes' => (int) $stats['attachments_demands'],
                    'Demandes avec reservations' => (int) $stats['reservations_demands'],
                    'Demandes avec suiveurs' => (int) $stats['followers_demands'],
                    'Evenements de reouverture' => (int) $stats['reopen_events'],
                ))
            .'</section>'
            .'</div>'
            .'<div class="suivi-demandes-detail-grid">'
            .'<section class="suivi-demandes-panel">'
                .'<h3>Principaux createurs</h3>'
                .self::renderStatisticsCountTable(array_slice($stats['by_creator'], 0, 10, true))
            .'</section>'
            .'<section class="suivi-demandes-panel">'
                .'<h3>Ressources les plus concernees</h3>'
                .self::renderStatisticsResourceTable(array_slice($stats['by_resource'], 0, 10, true))
            .'</section>'
            .'</div>';

        return $html;
    }

    private static function renderStatisticsResourceOptions($resources, $selectedRoomId)
    {
        $html = '<option value="0"'.self::selected((string) $selectedRoomId, '0').'>Toutes</option>';
        foreach ($resources as $resource) {
            $id = (int) $resource['id'];
            $html .= '<option value="'.self::html($id).'"'.self::selected((string) $selectedRoomId, (string) $id).'>'.self::html($resource['label']).'</option>';
        }

        return $html;
    }

    private static function renderStatisticsCreatorOptions($creators, $selectedCreator)
    {
        $html = '<option value=""'.self::selected($selectedCreator, '').'>Tous</option>';
        foreach ($creators as $creator) {
            $login = isset($creator['createur']) ? (string) $creator['createur'] : '';
            if ($login !== '') {
                $html .= '<option value="'.self::html($login).'"'.self::selected($selectedCreator, $login).'>'.self::html($login).'</option>';
            }
        }

        return $html;
    }

    private static function labelledStatusCounts($counts)
    {
        $labels = array();
        foreach ($counts as $status => $count) {
            $labels[SuiviDemandesConfig::statusLabel($status)] = (int) $count;
        }

        return $labels;
    }

    private static function labelledPriorityCounts($counts)
    {
        $labels = array();
        foreach ($counts as $priority => $count) {
            $labels[SuiviDemandesConfig::priorityLabel($priority)] = (int) $count;
        }

        return $labels;
    }

    private static function renderStatisticsCountTable($counts)
    {
        if (count($counts) === 0) {
            return '<div class="alert alert-info">Aucune donnee.</div>';
        }

        $html = '<div class="suivi-demandes-table-wrap">'
            .'<table class="table table-striped table-bordered">'
            .'<thead><tr><th>Element</th><th>Total</th></tr></thead><tbody>';

        foreach ($counts as $label => $count) {
            $html .= '<tr>'
                .'<td>'.self::html($label).'</td>'
                .'<td>'.self::html((int) $count).'</td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div>';
    }

    private static function renderStatisticsResourceTable($resources)
    {
        if (count($resources) === 0) {
            return '<div class="alert alert-info">Aucune donnee.</div>';
        }

        $html = '<div class="suivi-demandes-table-wrap">'
            .'<table class="table table-striped table-bordered">'
            .'<thead><tr><th>Ressource</th><th>Demandes</th></tr></thead><tbody>';

        foreach ($resources as $resource) {
            $html .= '<tr>'
                .'<td>'.self::html($resource['label']).'</td>'
                .'<td>'.self::html((int) $resource['count']).'</td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div>';
    }

    private static function renderDurationStatisticsTable($durations)
    {
        $html = '<div class="suivi-demandes-table-wrap">'
            .'<table class="table table-striped table-bordered">'
            .'<thead><tr><th>Indicateur</th><th>Demandes</th><th>Moyenne</th><th>Minimum</th><th>Maximum</th></tr></thead><tbody>';

        foreach ($durations as $label => $duration) {
            $html .= '<tr>'
                .'<td>'.self::html($label).'</td>'
                .'<td>'.self::html((int) $duration['count']).'</td>'
                .'<td>'.self::html(self::formatDuration($duration['average'])).'</td>'
                .'<td>'.self::html(self::formatDuration($duration['min'])).'</td>'
                .'<td>'.self::html(self::formatDuration($duration['max'])).'</td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div>';
    }

    private static function assets()
    {
        return '<style>'
            .'#suivi-demandes{width:100%;max-width:none;box-sizing:border-box;}'
            .'#suivi-demandes *,#menu-compte .suivi-demandes-account-btn{box-sizing:border-box;}'
            .'#menu-compte .suivi-demandes-account-btn{display:block;width:100%;max-width:100%;text-align:center;white-space:normal;overflow-wrap:anywhere;}'
            .'#suivi-demandes .btn{white-space:normal;}'
            .'#suivi-demandes .table-responsive{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;}'
            .'#suivi-demandes table{width:100%;}'
            .'#suivi-demandes th,#suivi-demandes td{overflow-wrap:anywhere;}'
            .'#suivi-demandes .suivi-demandes-actions{display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end;}'
            .'#suivi-demandes .suivi-demandes-list-header{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-top:14px;}'
            .'#suivi-demandes .suivi-demandes-list-header h3{margin:0;}'
            .'#suivi-demandes .suivi-demandes-list-switch{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end;}'
            .'#suivi-demandes .suivi-demandes-view-toggle{display:inline-flex;align-items:center;justify-content:center;width:36px;height:32px;padding:6px;}'
            .'#suivi-demandes .suivi-demandes-view-icon{display:grid;width:18px;height:18px;gap:2px;}'
            .'#suivi-demandes .suivi-demandes-view-icon span{display:block;background:#333;border-radius:1px;}'
            .'#suivi-demandes .suivi-demandes-view-lines{grid-template-rows:repeat(3,1fr);}'
            .'#suivi-demandes .suivi-demandes-view-cards{grid-template-columns:repeat(2,1fr);grid-template-rows:repeat(2,1fr);}'
            .'#suivi-demandes .suivi-demandes-card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;margin:12px 0;}'
            .'#suivi-demandes .suivi-demandes-card{border:1px solid #ddd;background:#fff;border-radius:6px;padding:14px;min-width:0;}'
            .'#suivi-demandes .suivi-demandes-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:8px;}'
            .'#suivi-demandes .suivi-demandes-card-title{font-size:16px;font-weight:600;line-height:1.3;margin:0;overflow-wrap:anywhere;}'
            .'#suivi-demandes .suivi-demandes-card-meta{display:grid;grid-template-columns:120px minmax(0,1fr);gap:6px 10px;margin:10px 0;color:#555;}'
            .'#suivi-demandes .suivi-demandes-card-meta dt{font-weight:600;color:#333;}'
            .'#suivi-demandes .suivi-demandes-card-meta dd{margin:0;min-width:0;overflow-wrap:anywhere;}'
            .'#suivi-demandes .suivi-demandes-table-wrap{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;margin:12px 0;}'
            .'#suivi-demandes .suivi-demandes-detail-grid,#suivi-demandes .suivi-demandes-conversation-grid,#suivi-demandes .suivi-demandes-secondary-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px;align-items:start;margin:14px 0;}'
            .'#suivi-demandes .suivi-demandes-panel{border:1px solid #ddd;background:#fff;border-radius:6px;padding:14px;min-width:0;}'
            .'#suivi-demandes .suivi-demandes-panel h3{margin-top:0;}'
            .'#suivi-demandes .suivi-demandes-summary-list{display:grid;grid-template-columns:130px minmax(0,1fr);gap:8px 12px;margin:0;}'
            .'#suivi-demandes .suivi-demandes-summary-list dt{font-weight:600;color:#333;}'
            .'#suivi-demandes .suivi-demandes-summary-list dd{margin:0;min-width:0;overflow-wrap:anywhere;}'
            .'#suivi-demandes .suivi-demandes-description{white-space:normal;line-height:1.5;}'
            .'#suivi-demandes .suivi-demandes-item-list{display:grid;gap:10px;}'
            .'#suivi-demandes .suivi-demandes-item{border:1px solid #e5e5e5;border-radius:6px;padding:10px;background:#fafafa;min-width:0;}'
            .'#suivi-demandes .suivi-demandes-item-head{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;margin-bottom:6px;}'
            .'#suivi-demandes .suivi-demandes-item-title{font-weight:600;overflow-wrap:anywhere;}'
            .'#suivi-demandes .suivi-demandes-item-meta{color:#666;font-size:12px;}'
            .'#suivi-demandes .suivi-demandes-item-body{margin-top:8px;overflow-wrap:anywhere;}'
            .'#suivi-demandes .suivi-demandes-item-details{display:grid;grid-template-columns:90px minmax(0,1fr);gap:4px 8px;margin:8px 0 0;color:#555;}'
            .'#suivi-demandes .suivi-demandes-item-details dt{font-weight:600;color:#333;}'
            .'#suivi-demandes .suivi-demandes-item-details dd{margin:0;min-width:0;overflow-wrap:anywhere;}'
            .'#suivi-demandes .suivi-demandes-item-actions{margin-top:8px;}'
            .'#suivi-demandes .suivi-demandes-compact-history .suivi-demandes-item{padding:8px;}'
            .'#suivi-demandes .suivi-demandes-compact-history .suivi-demandes-item-head{margin-bottom:0;}'
            .'#suivi-demandes .suivi-demandes-compact-history .suivi-demandes-item-body{margin-top:4px;color:#555;font-size:12px;}'
            .'#suivi-demandes .suivi-demandes-stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin:14px 0;}'
            .'#suivi-demandes .suivi-demandes-stat-card{border:1px solid #ddd;background:#fff;border-radius:6px;padding:12px;min-width:0;}'
            .'#suivi-demandes .suivi-demandes-stat-card strong{display:block;font-size:22px;line-height:1.2;}'
            .'#suivi-demandes .suivi-demandes-stat-card span{color:#555;}'
            .'#suivi-demandes .modal-dialog,#suivi-demandes-create-modal .modal-dialog,.suivi-demandes-modal .modal-dialog{width:calc(100% - 20px);max-width:1120px;}'
            .'@media (max-width:767px){#suivi-demandes .btn{width:100%;margin-bottom:8px;}#suivi-demandes .suivi-demandes-actions,#suivi-demandes .suivi-demandes-list-switch{justify-content:stretch;}#suivi-demandes .suivi-demandes-list-header{display:block;}#suivi-demandes .suivi-demandes-card-head,#suivi-demandes .suivi-demandes-item-head{display:block;}#suivi-demandes .suivi-demandes-card-meta,#suivi-demandes .suivi-demandes-summary-list,#suivi-demandes .suivi-demandes-item-details{grid-template-columns:1fr;}#suivi-demandes .suivi-demandes-detail-grid,#suivi-demandes .suivi-demandes-conversation-grid,#suivi-demandes .suivi-demandes-secondary-grid{grid-template-columns:1fr;}#suivi-demandes .modal-dialog,#suivi-demandes-create-modal .modal-dialog,.suivi-demandes-modal .modal-dialog{width:calc(100% - 20px);margin:10px auto!important;}#suivi-demandes table[data-responsive-table="1"],#suivi-demandes table[data-responsive-table="1"] thead,#suivi-demandes table[data-responsive-table="1"] tbody,#suivi-demandes table[data-responsive-table="1"] tr,#suivi-demandes table[data-responsive-table="1"] th,#suivi-demandes table[data-responsive-table="1"] td{display:block;width:100%;}#suivi-demandes table[data-responsive-table="1"] thead{display:none;}#suivi-demandes table[data-responsive-table="1"] tr{margin:0 0 12px;border:1px solid #ddd;background:#fff;border-radius:4px;overflow:hidden;}#suivi-demandes table[data-responsive-table="1"] td{display:flex;gap:10px;justify-content:space-between;align-items:flex-start;border:0;border-bottom:1px solid #eee;text-align:right;}#suivi-demandes table[data-responsive-table="1"] td:last-child{border-bottom:0;}#suivi-demandes table[data-responsive-table="1"] td:before{content:attr(data-label);font-weight:600;color:#555;text-align:left;flex:0 0 42%;}}'
            .'</style>'
            .'<script>(function(){if(window.suiviDemandesResponsiveReady){return;}window.suiviDemandesResponsiveReady=true;function prepare(){document.querySelectorAll("#suivi-demandes table").forEach(function(table){if(table.getAttribute("data-responsive-table")==="1"){return;}var heads=table.tHead&&table.tHead.rows.length?table.tHead.rows[0].cells:[];if(!heads.length){return;}table.setAttribute("data-responsive-table","1");Array.prototype.forEach.call(table.tBodies,function(body){Array.prototype.forEach.call(body.rows,function(row){Array.prototype.forEach.call(row.cells,function(cell,index){var head=heads[index];if(head){cell.setAttribute("data-label",head.textContent.trim());}});});});});}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",prepare);}else{prepare();}setTimeout(prepare,0);})();</script>';
    }

    public static function resourceConfigForm()
    {
        $roomId = self::currentEditRoomId();
        $login = self::currentLogin();
        if ($login === '') {
            return '';
        }

        $enabled = $roomId > 0 ? SuiviDemandesRepository::roomModuleEnabled($roomId) : true;

        return '<section id="suivi-demandes-room-config">'
            .'<h4 class="page-header">'.self::html(SuiviDemandesConfig::displayName()).'</h4>'
            .'<input type="hidden" name="suivi_demandes_room_config_present" value="1">'
            .'<div class="form-group row col-sm-12">'
                .'<label class="col col-sm-8" for="suivi_demandes_room_enabled">Activer le module pour cette ressource</label>'
                .'<div class="col col-sm-4">'
                    .'<input id="suivi_demandes_room_enabled" type="checkbox" name="suivi_demandes_room_enabled" value="1"'.($enabled ? ' checked' : '').'>'
                    .'<p class="help-block">Si cette option est decochee, cette ressource ne pourra plus etre utilisee pour creer ou associer de nouvelles demandes.</p>'
                .'</div>'
            .'</div>'
            .'</section>';
    }

    public static function resourceConfigSave()
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_POST['suivi_demandes_room_config_present'])) {
            return;
        }

        $roomId = self::currentEditRoomId();
        $login = self::currentLogin();
        if ($roomId <= 0 || $login === '') {
            return;
        }

        SuiviDemandesRepository::setRoomModuleEnabled($roomId, isset($_POST['suivi_demandes_room_enabled']));
    }

    public static function reservationForm()
    {
        $login = self::currentLogin();
        if (!SuiviDemandesConfig::reservationFormEnabled() || !SuiviDemandesRights::canAccessModule($login)) {
            return '';
        }

        $context = self::reservationContext('suivi_demandes_editentree_context');
        $roomId = isset($context['room_id']) ? (int) $context['room_id'] : 0;
        if ($roomId > 0 && !SuiviDemandesRepository::roomModuleEnabled($roomId)) {
            return '';
        }

        $canCreate = $roomId > 0
            ? SuiviDemandesRights::canCreateDemandForRoom($login, $roomId)
            : SuiviDemandesRights::canCreateDemand($login);
        $requesterUsers = $canCreate && SuiviDemandesRights::canCreateDemandForOtherUser($login, $roomId)
            ? SuiviDemandesRepository::activeUsersAvailableAsRequesters($login)
            : array();
        $demands = SuiviDemandesRepository::attachableDemandsForUser($login);
        if (!$canCreate && count($demands) === 0) {
            return '';
        }

        $existingDisabled = count($demands) === 0 ? ' disabled' : '';

        $html = '<div class="E" id="suivi-demandes-reservation">'
            .'<label>Suivi des demandes</label>'
            .'<div class="well">'
                .'<label><input type="radio" name="suivi_demandes_reservation_mode" value="none" checked> Ne pas associer de demande</label><br>'
                .'<label><input type="radio" name="suivi_demandes_reservation_mode" value="existing"'.$existingDisabled.'> Associer une demande existante</label>'
                .'<div id="suivi-demandes-existing-fields" style="display:none;margin-top:8px;">'
                .'<select class="form-control" name="suivi_demandes_existing_id"'.$existingDisabled.'>'
                    .'<option value="">Choisir une demande</option>'
                    .self::renderAttachableDemandOptions($demands)
                .'</select>'
                .'</div>';

        if ($canCreate) {
            $html .= '<br><label><input type="radio" name="suivi_demandes_reservation_mode" value="new"> Creer une nouvelle demande</label>'
                .'<div id="suivi-demandes-new-fields" style="display:none;margin-top:8px;">'
                .'<input class="form-control" type="text" name="suivi_demandes_title" maxlength="190" placeholder="Titre de la demande">'
                .self::renderReservationRequesterField($login, $requesterUsers)
                .'<select class="form-control" name="suivi_demandes_priority">'
                    .self::renderPriorityOptions(SuiviDemandesConfig::defaultPriority())
                .'</select>'
                .(SuiviDemandesConfig::categoriesEnabled()
                    ? '<select class="form-control" name="suivi_demandes_category">'
                        .self::renderCategoryOptions('')
                    .'</select>'
                    : '')
                .'<textarea class="form-control" name="suivi_demandes_description" rows="3" maxlength="5000" placeholder="Description de la demande"></textarea>'
                .'</div>';
        }

        return $html
            .'</div>'
            .'</div>'
            .self::reservationDynamicScript();
    }

    public static function reservationSubmit()
    {
        $login = self::currentLogin();
        if (!SuiviDemandesConfig::reservationFormEnabled() || !SuiviDemandesRights::canAccessModule($login)) {
            return;
        }

        $context = self::reservationContext('suivi_demandes_editentreetrt_context');
        $entryId = isset($context['entry_id']) ? (int) $context['entry_id'] : 0;
        $roomId = isset($context['room_id']) ? (int) $context['room_id'] : 0;
        if ($entryId <= 0 || $roomId <= 0 || !SecuAccess::UserResource($login, $roomId) || !SuiviDemandesRepository::roomModuleEnabled($roomId)) {
            return;
        }

        $mode = isset($_POST['suivi_demandes_reservation_mode']) ? (string) $_POST['suivi_demandes_reservation_mode'] : 'none';
        if ($mode === '' || $mode === 'none') {
            return;
        }

        if ($mode === 'existing') {
            $demandeId = isset($_POST['suivi_demandes_existing_id']) ? (int) $_POST['suivi_demandes_existing_id'] : 0;
            if ($demandeId <= 0 || !SuiviDemandesRepository::canAttachDemandToReservation($demandeId, $login)) {
                return;
            }

            SuiviDemandesRepository::associateReservation($demandeId, $entryId, $roomId, $login);
            return;
        }

        if ($mode !== 'new') {
            return;
        }

        if (!SuiviDemandesRights::canCreateDemandForRoom($login, $roomId)) {
            return;
        }

        if (isset($GLOBALS['suivi_demandes_reservation_created_id']) && (int) $GLOBALS['suivi_demandes_reservation_created_id'] > 0) {
            $demandeId = (int) $GLOBALS['suivi_demandes_reservation_created_id'];
        } else {
            $title = isset($_POST['suivi_demandes_title']) ? trim((string) $_POST['suivi_demandes_title']) : '';
            if ($title === '' && isset($context['name'])) {
                $title = trim((string) $context['name']);
            }
            if ($title === '') {
                $title = 'Demande liee a la reservation #'.$entryId;
            }
            if (strlen($title) > 190) {
                $title = substr($title, 0, 190);
            }

            $description = isset($_POST['suivi_demandes_description']) ? trim((string) $_POST['suivi_demandes_description']) : '';
            if ($description === '' && isset($context['description'])) {
                $description = trim((string) $context['description']);
            }
            if (strlen($description) > 5000) {
                $description = substr($description, 0, 5000);
            }

            $defaultPriority = SuiviDemandesConfig::defaultPriority();
            $priority = isset($_POST['suivi_demandes_priority']) ? (string) $_POST['suivi_demandes_priority'] : $defaultPriority;
            if (!SuiviDemandesConfig::isValidPriority($priority)) {
                $priority = $defaultPriority;
            }

            $category = '';
            if (SuiviDemandesConfig::categoriesEnabled()) {
                $category = isset($_POST['suivi_demandes_category']) ? trim((string) $_POST['suivi_demandes_category']) : '';
                if (strlen($category) > SuiviDemandesConfig::MAX_CATEGORY_LENGTH || !SuiviDemandesConfig::isValidCategory($category)) {
                    $category = '';
                }
            }

            $requesterErrors = array();
            $requesterLogin = self::validatedRequesterLogin(
                $login,
                isset($_POST['suivi_demandes_requester_login']) ? $_POST['suivi_demandes_requester_login'] : '',
                $roomId,
                $requesterErrors
            );
            if (count($requesterErrors) > 0) {
                return;
            }

            $demandeId = SuiviDemandesRepository::create($requesterLogin, $title, $description, $priority, array($roomId), $category, $login);
            if ($demandeId <= 0) {
                return;
            }

            SuiviDemandesNotification::notifyCreated($demandeId, $login);
            $GLOBALS['suivi_demandes_reservation_created_id'] = $demandeId;
        }

        SuiviDemandesRepository::associateReservation($demandeId, $entryId, $roomId, $login);
    }

    private static function renderReservationRequesterField($selectedLogin, $requesterUsers)
    {
        if (count($requesterUsers) === 0) {
            return '';
        }

        return '<label for="suivi_demandes_reservation_requester">Demandeur</label>'
            .'<select class="form-control" id="suivi_demandes_reservation_requester" name="suivi_demandes_requester_login">'
                .self::renderUserOptions($requesterUsers, $selectedLogin)
            .'</select>';
    }

    private static function reservationDynamicScript()
    {
        return '<script>'
            .'function suiviDemandesToggleReservationFields(){'
                .'var mode="none";'
                .'var radios=document.getElementsByName("suivi_demandes_reservation_mode");'
                .'for(var i=0;i<radios.length;i++){'
                    .'if(radios[i].checked){mode=radios[i].value;}'
                .'}'
                .'var existing=document.getElementById("suivi-demandes-existing-fields");'
                .'var created=document.getElementById("suivi-demandes-new-fields");'
                .'if(existing){existing.style.display=(mode==="existing")?"block":"none";}'
                .'if(created){created.style.display=(mode==="new")?"block":"none";}'
            .'}'
            .'if(document.addEventListener){'
                .'document.addEventListener("change",function(event){'
                    .'var target=event.target||event.srcElement;'
                    .'if(target&&target.name==="suivi_demandes_reservation_mode"){suiviDemandesToggleReservationFields();}'
                .'});'
                .'document.addEventListener("DOMContentLoaded",suiviDemandesToggleReservationFields);'
            .'}'
            .'suiviDemandesToggleReservationFields();'
            .'</script>';
    }

    public static function reservationLinks()
    {
        $login = self::currentLogin();
        if (!SuiviDemandesConfig::reservationDetailEnabled() || !SuiviDemandesRights::canAccessModule($login)) {
            return '';
        }

        $context = self::reservationContext('suivi_demandes_vuereservation_context');
        $entryId = isset($context['entry_id']) ? (int) $context['entry_id'] : 0;
        if ($entryId <= 0) {
            return '';
        }

        $visibleDemands = array();
        $demands = SuiviDemandesRepository::demandsForReservation($entryId);
        foreach ($demands as $demand) {
            if (SuiviDemandesRights::canViewDemand($login, $demand)) {
                $visibleDemands[] = $demand;
            }
        }

        if (count($visibleDemands) === 0) {
            return '';
        }

        $html = '<fieldset><legend style="font-weight:bold">Demandes associees</legend><ul>';
        foreach ($visibleDemands as $demand) {
            $category = SuiviDemandesConfig::categoriesEnabled()
                ? ' '.self::renderCategory(isset($demand['categorie']) ? $demand['categorie'] : '')
                : '';
            $html .= '<li><a href="compte/compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demand['id']).'">'
                .'#'.self::html($demand['id']).' - '.self::html($demand['titre'])
                .'</a> '.self::renderStatus($demand['statut']).$category.'</li>';
        }

        return $html.'</ul></fieldset>';
    }

    private static function renderDetailPage($login, $demandeId)
    {
        $followerState = self::handleFollower($login, $demandeId);
        $commentState = self::handleComment($login, $demandeId);
        $startState = self::handleStart($login, $demandeId);
        $closeState = self::handleClose($login, $demandeId);
        $reopenState = self::handleReopen($login, $demandeId);
        $resourceState = self::handleResources($login, $demandeId);
        $attachmentState = self::handleAttachment($login, $demandeId);
        $managerNotificationState = self::handleManagerNotification($login, $demandeId);
        $deleteState = self::handleDelete($login, $demandeId);
        $demand = SuiviDemandesRepository::findById($demandeId);

        if ($deleteState['deleted']) {
            return '<section id="suivi-demandes">'
                .self::assets()
                .'<p><a class="btn btn-default" href="compte.php?pc=suivi_demandes">Retour aux demandes</a></p>'
                .'<div class="alert alert-success">Demande supprimee.</div>'
                .'</section>';
        }

        if (!$demand || !SuiviDemandesRights::canViewDemand($login, $demand)) {
            return '<section id="suivi-demandes">'
                .self::assets()
                .'<p><a class="btn btn-default" href="compte.php?pc=suivi_demandes">Retour aux demandes</a></p>'
                .'<div class="alert alert-warning">Demande introuvable ou acces refuse.</div>'
                .'</section>';
        }

        $errors = array_merge($followerState['errors'], $commentState['errors'], $startState['errors'], $closeState['errors'], $reopenState['errors'], $resourceState['errors'], $attachmentState['errors'], $managerNotificationState['errors'], $deleteState['errors']);
        $message = '';
        foreach (array($followerState, $commentState, $startState, $closeState, $reopenState, $resourceState, $attachmentState, $managerNotificationState, $deleteState) as $state) {
            if (isset($state['message']) && $state['message'] !== '') {
                $message = $state['message'];
                break;
            }
        }
        if (isset($_GET['suivi_follower_added'])) {
            $message = 'Suiveur ajoute.';
        }
        if (isset($_GET['suivi_follower_removed'])) {
            $message = 'Suiveur retire.';
        }
        if (isset($_GET['suivi_commented'])) {
            $message = 'Commentaire ajoute.';
        }
        if (isset($_GET['suivi_internal_commented'])) {
            $message = 'Commentaire interne ajoute.';
        }
        if (isset($_GET['suivi_started'])) {
            $message = 'Demande passee en cours.';
        }
        if (isset($_GET['suivi_closed'])) {
            $message = 'Demande cloturee.';
        }
        if (isset($_GET['suivi_reopened'])) {
            $message = 'Demande reouverte.';
        }
        if (isset($_GET['suivi_resource_added'])) {
            $message = 'Ressource ajoutee.';
        }
        if (isset($_GET['suivi_resource_removed'])) {
            $message = 'Ressource retiree.';
        }
        if (isset($_GET['suivi_attachment_uploaded'])) {
            $message = 'Piece jointe ajoutee.';
        }
        if (isset($_GET['suivi_attachment_deleted'])) {
            $message = 'Piece jointe supprimee.';
        }
        if (isset($_GET['suivi_manager_notification_sent'])) {
            $message = 'Notification renvoyee aux gestionnaires.';
        }

        $followerSearch = self::followerSearchFromRequest();
        $openModalIds = array();
        if (count($startState['errors']) > 0) {
            $openModalIds[] = 'suivi-demandes-start-modal';
        }
        if (count($closeState['errors']) > 0) {
            $openModalIds[] = 'suivi-demandes-close-modal';
        }
        if (count($reopenState['errors']) > 0) {
            $openModalIds[] = 'suivi-demandes-reopen-modal';
        }
        if (count($resourceState['errors']) > 0) {
            $openModalIds[] = 'suivi-demandes-resource-modal';
        }
        if (count($attachmentState['errors']) > 0) {
            $openModalIds[] = 'suivi-demandes-attachment-modal';
        }
        if (count($followerState['errors']) > 0 || $followerSearch !== '') {
            $openModalIds[] = 'suivi-demandes-follower-modal';
        }
        if (count($commentState['errors']) > 0) {
            $openModalIds[] = 'suivi-demandes-comment-modal';
        }
        if (count($managerNotificationState['errors']) > 0) {
            $openModalIds[] = 'suivi-demandes-manager-notification-modal';
        }
        if (count($deleteState['errors']) > 0) {
            $openModalIds[] = 'suivi-demandes-delete-modal';
        }

        $resourcesHtml = self::renderDemandResourceManagement($login, $demand, $resourceState['selected']);
        $followersHtml = self::renderDemandFollowers($login, $demand, $followerState['selected'], $followerSearch);
        $secondaryHtml = '<div class="suivi-demandes-secondary-grid">'.$resourcesHtml.$followersHtml.'</div>';
        $reservationsHtml = self::renderDemandReservations((int) $demand['id']);

        return '<section id="suivi-demandes">'
            .self::assets()
            .'<p><a class="btn btn-default" href="compte.php?pc=suivi_demandes">Retour aux demandes</a></p>'
            .self::renderAlerts($errors, $message)
            .self::renderDemandDetail($login, $demand, $closeState['value'])
            .'<div class="suivi-demandes-conversation-grid">'
                .'<section class="suivi-demandes-panel">'.self::renderDemandComments($login, $demand).self::renderCommentForm($login, $demand, $commentState['value'], $commentState['internal']).'</section>'
                .'<section class="suivi-demandes-panel">'.self::renderDemandAttachments($login, $demand).'</section>'
            .'</div>'
            .$secondaryHtml
            .$reservationsHtml
            .self::renderDemandHistory($login, $demand)
            .self::renderModalScript($openModalIds)
            .'</section>';
    }

    private static function handleCreate($login, $resources, $canCreate)
    {
        $values = self::defaultValues($login);
        $errors = array();
        $message = '';

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('values' => $values, 'errors' => $errors, 'message' => $message);
        }

        if (!isset($_POST['suivi_demandes_action']) || $_POST['suivi_demandes_action'] !== 'create') {
            return array('values' => $values, 'errors' => $errors, 'message' => $message);
        }

        if (!$canCreate) {
            $errors[] = 'Vous ne pouvez pas creer de demande.';
            return array('values' => $values, 'errors' => $errors, 'message' => $message);
        }

        $values = array(
            'titre' => isset($_POST['titre']) ? trim((string) $_POST['titre']) : '',
            'description' => isset($_POST['description']) ? trim((string) $_POST['description']) : '',
            'priorite' => isset($_POST['priorite']) ? (string) $_POST['priorite'] : SuiviDemandesConfig::defaultPriority(),
            'categorie' => SuiviDemandesConfig::categoriesEnabled() && isset($_POST['categorie']) ? trim((string) $_POST['categorie']) : '',
            'requester_login' => isset($_POST['requester_login']) ? trim((string) $_POST['requester_login']) : $login,
            'rooms' => self::selectedRoomIds(isset($_POST['rooms']) ? $_POST['rooms'] : array()),
        );

        if ($values['titre'] === '') {
            $errors[] = 'Le titre est obligatoire.';
        } elseif (strlen($values['titre']) > 190) {
            $errors[] = 'Le titre est trop long.';
        }

        if (strlen($values['description']) > 5000) {
            $errors[] = 'La description est trop longue.';
        }

        if (!SuiviDemandesConfig::isValidPriority($values['priorite'])) {
            $errors[] = 'La priorite est invalide.';
        }

        if (SuiviDemandesConfig::categoriesEnabled() && (strlen($values['categorie']) > SuiviDemandesConfig::MAX_CATEGORY_LENGTH || !SuiviDemandesConfig::isValidCategory($values['categorie']))) {
            $errors[] = 'La categorie est invalide.';
        }

        $visibleRoomIds = self::resourceIds($resources);
        $validatedRooms = array();
        foreach ($values['rooms'] as $roomId) {
            if (isset($visibleRoomIds[$roomId])) {
                $validatedRooms[] = $roomId;
            }
        }
        $values['rooms'] = $validatedRooms;

        if (count($values['rooms']) === 0) {
            $errors[] = 'Selectionnez au moins une ressource.';
        }

        $requesterErrors = array();
        $values['requester_login'] = self::validatedRequesterLogin($login, $values['requester_login'], 0, $requesterErrors);
        $errors = array_merge($errors, $requesterErrors);

        if (!SuiviDemandesRepository::sameLogin($values['requester_login'], $login)
            && !SuiviDemandesRights::isAdmin($login)
            && !self::allRoomsManagedBy($login, $values['rooms'])) {
            $errors[] = 'Vous ne pouvez creer une demande pour un autre utilisateur que sur les ressources que vous gerez.';
        }

        if (count($errors) > 0) {
            return array('values' => $values, 'errors' => $errors, 'message' => $message);
        }

        $demandeId = SuiviDemandesRepository::create(
            $values['requester_login'],
            $values['titre'],
            $values['description'],
            $values['priorite'],
            $values['rooms'],
            $values['categorie'],
            $login
        );

        if ($demandeId <= 0) {
            $errors[] = 'La demande n a pas pu etre creee.';
            return array('values' => $values, 'errors' => $errors, 'message' => $message);
        }

        SuiviDemandesNotification::notifyCreated($demandeId, $login);

        if (!headers_sent()) {
            header('Location: compte.php?pc=suivi_demandes&suivi_created='.$demandeId);
            exit;
        }

        return array('values' => self::defaultValues($login), 'errors' => $errors, 'message' => 'Demande creee.');
    }

    private static function handleClose($login, $demandeId)
    {
        $errors = array();
        $message = '';
        $value = '';

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('errors' => $errors, 'message' => $message, 'value' => $value);
        }

        if (!isset($_POST['suivi_demandes_action']) || $_POST['suivi_demandes_action'] !== 'close') {
            return array('errors' => $errors, 'message' => $message, 'value' => $value);
        }

        $postedId = isset($_POST['demande_id']) ? (int) $_POST['demande_id'] : 0;
        $value = isset($_POST['commentaire_cloture']) ? trim((string) $_POST['commentaire_cloture']) : '';
        if ($postedId !== (int) $demandeId) {
            $errors[] = 'La demande a cloturer est invalide.';
            return array('errors' => $errors, 'message' => $message, 'value' => $value);
        }

        $demand = SuiviDemandesRepository::findById($demandeId);
        if (!$demand || !SuiviDemandesRights::canCloseDemand($login, $demand)) {
            $errors[] = 'Vous ne pouvez pas cloturer cette demande.';
            return array('errors' => $errors, 'message' => $message, 'value' => $value);
        }

        if ($value === '') {
            $errors[] = 'Le commentaire de cloture est obligatoire.';
        } elseif (strlen($value) > 5000) {
            $errors[] = 'Le commentaire de cloture est trop long.';
        }

        if (count($errors) > 0) {
            return array('errors' => $errors, 'message' => $message, 'value' => $value);
        }

        if (!SuiviDemandesRepository::addComment($demandeId, $login, $value, 0)) {
            $errors[] = 'Le commentaire de cloture n a pas pu etre ajoute.';
            return array('errors' => $errors, 'message' => $message, 'value' => $value);
        }

        if (!SuiviDemandesRepository::closeDemand($demandeId, $login)) {
            $errors[] = 'La demande n a pas pu etre cloturee.';
            return array('errors' => $errors, 'message' => $message, 'value' => $value);
        }

        SuiviDemandesNotification::notifyComment($demandeId, $login, $value);
        SuiviDemandesNotification::notifyStatusChanged($demandeId, $login, 'cloturee');

        if (!headers_sent()) {
            header('Location: compte.php?pc=suivi_demandes&demande_id='.$demandeId.'&suivi_closed=1');
            exit;
        }

        return array('errors' => $errors, 'message' => 'Demande cloturee.', 'value' => '');
    }

    private static function handleReopen($login, $demandeId)
    {
        $errors = array();
        $message = '';

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('errors' => $errors, 'message' => $message);
        }

        if (!isset($_POST['suivi_demandes_action']) || $_POST['suivi_demandes_action'] !== 'reopen') {
            return array('errors' => $errors, 'message' => $message);
        }

        $postedId = isset($_POST['demande_id']) ? (int) $_POST['demande_id'] : 0;
        if ($postedId !== (int) $demandeId) {
            $errors[] = 'La demande a reouvrir est invalide.';
            return array('errors' => $errors, 'message' => $message);
        }

        $status = isset($_POST['reopen_status']) ? (string) $_POST['reopen_status'] : 'ouverte';
        if (!in_array($status, array('ouverte', 'en_cours'), true)) {
            $errors[] = 'Le statut de reouverture est invalide.';
            return array('errors' => $errors, 'message' => $message);
        }

        $demand = SuiviDemandesRepository::findById($demandeId);
        if (!$demand || !SuiviDemandesRights::canReopenDemand($login, $demand)) {
            $errors[] = 'Vous ne pouvez pas reouvrir cette demande.';
            return array('errors' => $errors, 'message' => $message);
        }

        if (!SuiviDemandesRepository::reopenDemand($demandeId, $login, $status)) {
            $errors[] = 'La demande n a pas pu etre reouverte.';
            return array('errors' => $errors, 'message' => $message);
        }

        SuiviDemandesNotification::notifyStatusChanged($demandeId, $login, $status);

        if (!headers_sent()) {
            header('Location: compte.php?pc=suivi_demandes&demande_id='.$demandeId.'&suivi_reopened=1');
            exit;
        }

        return array('errors' => $errors, 'message' => 'Demande reouverte.');
    }

    private static function handleComment($login, $demandeId)
    {
        $errors = array();
        $message = '';
        $value = '';
        $internal = false;

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('errors' => $errors, 'message' => $message, 'value' => $value, 'internal' => $internal);
        }

        if (!isset($_POST['suivi_demandes_action']) || $_POST['suivi_demandes_action'] !== 'comment') {
            return array('errors' => $errors, 'message' => $message, 'value' => $value, 'internal' => $internal);
        }

        $postedId = isset($_POST['demande_id']) ? (int) $_POST['demande_id'] : 0;
        $value = isset($_POST['commentaire']) ? trim((string) $_POST['commentaire']) : '';
        $internal = isset($_POST['commentaire_interne']);

        if ($postedId !== (int) $demandeId) {
            $errors[] = 'La demande commentee est invalide.';
            return array('errors' => $errors, 'message' => $message, 'value' => $value, 'internal' => $internal);
        }

        $demand = SuiviDemandesRepository::findById($demandeId);
        if (!$demand || !SuiviDemandesRights::canCommentDemand($login, $demand)) {
            $errors[] = 'Vous ne pouvez pas commenter cette demande.';
            return array('errors' => $errors, 'message' => $message, 'value' => $value, 'internal' => $internal);
        }

        if ($internal && !SuiviDemandesRights::canAddInternalComment($login, $demand)) {
            $errors[] = 'Vous ne pouvez pas ajouter de commentaire interne sur cette demande.';
        }

        if ($value === '') {
            $errors[] = 'Le commentaire est obligatoire.';
        } elseif (strlen($value) > 5000) {
            $errors[] = 'Le commentaire est trop long.';
        }

        if (count($errors) > 0) {
            return array('errors' => $errors, 'message' => $message, 'value' => $value, 'internal' => $internal);
        }

        if (!SuiviDemandesRepository::addComment($demandeId, $login, $value, $internal ? 1 : 0)) {
            $errors[] = 'Le commentaire n a pas pu etre ajoute.';
            return array('errors' => $errors, 'message' => $message, 'value' => $value, 'internal' => $internal);
        }

        if (!$internal) {
            SuiviDemandesNotification::notifyComment($demandeId, $login, $value);
        }

        if (!headers_sent()) {
            header('Location: compte.php?pc=suivi_demandes&demande_id='.$demandeId.($internal ? '&suivi_internal_commented=1' : '&suivi_commented=1'));
            exit;
        }

        return array('errors' => $errors, 'message' => $internal ? 'Commentaire interne ajoute.' : 'Commentaire ajoute.', 'value' => '', 'internal' => false);
    }

    private static function handleAttachment($login, $demandeId)
    {
        $errors = array();
        $message = '';

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('errors' => $errors, 'message' => $message);
        }

        if (!isset($_POST['suivi_demandes_action'])
            || ($_POST['suivi_demandes_action'] !== 'upload_attachment' && $_POST['suivi_demandes_action'] !== 'delete_attachment')) {
            return array('errors' => $errors, 'message' => $message);
        }

        $postedId = isset($_POST['demande_id']) ? (int) $_POST['demande_id'] : 0;
        if ($postedId !== (int) $demandeId) {
            $errors[] = 'La demande est invalide.';
            return array('errors' => $errors, 'message' => $message);
        }

        $demand = SuiviDemandesRepository::findById($demandeId);
        if (!$demand || !SuiviDemandesRights::canViewDemand($login, $demand)) {
            $errors[] = 'Vous ne pouvez pas gerer les pieces jointes de cette demande.';
            return array('errors' => $errors, 'message' => $message);
        }

        if ($_POST['suivi_demandes_action'] === 'delete_attachment') {
            $attachmentId = isset($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
            $attachment = SuiviDemandesRepository::attachmentById($attachmentId);
            $attachmentInternal = $attachment && isset($attachment['commentaire_id'])
                ? SuiviDemandesRepository::commentIsInternal((int) $attachment['commentaire_id'])
                : false;
            if (!$attachment || (int) $attachment['demande_id'] !== (int) $demandeId) {
                $errors[] = 'Piece jointe introuvable.';
            } elseif (!SuiviDemandesRights::canDeleteAttachment($login, $demand, $attachment)) {
                $errors[] = 'Vous ne pouvez pas supprimer cette piece jointe.';
            } elseif (!SuiviDemandesRepository::deleteAttachment($attachment, $login)) {
                $errors[] = 'La piece jointe n a pas pu etre supprimee.';
            }

            if (count($errors) === 0 && !$attachmentInternal) {
                SuiviDemandesNotification::notifyAttachmentChanged(
                    $demandeId,
                    $login,
                    isset($attachment['original_name']) ? $attachment['original_name'] : '',
                    false
                );
            }

            if (count($errors) === 0 && !headers_sent()) {
                header('Location: compte.php?pc=suivi_demandes&demande_id='.$demandeId.'&suivi_attachment_deleted=1');
                exit;
            }

            return array('errors' => $errors, 'message' => count($errors) === 0 ? 'Piece jointe supprimee.' : $message);
        }

        $attachmentCommentId = isset($_POST['attachment_comment_id']) ? (int) $_POST['attachment_comment_id'] : 0;
        $attachmentCommentInternal = false;
        if ($attachmentCommentId > 0) {
            $attachmentComment = SuiviDemandesRepository::commentForDemand($attachmentCommentId, $demandeId);
            if (!$attachmentComment) {
                $errors[] = 'Le commentaire associe a la piece jointe est invalide.';
            } elseif (isset($attachmentComment['interne']) && (int) $attachmentComment['interne'] === 1) {
                $attachmentCommentInternal = true;
                if (!SuiviDemandesRights::canViewInternalComments($login, $demand)) {
                    $errors[] = 'Vous ne pouvez pas associer une piece jointe a ce commentaire.';
                }
            }
        }

        if (count($errors) > 0) {
            return array('errors' => $errors, 'message' => $message);
        }

        if (!SuiviDemandesRights::canUploadAttachment($login, $demand)) {
            $errors[] = 'Vous ne pouvez pas ajouter de piece jointe a cette demande.';
            return array('errors' => $errors, 'message' => $message);
        }

        if (!SuiviDemandesConfig::attachmentsEnabled()) {
            $errors[] = 'L ajout de pieces jointes est desactive.';
            return array('errors' => $errors, 'message' => $message);
        }

        if (!isset($_FILES['suivi_attachment']) || !is_array($_FILES['suivi_attachment'])) {
            $errors[] = 'Selectionnez un fichier.';
            return array('errors' => $errors, 'message' => $message);
        }

        $file = $_FILES['suivi_attachment'];
        $uploadError = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_OK) {
            $errors[] = self::uploadErrorMessage($uploadError);
            return array('errors' => $errors, 'message' => $message);
        }

        $originalName = self::sanitizeAttachmentName(isset($file['name']) ? (string) $file['name'] : '');
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, self::allowedAttachmentExtensions(), true)) {
            $errors[] = 'Extension de fichier non autorisee.';
            return array('errors' => $errors, 'message' => $message);
        }

        $size = isset($file['size']) ? (int) $file['size'] : 0;
        $maxBytes = SuiviDemandesConfig::attachmentMaxBytes();
        if ($size <= 0 || $size > $maxBytes) {
            $errors[] = 'La taille du fichier doit etre comprise entre 1 octet et '.self::formatBytes($maxBytes).'.';
            return array('errors' => $errors, 'message' => $message);
        }

        if (!SuiviDemandesRepository::ensureAttachmentStorage()) {
            $errors[] = 'Le dossier de stockage des pieces jointes n est pas accessible en ecriture.';
            return array('errors' => $errors, 'message' => $message);
        }

        $storedName = self::generateAttachmentStoredName();
        $targetPath = SuiviDemandesRepository::attachmentPath($storedName);
        if ($targetPath === '' || !move_uploaded_file($file['tmp_name'], $targetPath)) {
            $errors[] = 'Le fichier n a pas pu etre enregistre.';
            return array('errors' => $errors, 'message' => $message);
        }

        $mimeType = self::attachmentMimeType($targetPath, isset($file['type']) ? (string) $file['type'] : '');
        if (!SuiviDemandesRepository::addAttachment($demandeId, $login, $originalName, $storedName, $mimeType, $size, $attachmentCommentId)) {
            @unlink($targetPath);
            $errors[] = 'La piece jointe n a pas pu etre enregistree en base.';
            return array('errors' => $errors, 'message' => $message);
        }

        if (!$attachmentCommentInternal) {
            SuiviDemandesNotification::notifyAttachmentChanged($demandeId, $login, $originalName, true);
        }

        if (!headers_sent()) {
            header('Location: compte.php?pc=suivi_demandes&demande_id='.$demandeId.'&suivi_attachment_uploaded=1');
            exit;
        }

        return array('errors' => $errors, 'message' => 'Piece jointe ajoutee.');
    }

    private static function handleManagerNotification($login, $demandeId)
    {
        $errors = array();
        $message = '';

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('errors' => $errors, 'message' => $message);
        }

        if (!isset($_POST['suivi_demandes_action']) || $_POST['suivi_demandes_action'] !== 'resend_manager_notification') {
            return array('errors' => $errors, 'message' => $message);
        }

        $postedId = isset($_POST['demande_id']) ? (int) $_POST['demande_id'] : 0;
        if ($postedId !== (int) $demandeId) {
            $errors[] = 'La demande est invalide.';
            return array('errors' => $errors, 'message' => $message);
        }

        $demand = SuiviDemandesRepository::findById($demandeId);
        if (!$demand || !SuiviDemandesRights::canResendManagerNotification($login, $demand)) {
            $errors[] = 'Vous ne pouvez pas renvoyer cette notification.';
            return array('errors' => $errors, 'message' => $message);
        }

        $result = SuiviDemandesNotification::resendToManagers($demandeId, $login);
        if (!isset($result['ok']) || !$result['ok']) {
            $reason = isset($result['reason']) ? (string) $result['reason'] : '';
            if ($reason === 'mail_disabled') {
                $errors[] = 'La configuration mail GRR ou les notifications du module sont inactives.';
            } elseif ($reason === 'no_recipient') {
                $errors[] = 'Aucun gestionnaire avec adresse e-mail active n a ete trouve pour les ressources associees.';
            } elseif ($reason === 'mail_unavailable') {
                $errors[] = 'Le service d envoi de mail n est pas disponible ou l expediteur n est pas configure.';
            } else {
                $errors[] = 'La notification n a pas pu etre renvoyee.';
            }

            return array('errors' => $errors, 'message' => $message);
        }

        $count = isset($result['count']) ? (int) $result['count'] : 0;
        SuiviDemandesRepository::addHistory(
            $demandeId,
            $login,
            'notification_gestionnaires',
            'Notification renvoyee aux gestionnaires ('.$count.' destinataire'.($count > 1 ? 's' : '').')'
        );

        if (!headers_sent()) {
            header('Location: compte.php?pc=suivi_demandes&demande_id='.$demandeId.'&suivi_manager_notification_sent=1');
            exit;
        }

        return array('errors' => $errors, 'message' => 'Notification renvoyee aux gestionnaires.');
    }

    private static function handleDelete($login, $demandeId)
    {
        $errors = array();
        $message = '';
        $deleted = false;

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('errors' => $errors, 'message' => $message, 'deleted' => $deleted);
        }

        if (!isset($_POST['suivi_demandes_action']) || $_POST['suivi_demandes_action'] !== 'delete_demand') {
            return array('errors' => $errors, 'message' => $message, 'deleted' => $deleted);
        }

        $postedId = isset($_POST['demande_id']) ? (int) $_POST['demande_id'] : 0;
        if ($postedId !== (int) $demandeId) {
            $errors[] = 'La demande a supprimer est invalide.';
            return array('errors' => $errors, 'message' => $message, 'deleted' => $deleted);
        }

        $demand = SuiviDemandesRepository::findById($demandeId);
        if (!$demand || !SuiviDemandesRights::canDeleteDemand($login, $demand)) {
            $errors[] = 'Vous ne pouvez pas supprimer cette demande.';
            return array('errors' => $errors, 'message' => $message, 'deleted' => $deleted);
        }

        if (!SuiviDemandesRepository::deleteDemand($demandeId)) {
            $errors[] = 'La demande n a pas pu etre supprimee.';
            return array('errors' => $errors, 'message' => $message, 'deleted' => $deleted);
        }

        if (!headers_sent()) {
            header('Location: compte.php?pc=suivi_demandes&suivi_deleted=1');
            exit;
        }

        return array('errors' => $errors, 'message' => 'Demande supprimee.', 'deleted' => true);
    }

    private static function handleFollower($login, $demandeId)
    {
        $errors = array();
        $message = '';
        $selected = '';

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        if (!isset($_POST['suivi_demandes_action'])
            || ($_POST['suivi_demandes_action'] !== 'add_follower' && $_POST['suivi_demandes_action'] !== 'remove_follower')) {
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        $postedId = isset($_POST['demande_id']) ? (int) $_POST['demande_id'] : 0;
        $followerLogin = isset($_POST['follower_login']) ? SecuChaine::CleanLogin((string) $_POST['follower_login']) : '';
        $selected = $followerLogin;

        if ($postedId !== (int) $demandeId) {
            $errors[] = 'La demande est invalide.';
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        $demand = SuiviDemandesRepository::findById($demandeId);
        if (!$demand || !SuiviDemandesRights::canManageFollowers($login, $demand)) {
            $errors[] = 'Vous ne pouvez pas gerer les suiveurs de cette demande.';
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        if ($followerLogin === '') {
            $errors[] = 'Selectionnez un utilisateur.';
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        if ($_POST['suivi_demandes_action'] === 'add_follower') {
            if (SuiviDemandesRepository::sameLogin($followerLogin, $demand['createur'])) {
                $errors[] = 'Le createur voit deja cette demande.';
            } elseif (!SuiviDemandesRepository::activeUserExists($followerLogin)) {
                $errors[] = 'Utilisateur introuvable ou inactif.';
            } elseif (SuiviDemandesRepository::isFollower($demandeId, $followerLogin)) {
                $errors[] = 'Cet utilisateur est deja suiveur.';
            }

            if (count($errors) > 0) {
                return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
            }

            if (!SuiviDemandesRepository::addFollower($demandeId, $followerLogin, $login)) {
                $errors[] = 'Le suiveur n a pas pu etre ajoute.';
                return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
            }

            SuiviDemandesNotification::notifyFollowerChanged($demandeId, $login, $followerLogin, true);

            if (!headers_sent()) {
                header('Location: compte.php?pc=suivi_demandes&demande_id='.$demandeId.'&suivi_follower_added=1');
                exit;
            }

            return array('errors' => $errors, 'message' => 'Suiveur ajoute.', 'selected' => '');
        }

        if (!SuiviDemandesRepository::isFollower($demandeId, $followerLogin)) {
            $errors[] = 'Cet utilisateur n est pas suiveur.';
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        if (!SuiviDemandesRepository::removeFollower($demandeId, $followerLogin, $login)) {
            $errors[] = 'Le suiveur n a pas pu etre retire.';
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        SuiviDemandesNotification::notifyFollowerChanged($demandeId, $login, $followerLogin, false);

        if (!headers_sent()) {
            header('Location: compte.php?pc=suivi_demandes&demande_id='.$demandeId.'&suivi_follower_removed=1');
            exit;
        }

        return array('errors' => $errors, 'message' => 'Suiveur retire.', 'selected' => '');
    }

    private static function handleResources($login, $demandeId)
    {
        $errors = array();
        $message = '';
        $selected = 0;

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        if (!isset($_POST['suivi_demandes_action'])
            || ($_POST['suivi_demandes_action'] !== 'add_resource' && $_POST['suivi_demandes_action'] !== 'remove_resource')) {
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        $postedId = isset($_POST['demande_id']) ? (int) $_POST['demande_id'] : 0;
        $roomId = isset($_POST['resource_room_id']) ? (int) $_POST['resource_room_id'] : 0;
        $selected = $roomId;

        if ($postedId !== (int) $demandeId) {
            $errors[] = 'La demande est invalide.';
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        $demand = SuiviDemandesRepository::findById($demandeId);
        if (!$demand || !SuiviDemandesRights::canManageResources($login, $demand)) {
            $errors[] = 'Vous ne pouvez pas gerer les ressources de cette demande.';
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        if ($roomId <= 0) {
            $errors[] = 'Selectionnez une ressource.';
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        if ($_POST['suivi_demandes_action'] === 'add_resource') {
            if (SuiviDemandesRepository::demandHasResource($demandeId, $roomId)) {
                $errors[] = 'Cette ressource est deja associee.';
                return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
            }

            if (!SuiviDemandesRepository::roomModuleEnabled($roomId)) {
                $errors[] = 'Le module est desactive pour cette ressource.';
                return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
            }

            if (!SuiviDemandesRepository::resourceAvailableToAdd($login, $demandeId, $roomId)) {
                $errors[] = 'Vous ne pouvez pas ajouter cette ressource.';
                return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
            }

            if (!SuiviDemandesRepository::addResource($demandeId, $roomId, $login)) {
                $errors[] = 'La ressource n a pas pu etre ajoutee.';
                return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
            }

            SuiviDemandesNotification::notifyResourceChanged($demandeId, $login, $roomId, true);

            if (!headers_sent()) {
                header('Location: compte.php?pc=suivi_demandes&demande_id='.$demandeId.'&suivi_resource_added=1');
                exit;
            }

            return array('errors' => $errors, 'message' => 'Ressource ajoutee.', 'selected' => 0);
        }

        if (!SuiviDemandesRepository::userManagesResource($login, $roomId)) {
            $errors[] = 'Vous ne gerez pas cette ressource.';
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        if (!SuiviDemandesRepository::demandHasResource($demandeId, $roomId)) {
            $errors[] = 'Cette ressource n est pas associee.';
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        if (SuiviDemandesRepository::countResourcesForDemand($demandeId) <= 1) {
            $errors[] = 'La derniere ressource associee ne peut pas etre retiree.';
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        if (SuiviDemandesRepository::resourceHasReservationLink($demandeId, $roomId)) {
            $errors[] = 'Cette ressource est liee a une reservation associee et ne peut pas etre retiree.';
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        if (!SuiviDemandesRepository::removeResource($demandeId, $roomId, $login)) {
            $errors[] = 'La ressource n a pas pu etre retiree.';
            return array('errors' => $errors, 'message' => $message, 'selected' => $selected);
        }

        SuiviDemandesNotification::notifyResourceChanged($demandeId, $login, $roomId, false);

        if (!headers_sent()) {
            header('Location: compte.php?pc=suivi_demandes&demande_id='.$demandeId.'&suivi_resource_removed=1');
            exit;
        }

        return array('errors' => $errors, 'message' => 'Ressource retiree.', 'selected' => 0);
    }

    private static function handleStart($login, $demandeId)
    {
        $errors = array();
        $message = '';

        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            return array('errors' => $errors, 'message' => $message);
        }

        if (!isset($_POST['suivi_demandes_action']) || $_POST['suivi_demandes_action'] !== 'start') {
            return array('errors' => $errors, 'message' => $message);
        }

        $postedId = isset($_POST['demande_id']) ? (int) $_POST['demande_id'] : 0;
        if ($postedId !== (int) $demandeId) {
            $errors[] = 'La demande a passer en cours est invalide.';
            return array('errors' => $errors, 'message' => $message);
        }

        $demand = SuiviDemandesRepository::findById($demandeId);
        if (!$demand || !SuiviDemandesRights::canStartDemand($login, $demand)) {
            $errors[] = 'Vous ne pouvez pas passer cette demande en cours.';
            return array('errors' => $errors, 'message' => $message);
        }

        if (!SuiviDemandesRepository::startDemand($demandeId, $login)) {
            $errors[] = 'La demande n a pas pu etre passee en cours.';
            return array('errors' => $errors, 'message' => $message);
        }

        SuiviDemandesNotification::notifyStatusChanged($demandeId, $login, 'en_cours');

        if (!headers_sent()) {
            header('Location: compte.php?pc=suivi_demandes&demande_id='.$demandeId.'&suivi_started=1');
            exit;
        }

        return array('errors' => $errors, 'message' => 'Demande passee en cours.');
    }

    private static function renderAccountActions($login, $canCreate, $adminUrl, $statsUrl)
    {
        $html = '<p>';
        if ($canCreate) {
            $html .= '<button type="button" id="suivi-demandes-create-button" class="btn btn-primary" onclick="return suiviDemandesOpenCreateModal();">Nouvelle demande</button> ';
        }

        if (SuiviDemandesRights::isAdmin($login)) {
            $html .= '<a class="btn btn-default" href="'.self::html($statsUrl).'">Statistiques</a> '
                .'<a class="btn btn-default" href="'.self::html($adminUrl).'">Administration du module</a>';
        }

        return trim($html) === '<p>' ? '' : $html.'</p>';
    }

    private static function renderCreateModal($values, $resources, $requesterUsers, $errors, $message, $canCreate)
    {
        if (!$canCreate) {
            return self::renderAlerts($errors, $message)
                .'<div class="alert alert-info">La creation de demandes n est pas autorisee pour ce compte.</div>';
        }

        $html = '<div id="suivi-demandes-create-modal" role="dialog" aria-labelledby="suivi-demandes-create-title" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.45);">'
            .'<div class="modal-dialog modal-lg" role="document" style="margin:30px auto;">'
            .'<div class="modal-content">'
            .'<div class="modal-header">'
                .'<h4 class="modal-title" id="suivi-demandes-create-title">Nouvelle demande</h4>'
                .'<button type="button" class="btn-close" aria-label="Fermer" onclick="return suiviDemandesCloseCreateModal();"></button>'
            .'</div>'
            .'<div class="modal-body">'
                .self::renderCreateForm($values, $resources, $requesterUsers, $errors, $message, $canCreate)
            .'</div>'
            .'</div>'
            .'</div>'
            .'</div>';

        return $html;
    }

    private static function renderCreateModalScript($errors)
    {
        return '<script>'
            .'function suiviDemandesOpenCreateModal(){'
                .'var modalElement=document.getElementById("suivi-demandes-create-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="block";'
                .'document.body.classList.add("modal-open");'
                .'return false;'
            .'}'
            .'function suiviDemandesCloseCreateModal(){'
                .'var modalElement=document.getElementById("suivi-demandes-create-modal");'
                .'if(!modalElement){return false;}'
                .'modalElement.style.display="none";'
                .'document.body.classList.remove("modal-open");'
                .'return false;'
            .'}'
            .'document.addEventListener("click",function(event){'
                .'var modalElement=document.getElementById("suivi-demandes-create-modal");'
                .'if(modalElement&&event.target===modalElement){suiviDemandesCloseCreateModal();}'
            .'});'
            .'document.addEventListener("keydown",function(event){'
                .'if(event.key==="Escape"){suiviDemandesCloseCreateModal();}'
            .'});'
            .'document.addEventListener("DOMContentLoaded",function(){'
                .(count($errors) > 0 ? 'suiviDemandesOpenCreateModal();' : '')
            .'});'
            .'</script>';
    }

    private static function renderRequesterFormGroup($selectedLogin, $requesterUsers)
    {
        if (count($requesterUsers) === 0) {
            return '';
        }

        return '<div class="form-group row col-sm-12">'
            .'<label class="col-sm-3" for="suivi_demandes_requester">Demandeur</label>'
            .'<div class="col-sm-9"><select class="form-control" id="suivi_demandes_requester" name="requester_login">'
                .self::renderUserOptions($requesterUsers, $selectedLogin)
            .'</select></div>'
            .'</div>';
    }

    private static function renderModalButton($modalId, $label, $class)
    {
        return '<button type="button" class="'.self::html($class).'" data-suivi-modal-open="'.self::html($modalId).'" aria-controls="'.self::html($modalId).'">'.self::html($label).'</button>';
    }

    private static function renderModal($modalId, $title, $body)
    {
        $safeId = self::html($modalId);
        $titleId = $safeId.'-title';

        return '<div id="'.$safeId.'" class="suivi-demandes-modal" role="dialog" aria-modal="true" aria-labelledby="'.$titleId.'" style="display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.45);">'
            .'<div class="modal-dialog modal-lg" role="document" style="margin:30px auto;">'
            .'<div class="modal-content">'
            .'<div class="modal-header">'
            .'<h4 class="modal-title" id="'.$titleId.'">'.self::html($title).'</h4>'
            .'<button type="button" class="close" data-suivi-modal-close="'.self::html($modalId).'" aria-label="Fermer"><span aria-hidden="true">&times;</span></button>'
            .'</div>'
            .'<div class="modal-body">'.$body.'</div>'
            .'</div>'
            .'</div>'
            .'</div>';
    }

    private static function renderModalScript($openIds)
    {
        $openScript = '';
        foreach ($openIds as $openId) {
            $openScript .= 'suiviDemandesOpenModal('.self::js($openId).');';
        }

        return '<script>'
            .'function suiviDemandesOpenModal(id){'
                .'var modal=document.getElementById(id);'
                .'if(!modal){return false;}'
                .'modal.style.display="block";'
                .'document.body.classList.add("modal-open");'
                .'return false;'
            .'}'
            .'function suiviDemandesCloseModal(id){'
                .'var modal=document.getElementById(id);'
                .'if(!modal){return false;}'
                .'modal.style.display="none";'
                .'document.body.classList.remove("modal-open");'
                .'return false;'
            .'}'
            .'document.addEventListener("click",function(event){'
                .'var opener=event.target.closest("[data-suivi-modal-open]");'
                .'if(opener){event.preventDefault();suiviDemandesOpenModal(opener.getAttribute("data-suivi-modal-open"));return;}'
                .'var closer=event.target.closest("[data-suivi-modal-close]");'
                .'if(closer){event.preventDefault();suiviDemandesCloseModal(closer.getAttribute("data-suivi-modal-close"));return;}'
                .'var target=event.target;'
                .'if(target&&target.classList&&target.classList.contains("suivi-demandes-modal")){suiviDemandesCloseModal(target.id);}'
            .'});'
            .'document.addEventListener("keydown",function(event){'
                .'if(event.key!=="Escape"){return;}'
                .'var modals=document.querySelectorAll(".suivi-demandes-modal");'
                .'for(var index=0;index<modals.length;index++){'
                    .'if(modals[index].style.display==="block"){suiviDemandesCloseModal(modals[index].id);}'
                .'}'
            .'});'
            .'document.addEventListener("DOMContentLoaded",function(){'.$openScript.'});'
            .'</script>';
    }

    private static function renderCreateForm($values, $resources, $requesterUsers, $errors, $message, $canCreate)
    {
        $html = self::renderAlerts($errors, $message);

        if (!$canCreate) {
            return $html.'<div class="alert alert-info">La creation de demandes n est pas autorisee pour ce compte.</div>';
        }

        if (count($resources) === 0) {
            return $html.'<div class="alert alert-warning">Aucune ressource visible. La creation d une demande est impossible pour ce compte.</div>';
        }

        $html .= '<form method="post" action="compte.php?pc=suivi_demandes">'
            .'<input type="hidden" name="suivi_demandes_action" value="create">'
            .'<div class="form-group row col-sm-12">'
                .'<label class="col-sm-3" for="suivi_demandes_titre">Titre</label>'
                .'<div class="col-sm-9"><input class="form-control" id="suivi_demandes_titre" type="text" name="titre" maxlength="190" value="'.self::html($values['titre']).'" required></div>'
            .'</div>'
            .self::renderRequesterFormGroup($values['requester_login'], $requesterUsers)
            .'<div class="form-group row col-sm-12">'
                .'<label class="col-sm-3" for="suivi_demandes_priorite">Priorite</label>'
                .'<div class="col-sm-9"><select class="form-control" id="suivi_demandes_priorite" name="priorite">'
                    .self::renderPriorityOptions($values['priorite'])
                .'</select></div>'
            .'</div>'
            .(SuiviDemandesConfig::categoriesEnabled()
                ? '<div class="form-group row col-sm-12">'
                    .'<label class="col-sm-3" for="suivi_demandes_categorie">Categorie</label>'
                    .'<div class="col-sm-9"><select class="form-control" id="suivi_demandes_categorie" name="categorie">'
                        .self::renderCategoryOptions($values['categorie'])
                    .'</select></div>'
                .'</div>'
                : '')
            .'<div class="form-group row col-sm-12">'
                .'<label class="col-sm-3" for="suivi_demandes_rooms">Ressources</label>'
                .'<div class="col-sm-9"><select class="form-control" id="suivi_demandes_rooms" name="rooms[]" multiple size="'.self::html(min(8, max(3, count($resources)))).'" required>'
                    .self::renderResourceOptions($resources, $values['rooms'])
                .'</select></div>'
            .'</div>'
            .'<div class="form-group row col-sm-12">'
                .'<label class="col-sm-3" for="suivi_demandes_description">Description</label>'
                .'<div class="col-sm-9"><textarea class="form-control" id="suivi_demandes_description" name="description" rows="5" maxlength="5000">'.self::html($values['description']).'</textarea></div>'
            .'</div>'
            .'<div class="form-group row col-sm-12">'
                .'<div class="col-sm-offset-3 col-sm-9"><button type="submit" class="btn btn-primary">Creer la demande</button></div>'
            .'</div>'
            .'</form>';

        return $html;
    }

    private static function renderDemandList($demands, $filters, $exportUrl, $viewMode)
    {
        $html = '<div class="suivi-demandes-list-header">'
                .'<h3>Demandes visibles</h3>'
                .self::renderListViewSwitch($filters, $viewMode)
            .'</div>'
            .self::renderDemandFilters($filters, $exportUrl, $viewMode);

        if (count($demands) === 0) {
            return $html.'<div class="alert alert-info">Aucune demande visible pour le moment.</div>';
        }

        $html .= '<p>Affichage limite aux '.self::html($filters['limit']).' demandes les plus recentes.</p>';

        if ($viewMode === 'ligne') {
            return $html.self::renderDemandListRows($demands);
        }

        return $html.self::renderDemandListCards($demands);
    }

    private static function renderDemandListCards($demands)
    {
        $html = '<div class="suivi-demandes-card-grid">';

        foreach ($demands as $demand) {
            $resources = SuiviDemandesRepository::resourcesForDemand((int) $demand['id']);
            $category = isset($demand['categorie']) ? $demand['categorie'] : '';
            $detailUrl = 'compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demand['id']);
            $html .= '<article class="suivi-demandes-card">'
                .'<div class="suivi-demandes-card-head">'
                    .'<div>'
                        .'<p class="suivi-demandes-card-title"><a href="'.$detailUrl.'">#'.self::html($demand['id']).' - '.self::html($demand['titre']).'</a></p>'
                    .'</div>'
                    .'<div>'.self::renderStatus($demand['statut']).'</div>'
                .'</div>'
                .'<dl class="suivi-demandes-card-meta">'
                    .'<dt>Priorite</dt><dd>'.self::renderPriority($demand['priorite']).'</dd>'
                    .(SuiviDemandesConfig::categoriesEnabled() ? '<dt>Categorie</dt><dd>'.self::renderCategory($category).'</dd>' : '')
                    .'<dt>Ressources</dt><dd>'.self::html(count($resources) > 0 ? implode(', ', $resources) : 'Aucune ressource').'</dd>'
                    .'<dt>Createur</dt><dd>'.self::html($demand['createur']).'</dd>'
                    .'<dt>Creee le</dt><dd>'.self::html(self::formatDate($demand['created_at'])).'</dd>'
                    .'<dt>Mise a jour</dt><dd>'.self::html(self::formatDate($demand['updated_at'])).'</dd>'
                    .((int) $demand['closed_at'] > 0 ? '<dt>Cloturee le</dt><dd>'.self::html(self::formatDate($demand['closed_at'])).'</dd>' : '')
                .'</dl>'
                .'<a class="btn btn-default btn-sm" href="'.$detailUrl.'">Voir la demande</a>'
                .'</article>';
        }

        return $html.'</div>';
    }

    private static function renderDemandListRows($demands)
    {
        $categoryHeader = SuiviDemandesConfig::categoriesEnabled() ? '<th>Categorie</th>' : '';
        $html = '<div class="suivi-demandes-table-wrap">'
            .'<table class="table table-striped table-bordered">'
            .'<thead><tr>'
                .'<th>Demande</th>'
                .'<th>Statut</th>'
                .'<th>Priorite</th>'
                .$categoryHeader
                .'<th>Ressources</th>'
                .'<th>Createur</th>'
                .'<th>Mise a jour</th>'
                .'<th></th>'
            .'</tr></thead><tbody>';

        foreach ($demands as $demand) {
            $resources = SuiviDemandesRepository::resourcesForDemand((int) $demand['id']);
            $category = isset($demand['categorie']) ? $demand['categorie'] : '';
            $detailUrl = 'compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demand['id']);
            $html .= '<tr>'
                .'<td><a href="'.$detailUrl.'">#'.self::html($demand['id']).' - '.self::html($demand['titre']).'</a></td>'
                .'<td>'.self::renderStatus($demand['statut']).'</td>'
                .'<td>'.self::renderPriority($demand['priorite']).'</td>'
                .(SuiviDemandesConfig::categoriesEnabled() ? '<td>'.self::renderCategory($category).'</td>' : '')
                .'<td>'.self::html(count($resources) > 0 ? implode(', ', $resources) : 'Aucune ressource').'</td>'
                .'<td>'.self::html($demand['createur']).'</td>'
                .'<td>'.self::html(self::formatDate($demand['updated_at'])).'</td>'
                .'<td><a class="btn btn-default btn-sm" href="'.$detailUrl.'">Voir</a></td>'
                .'</tr>';
        }

        return $html.'</tbody></table></div>';
    }

    private static function renderListViewSwitch($filters, $viewMode)
    {
        $targetMode = $viewMode === 'ligne' ? 'carte' : 'ligne';
        $title = $targetMode === 'ligne' ? 'Afficher en lignes' : 'Afficher en cartes enrichies';
        $iconClass = $targetMode === 'ligne' ? 'suivi-demandes-view-lines' : 'suivi-demandes-view-cards';
        $iconSpans = $targetMode === 'ligne' ? '<span></span><span></span><span></span>' : '<span></span><span></span><span></span><span></span>';
        $params = array_merge(array('pc' => SuiviDemandesConfig::MODULE), self::filterParams($filters), array('suivi_vue' => $targetMode));

        return '<div class="suivi-demandes-list-switch">'
            .'<a class="btn btn-default btn-sm suivi-demandes-view-toggle" href="'.self::html(self::accountUrl($params)).'" title="'.self::html($title).'" aria-label="'.self::html($title).'">'
                .'<span class="suivi-demandes-view-icon '.self::html($iconClass).'" aria-hidden="true">'.$iconSpans.'</span>'
            .'</a>'
            .'</div>';
    }

    private static function renderDashboard($counts)
    {
        $items = array(
            array(
                'label' => SuiviDemandesConfig::statusLabel('ouverte'),
                'count' => isset($counts['ouverte']) ? (int) $counts['ouverte'] : 0,
                'params' => array('pc' => SuiviDemandesConfig::MODULE, 'suivi_statut' => 'ouverte'),
            ),
            array(
                'label' => SuiviDemandesConfig::statusLabel('en_cours'),
                'count' => isset($counts['en_cours']) ? (int) $counts['en_cours'] : 0,
                'params' => array('pc' => SuiviDemandesConfig::MODULE, 'suivi_statut' => 'en_cours'),
            ),
            array(
                'label' => SuiviDemandesConfig::statusLabel('cloturee'),
                'count' => isset($counts['cloturee']) ? (int) $counts['cloturee'] : 0,
                'params' => array('pc' => SuiviDemandesConfig::MODULE, 'suivi_statut' => 'cloturee'),
            ),
            array(
                'label' => 'Priorite '.SuiviDemandesConfig::priorityLabel('haute'),
                'count' => isset($counts['haute']) ? (int) $counts['haute'] : 0,
                'params' => array('pc' => SuiviDemandesConfig::MODULE, 'suivi_priorite' => 'haute'),
            ),
            array(
                'label' => 'Creees par moi',
                'count' => isset($counts['created']) ? (int) $counts['created'] : 0,
                'params' => array('pc' => SuiviDemandesConfig::MODULE, 'suivi_perimetre' => 'created'),
            ),
            array(
                'label' => 'Suivies par moi',
                'count' => isset($counts['followed']) ? (int) $counts['followed'] : 0,
                'params' => array('pc' => SuiviDemandesConfig::MODULE, 'suivi_perimetre' => 'followed'),
            ),
        );

        $html = '<div class="well suivi-demandes-dashboard">'
            .'<h3>Synthese</h3>'
            .'<div class="row">';

        foreach ($items as $item) {
            $html .= '<div class="col-sm-2 col-xs-6">'
                .'<a class="btn btn-default btn-block" href="'.self::html(self::accountUrl($item['params'])).'">'
                    .'<strong>'.self::html($item['count']).'</strong><br>'
                    .self::html($item['label'])
                .'</a>'
            .'</div>';
        }

        return $html.'</div></div>';
    }

    private static function renderDemandFilters($filters, $exportUrl, $viewMode)
    {
        $form = '<form method="get" action="compte.php" class="form-inline">'
                .'<input type="hidden" name="pc" value="suivi_demandes">'
                .'<input type="hidden" name="suivi_vue" value="'.self::html($viewMode).'">'
                .'<div class="form-group">'
                    .'<label for="suivi_demandes_filter_scope">Perimetre</label> '
                    .'<select class="form-control" id="suivi_demandes_filter_scope" name="suivi_perimetre">'
                        .self::renderScopeFilterOptions($filters['scope'])
                    .'</select>'
                .'</div> '
                .'<div class="form-group">'
                    .'<label for="suivi_demandes_filter_status">Statut</label> '
                    .'<select class="form-control" id="suivi_demandes_filter_status" name="suivi_statut">'
                        .self::renderStatusFilterOptions($filters['status'])
                    .'</select>'
                .'</div> '
                .'<div class="form-group">'
                    .'<label for="suivi_demandes_filter_priority">Priorite</label> '
                    .'<select class="form-control" id="suivi_demandes_filter_priority" name="suivi_priorite">'
                        .self::renderPriorityFilterOptions($filters['priority'])
                    .'</select>'
                .'</div> '
                .(SuiviDemandesConfig::categoriesEnabled()
                    ? '<div class="form-group">'
                        .'<label for="suivi_demandes_filter_category">Categorie</label> '
                        .'<select class="form-control" id="suivi_demandes_filter_category" name="suivi_categorie">'
                            .self::renderCategoryFilterOptions($filters['category'])
                        .'</select>'
                    .'</div> '
                    : '')
                .'<div class="form-group">'
                    .'<label for="suivi_demandes_filter_search">Recherche</label> '
                    .'<input class="form-control" id="suivi_demandes_filter_search" type="text" name="suivi_recherche" maxlength="100" value="'.self::html($filters['search']).'">'
                .'</div> '
                .'<div class="form-group">'
                    .'<label for="suivi_demandes_filter_limit">Afficher</label> '
                    .'<select class="form-control" id="suivi_demandes_filter_limit" name="suivi_limite">'
                        .self::renderLimitFilterOptions($filters['limit'])
                    .'</select>'
                .'</div> '
                .'<button type="submit" class="btn btn-primary">Filtrer</button> '
                .'<a class="btn btn-default" href="compte.php?pc=suivi_demandes">Reinitialiser</a>'
            .'</form>';

        return '<p class="suivi-demandes-filter-actions">'
            .self::renderModalButton('suivi-demandes-filter-modal', 'Filtres', 'btn btn-default')
            .' <a class="btn btn-default" href="'.self::html($exportUrl).'">Exporter CSV</a>'
            .'</p>'
            .self::renderModal('suivi-demandes-filter-modal', 'Filtres des demandes', $form);
    }

    private static function renderDemandDetail($login, $demand, $closeCommentValue)
    {
        $resources = SuiviDemandesRepository::resourcesForDemand((int) $demand['id']);
        $description = trim((string) $demand['description']);
        $actions = self::renderDemandActions($login, $demand, $closeCommentValue);

        $html = '<h2>Demande #'.self::html($demand['id']).'</h2>'
            .'<div class="suivi-demandes-detail-header" style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:wrap;">'
                .'<h3 style="margin-top:0;">'.self::html($demand['titre']).'</h3>'
                .$actions
            .'</div>'
            .'<div class="suivi-demandes-detail-grid">'
                .'<section class="suivi-demandes-panel">'
                    .'<h3>Resume</h3>'
                    .'<dl class="suivi-demandes-summary-list">'
                        .'<dt>Statut</dt><dd>'.self::renderStatus($demand['statut']).'</dd>'
                        .'<dt>Priorite</dt><dd>'.self::renderPriority($demand['priorite']).'</dd>'
                        .(SuiviDemandesConfig::categoriesEnabled() ? '<dt>Categorie</dt><dd>'.self::renderCategory(isset($demand['categorie']) ? $demand['categorie'] : '').'</dd>' : '')
                        .'<dt>Createur</dt><dd>'.self::html($demand['createur']).'</dd>'
                        .'<dt>Ressources</dt><dd>'.self::html(count($resources) > 0 ? implode(', ', $resources) : 'Aucune ressource').'</dd>'
                        .'<dt>Creee le</dt><dd>'.self::html(self::formatDate($demand['created_at'])).'</dd>'
                        .'<dt>Mise a jour</dt><dd>'.self::html(self::formatDate($demand['updated_at'])).'</dd>'
                        .((int) $demand['closed_at'] > 0 ? '<dt>Cloturee le</dt><dd>'.self::html(self::formatDate($demand['closed_at'])).'</dd>' : '')
                    .'</dl>'
                .'</section>'
                .'<section class="suivi-demandes-panel">'
                    .'<h3>Description</h3>';

        if ($description === '') {
            $html .= '<div class="alert alert-info">Aucune description.</div>';
        } else {
            $html .= '<div class="suivi-demandes-description">'.nl2br(self::html($description)).'</div>';
        }

        return $html.'</section></div>';
    }

    private static function renderDemandReservations($demandeId)
    {
        $reservations = SuiviDemandesRepository::reservationsForDemand((int) $demandeId);
        if (count($reservations) === 0) {
            return '';
        }

        $html = '<section class="suivi-demandes-panel">'
            .'<h3>Reservations associees</h3>'
            .'<div class="suivi-demandes-item-list">';

        foreach ($reservations as $reservation) {
            $entryId = (int) $reservation['entry_id'];
            $hasEntry = isset($reservation['start_time']) && (int) $reservation['start_time'] > 0;
            $name = $hasEntry ? (string) $reservation['name'] : 'Reservation introuvable';
            $room = '';
            if ($hasEntry) {
                $room = trim((string) $reservation['area_name'].' > '.(string) $reservation['room_name']);
            }

            $title = self::html($name);

            if ($hasEntry) {
                $title = '<a href="../app.php?p=vuereservation&amp;id='.self::html($entryId).'&amp;mode=page">'.$title.'</a>';
            }

            $html .= '<article class="suivi-demandes-item">'
                .'<div class="suivi-demandes-item-head">'
                    .'<div class="suivi-demandes-item-title">'.$title.'</div>'
                    .'<div class="suivi-demandes-item-meta">#'.self::html($entryId).'</div>'
                .'</div>'
                .'<dl class="suivi-demandes-item-details">'
                    .'<dt>Ressource</dt><dd>'.self::html($room !== '' ? $room : '-').'</dd>'
                    .'<dt>Debut</dt><dd>'.self::html($hasEntry ? self::formatDate($reservation['start_time']) : '-').'</dd>'
                    .'<dt>Fin</dt><dd>'.self::html($hasEntry ? self::formatDate($reservation['end_time']) : '-').'</dd>'
                .'</dl>'
                .'</article>';
        }

        return $html.'</div></section>';
    }

    private static function renderDemandAttachments($login, $demand)
    {
        $demandeId = (int) $demand['id'];
        $includeInternal = SuiviDemandesRights::canViewInternalComments($login, $demand);
        $attachments = SuiviDemandesRepository::attachmentsForDemand($demandeId, $includeInternal);
        $comments = SuiviDemandesRepository::commentsForDemand($demandeId, $includeInternal);
        $commentLabels = self::attachmentCommentLabels($comments);
        $attachmentsEnabled = SuiviDemandesConfig::attachmentsEnabled();
        $canUpload = $attachmentsEnabled && SuiviDemandesRights::canUploadAttachment($login, $demand);
        if (!$attachmentsEnabled && count($attachments) === 0) {
            return '';
        }

        $html = '<h3>Pieces jointes</h3>';

        if (count($attachments) === 0) {
            $html .= '<div class="alert alert-info">Aucune piece jointe.</div>';
        } else {
            $html .= '<div class="suivi-demandes-item-list">';

            foreach ($attachments as $attachment) {
                $downloadUrl = '../personnalisation/modules/suivi_demandes/download.php?id='.(int) $attachment['id'];
                $action = '';

                if (SuiviDemandesRights::canDeleteAttachment($login, $demand, $attachment)) {
                    $deleteModalId = 'suivi-demandes-delete-attachment-modal-'.(int) $attachment['id'];
                    $deleteForm = '<form method="post" action="compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demandeId).'">'
                        .'<input type="hidden" name="suivi_demandes_action" value="delete_attachment">'
                        .'<input type="hidden" name="demande_id" value="'.self::html($demandeId).'">'
                        .'<input type="hidden" name="attachment_id" value="'.self::html($attachment['id']).'">'
                        .'<p>Supprimer la piece jointe '.self::html($attachment['original_name']).' ?</p>'
                        .'<button type="submit" class="btn btn-default btn-sm">Supprimer</button>'
                        .'</form>';
                    $action = self::renderModalButton($deleteModalId, 'Supprimer', 'btn btn-default btn-sm')
                        .self::renderModal($deleteModalId, 'Supprimer une piece jointe', $deleteForm);
                }

                $html .= '<article class="suivi-demandes-item">'
                    .'<div class="suivi-demandes-item-head">'
                        .'<div>'
                            .'<div class="suivi-demandes-item-title"><a href="'.self::html($downloadUrl).'">'.self::html($attachment['original_name']).'</a></div>'
                            .'<div class="suivi-demandes-item-meta">'.self::html(self::formatBytes((int) $attachment['taille'])).' - '.self::html($attachment['uploader']).' - '.self::html(self::formatDate($attachment['created_at'])).'</div>'
                        .'</div>'
                    .'</div>'
                    .'<div class="suivi-demandes-item-body">'.self::html(self::attachmentCommentLabel($attachment, $commentLabels)).'</div>'
                    .($action !== '' ? '<div class="suivi-demandes-item-actions">'.$action.'</div>' : '')
                    .'</article>';
            }

            $html .= '</div>';
        }

        if (!$canUpload) {
            if (!$attachmentsEnabled) {
                return $html.'<div class="alert alert-info">L ajout de pieces jointes est desactive.</div>';
            }

            if ($demand['statut'] === 'cloturee') {
                return $html.'<div class="alert alert-info">Cette demande est cloturee. Aucune piece jointe ne peut etre ajoutee.</div>';
            }

            return $html;
        }

        $commentSelect = '';
        if (count($comments) > 0) {
            $commentSelect = '<div class="form-group row col-sm-12">'
                    .'<label class="col-sm-3" for="suivi_demandes_attachment_comment">Commentaire</label>'
                    .'<div class="col-sm-9"><select class="form-control" id="suivi_demandes_attachment_comment" name="attachment_comment_id">'
                        .'<option value="0">Demande sans commentaire</option>'
                        .self::renderAttachmentCommentOptions($comments)
                    .'</select></div>'
                .'</div>';
        }

        $form = '<form method="post" enctype="multipart/form-data" action="compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demandeId).'">'
                .'<input type="hidden" name="suivi_demandes_action" value="upload_attachment">'
                .'<input type="hidden" name="demande_id" value="'.self::html($demandeId).'">'
                .'<div class="form-group row col-sm-12">'
                    .'<label class="col-sm-3" for="suivi_demandes_attachment">Fichier</label>'
                    .'<div class="col-sm-9">'
                        .'<input class="form-control" id="suivi_demandes_attachment" type="file" name="suivi_attachment" required>'
                        .'<p class="help-block">Extensions autorisees : '.self::html(implode(', ', self::allowedAttachmentExtensions())).'. Taille maximale : '.self::html(self::formatBytes(SuiviDemandesConfig::attachmentMaxBytes())).'.</p>'
                    .'</div>'
                .'</div>'
                .$commentSelect
                .'<div class="form-group row col-sm-12">'
                    .'<div class="col-sm-offset-3 col-sm-9"><button type="submit" class="btn btn-primary">Ajouter la piece jointe</button></div>'
                .'</div>'
            .'</form>';

        return $html.'<p>'.self::renderModalButton('suivi-demandes-attachment-modal', 'Ajouter une piece jointe', 'btn btn-primary').'</p>'
            .self::renderModal('suivi-demandes-attachment-modal', 'Ajouter une piece jointe', $form);
    }

    private static function attachmentCommentLabels($comments)
    {
        $labels = array();
        foreach ($comments as $comment) {
            $id = (int) $comment['id'];
            $label = self::attachmentCommentDisplayLabel($comment, 120);
            $labels[$id] = $label;
        }

        return $labels;
    }

    private static function attachmentCommentLabel($attachment, $commentLabels)
    {
        $commentId = isset($attachment['commentaire_id']) ? (int) $attachment['commentaire_id'] : 0;
        if ($commentId <= 0) {
            return 'Demande sans commentaire';
        }

        return isset($commentLabels[$commentId]) ? $commentLabels[$commentId] : 'Commentaire introuvable';
    }

    private static function renderAttachmentCommentOptions($comments)
    {
        $html = '';
        foreach ($comments as $comment) {
            $id = (int) $comment['id'];
            $html .= '<option value="'.self::html($id).'">'.self::html(self::attachmentCommentDisplayLabel($comment, 90)).'</option>';
        }

        return $html;
    }

    private static function attachmentCommentDisplayLabel($comment, $maxLength)
    {
        $snippet = trim(preg_replace('/\s+/', ' ', (string) $comment['commentaire']));
        if ($snippet === '') {
            $snippet = 'Commentaire sans texte';
        }

        if (strlen($snippet) > $maxLength) {
            $snippet = substr($snippet, 0, $maxLength).'...';
        }

        $label = $snippet.' (#'.(int) $comment['id'].' - '.self::formatDate($comment['created_at']).' - '.$comment['auteur'];
        if (isset($comment['interne']) && (int) $comment['interne'] === 1) {
            $label .= ' - interne';
        }
        $label .= ')';

        return $label;
    }

    private static function renderDemandResourceManagement($login, $demand, $selectedRoomId)
    {
        $demandeId = (int) $demand['id'];
        $resources = SuiviDemandesRepository::resourcesWithIdsForDemand($demandeId);
        $canManage = SuiviDemandesRights::canManageResources($login, $demand);
        $availableResources = $canManage ? SuiviDemandesRepository::resourcesAvailableToAdd($login, $demandeId) : array();
        $resourceCount = count($resources);

        $html = '<section class="suivi-demandes-panel"><h3>Ressources associees</h3>';

        if ($resourceCount > 0) {
            $html .= '<div class="suivi-demandes-item-list">';

            foreach ($resources as $resource) {
                $roomId = (int) $resource['id'];
                $hasReservationLink = SuiviDemandesRepository::resourceHasReservationLink($demandeId, $roomId);
                $canRemove = $resourceCount > 1
                    && !$hasReservationLink
                    && SuiviDemandesRepository::userManagesResource($login, $roomId);
                $action = '';

                if ($canManage && $canRemove) {
                    $removeModalId = 'suivi-demandes-remove-resource-modal-'.self::modalIdSuffix($roomId);
                    $removeForm = '<form method="post" action="compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demandeId).'">'
                        .'<input type="hidden" name="suivi_demandes_action" value="remove_resource">'
                        .'<input type="hidden" name="demande_id" value="'.self::html($demandeId).'">'
                        .'<input type="hidden" name="resource_room_id" value="'.self::html($roomId).'">'
                        .'<p>Retirer la ressource '.self::html($resource['label']).' de cette demande ?</p>'
                        .'<button type="submit" class="btn btn-default btn-sm">Retirer</button>'
                        .'</form>';
                    $action = self::renderModalButton($removeModalId, 'Retirer', 'btn btn-default btn-sm')
                        .self::renderModal($removeModalId, 'Retirer une ressource', $removeForm);
                } elseif ($canManage && $hasReservationLink) {
                    $action = '<span class="text-muted">Liee a une reservation</span>';
                } elseif ($canManage && $resourceCount <= 1) {
                    $action = '<span class="text-muted">Derniere ressource</span>';
                } elseif ($canManage) {
                    $action = '<span class="text-muted">Droit insuffisant</span>';
                }

                $html .= '<article class="suivi-demandes-item">'
                    .'<div class="suivi-demandes-item-head">'
                        .'<div class="suivi-demandes-item-title">'.self::html($resource['label']).'</div>'
                    .'</div>'
                    .($action !== '' ? '<div class="suivi-demandes-item-actions">'.$action.'</div>' : '')
                    .'</article>';
            }

            $html .= '</div>';
        } else {
            $html .= '<div class="alert alert-info">Aucune ressource associee.</div>';
        }

        if (!$canManage) {
            return $html.'</section>';
        }

        if (count($availableResources) === 0) {
            return $html.'<div class="alert alert-info">Aucune ressource gerable a ajouter.</div></section>';
        }

        $form = '<form method="post" action="compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demandeId).'">'
                .'<input type="hidden" name="suivi_demandes_action" value="add_resource">'
                .'<input type="hidden" name="demande_id" value="'.self::html($demandeId).'">'
                .'<div class="form-group row col-sm-12">'
                    .'<label class="col-sm-3" for="suivi_demandes_resource_room">Ressource</label>'
                    .'<div class="col-sm-9"><select class="form-control" id="suivi_demandes_resource_room" name="resource_room_id" required>'
                        .'<option value="">Choisir une ressource</option>'
                        .self::renderResourceOptions($availableResources, array((int) $selectedRoomId))
                    .'</select></div>'
                .'</div>'
                .'<div class="form-group row col-sm-12">'
                    .'<div class="col-sm-offset-3 col-sm-9"><button type="submit" class="btn btn-primary">Ajouter la ressource</button></div>'
                .'</div>'
            .'</form>';

        return $html.'<p>'.self::renderModalButton('suivi-demandes-resource-modal', 'Ajouter une ressource', 'btn btn-primary').'</p>'
            .self::renderModal('suivi-demandes-resource-modal', 'Ajouter une ressource', $form)
            .'</section>';
    }

    private static function renderDemandActions($login, $demand, $closeCommentValue)
    {
        $buttons = '';
        $modals = '';
        $demandeId = (int) $demand['id'];

        if (SuiviDemandesRights::canStartDemand($login, $demand)) {
            $form = '<form method="post" action="compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demandeId).'">'
                .'<input type="hidden" name="suivi_demandes_action" value="start">'
                .'<input type="hidden" name="demande_id" value="'.self::html($demandeId).'">'
                .'<p>Confirmer le passage de cette demande en cours.</p>'
                .'<button type="submit" class="btn btn-primary">Passer en cours</button>'
                .'</form>';
            $buttons .= self::renderModalButton('suivi-demandes-start-modal', 'Passer en cours', 'btn btn-primary').' ';
            $modals .= self::renderModal('suivi-demandes-start-modal', 'Passer la demande en cours', $form);
        }

        if (SuiviDemandesRights::canCloseDemand($login, $demand)) {
            $form = '<form method="post" action="compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demandeId).'">'
                .'<input type="hidden" name="suivi_demandes_action" value="close">'
                .'<input type="hidden" name="demande_id" value="'.self::html($demandeId).'">'
                .'<p>Ajouter un commentaire associe a la cloture de cette demande.</p>'
                .'<div class="form-group">'
                    .'<label for="suivi_demandes_commentaire_cloture">Commentaire de cloture</label>'
                    .'<textarea class="form-control" id="suivi_demandes_commentaire_cloture" name="commentaire_cloture" rows="4" maxlength="5000" required>'.self::html($closeCommentValue).'</textarea>'
                .'</div>'
                .'<button type="submit" class="btn btn-warning">Cloturer la demande</button>'
                .'</form>';
            $buttons .= self::renderModalButton('suivi-demandes-close-modal', 'Cloturer la demande', 'btn btn-warning').' ';
            $modals .= self::renderModal('suivi-demandes-close-modal', 'Cloturer la demande', $form);
        }

        if (SuiviDemandesRights::canReopenDemand($login, $demand)) {
            $form = '<form method="post" action="compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demandeId).'">'
                .'<input type="hidden" name="suivi_demandes_action" value="reopen">'
                .'<input type="hidden" name="demande_id" value="'.self::html($demandeId).'">'
                .'<div class="form-group">'
                .'<label for="suivi_demandes_reopen_status">Reouvrir avec le statut</label>'
                .'<select class="form-control" id="suivi_demandes_reopen_status" name="reopen_status">'
                    .'<option value="ouverte">'.self::html(SuiviDemandesConfig::statusLabel('ouverte')).'</option>'
                    .'<option value="en_cours">'.self::html(SuiviDemandesConfig::statusLabel('en_cours')).'</option>'
                .'</select>'
                .'</div>'
                .'<button type="submit" class="btn btn-primary">Reouvrir la demande</button>'
                .'</form>';
            $buttons .= self::renderModalButton('suivi-demandes-reopen-modal', 'Reouvrir la demande', 'btn btn-primary').' ';
            $modals .= self::renderModal('suivi-demandes-reopen-modal', 'Reouvrir la demande', $form);
        }

        if (SuiviDemandesRights::canResendManagerNotification($login, $demand)) {
            $form = '<form method="post" action="compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demandeId).'">'
                .'<input type="hidden" name="suivi_demandes_action" value="resend_manager_notification">'
                .'<input type="hidden" name="demande_id" value="'.self::html($demandeId).'">'
                .'<p>Renvoyer une notification e-mail aux gestionnaires des ressources associees a cette demande.</p>'
                .'<p>Le createur et les suiveurs ne seront pas destinataires de ce renvoi cible, sauf s ils sont egalement gestionnaires d une ressource concernee.</p>'
                .'<button type="submit" class="btn btn-primary">Renvoyer aux gestionnaires</button>'
                .'</form>';
            $buttons .= self::renderModalButton('suivi-demandes-manager-notification-modal', 'Renvoyer aux gestionnaires', 'btn btn-primary').' ';
            $modals .= self::renderModal('suivi-demandes-manager-notification-modal', 'Renvoyer la notification aux gestionnaires', $form);
        }

        if (SuiviDemandesRights::canDeleteDemand($login, $demand)) {
            $form = '<form method="post" action="compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demandeId).'">'
                .'<input type="hidden" name="suivi_demandes_action" value="delete_demand">'
                .'<input type="hidden" name="demande_id" value="'.self::html($demandeId).'">'
                .'<p>Supprimer definitivement cette demande et toutes les donnees associees du module : pieces jointes, commentaires, historique, suiveurs, ressources et liens de reservations.</p>'
                .'<p><strong>Cette action est irreversible.</strong></p>'
                .'<button type="submit" class="btn btn-danger">Supprimer la demande</button>'
                .'</form>';
            $buttons .= self::renderModalButton('suivi-demandes-delete-modal', 'Supprimer la demande', 'btn btn-danger').' ';
            $modals .= self::renderModal('suivi-demandes-delete-modal', 'Supprimer la demande', $form);
        }

        if ($buttons === '') {
            return '';
        }

        return '<div class="suivi-demandes-actions" style="text-align:right;">'.$buttons.'</div>'.$modals;
    }

    private static function renderDemandFollowers($login, $demand, $selectedLogin, $search)
    {
        $demandeId = (int) $demand['id'];
        $followers = SuiviDemandesRepository::followersForDemand($demandeId);
        $canManage = SuiviDemandesRights::canManageFollowers($login, $demand);
        $html = '<section class="suivi-demandes-panel"><h3>Suiveurs</h3>';

        if (count($followers) === 0) {
            $html .= '<div class="alert alert-info">Aucun suiveur.</div>';
        } else {
            $html .= '<div class="suivi-demandes-item-list">';

            foreach ($followers as $follower) {
                $action = '';

                if ($canManage) {
                    $removeModalId = 'suivi-demandes-remove-follower-modal-'.self::modalIdSuffix($follower['login']);
                    $removeForm = '<form method="post" action="compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demandeId).'">'
                        .'<input type="hidden" name="suivi_demandes_action" value="remove_follower">'
                        .'<input type="hidden" name="demande_id" value="'.self::html($demandeId).'">'
                        .'<input type="hidden" name="follower_login" value="'.self::html($follower['login']).'">'
                        .'<p>Retirer '.self::html(self::userLabel($follower)).' des suiveurs ?</p>'
                        .'<button type="submit" class="btn btn-default btn-sm">Retirer</button>'
                        .'</form>';
                    $action = self::renderModalButton($removeModalId, 'Retirer', 'btn btn-default btn-sm')
                        .self::renderModal($removeModalId, 'Retirer un suiveur', $removeForm);
                }

                $html .= '<article class="suivi-demandes-item">'
                    .'<div class="suivi-demandes-item-head">'
                        .'<div class="suivi-demandes-item-title">'.self::html(self::userLabel($follower)).'</div>'
                    .'</div>'
                    .($action !== '' ? '<div class="suivi-demandes-item-actions">'.$action.'</div>' : '')
                    .'</article>';
            }

            $html .= '</div>';
        }

        if (!$canManage) {
            return $html.'</section>';
        }

        $search = trim((string) $search);
        $body = '<form method="get" action="compte.php" class="form-inline">'
                .'<input type="hidden" name="pc" value="suivi_demandes">'
                .'<input type="hidden" name="demande_id" value="'.self::html($demandeId).'">'
                .'<div class="form-group">'
                    .'<label for="suivi_demandes_follower_search">Recherche</label> '
                    .'<input class="form-control" id="suivi_demandes_follower_search" type="text" name="suivi_user_search" maxlength="80" value="'.self::html($search).'" placeholder="Nom, prenom ou login">'
                .'</div> '
                .'<button type="submit" class="btn btn-default">Rechercher</button> '
                .'<a class="btn btn-default" href="compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demandeId).'">Reinitialiser</a>'
            .'</form>';

        if ($search !== '') {
            $searchedUsers = SuiviDemandesRepository::activeUsersAvailableAsFollowers($demandeId, $demand['createur'], $search);
            if (count($searchedUsers) === 0) {
                $body .= '<div class="alert alert-info">Aucun utilisateur ajoutable comme suiveur pour cette recherche.</div>';
            } else {
                $body .= '<table class="table table-striped table-bordered">'
                    .'<thead><tr>'
                        .'<th>Utilisateur trouve</th>'
                        .'<th>Action</th>'
                    .'</tr></thead><tbody>';

                foreach ($searchedUsers as $user) {
                    $userLogin = (string) $user['login'];
                    $body .= '<tr>'
                        .'<td>'.self::html(self::userLabel($user)).'</td>'
                        .'<td><form method="post" action="compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demandeId).'&amp;suivi_user_search='.self::html(rawurlencode($search)).'">'
                            .'<input type="hidden" name="suivi_demandes_action" value="add_follower">'
                            .'<input type="hidden" name="demande_id" value="'.self::html($demandeId).'">'
                            .'<input type="hidden" name="follower_login" value="'.self::html($userLogin).'">'
                            .'<button type="submit" class="btn btn-primary btn-sm">Selectionner</button>'
                        .'</form></td>'
                    .'</tr>';
                }

                $body .= '</tbody></table>';
            }
        }

        $availableUsers = SuiviDemandesRepository::activeUsersAvailableAsFollowers($demandeId, $demand['createur']);
        if (count($availableUsers) === 0) {
            $body .= '<div class="alert alert-info">Aucun utilisateur ajoutable comme suiveur.</div>';

            return $html.'<p>'.self::renderModalButton('suivi-demandes-follower-modal', 'Ajouter un suiveur', 'btn btn-primary').'</p>'
                .self::renderModal('suivi-demandes-follower-modal', 'Ajouter un suiveur', $body)
                .'</section>';
        }

        $body .= '<p class="help-block">La liste deroulante reste limitee aux '.self::html(SuiviDemandesRepository::MAX_USER_SELECT).' premiers utilisateurs actifs ajoutables.</p>'
            .'<form method="post" action="compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demandeId).'">'
                .'<input type="hidden" name="suivi_demandes_action" value="add_follower">'
                .'<input type="hidden" name="demande_id" value="'.self::html($demandeId).'">'
                .'<div class="form-group row col-sm-12">'
                    .'<label class="col-sm-3" for="suivi_demandes_follower">Utilisateur</label>'
                    .'<div class="col-sm-9"><select class="form-control" id="suivi_demandes_follower" name="follower_login" required>'
                        .'<option value="">Choisir un utilisateur</option>'
                        .self::renderUserOptions($availableUsers, $selectedLogin)
                    .'</select></div>'
                .'</div>'
                .'<div class="form-group row col-sm-12">'
                    .'<div class="col-sm-offset-3 col-sm-9"><button type="submit" class="btn btn-primary">Ajouter le suiveur</button></div>'
                .'</div>'
            .'</form>';

        return $html.'<p>'.self::renderModalButton('suivi-demandes-follower-modal', 'Ajouter un suiveur', 'btn btn-primary').'</p>'
            .self::renderModal('suivi-demandes-follower-modal', 'Ajouter un suiveur', $body)
            .'</section>';
    }

    private static function renderUserOptions($users, $selectedLogin)
    {
        $html = '';
        foreach ($users as $user) {
            $login = (string) $user['login'];
            $html .= '<option value="'.self::html($login).'"'.self::selected($selectedLogin, $login).'>'.self::html(self::userLabel($user)).'</option>';
        }

        return $html;
    }

    private static function userLabel($user)
    {
        $name = trim((string) $user['prenom'].' '.(string) $user['nom']);
        if ($name === '') {
            return (string) $user['login'];
        }

        return $name.' ('.$user['login'].')';
    }

    private static function renderDemandComments($login, $demand)
    {
        $includeInternal = SuiviDemandesRights::canViewInternalComments($login, $demand);
        $comments = SuiviDemandesRepository::commentsForDemand((int) $demand['id'], $includeInternal);
        $html = '<h3>Commentaires</h3>';

        if (count($comments) === 0) {
            return $html.'<div class="alert alert-info">Aucun commentaire.</div>';
        }

        $html .= '<div class="suivi-demandes-item-list">';

        foreach ($comments as $comment) {
            $internal = isset($comment['interne']) && (int) $comment['interne'] === 1;
            $label = $internal ? ' <span class="label label-info">Interne</span>' : '';
            $html .= '<article class="suivi-demandes-item">'
                .'<div class="suivi-demandes-item-head">'
                    .'<div class="suivi-demandes-item-title">'.self::html($comment['auteur']).$label.'</div>'
                    .'<div class="suivi-demandes-item-meta">'.self::html(self::formatDate($comment['created_at'])).'</div>'
                .'</div>'
                .'<div class="suivi-demandes-item-body">'.nl2br(self::html($comment['commentaire'])).'</div>'
                .'</article>';
        }

        return $html.'</div>';
    }

    private static function renderCommentForm($login, $demand, $value, $internalValue)
    {
        if (!SuiviDemandesRights::canCommentDemand($login, $demand)) {
            if ($demand['statut'] === 'cloturee') {
                return '<div class="alert alert-info">Cette demande est cloturee. Aucun commentaire ne peut etre ajoute.</div>';
            }

            return '';
        }

        $internalOption = '';
        if (SuiviDemandesRights::canAddInternalComment($login, $demand)) {
            $internalOption = '<div class="form-group row col-sm-12">'
                    .'<div class="col-sm-offset-3 col-sm-9">'
                        .'<label><input type="checkbox" name="commentaire_interne" value="1"'.($internalValue ? ' checked' : '').'> Commentaire interne visible uniquement par les gestionnaires et administrateurs</label>'
                    .'</div>'
                .'</div>';
        }

        $form = '<form method="post" action="compte.php?pc=suivi_demandes&amp;demande_id='.self::html($demand['id']).'">'
                .'<input type="hidden" name="suivi_demandes_action" value="comment">'
                .'<input type="hidden" name="demande_id" value="'.self::html($demand['id']).'">'
                .'<div class="form-group row col-sm-12">'
                    .'<label class="col-sm-3" for="suivi_demandes_commentaire">Commentaire</label>'
                    .'<div class="col-sm-9"><textarea class="form-control" id="suivi_demandes_commentaire" name="commentaire" rows="4" maxlength="5000" required>'.self::html($value).'</textarea></div>'
                .'</div>'
                .$internalOption
                .'<div class="form-group row col-sm-12">'
                    .'<div class="col-sm-offset-3 col-sm-9"><button type="submit" class="btn btn-primary">Ajouter le commentaire</button></div>'
                .'</div>'
            .'</form>';

        return '<p>'.self::renderModalButton('suivi-demandes-comment-modal', 'Ajouter un commentaire', 'btn btn-primary').'</p>'
            .self::renderModal('suivi-demandes-comment-modal', 'Ajouter un commentaire', $form);
    }

    private static function renderDemandHistory($login, $demand)
    {
        $includeInternal = SuiviDemandesRights::canViewInternalComments($login, $demand);
        $history = SuiviDemandesRepository::historyForDemand((int) $demand['id'], $includeInternal);
        $html = '<section class="suivi-demandes-panel"><h3>Historique</h3>';

        if (count($history) === 0) {
            return $html.'<div class="alert alert-info">Aucun historique.</div></section>';
        }

        $html .= '<div class="suivi-demandes-item-list suivi-demandes-compact-history">';

        foreach ($history as $entry) {
            $details = trim((string) $entry['details']);
            $html .= '<article class="suivi-demandes-item">'
                .'<div class="suivi-demandes-item-head">'
                    .'<div>'
                        .'<span class="suivi-demandes-item-title">'.self::html($entry['action']).'</span> '
                        .'<span class="suivi-demandes-item-meta">'.self::html($entry['auteur']).'</span>'
                    .'</div>'
                    .'<div class="suivi-demandes-item-meta">'.self::html(self::formatDate($entry['created_at'])).'</div>'
                .'</div>'
                .($details !== '' ? '<div class="suivi-demandes-item-body">'.self::html($details).'</div>' : '')
                .'</article>';
        }

        return $html.'</div></section>';
    }

    private static function renderAlerts($errors, $message)
    {
        $html = '';
        if ($message !== '') {
            $html .= '<div class="alert alert-success">'.self::html($message).'</div>';
        }

        foreach ($errors as $error) {
            $html .= '<div class="alert alert-danger">'.self::html($error).'</div>';
        }

        return $html;
    }

    private static function renderPriorityOptions($selected)
    {
        $html = '';
        foreach (SuiviDemandesConfig::priorities() as $value => $label) {
            $html .= '<option value="'.self::html($value).'"'.self::selected($selected, $value).'>'.self::html($label).'</option>';
        }
        return $html;
    }

    private static function renderCategoryOptions($selected)
    {
        $html = '<option value=""'.self::selected($selected, '').'>Sans categorie</option>';
        foreach (SuiviDemandesConfig::categoryOptions() as $category) {
            $html .= '<option value="'.self::html($category).'"'.self::selected($selected, $category).'>'.self::html($category).'</option>';
        }

        return $html;
    }

    private static function renderStatusFilterOptions($selected)
    {
        $html = '<option value=""'.self::selected($selected, '').'>Tous</option>';
        foreach (SuiviDemandesConfig::statuses() as $value => $label) {
            $html .= '<option value="'.self::html($value).'"'.self::selected($selected, $value).'>'.self::html($label).'</option>';
        }

        return $html;
    }

    private static function renderScopeFilterOptions($selected)
    {
        $options = array(
            '' => 'Toutes les demandes visibles',
            'created' => 'Creees par moi',
            'followed' => 'Suivies par moi',
        );

        $html = '';
        foreach ($options as $value => $label) {
            $html .= '<option value="'.self::html($value).'"'.self::selected($selected, $value).'>'.self::html($label).'</option>';
        }

        return $html;
    }

    private static function renderPriorityFilterOptions($selected)
    {
        $html = '<option value=""'.self::selected($selected, '').'>Toutes</option>';
        foreach (SuiviDemandesConfig::priorityDefinitions() as $value => $defaultLabel) {
            $html .= '<option value="'.self::html($value).'"'.self::selected($selected, $value).'>'.self::html(SuiviDemandesConfig::priorityLabel($value)).'</option>';
        }

        return $html;
    }

    private static function renderCategoryFilterOptions($selected)
    {
        $html = '<option value=""'.self::selected($selected, '').'>Toutes</option>'
            .'<option value="__none__"'.self::selected($selected, '__none__').'>Sans categorie</option>';
        foreach (SuiviDemandesConfig::categoryOptions() as $category) {
            $html .= '<option value="'.self::html($category).'"'.self::selected($selected, $category).'>'.self::html($category).'</option>';
        }

        return $html;
    }

    private static function renderLimitFilterOptions($selected)
    {
        $html = '';
        foreach (SuiviDemandesRepository::listLimitOptions() as $limit) {
            $html .= '<option value="'.self::html($limit).'"'.self::selected((string) $selected, (string) $limit).'>'.self::html($limit).'</option>';
        }

        return $html;
    }

    private static function renderAttachableDemandOptions($demands)
    {
        $html = '';
        $categoriesEnabled = SuiviDemandesConfig::categoriesEnabled();
        foreach ($demands as $demand) {
            $label = '#'.$demand['id'].' - '.$demand['titre'].' ('.SuiviDemandesConfig::statusLabel($demand['statut']).')';
            if ($categoriesEnabled && isset($demand['categorie']) && trim((string) $demand['categorie']) !== '') {
                $label .= ' - '.$demand['categorie'];
            }
            $html .= '<option value="'.self::html($demand['id']).'">'.self::html($label).'</option>';
        }

        return $html;
    }

    private static function renderResourceOptions($resources, $selectedIds)
    {
        $html = '';
        $selected = array_flip($selectedIds);

        foreach ($resources as $resource) {
            $id = (int) $resource['id'];
            $html .= '<option value="'.self::html($id).'"'.(isset($selected[$id]) ? ' selected' : '').'>'.self::html($resource['label']).'</option>';
        }

        return $html;
    }

    private static function renderStatus($status)
    {
        $class = 'label-info';
        if ($status === 'en_cours') {
            $class = 'label-warning';
        } elseif ($status === 'cloturee') {
            $class = 'label-default';
        }

        return '<span class="label '.$class.'">'.self::html(SuiviDemandesConfig::statusLabel($status)).'</span>';
    }

    private static function renderPriority($priority)
    {
        $class = 'label-primary';
        if ($priority === 'basse') {
            $class = 'label-default';
        } elseif ($priority === 'haute') {
            $class = 'label-danger';
        }

        return '<span class="label '.$class.'">'.self::html(SuiviDemandesConfig::priorityLabel($priority)).'</span>';
    }

    private static function renderCategory($category)
    {
        return self::html(SuiviDemandesConfig::categoryLabel($category));
    }

    private static function selected($value, $expected)
    {
        return $value === $expected ? ' selected' : '';
    }

    private static function filterQueryString($filters)
    {
        $params = self::filterParams($filters);
        $query = http_build_query($params, '', '&');
        return $query === '' ? '' : '?'.$query;
    }

    private static function statisticsQueryString($filters)
    {
        $params = self::statisticsParams($filters);
        $query = http_build_query($params, '', '&');
        return $query === '' ? '' : '?'.$query;
    }

    private static function filterParams($filters)
    {
        $params = array();
        if (isset($filters['scope']) && $filters['scope'] !== '') {
            $params['suivi_perimetre'] = $filters['scope'];
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $params['suivi_statut'] = $filters['status'];
        }
        if (isset($filters['priority']) && $filters['priority'] !== '') {
            $params['suivi_priorite'] = $filters['priority'];
        }
        if (SuiviDemandesConfig::categoriesEnabled() && isset($filters['category']) && $filters['category'] !== '') {
            $params['suivi_categorie'] = $filters['category'];
        }
        if (isset($filters['search']) && $filters['search'] !== '') {
            $params['suivi_recherche'] = $filters['search'];
        }
        if (isset($filters['limit']) && (int) $filters['limit'] !== SuiviDemandesRepository::MAX_LIST_ROWS) {
            $params['suivi_limite'] = (int) $filters['limit'];
        }

        return $params;
    }

    private static function statisticsParams($filters)
    {
        $params = array();
        if (isset($filters['from']) && $filters['from'] !== '') {
            $params['suivi_stats_from'] = $filters['from'];
        }
        if (isset($filters['to']) && $filters['to'] !== '') {
            $params['suivi_stats_to'] = $filters['to'];
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $params['suivi_stats_status'] = $filters['status'];
        }
        if (isset($filters['priority']) && $filters['priority'] !== '') {
            $params['suivi_stats_priority'] = $filters['priority'];
        }
        if (SuiviDemandesConfig::categoriesEnabled() && isset($filters['category']) && $filters['category'] !== '') {
            $params['suivi_stats_category'] = $filters['category'];
        }
        if (isset($filters['room_id']) && (int) $filters['room_id'] > 0) {
            $params['suivi_stats_room'] = (int) $filters['room_id'];
        }
        if (isset($filters['creator']) && $filters['creator'] !== '') {
            $params['suivi_stats_creator'] = $filters['creator'];
        }

        return $params;
    }

    private static function listViewFromRequest($source)
    {
        if (!is_array($source)) {
            return 'carte';
        }

        $viewMode = isset($source['suivi_vue']) ? (string) $source['suivi_vue'] : 'carte';
        return $viewMode === 'ligne' ? 'ligne' : 'carte';
    }

    private static function accountUrl($params)
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
        $base = strpos($script, '/compte/') !== false ? 'compte.php' : 'compte/compte.php';

        return $base.'?'.http_build_query($params, '', '&');
    }

    private static function selectedRoomIds($rawRooms)
    {
        $ids = array();
        if (!is_array($rawRooms)) {
            $rawRooms = array($rawRooms);
        }

        foreach ($rawRooms as $roomId) {
            $roomId = (int) $roomId;
            if ($roomId > 0) {
                $ids[$roomId] = $roomId;
            }
        }

        return array_values($ids);
    }

    private static function resourceIds($resources)
    {
        $ids = array();
        foreach ($resources as $resource) {
            $ids[(int) $resource['id']] = true;
        }
        return $ids;
    }

    private static function defaultValues($login = '')
    {
        return array(
            'titre' => '',
            'description' => '',
            'priorite' => SuiviDemandesConfig::defaultPriority(),
            'categorie' => '',
            'requester_login' => $login,
            'rooms' => array(),
        );
    }

    private static function validatedRequesterLogin($actorLogin, $rawLogin, $roomId, &$errors)
    {
        $requesterLogin = trim((string) $rawLogin);
        if ($requesterLogin === '') {
            return $actorLogin;
        }

        if (SuiviDemandesRepository::sameLogin($requesterLogin, $actorLogin)) {
            return $actorLogin;
        }

        if (!SuiviDemandesRights::canCreateDemandForOtherUser($actorLogin, (int) $roomId)) {
            $errors[] = 'Vous ne pouvez pas creer de demande pour un autre utilisateur.';
            return $actorLogin;
        }

        $canonicalLogin = SuiviDemandesRepository::activeUserLogin($requesterLogin);
        if ($canonicalLogin === '') {
            $errors[] = 'Le demandeur selectionne est invalide ou inactif.';
            return $actorLogin;
        }

        return $canonicalLogin;
    }

    private static function allRoomsManagedBy($login, $roomIds)
    {
        foreach ($roomIds as $roomId) {
            if (!SuiviDemandesRepository::userManagesResource($login, (int) $roomId)) {
                return false;
            }
        }

        return true;
    }

    private static function followerSearchFromRequest()
    {
        $search = isset($_GET['suivi_user_search']) ? trim((string) $_GET['suivi_user_search']) : '';
        if (strlen($search) > 80) {
            $search = substr($search, 0, 80);
        }

        return $search;
    }

    private static function currentDemandId()
    {
        if (!isset($_GET['demande_id'])) {
            return 0;
        }

        return max(0, (int) $_GET['demande_id']);
    }

    private static function currentEditRoomId()
    {
        if (isset($GLOBALS['suivi_demandes_edit_room_id'])) {
            return max(0, (int) $GLOBALS['suivi_demandes_edit_room_id']);
        }

        if (isset($_POST['room'])) {
            return max(0, (int) $_POST['room']);
        }

        if (isset($_GET['room'])) {
            return max(0, (int) $_GET['room']);
        }

        return 0;
    }

    private static function reservationContext($name)
    {
        if (!isset($GLOBALS[$name]) || !is_array($GLOBALS[$name])) {
            return array();
        }

        return $GLOBALS[$name];
    }

    private static function allowedAttachmentExtensions()
    {
        return SuiviDemandesConfig::attachmentExtensions();
    }

    private static function sanitizeAttachmentName($name)
    {
        $name = basename(str_replace('\\', '/', (string) $name));
        $name = preg_replace('/[[:cntrl:]]/', '', $name);
        $name = trim($name);
        if ($name === '' || $name === '.' || $name === '..') {
            $name = 'fichier';
        }

        if (strlen($name) > 190) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $base = substr(pathinfo($name, PATHINFO_FILENAME), 0, 170);
            $name = $extension !== '' ? $base.'.'.$extension : substr($name, 0, 190);
        }

        return $name;
    }

    private static function generateAttachmentStoredName()
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes(20));
        }

        return sha1(uniqid('', true).mt_rand());
    }

    private static function attachmentMimeType($path, $fallback)
    {
        $mimeType = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeType = (string) finfo_file($finfo, $path);
                finfo_close($finfo);
            }
        }

        if ($mimeType === '') {
            $mimeType = trim((string) $fallback);
        }

        if ($mimeType === '') {
            $mimeType = 'application/octet-stream';
        }

        return substr($mimeType, 0, 120);
    }

    private static function uploadErrorMessage($error)
    {
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return 'Le fichier depasse la taille maximale autorisee.';
        }

        if ($error === UPLOAD_ERR_NO_FILE) {
            return 'Selectionnez un fichier.';
        }

        return 'Le fichier n a pas pu etre transfere.';
    }

    private static function formatBytes($bytes)
    {
        $bytes = (int) $bytes;
        if ($bytes < 1024) {
            return $bytes.' o';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' Ko';
        }

        return round($bytes / 1048576, 1).' Mo';
    }

    private static function formatDuration($seconds)
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

    private static function formatDate($timestamp)
    {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return '';
        }

        return date('d/m/Y H:i', $timestamp);
    }

    private static function currentLogin()
    {
        return function_exists('getUserName') ? (string) getUserName() : '';
    }

    private static function modalIdSuffix($value)
    {
        $suffix = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $value);
        $suffix = trim($suffix, '-');

        return $suffix === '' ? 'item' : $suffix;
    }

    private static function js($value)
    {
        return json_encode((string) $value);
    }

    private static function html($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
