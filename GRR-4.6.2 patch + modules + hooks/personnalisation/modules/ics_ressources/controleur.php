<?php
require_once __DIR__.'/lib/ModuleConfig.php';

if (!function_exists('grr_ics_html')) {
    function grr_ics_html($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('grr_ics_checked')) {
    function grr_ics_checked($enabled)
    {
        return $enabled ? ' checked' : '';
    }
}

if (!function_exists('grr_ics_selected')) {
    function grr_ics_selected($value, $expected)
    {
        return $value === $expected ? ' selected' : '';
    }
}

if ($identifiant_hook === 'vocab') {
    $CtnHook['vocab'] = array(
        'ics_ressources_title' => 'Feeds ICS par ressource',
        'ics_ressources_feed' => 'Feed ICS',
    );
}

if ($identifiant_hook === 'hookEditRoom1') {
    $roomId = isset($_POST['room']) ? (int) $_POST['room'] : (isset($_GET['room']) ? (int) $_GET['room'] : 0);

    if ($roomId > 0) {
        $message = '';
        $isPost = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';
        $userName = getUserName();
        $canConfigureIcs = (SecuAccess::UserLevel($userName, -1, 'area') >= 4)
            || (SecuAccess::UserLevel($userName, -1, 'user') == 1);

        if ($canConfigureIcs && $isPost && isset($_POST['ics_ressources_regenerate'])) {
            GrrIcsConfig::regenerateSecret();
            $message = 'Tous les liens ICS ont ete regeneres.';
        } elseif ($canConfigureIcs && $isPost && isset($_POST['ics_ressources_save'])) {
            $privacy = isset($_POST['ics_ressources_privacy']) ? $_POST['ics_ressources_privacy'] : 'busy';
            if (!in_array($privacy, array('busy', 'title', 'full'), true)) {
                $privacy = 'busy';
            }

            GrrIcsConfig::set('enabled', isset($_POST['ics_ressources_enabled']) ? '1' : '0');
            GrrIcsConfig::set('privacy', $privacy);
            GrrIcsConfig::set('past_days', isset($_POST['ics_ressources_past_days']) ? max(0, (int) $_POST['ics_ressources_past_days']) : 30);
            GrrIcsConfig::set('future_days', isset($_POST['ics_ressources_future_days']) ? max(1, (int) $_POST['ics_ressources_future_days']) : 365);
            GrrIcsConfig::set('include_moderated', isset($_POST['ics_ressources_include_moderated']) ? '1' : '0');
            GrrIcsConfig::set('include_option', isset($_POST['ics_ressources_include_option']) ? '1' : '0');
            GrrIcsConfig::set('include_inactive_rooms', isset($_POST['ics_ressources_include_inactive_rooms']) ? '1' : '0');
            GrrIcsConfig::setRoomEnabled($roomId, isset($_POST['ics_ressources_room_enabled']));
            $message = 'Configuration ICS enregistree.';
        }

        $feedUrl = GrrIcsConfig::feedUrl($roomId);
        $privacy = GrrIcsConfig::privacy();
        $globalEnabled = GrrIcsConfig::isEnabled();
        $roomEnabled = GrrIcsConfig::isRoomEnabled($roomId);

        $status = '';
        if (!$globalEnabled) {
            $status = '<div class="alert alert-warning">Les feeds ICS sont desactives globalement.</div>';
        } elseif (!$roomEnabled) {
            $status = '<div class="alert alert-warning">Le feed ICS de cette ressource est desactive.</div>';
        } else {
            $status = '<div class="alert alert-info">Le feed ICS de cette ressource est actif.</div>';
        }

        $CtnHook['hookEditRoom1'] = '<section id="ics-ressources">'
            .'<h4 class="page-header">Feed ICS</h4>'
            .($message !== '' ? '<div class="alert alert-success">'.grr_ics_html($message).'</div>' : '')
            .$status
            .(!$canConfigureIcs ? '<div class="alert alert-warning">La configuration ICS est reservee aux administrateurs.</div>' : '')
            .'<div class="form-group row col-sm-12">'
                .'<label class="col col-sm-8" for="ics_ressources_feed_url">URL ICS de cette ressource</label>'
                .'<div class="col col-sm-4">'
                    .'<input class="form-control" id="ics_ressources_feed_url" type="text" readonly value="'.grr_ics_html($feedUrl).'">'
                    .'<p><a class="btn btn-default btn-sm" href="'.grr_ics_html($feedUrl).'" target="_blank">Ouvrir le feed ICS</a></p>'
                .'</div>'
            .'</div>'
            .($canConfigureIcs ? '<div class="form-group row col-sm-12">'
                .'<label class="col col-sm-8" for="ics_ressources_enabled">Activer les feeds ICS</label>'
                .'<div class="col col-sm-4"><input id="ics_ressources_enabled" type="checkbox" name="ics_ressources_enabled" value="1"'.grr_ics_checked($globalEnabled).'></div>'
            .'</div>'
            .'<div class="form-group row col-sm-12">'
                .'<label class="col col-sm-8" for="ics_ressources_room_enabled">Publier cette ressource</label>'
                .'<div class="col col-sm-4"><input id="ics_ressources_room_enabled" type="checkbox" name="ics_ressources_room_enabled" value="1"'.grr_ics_checked($roomEnabled).'></div>'
            .'</div>'
            .'<div class="form-group row col-sm-12">'
                .'<label class="col col-sm-8" for="ics_ressources_privacy">Confidentialite</label>'
                .'<div class="col col-sm-4">'
                    .'<select class="form-control" id="ics_ressources_privacy" name="ics_ressources_privacy">'
                        .'<option value="busy"'.grr_ics_selected($privacy, 'busy').'>Occupe uniquement</option>'
                        .'<option value="title"'.grr_ics_selected($privacy, 'title').'>Titre de la reservation</option>'
                        .'<option value="full"'.grr_ics_selected($privacy, 'full').'>Titre + description + beneficiaire</option>'
                    .'</select>'
                .'</div>'
            .'</div>'
            .'<div class="form-group row col-sm-12">'
                .'<label class="col col-sm-8" for="ics_ressources_past_days">Jours passes inclus</label>'
                .'<div class="col col-sm-4"><input class="form-control" id="ics_ressources_past_days" type="number" min="0" name="ics_ressources_past_days" value="'.grr_ics_html(GrrIcsConfig::pastDays()).'"></div>'
            .'</div>'
            .'<div class="form-group row col-sm-12">'
                .'<label class="col col-sm-8" for="ics_ressources_future_days">Jours futurs inclus</label>'
                .'<div class="col col-sm-4"><input class="form-control" id="ics_ressources_future_days" type="number" min="1" name="ics_ressources_future_days" value="'.grr_ics_html(GrrIcsConfig::futureDays()).'"></div>'
            .'</div>'
            .'<div class="form-group row col-sm-12">'
                .'<label class="col col-sm-8" for="ics_ressources_include_moderated">Inclure les reservations moderees</label>'
                .'<div class="col col-sm-4"><input id="ics_ressources_include_moderated" type="checkbox" name="ics_ressources_include_moderated" value="1"'.grr_ics_checked(GrrIcsConfig::includeModerated()).'></div>'
            .'</div>'
            .'<div class="form-group row col-sm-12">'
                .'<label class="col col-sm-8" for="ics_ressources_include_option">Inclure les reservations optionnelles</label>'
                .'<div class="col col-sm-4"><input id="ics_ressources_include_option" type="checkbox" name="ics_ressources_include_option" value="1"'.grr_ics_checked(GrrIcsConfig::includeOption()).'></div>'
            .'</div>'
            .'<div class="form-group row col-sm-12">'
                .'<label class="col col-sm-8" for="ics_ressources_include_inactive_rooms">Autoriser les ressources inactives</label>'
                .'<div class="col col-sm-4"><input id="ics_ressources_include_inactive_rooms" type="checkbox" name="ics_ressources_include_inactive_rooms" value="1"'.grr_ics_checked(GrrIcsConfig::includeInactiveRooms()).'></div>'
            .'</div>'
            .'<div class="form-group row col-sm-12">'
                .'<div class="col col-sm-8"></div>'
                .'<div class="col col-sm-4">'
                    .'<button class="btn btn-primary btn-sm" type="submit" name="ics_ressources_save" value="1">Enregistrer la configuration ICS</button> '
                    .'<button class="btn btn-warning btn-sm" type="submit" name="ics_ressources_regenerate" value="1" onclick="return confirm(\'Regenerer le secret invalidera tous les anciens liens ICS. Continuer ?\');">Regenerer les liens</button>'
                .'</div>'
            .'</div>' : '')
            .'</section>';
    }
}
