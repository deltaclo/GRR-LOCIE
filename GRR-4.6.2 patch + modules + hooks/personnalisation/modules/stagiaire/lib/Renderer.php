<?php

class StagiaireRenderer
{
    public static function accountMenu()
    {
        if (!self::isAdmin(self::currentLogin())) {
            return '';
        }

        return '<br><br><a href="compte.php?pc=stagiaire" class="btn btn-primary col-lg-12 col-md-12 col-sm-12 col-xs-12 stagiaire-account-btn">'
            .self::html(StagiaireConfig::displayName())
            .'</a>';
    }

    public static function accountPage()
    {
        $pc = isset($_GET['pc']) ? (string) $_GET['pc'] : '';
        if ($pc !== StagiaireConfig::MODULE) {
            return '';
        }

        if (!self::isAdmin(self::currentLogin())) {
            return '<div class="alert alert-warning">Acces refuse.</div>';
        }

        if (isset($_GET['admin']) && (string) $_GET['admin'] === '1') {
            return self::renderEmbeddedAdminPage();
        }

        return self::renderAdminReservationsPage();
    }

    public static function reservationForm()
    {
        if (!self::shouldUseReservationFields()) {
            return '';
        }

        $values = self::formValues();
        if (!self::requestHasReservationValues()) {
            $context = self::reservationContext('grr_editentree_context');
            if (empty($context)) {
                $context = self::reservationContext('suivi_demandes_editentree_context');
            }

            $entryId = isset($context['entry_id']) ? (int) $context['entry_id'] : 0;
            $storedValues = StagiaireRepository::reservationData($entryId);
            if (!empty($storedValues)) {
                $values = array(
                    'nom' => isset($storedValues['nom']) ? $storedValues['nom'] : '',
                    'prenom' => isset($storedValues['prenom']) ? $storedValues['prenom'] : '',
                    'email' => isset($storedValues['email']) ? $storedValues['email'] : '',
                    'encadrant' => isset($storedValues['encadrant']) ? $storedValues['encadrant'] : '',
                );
            }
        }

        return '<div class="E" id="stagiaire-reservation">'
            .'<label>'.self::html(StagiaireConfig::displayName()).'</label>'
            .'<div class="well">'
                .'<div class="row">'
                    .'<div class="form-group col-sm-6">'
                        .'<label for="stagiaire_nom">Nom *</label>'
                        .'<input class="form-control" id="stagiaire_nom" type="text" name="stagiaire_nom" maxlength="100" value="'.self::html($values['nom']).'" required>'
                    .'</div>'
                    .'<div class="form-group col-sm-6">'
                        .'<label for="stagiaire_prenom">Prenom *</label>'
                        .'<input class="form-control" id="stagiaire_prenom" type="text" name="stagiaire_prenom" maxlength="100" value="'.self::html($values['prenom']).'" required>'
                    .'</div>'
                .'</div>'
                .'<div class="row">'
                    .'<div class="form-group col-sm-6">'
                        .'<label for="stagiaire_email">Email *</label>'
                        .'<input class="form-control" id="stagiaire_email" type="email" name="stagiaire_email" maxlength="190" value="'.self::html($values['email']).'" required>'
                    .'</div>'
                    .'<div class="form-group col-sm-6">'
                        .'<label for="stagiaire_encadrant">Encadrant *</label>'
                        .'<input class="form-control" id="stagiaire_encadrant" type="text" name="stagiaire_encadrant" maxlength="190" value="'.self::html($values['encadrant']).'" required>'
                    .'</div>'
                .'</div>'
            .'</div>'
        .'</div>';
    }

    public static function validateReservation()
    {
        $errors = array();
        if (!self::shouldUseReservationFields()) {
            return $errors;
        }

        $values = self::formValues();

        if ($values['nom'] === '') {
            $errors[] = 'Le nom du stagiaire est obligatoire.';
        } elseif (strlen($values['nom']) > 100) {
            $errors[] = 'Le nom du stagiaire ne doit pas depasser 100 caracteres.';
        }

        if ($values['prenom'] === '') {
            $errors[] = 'Le prenom du stagiaire est obligatoire.';
        } elseif (strlen($values['prenom']) > 100) {
            $errors[] = 'Le prenom du stagiaire ne doit pas depasser 100 caracteres.';
        }

        if ($values['email'] === '') {
            $errors[] = 'L email du stagiaire est obligatoire.';
        } elseif (strlen($values['email']) > 190 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'L email du stagiaire est invalide.';
        }

        if ($values['encadrant'] === '') {
            $errors[] = 'L encadrant du stagiaire est obligatoire.';
        } elseif (strlen($values['encadrant']) > 190) {
            $errors[] = 'L encadrant du stagiaire ne doit pas depasser 190 caracteres.';
        }

        return $errors;
    }

    public static function reservationSubmit()
    {
        if (!self::shouldUseReservationFields()) {
            return;
        }

        $context = self::reservationContext('grr_editentreetrt_context');
        if (empty($context)) {
            $context = self::reservationContext('suivi_demandes_editentreetrt_context');
        }

        $entryIds = self::contextEntryIds($context);
        if (count($entryIds) === 0) {
            return;
        }

        if (count(self::validateReservation()) > 0) {
            return;
        }

        $savedEntryIds = StagiaireRepository::saveReservationDataForEntries($entryIds, self::currentLogin(), self::formValues());
        if (count($savedEntryIds) > 0) {
            StagiaireNotification::sendInitialConfirmationForEntries($savedEntryIds);
        }
    }

    public static function reservationDetail()
    {
        if (!StagiaireConfig::isEnabled() || !StagiaireConfig::detailEnabled()) {
            return '';
        }

        $entryId = self::detailEntryId();
        if ($entryId <= 0) {
            return '';
        }

        $values = StagiaireRepository::reservationData($entryId);
        if (empty($values)) {
            return '';
        }

        $email = isset($values['email']) ? (string) $values['email'] : '';
        $emailHtml = self::html($email);
        $emailCell = $email === ''
            ? ''
            : '<a href="mailto:'.$emailHtml.'">'.$emailHtml.'</a>';

        return '<fieldset id="stagiaire-reservation-detail">'
            .'<legend style="font-weight:bold">'.self::html(StagiaireConfig::displayName()).'</legend>'
            .'<table class="table table-bordered table-condensed">'
                .'<tr><th>Nom</th><td>'.self::html(isset($values['nom']) ? $values['nom'] : '').'</td></tr>'
                .'<tr><th>Prenom</th><td>'.self::html(isset($values['prenom']) ? $values['prenom'] : '').'</td></tr>'
                .'<tr><th>Email</th><td>'.$emailCell.'</td></tr>'
                .'<tr><th>Encadrant</th><td>'.self::html(isset($values['encadrant']) ? $values['encadrant'] : '').'</td></tr>'
            .'</table>'
        .'</fieldset>';
    }

    private static function shouldUseReservationFields()
    {
        if (!StagiaireConfig::isEnabled() || !StagiaireConfig::formEnabled()) {
            return false;
        }

        $login = self::currentLogin();
        if ($login === '') {
            return false;
        }

        return StagiaireRepository::isStagiaire($login);
    }

    private static function renderAccountAdminAccess()
    {
        $adminUrl = 'compte.php?pc=stagiaire&admin=1';
        $reservationsUrl = 'compte.php?pc=stagiaire';

        return '<section id="stagiaire-account-admin">'
            .self::assets()
            .'<h1>'.self::html(StagiaireConfig::displayName()).'</h1>'
            .'<div class="well">'
                .'<p><a class="btn btn-primary" href="'.self::html($adminUrl).'">Administration du module</a></p>'
                .'<p><a class="btn btn-default" href="'.self::html($reservationsUrl).'">Reservations stagiaires</a></p>'
            .'</div>'
        .'</section>';
    }

    private static function renderEmbeddedAdminPage()
    {
        ob_start();
        $stagiaire_admin_embedded = true;
        include __DIR__.'/../admin.php';
        $html = ob_get_clean();

        return '<section id="stagiaire-account-admin">'.self::assets().$html.'</section>';
    }

    private static function renderAdminReservationsPage()
    {
        $filters = StagiaireRepository::reservationFiltersFromRequest($_GET);
        $reservations = StagiaireRepository::stagiaireReservations($filters);
        $resources = StagiaireRepository::allResourcesForFilter();
        $users = StagiaireRepository::stagiaireReservationUsersForFilter();
        $exportUrl = '../personnalisation/modules/stagiaire/export.php'.self::reservationFilterQueryString($filters);

        $html = '<section id="stagiaire-reservations-admin">'
            .self::assets()
            .'<h1>Reservations stagiaires</h1>'
            .'<p><a class="btn btn-primary" href="compte.php?pc=stagiaire&amp;admin=1">Administration du module</a></p>'
            .self::renderReservationFilters($filters, $resources, $users, $exportUrl)
            .'<p><strong>'.self::html(count($reservations)).'</strong> reservation(s) affichee(s).</p>'
            .self::renderReservationTable($reservations)
        .'</section>';

        return $html;
    }

    private static function assets()
    {
        return '<style>'
            .'#stagiaire-account-admin,#stagiaire-reservations-admin{width:100%;max-width:none;box-sizing:border-box;}'
            .'#stagiaire-account-admin *,#stagiaire-reservations-admin *,#menu-compte .stagiaire-account-btn{box-sizing:border-box;}'
            .'#menu-compte .stagiaire-account-btn{display:block;width:100%;max-width:100%;text-align:center;white-space:normal;overflow-wrap:anywhere;}'
            .'#stagiaire-account-admin .btn,#stagiaire-reservations-admin .btn{white-space:normal;}'
            .'#stagiaire-reservations-admin .table-responsive{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;}'
            .'#stagiaire-reservations-admin table{width:100%;}'
            .'#stagiaire-reservations-admin th,#stagiaire-reservations-admin td{overflow-wrap:anywhere;}'
            .'@media (max-width:767px){#stagiaire-account-admin .btn,#stagiaire-reservations-admin .btn{width:100%;margin-bottom:8px;}#stagiaire-reservations-admin .form-inline .form-group{display:block;width:100%;margin-right:0!important;}#stagiaire-reservations-admin .form-inline .form-control{width:100%;}#stagiaire-reservations-admin table[data-responsive-table="1"],#stagiaire-reservations-admin table[data-responsive-table="1"] thead,#stagiaire-reservations-admin table[data-responsive-table="1"] tbody,#stagiaire-reservations-admin table[data-responsive-table="1"] tr,#stagiaire-reservations-admin table[data-responsive-table="1"] th,#stagiaire-reservations-admin table[data-responsive-table="1"] td{display:block;width:100%;}#stagiaire-reservations-admin table[data-responsive-table="1"] thead{display:none;}#stagiaire-reservations-admin table[data-responsive-table="1"] tr{margin:0 0 12px;border:1px solid #ddd;background:#fff;border-radius:4px;overflow:hidden;}#stagiaire-reservations-admin table[data-responsive-table="1"] td{display:flex;gap:10px;justify-content:space-between;align-items:flex-start;border:0;border-bottom:1px solid #eee;text-align:right;}#stagiaire-reservations-admin table[data-responsive-table="1"] td:last-child{border-bottom:0;}#stagiaire-reservations-admin table[data-responsive-table="1"] td:before{content:attr(data-label);font-weight:600;color:#555;text-align:left;flex:0 0 42%;}}'
            .'</style>'
            .'<script>(function(){if(window.stagiaireResponsiveReady){return;}window.stagiaireResponsiveReady=true;function prepare(){document.querySelectorAll("#stagiaire-reservations-admin table").forEach(function(table){if(table.getAttribute("data-responsive-table")==="1"){return;}var heads=table.tHead&&table.tHead.rows.length?table.tHead.rows[0].cells:[];if(!heads.length){return;}table.setAttribute("data-responsive-table","1");Array.prototype.forEach.call(table.tBodies,function(body){Array.prototype.forEach.call(body.rows,function(row){Array.prototype.forEach.call(row.cells,function(cell,index){var head=heads[index];if(head){cell.setAttribute("data-label",head.textContent.trim());}});});});});}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",prepare);}else{prepare();}setTimeout(prepare,0);})();</script>';
    }

    private static function renderReservationFilters($filters, $resources, $users, $exportUrl)
    {
        $html = '<form class="form-inline" method="get" action="compte.php">'
            .'<input type="hidden" name="pc" value="stagiaire">'
            .'<div class="form-group" style="margin-right:8px;">'
                .'<label for="stagiaire_date_from">Du</label> '
                .'<input class="form-control" id="stagiaire_date_from" type="date" name="stagiaire_date_from" value="'.self::html($filters['date_from']).'">'
            .'</div>'
            .'<div class="form-group" style="margin-right:8px;">'
                .'<label for="stagiaire_date_to">Au</label> '
                .'<input class="form-control" id="stagiaire_date_to" type="date" name="stagiaire_date_to" value="'.self::html($filters['date_to']).'">'
            .'</div>'
            .'<div class="form-group" style="margin-right:8px;">'
                .'<label for="stagiaire_room_id">Ressource</label> '
                .'<select class="form-control" id="stagiaire_room_id" name="stagiaire_room_id">'
                    .'<option value="0">Toutes</option>'
                    .self::reservationResourceOptions($resources, (int) $filters['room_id'])
                .'</select>'
            .'</div>'
            .'<div class="form-group" style="margin-right:8px;">'
                .'<label for="stagiaire_login">Compte</label> '
                .'<select class="form-control" id="stagiaire_login" name="stagiaire_login">'
                    .'<option value="">Tous</option>'
                    .self::reservationUserOptions($users, $filters['login'])
                .'</select>'
            .'</div>'
            .'<div class="form-group" style="margin-right:8px;">'
                .'<label for="stagiaire_email">Email</label> '
                .'<input class="form-control" id="stagiaire_email" type="text" name="stagiaire_email" maxlength="190" value="'.self::html($filters['email']).'">'
            .'</div>'
            .'<div class="form-group" style="margin-right:8px;">'
                .'<label for="stagiaire_limit">Afficher</label> '
                .'<select class="form-control" id="stagiaire_limit" name="stagiaire_limit">'
                    .self::reservationLimitOptions((int) $filters['limit'])
                .'</select>'
            .'</div>'
            .'<button class="btn btn-primary" type="submit">Filtrer</button> '
            .'<a class="btn btn-default" href="compte.php?pc=stagiaire">Reinitialiser</a> '
            .'<a class="btn btn-default" href="'.self::html($exportUrl).'">Exporter CSV</a>'
        .'</form>';

        return $html;
    }

    private static function renderReservationTable($reservations)
    {
        $html = '<div class="table-responsive" style="margin-top:14px;">'
            .'<table class="table table-striped table-bordered">'
                .'<thead><tr>'
                    .'<th>ID</th>'
                    .'<th>Reservation</th>'
                    .'<th>Debut</th>'
                    .'<th>Fin</th>'
                    .'<th>Ressource</th>'
                    .'<th>Stagiaire</th>'
                    .'<th>Email</th>'
                    .'<th>Encadrant</th>'
                    .'<th>Compte</th>'
                    .'<th>Statut</th>'
                .'</tr></thead><tbody>';

        if (count($reservations) === 0) {
            $html .= '<tr><td colspan="10">Aucune reservation stagiaire trouvee.</td></tr>';
        }

        foreach ($reservations as $reservation) {
            $entryId = isset($reservation['entry_id']) ? (int) $reservation['entry_id'] : 0;
            $name = isset($reservation['name']) ? trim((string) $reservation['name']) : '';
            if ($name === '') {
                $name = 'Reservation #'.$entryId;
            }

            $detailUrl = '../app.php?p=vuereservation&amp;id='.self::html($entryId).'&amp;mode=page';
            $resource = self::resourceLabel($reservation);
            $stagiaireName = trim((isset($reservation['prenom']) ? (string) $reservation['prenom'] : '').' '.(isset($reservation['nom']) ? (string) $reservation['nom'] : ''));

            $html .= '<tr>'
                .'<td>'.self::html($entryId).'</td>'
                .'<td><a href="'.$detailUrl.'">'.self::html($name).'</a></td>'
                .'<td>'.self::html(self::formatDate(isset($reservation['start_time']) ? (int) $reservation['start_time'] : 0)).'</td>'
                .'<td>'.self::html(self::formatDate(isset($reservation['end_time']) ? (int) $reservation['end_time'] : 0)).'</td>'
                .'<td>'.self::html($resource).'</td>'
                .'<td>'.self::html($stagiaireName).'</td>'
                .'<td>'.self::emailLink(isset($reservation['email']) ? (string) $reservation['email'] : '').'</td>'
                .'<td>'.self::html(isset($reservation['encadrant']) ? $reservation['encadrant'] : '').'</td>'
                .'<td>'.self::html(self::reservationUserLabel($reservation)).'</td>'
                .'<td>'.self::html(self::moderationLabel(isset($reservation['moderate']) ? (int) $reservation['moderate'] : 0)).'</td>'
            .'</tr>';
        }

        return $html.'</tbody></table></div>';
    }

    private static function reservationFilterQueryString($filters)
    {
        $query = array(
            'stagiaire_date_from' => $filters['date_from'],
            'stagiaire_date_to' => $filters['date_to'],
            'stagiaire_room_id' => (int) $filters['room_id'],
            'stagiaire_login' => $filters['login'],
            'stagiaire_email' => $filters['email'],
            'stagiaire_limit' => (int) $filters['limit'],
        );

        return '?'.http_build_query($query);
    }

    private static function reservationResourceOptions($resources, $selectedRoomId)
    {
        $html = '';
        foreach ($resources as $resource) {
            $id = isset($resource['id']) ? (int) $resource['id'] : 0;
            if ($id <= 0) {
                continue;
            }
            $html .= '<option value="'.self::html($id).'"'.self::selected($id, $selectedRoomId).'>'
                .self::html(isset($resource['label']) ? $resource['label'] : $id)
                .'</option>';
        }

        return $html;
    }

    private static function reservationUserOptions($users, $selectedLogin)
    {
        $html = '';
        foreach ($users as $user) {
            $login = isset($user['login']) ? (string) $user['login'] : '';
            if ($login === '') {
                continue;
            }
            $html .= '<option value="'.self::html($login).'"'.self::selected($login, $selectedLogin).'>'
                .self::html(isset($user['label']) ? $user['label'] : $login)
                .'</option>';
        }

        return $html;
    }

    private static function reservationLimitOptions($selectedLimit)
    {
        $html = '';
        foreach (StagiaireRepository::reservationListLimitOptions() as $limit) {
            $html .= '<option value="'.self::html($limit).'"'.self::selected((int) $limit, (int) $selectedLimit).'>'.self::html($limit).'</option>';
        }

        return $html;
    }

    private static function selected($value, $expected)
    {
        return (string) $value === (string) $expected ? ' selected' : '';
    }

    private static function resourceLabel($reservation)
    {
        $area = isset($reservation['area_name']) ? trim((string) $reservation['area_name']) : '';
        $room = isset($reservation['room_name']) ? trim((string) $reservation['room_name']) : '';
        if ($area !== '' && $room !== '') {
            return $area.' > '.$room;
        }

        return $room;
    }

    private static function reservationUserLabel($reservation)
    {
        $login = isset($reservation['created_by']) ? trim((string) $reservation['created_by']) : '';
        $parts = array();
        if (isset($reservation['user_nom']) && trim((string) $reservation['user_nom']) !== '') {
            $parts[] = trim((string) $reservation['user_nom']);
        }
        if (isset($reservation['user_prenom']) && trim((string) $reservation['user_prenom']) !== '') {
            $parts[] = trim((string) $reservation['user_prenom']);
        }

        $label = trim(implode(' ', $parts));
        if ($label === '') {
            return $login;
        }

        return $login === '' ? $label : $label.' ('.$login.')';
    }

    private static function moderationLabel($moderate)
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

    private static function emailLink($email)
    {
        $email = trim((string) $email);
        if ($email === '') {
            return '';
        }

        return '<a href="mailto:'.self::html($email).'">'.self::html($email).'</a>';
    }

    private static function formatDate($timestamp)
    {
        $timestamp = (int) $timestamp;
        if ($timestamp <= 0) {
            return '';
        }

        return date('d/m/Y H:i', $timestamp);
    }

    private static function formValues()
    {
        return array(
            'nom' => self::requestValue('stagiaire_nom'),
            'prenom' => self::requestValue('stagiaire_prenom'),
            'email' => self::requestValue('stagiaire_email'),
            'encadrant' => self::requestValue('stagiaire_encadrant'),
        );
    }

    private static function requestHasReservationValues()
    {
        return isset($_REQUEST['stagiaire_nom'])
            || isset($_REQUEST['stagiaire_prenom'])
            || isset($_REQUEST['stagiaire_email'])
            || isset($_REQUEST['stagiaire_encadrant']);
    }

    private static function requestValue($name)
    {
        return isset($_REQUEST[$name]) ? trim((string) $_REQUEST[$name]) : '';
    }

    private static function reservationContext($name)
    {
        return isset($GLOBALS[$name]) && is_array($GLOBALS[$name]) ? $GLOBALS[$name] : array();
    }

    private static function contextEntryIds($context)
    {
        $entryIds = array();
        if (isset($context['entry_ids']) && is_array($context['entry_ids'])) {
            foreach ($context['entry_ids'] as $entryId) {
                $entryId = (int) $entryId;
                if ($entryId > 0) {
                    $entryIds[$entryId] = $entryId;
                }
            }
        }

        if (count($entryIds) === 0 && isset($context['entry_id'])) {
            $entryId = (int) $context['entry_id'];
            if ($entryId > 0) {
                $entryIds[$entryId] = $entryId;
            }
        }

        return array_values($entryIds);
    }

    private static function detailEntryId()
    {
        $context = self::reservationContext('grr_vuereservation_context');
        if (empty($context)) {
            $context = self::reservationContext('suivi_demandes_vuereservation_context');
        }

        if (isset($context['entry_id']) && (int) $context['entry_id'] > 0) {
            return (int) $context['entry_id'];
        }

        return isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
    }

    private static function currentLogin()
    {
        return function_exists('getUserName') ? (string) getUserName() : '';
    }

    private static function isAdmin($login)
    {
        return $login !== ''
            && class_exists('SecuAccess')
            && SecuAccess::UserLevel($login, -1) >= 6;
    }

    private static function html($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
