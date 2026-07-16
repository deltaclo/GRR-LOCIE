<?php

$gestionMaterielAdminEmbedded = isset($gestion_materiel_admin_embedded) && $gestion_materiel_admin_embedded;

require_once __DIR__.'/lib/bootstrap.php';

$sessionOk = $gestionMaterielAdminEmbedded ? true : grr_gestion_materiel_bootstrap(true);

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';

if (!$sessionOk) {
    header('Location: ../../../index.php');
    exit;
}

$login = function_exists('getUserName') ? (string) getUserName() : '';
if ($login === '' || SecuAccess::UserLevel($login, -1) < 6) {
    if ($gestionMaterielAdminEmbedded) {
        echo '<div class="alert alert-warning">Acces refuse.</div>';
        return;
    }

    http_response_code(403);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Acces refuse</title></head><body>';
    echo '<h1>Acces refuse</h1><p>Cette page est reservee aux administrateurs generaux GRR.</p>';
    echo '</body></html>';
    exit;
}

function gestion_materiel_admin_html($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function gestion_materiel_admin_status($ok)
{
    return $ok
        ? '<span class="label label-success">OK</span>'
        : '<span class="label label-danger">Erreur</span>';
}

function gestion_materiel_admin_modal_button($id, $label, $class = 'btn btn-primary')
{
    return '<button class="'.gestion_materiel_admin_html($class).'" type="button" data-gm-admin-modal-open="'.gestion_materiel_admin_html($id).'">'.gestion_materiel_admin_html($label).'</button>';
}

function gestion_materiel_admin_modal($id, $title, $content, $open = false)
{
    return '<div class="gm-admin-modal'.($open ? ' is-open' : '').'" id="'.gestion_materiel_admin_html($id).'" role="dialog" aria-modal="true">'
        .'<div class="gm-admin-modal-dialog">'
        .'<div class="gm-admin-modal-head"><strong>'.gestion_materiel_admin_html($title).'</strong><button class="gm-admin-modal-close" type="button" data-gm-admin-modal-close>&times;</button></div>'
        .'<div class="gm-admin-modal-body">'.$content.'</div>'
        .'</div></div>';
}

function gestion_materiel_admin_contrast_color($color)
{
    $color = ltrim((string) $color, '#');
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
        return '#ffffff';
    }

    $red = hexdec(substr($color, 0, 2));
    $green = hexdec(substr($color, 2, 2));
    $blue = hexdec(substr($color, 4, 2));
    $brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;

    return $brightness >= 160 ? '#212529' : '#ffffff';
}

function gestion_materiel_admin_logins($logins)
{
    $clean = array();
    if (!is_array($logins)) {
        $logins = array($logins);
    }

    foreach ($logins as $login) {
        $login = trim((string) $login);
        if ($login !== '' && strlen($login) <= 190) {
            $clean[$login] = $login;
        }
    }

    return array_values($clean);
}

function gestion_materiel_admin_manager_options($users, $managerLogins, $enabled)
{
    $html = '';
    $managerMap = array_flip($managerLogins);

    foreach ($users as $user) {
        $login = isset($user['login']) ? (string) $user['login'] : '';
        if ($login === '') {
            continue;
        }

        $isEnabled = isset($managerMap[$login]);
        if ((bool) $isEnabled !== (bool) $enabled) {
            continue;
        }

        $label = isset($user['label']) ? (string) $user['label'] : $login;
        $html .= '<option value="'.gestion_materiel_admin_html($login).'">'.gestion_materiel_admin_html($label).'</option>';
    }

    return $html;
}

function gestion_materiel_admin_cron_url($token)
{
    $token = trim((string) $token);
    if ($token === '') {
        return '';
    }

    $baseUrl = function_exists('traite_grr_url') ? traite_grr_url('', 'y') : '';
    return $baseUrl.'personnalisation/modules/gestion_materiel/cron_notifications.php?token='.rawurlencode($token);
}

$module_version = '';
$module_versionBDD = '';
if (file_exists(__DIR__.'/infos.php')) {
    include __DIR__.'/infos.php';
}

GestionMaterielRepository::ensureTables();

$message = '';
$errors = array();
$dashboardTileConfigInput = null;
$documentsEnabledInput = null;
$documentMaxMbInput = null;
$documentExtensionsInput = null;
$gestionMaterielAdminAction = $gestionMaterielAdminEmbedded ? 'compte.php?pc=gestion_materiel&admin=1' : 'admin.php';
$openConfigModal = false;
$openTokenModal = false;

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminAction = isset($_POST['gm_admin_action']) ? (string) $_POST['gm_admin_action'] : 'save_config';

    if ($adminAction === 'generate_notification_token') {
        $openTokenModal = true;
        $token = GestionMaterielConfig::generateNotificationToken();
        if (GestionMaterielConfig::setNotificationToken($token)) {
            $message = 'Token de notification genere.';
            $openTokenModal = false;
        } else {
            $errors[] = 'Erreur lors de la generation du token de notification.';
        }
    } else {
        $openConfigModal = true;
        $displayName = isset($_POST['display_name']) ? trim((string) $_POST['display_name']) : '';
        $upcomingDays = isset($_POST['upcoming_days']) ? (int) $_POST['upcoming_days'] : GestionMaterielConfig::upcomingDays();
        $managerLogins = gestion_materiel_admin_logins(isset($_POST['gm_managers_enabled']) ? $_POST['gm_managers_enabled'] : array());
        $alertLinkColors = array();
        $dashboardTileDefinitions = GestionMaterielConfig::dashboardTileDefinitions();
        $dashboardTileCurrentConfig = GestionMaterielConfig::dashboardTileConfig();
        $dashboardTileOrder = isset($_POST['dashboard_tile_order']) && is_array($_POST['dashboard_tile_order'])
            ? $_POST['dashboard_tile_order']
            : $dashboardTileCurrentConfig['order'];
        $dashboardTileEnabledPost = isset($_POST['dashboard_tile_enabled']) && is_array($_POST['dashboard_tile_enabled'])
            ? $_POST['dashboard_tile_enabled']
            : array();
        $dashboardTileColorsPost = isset($_POST['dashboard_tile_color']) && is_array($_POST['dashboard_tile_color'])
            ? $_POST['dashboard_tile_color']
            : array();
        $dashboardTileColumns = isset($_POST['dashboard_tile_columns']) ? (int) $_POST['dashboard_tile_columns'] : 4;
        $dashboardTileSize = isset($_POST['dashboard_tile_size']) ? (string) $_POST['dashboard_tile_size'] : 'compact';
        $documentsEnabledInput = isset($_POST['documents_enabled']);
        $documentMaxMbInput = isset($_POST['document_max_mb'])
            ? (int) $_POST['document_max_mb']
            : GestionMaterielConfig::documentMaxMb();
        $documentExtensionsInput = isset($_POST['document_extensions'])
            ? trim((string) $_POST['document_extensions'])
            : GestionMaterielConfig::documentExtensionsText();
        $documentExtensions = GestionMaterielConfig::documentExtensionsFromText($documentExtensionsInput);
        $invalidDocumentExtensions = GestionMaterielConfig::invalidDocumentExtensionsFromText($documentExtensionsInput);
        $dashboardTileConfigInput = array(
            'order' => $dashboardTileOrder,
            'enabled' => array(),
            'colors' => array(),
            'columns' => $dashboardTileColumns,
            'size' => $dashboardTileSize,
        );

        if ($displayName === '') {
            $errors[] = 'Le nom affiche est obligatoire.';
        } elseif (strlen($displayName) > 80) {
            $errors[] = 'Le nom affiche ne doit pas depasser 80 caracteres.';
        }

        if ($upcomingDays < 1 || $upcomingDays > 365) {
            $errors[] = 'Le nombre de jours pour les echeances a venir doit etre compris entre 1 et 365.';
        }

        if (
            $documentMaxMbInput < GestionMaterielConfig::MIN_DOCUMENT_MAX_MB
            || $documentMaxMbInput > GestionMaterielConfig::MAX_DOCUMENT_MAX_MB
        ) {
            $errors[] = 'La taille maximale d un document doit etre comprise entre '
                .GestionMaterielConfig::MIN_DOCUMENT_MAX_MB.' et '
                .GestionMaterielConfig::MAX_DOCUMENT_MAX_MB.' Mo.';
        }
        if (count($invalidDocumentExtensions) > 0) {
            $errors[] = 'Extensions de documents interdites ou invalides : '.implode(', ', $invalidDocumentExtensions).'.';
        }
        if (count($documentExtensions) === 0) {
            $errors[] = 'Au moins une extension de document doit etre autorisee.';
        }

        $dashboardTileColumnOptions = GestionMaterielConfig::dashboardTileColumnOptions();
        if (!isset($dashboardTileColumnOptions[$dashboardTileColumns])) {
            $errors[] = 'La disposition des tuiles est invalide.';
        }
        $dashboardTileSizeOptions = GestionMaterielConfig::dashboardTileSizeOptions();
        if (!isset($dashboardTileSizeOptions[$dashboardTileSize])) {
            $errors[] = 'La taille des tuiles est invalide.';
        }

        foreach ($dashboardTileDefinitions as $key => $definition) {
            $dashboardTileConfigInput['enabled'][$key] = isset($dashboardTileEnabledPost[$key]);
            $color = isset($dashboardTileColorsPost[$key])
                ? trim((string) $dashboardTileColorsPost[$key])
                : $dashboardTileCurrentConfig['colors'][$key];
            $color = GestionMaterielConfig::normalizeColor($color, '');
            if ($color === '') {
                $errors[] = 'La couleur de la tuile '.$definition['label'].' doit etre au format #RRGGBB.';
                $color = $definition['color'];
            }
            $dashboardTileConfigInput['colors'][$key] = $color;
        }
        $dashboardTileConfigInput = GestionMaterielConfig::normalizeDashboardTileConfig($dashboardTileConfigInput);

        foreach (GestionMaterielConfig::alertLinkColorDefaults() as $status => $defaultColor) {
            $color = isset($_POST['alert_link_color_'.$status])
                ? trim((string) $_POST['alert_link_color_'.$status])
                : GestionMaterielConfig::alertLinkColor($status);

            $color = GestionMaterielConfig::normalizeColor($color, '');
            if ($color === '') {
                $errors[] = 'La couleur de notification '.($status === 'overdue' ? 'en retard' : 'a venir').' doit etre au format #RRGGBB.';
            }

            $alertLinkColors[$status] = $color === '' ? $defaultColor : $color;
        }

        if (count($errors) === 0) {
            $saveResults = array(
                'nom affiche' => GestionMaterielConfig::set('display_name', $displayName),
                'activation du module' => GestionMaterielConfig::set('enabled', isset($_POST['enabled']) ? '1' : '0'),
                'echeances a venir' => GestionMaterielConfig::setUpcomingDays($upcomingDays),
                'gestionnaires' => GestionMaterielConfig::setManagerLogins(GestionMaterielRepository::validUserLogins($managerLogins)),
                'tuiles statistiques' => GestionMaterielConfig::setDashboardTileConfig($dashboardTileConfigInput),
                'activation des documents' => GestionMaterielConfig::set('documents_enabled', $documentsEnabledInput ? '1' : '0'),
                'taille maximale des documents' => GestionMaterielConfig::set('document_max_mb', (string) $documentMaxMbInput),
                'extensions des documents' => GestionMaterielConfig::setDocumentExtensions($documentExtensions),
            );
            foreach (GestionMaterielConfig::alertLinkColorDefaults() as $status => $defaultColor) {
                $saveResults['couleur '.$status] = GestionMaterielConfig::setAlertLinkColor($status, $alertLinkColors[$status]);
            }

            foreach ($saveResults as $label => $saved) {
                if (!$saved) {
                    $errors[] = 'Erreur lors de l enregistrement : '.$label.'.';
                }
            }

            if (count($errors) === 0) {
                $message = 'Configuration enregistree.';
                $openConfigModal = false;
            }
        }
    }
}

$gestionMaterielAlertLinkColors = array();
foreach (GestionMaterielConfig::alertLinkColorDefaults() as $status => $defaultColor) {
    $gestionMaterielAlertLinkColors[$status] = isset($alertLinkColors[$status])
        ? $alertLinkColors[$status]
        : GestionMaterielConfig::alertLinkColor($status);
}
$gestionMaterielUpcomingDays = isset($upcomingDays) ? $upcomingDays : GestionMaterielConfig::upcomingDays();
$gestionMaterielDashboardTileConfig = $dashboardTileConfigInput === null
    ? GestionMaterielConfig::dashboardTileConfig()
    : $dashboardTileConfigInput;
$gestionMaterielDashboardTileDefinitions = GestionMaterielConfig::dashboardTileDefinitions();
$gestionMaterielDocumentsEnabled = $documentsEnabledInput === null
    ? GestionMaterielConfig::documentsEnabled()
    : $documentsEnabledInput;
$gestionMaterielDocumentMaxMb = $documentMaxMbInput === null
    ? GestionMaterielConfig::documentMaxMb()
    : $documentMaxMbInput;
$gestionMaterielDocumentExtensions = $documentExtensionsInput === null
    ? GestionMaterielConfig::documentExtensionsText()
    : $documentExtensionsInput;
$gestionMaterielManagerLogins = GestionMaterielConfig::managerLogins();
$gestionMaterielActiveUsers = GestionMaterielRepository::activeUsers();
$gestionMaterielNotificationToken = GestionMaterielConfig::notificationToken();
$gestionMaterielCronUrl = gestion_materiel_admin_cron_url($gestionMaterielNotificationToken);

if (!$gestionMaterielAdminEmbedded) {
    echo '<!doctype html><html><head><meta charset="utf-8"><title>'.gestion_materiel_admin_html(GestionMaterielConfig::displayName()).'</title>';
    echo '<link rel="stylesheet" href="../../../themes/default/css/style.css">';
    echo '</head><body>';
}

echo '<style>'
    .'.gestion-materiel-admin{width:100%;max-width:none;margin:0;box-sizing:border-box;}'
    .'.gestion-materiel-admin *{box-sizing:border-box;}'
    .'.gestion-materiel-admin h1{margin-top:0;}'
    .'.gestion-materiel-admin .gm-admin-actions{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0 18px;}'
    .'.gestion-materiel-admin .gm-admin-actions .btn{white-space:normal;}'
    .'.gestion-materiel-admin .gm-dual-list{display:flex;gap:16px;align-items:center;max-width:920px;}'
    .'.gestion-materiel-admin .gm-dual-column{flex:1;min-width:0;}'
    .'.gestion-materiel-admin .gm-dual-column select{width:100%;min-height:260px;}'
    .'.gestion-materiel-admin .gm-dual-actions{display:flex;flex-direction:column;gap:8px;}'
    .'.gestion-materiel-admin .gm-dual-actions button{min-width:120px;}'
    .'.gestion-materiel-admin .gm-dashboard-config td{vertical-align:middle;}'
    .'.gestion-materiel-admin .gm-dashboard-config .gm-tile-order{white-space:nowrap;}'
    .'.gestion-materiel-admin .gm-dashboard-config .gm-tile-preview{display:inline-block;min-width:150px;padding:6px 10px;border-radius:4px;text-align:center;}'
    .'.gestion-materiel-admin .gm-admin-modal{display:none;position:fixed;z-index:1050;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,.45);padding:24px 10px;}'
    .'.gestion-materiel-admin .gm-admin-modal.is-open{display:block;}'
    .'.gestion-materiel-admin .gm-admin-modal-dialog{background:#fff;border-radius:4px;box-shadow:0 12px 40px rgba(0,0,0,.28);margin:0 auto;width:100%;max-width:1120px;}'
    .'.gestion-materiel-admin .gm-admin-modal-head{align-items:center;border-bottom:1px solid #ddd;display:flex;gap:12px;justify-content:space-between;padding:12px 16px;}'
    .'.gestion-materiel-admin .gm-admin-modal-head strong{overflow-wrap:anywhere;}'
    .'.gestion-materiel-admin .gm-admin-modal-close{background:transparent;border:0;font-size:24px;line-height:1;padding:0 4px;}'
    .'.gestion-materiel-admin .gm-admin-modal-body{padding:16px;max-height:calc(100vh - 140px);overflow:auto;}'
    .'.gestion-materiel-admin .table-responsive{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;}'
    .'.gestion-materiel-admin table{width:100%;}'
    .'.gestion-materiel-admin th,.gestion-materiel-admin td{overflow-wrap:anywhere;}'
    .'@media (max-width:767px){'
        .'.gestion-materiel-admin{padding-left:0;padding-right:0;}'
        .'.gestion-materiel-admin .gm-admin-actions{align-items:stretch;}'
        .'.gestion-materiel-admin .gm-admin-actions .btn,.gestion-materiel-admin .gm-admin-actions a{width:100%;text-align:center;}'
        .'.gestion-materiel-admin .gm-dual-list{display:block;}'
        .'.gestion-materiel-admin .gm-dual-column,.gestion-materiel-admin .gm-dual-actions{width:100%;margin-bottom:10px;}'
        .'.gestion-materiel-admin .gm-dual-actions button{width:100%;}'
        .'.gestion-materiel-admin .gm-admin-modal{padding:10px;}'
        .'.gestion-materiel-admin .gm-admin-modal-dialog{max-width:none;}'
        .'.gestion-materiel-admin .gm-admin-modal-body{padding:12px;max-height:none;}'
        .'.gestion-materiel-admin table[data-responsive-table="1"],.gestion-materiel-admin table[data-responsive-table="1"] thead,.gestion-materiel-admin table[data-responsive-table="1"] tbody,.gestion-materiel-admin table[data-responsive-table="1"] tr,.gestion-materiel-admin table[data-responsive-table="1"] th,.gestion-materiel-admin table[data-responsive-table="1"] td{display:block;width:100%;}'
        .'.gestion-materiel-admin table[data-responsive-table="1"] thead{display:none;}'
        .'.gestion-materiel-admin table[data-responsive-table="1"] tr{margin:0 0 12px;border:1px solid #ddd;background:#fff;border-radius:4px;overflow:hidden;}'
        .'.gestion-materiel-admin table[data-responsive-table="1"] td{display:flex;gap:10px;justify-content:space-between;align-items:flex-start;border:0;border-bottom:1px solid #eee;text-align:right;}'
        .'.gestion-materiel-admin table[data-responsive-table="1"] td:last-child{border-bottom:0;}'
        .'.gestion-materiel-admin table[data-responsive-table="1"] td:before{content:attr(data-label);font-weight:600;color:#555;text-align:left;flex:0 0 42%;}'
    .'}'
    .'</style>';

echo '<script>(function(){if(window.gmAdminResponsiveReady){return;}window.gmAdminResponsiveReady=true;function prepare(){document.querySelectorAll(".gestion-materiel-admin table").forEach(function(table){if(table.getAttribute("data-responsive-table")==="1"){return;}var heads=table.tHead&&table.tHead.rows.length?table.tHead.rows[0].cells:[];if(!heads.length){return;}table.setAttribute("data-responsive-table","1");Array.prototype.forEach.call(table.tBodies,function(body){Array.prototype.forEach.call(body.rows,function(row){Array.prototype.forEach.call(row.cells,function(cell,index){var head=heads[index];if(head){cell.setAttribute("data-label",head.textContent.trim());}});});});});}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",prepare);}else{prepare();}setTimeout(prepare,0);})();</script>';

echo '<div class="container-fluid gestion-materiel-admin">';
echo '<h1>'.gestion_materiel_admin_html(GestionMaterielConfig::displayName()).'</h1>';

if ($gestionMaterielAdminEmbedded) {
    echo '<p><a href="compte.php?pc=gestion_materiel">Retour au module</a> | <a href="../personnalisation/modules/gestion_materiel/admin.php">Ouvrir la page autonome</a></p>';
} else {
    echo '<p><a href="../../../compte/compte.php?pc=gestion_materiel">Ouvrir le module utilisateur</a></p>';
}

if ($message !== '') {
    echo '<div class="alert alert-success">'.gestion_materiel_admin_html($message).'</div>';
}
foreach ($errors as $error) {
    echo '<div class="alert alert-danger">'.gestion_materiel_admin_html($error).'</div>';
}

echo '<div class="gm-admin-actions">'
    .gestion_materiel_admin_modal_button('gm-admin-config-modal', 'Configuration')
    .gestion_materiel_admin_modal_button('gm-admin-token-modal', 'Execution planifiee', 'btn btn-default')
    .'</div>';

ob_start();
echo '<form method="post" action="'.gestion_materiel_admin_html($gestionMaterielAdminAction).'" onsubmit="gestionMaterielSelectAllDualListOptions();">';
echo '<div class="form-group">';
echo '<label><input type="checkbox" name="enabled" value="1"'.(GestionMaterielConfig::isEnabled() ? ' checked' : '').'> Activer le module</label>';
echo '</div>';
echo '<div class="form-group">';
echo '<label for="display_name">Nom affiche</label>';
echo '<input class="form-control" id="display_name" type="text" name="display_name" maxlength="80" value="'.gestion_materiel_admin_html(GestionMaterielConfig::displayName()).'">';
echo '</div>';
echo '<div class="form-group">';
echo '<label for="upcoming_days">Echeances a venir</label>';
echo '<input class="form-control" id="upcoming_days" type="number" name="upcoming_days" min="1" max="365" value="'.gestion_materiel_admin_html($gestionMaterielUpcomingDays).'">';
echo '<p class="help-block">Nombre de jours utilises pour les alertes du tableau de bord, les notifications haut de page et la page Notifications.</p>';
echo '</div>';
echo '<h2>Gestionnaires du module</h2>';
echo '<p>Les gestionnaires voient tout le module, peuvent ajouter du materiel, modifier les materiels et gerer les utilisateurs assignes.</p>';
echo '<div class="gm-dual-list">';
echo '<div class="gm-dual-column">';
echo '<label for="gm_managers_enabled">Gestionnaires</label>';
echo '<select id="gm_managers_enabled" name="gm_managers_enabled[]" multiple size="12">';
echo gestion_materiel_admin_manager_options($gestionMaterielActiveUsers, $gestionMaterielManagerLogins, true);
echo '</select>';
echo '</div>';
echo '<div class="gm-dual-actions">';
echo '<button type="button" class="btn btn-default" onclick="gestionMaterielMoveOptions(\'gm_managers_enabled\', \'gm_managers_disabled\');">Retirer &gt;</button>';
echo '<button type="button" class="btn btn-default" onclick="gestionMaterielMoveOptions(\'gm_managers_disabled\', \'gm_managers_enabled\');">&lt; Ajouter</button>';
echo '</div>';
echo '<div class="gm-dual-column">';
echo '<label for="gm_managers_disabled">Autres comptes actifs</label>';
echo '<select id="gm_managers_disabled" name="gm_managers_disabled[]" multiple size="12">';
echo gestion_materiel_admin_manager_options($gestionMaterielActiveUsers, $gestionMaterielManagerLogins, false);
echo '</select>';
echo '</div>';
echo '</div>';
echo '<h2>Tuiles statistiques</h2>';
echo '<p>Selectionnez les statistiques affichees, leur ordre et leur couleur sur le tableau de bord.</p>';
echo '<div class="row">';
echo '<div class="col-md-4 form-group">';
echo '<label for="dashboard_tile_columns">Disposition sur ordinateur</label>';
echo '<select class="form-control" id="dashboard_tile_columns" name="dashboard_tile_columns">';
foreach (GestionMaterielConfig::dashboardTileColumnOptions() as $columns => $label) {
    echo '<option value="'.gestion_materiel_admin_html($columns).'"'.((int) $gestionMaterielDashboardTileConfig['columns'] === (int) $columns ? ' selected' : '').'>'.gestion_materiel_admin_html($label).'</option>';
}
echo '</select>';
echo '</div>';
echo '<div class="col-md-4 form-group">';
echo '<label for="dashboard_tile_size">Taille globale</label>';
echo '<select class="form-control" id="dashboard_tile_size" name="dashboard_tile_size">';
foreach (GestionMaterielConfig::dashboardTileSizeOptions() as $size => $label) {
    echo '<option value="'.gestion_materiel_admin_html($size).'"'.((string) $gestionMaterielDashboardTileConfig['size'] === (string) $size ? ' selected' : '').'>'.gestion_materiel_admin_html($label).'</option>';
}
echo '</select>';
echo '</div>';
echo '</div>';
echo '<div class="table-responsive">';
echo '<table class="table table-bordered table-striped gm-dashboard-config">';
echo '<thead><tr><th>Ordre</th><th>Afficher</th><th>Statistique</th><th>Couleur</th><th>Apercu</th></tr></thead>';
echo '<tbody id="gm-dashboard-tile-list">';
foreach ($gestionMaterielDashboardTileConfig['order'] as $key) {
    if (!isset($gestionMaterielDashboardTileDefinitions[$key])) {
        continue;
    }
    $definition = $gestionMaterielDashboardTileDefinitions[$key];
    $color = $gestionMaterielDashboardTileConfig['colors'][$key];
    $textColor = gestion_materiel_admin_contrast_color($color);
    echo '<tr class="gm-dashboard-tile-row" data-tile-key="'.gestion_materiel_admin_html($key).'">';
    echo '<td class="gm-tile-order">'
        .'<input type="hidden" name="dashboard_tile_order[]" value="'.gestion_materiel_admin_html($key).'">'
        .'<button type="button" class="btn btn-default btn-xs gm-tile-up" onclick="gestionMaterielMoveTile(this,-1);" aria-label="Monter">&uarr;</button> '
        .'<button type="button" class="btn btn-default btn-xs gm-tile-down" onclick="gestionMaterielMoveTile(this,1);" aria-label="Descendre">&darr;</button>'
        .'</td>';
    echo '<td><input type="checkbox" name="dashboard_tile_enabled['.gestion_materiel_admin_html($key).']" value="1"'
        .(!empty($gestionMaterielDashboardTileConfig['enabled'][$key]) ? ' checked' : '').'></td>';
    echo '<td>'.gestion_materiel_admin_html($definition['label']).'</td>';
    echo '<td><input class="gm-tile-color" type="color" name="dashboard_tile_color['.gestion_materiel_admin_html($key).']" value="'.gestion_materiel_admin_html($color).'" oninput="gestionMaterielUpdateTilePreview(this);"></td>';
    echo '<td><span class="gm-tile-preview" style="background-color:'.gestion_materiel_admin_html($color).';color:'.gestion_materiel_admin_html($textColor).';">'.gestion_materiel_admin_html($definition['label']).'</span></td>';
    echo '</tr>';
}
echo '</tbody></table></div>';
echo '<h2>Documents par materiel</h2>';
echo '<div class="form-group">';
echo '<label><input type="checkbox" name="documents_enabled" value="1"'.($gestionMaterielDocumentsEnabled ? ' checked' : '').'> Autoriser l ajout de documents</label>';
echo '<p class="help-block">Les documents deja presents restent consultables si l ajout est desactive.</p>';
echo '</div>';
echo '<div class="row">';
echo '<div class="col-md-4 form-group">';
echo '<label for="document_max_mb">Taille maximale par fichier (Mo)</label>';
echo '<input class="form-control" id="document_max_mb" type="number" name="document_max_mb" min="'
    .gestion_materiel_admin_html(GestionMaterielConfig::MIN_DOCUMENT_MAX_MB).'" max="'
    .gestion_materiel_admin_html(GestionMaterielConfig::MAX_DOCUMENT_MAX_MB).'" value="'
    .gestion_materiel_admin_html($gestionMaterielDocumentMaxMb).'">';
echo '</div>';
echo '<div class="col-md-8 form-group">';
echo '<label for="document_extensions">Extensions autorisees</label>';
echo '<textarea class="form-control" id="document_extensions" name="document_extensions" rows="2">'
    .gestion_materiel_admin_html($gestionMaterielDocumentExtensions).'</textarea>';
echo '<p class="help-block">Separateurs acceptes : virgule, point-virgule ou espace. Les extensions executables et web sont refusees.</p>';
echo '</div>';
echo '</div>';
echo '<h2>Notifications haut de page</h2>';
echo '<p>Ces couleurs s appliquent aux liens affiches sous les notifications de reservations en attente de moderation.</p>';
echo '<table class="table table-bordered table-striped">';
echo '<thead><tr><th>Lien</th><th>Couleur de fond</th><th>Apercu</th></tr></thead><tbody>';
foreach (GestionMaterielConfig::alertLinkColorDefaults() as $status => $defaultColor) {
    $label = $status === 'overdue' ? 'Materiel en retard' : 'Echeance materiel a venir';
    $sample = $status === 'overdue' ? '1 echeance materiel en retard' : '1 echeance materiel a venir';
    $color = $gestionMaterielAlertLinkColors[$status];
    echo '<tr>';
    echo '<td>'.gestion_materiel_admin_html($label).'</td>';
    echo '<td><input id="alert_link_color_'.gestion_materiel_admin_html($status).'" type="color" name="alert_link_color_'.gestion_materiel_admin_html($status).'" value="'.gestion_materiel_admin_html($color).'"></td>';
    echo '<td><span style="background-color:'.gestion_materiel_admin_html($color).'; color:#fff; display:inline-block; padding:2px 6px;">'.gestion_materiel_admin_html($sample).'</span></td>';
    echo '</tr>';
}
echo '</tbody></table>';
echo '<button class="btn btn-primary" type="submit">Enregistrer</button>';
echo '</form>';
$gestionMaterielConfigForm = ob_get_clean();
echo gestion_materiel_admin_modal('gm-admin-config-modal', 'Configuration du module', $gestionMaterielConfigForm, $openConfigModal);

ob_start();
echo '<p>Ce lien permet a une tache planifiee NAS Synology de declencher l envoi des notifications non encore envoyees.</p>';
if ($gestionMaterielNotificationToken === '') {
    echo '<div class="alert alert-warning">Aucun token configure. Le script planifie refuse les appels tant qu un token n est pas genere.</div>';
} else {
    echo '<div class="form-group">';
    echo '<label for="notification_cron_url">URL a utiliser dans la tache planifiee</label>';
    echo '<input class="form-control" id="notification_cron_url" type="text" readonly value="'.gestion_materiel_admin_html($gestionMaterielCronUrl).'">';
    echo '<p class="help-block">Le delai utilise est celui configure dans "Echeances a venir". Optionnel : ajouter &amp;days=14 pour forcer une autre periode.</p>';
    echo '</div>';
}
echo '<form method="post" action="'.gestion_materiel_admin_html($gestionMaterielAdminAction).'">';
echo '<input type="hidden" name="gm_admin_action" value="generate_notification_token">';
echo '<button class="btn btn-default" type="submit">'.($gestionMaterielNotificationToken === '' ? 'Generer le token' : 'Renouveler le token').'</button>';
echo '</form>';
$gestionMaterielTokenForm = ob_get_clean();
echo gestion_materiel_admin_modal('gm-admin-token-modal', 'Execution planifiee des notifications', $gestionMaterielTokenForm, $openTokenModal);

echo '<hr>';
echo '<h2>Diagnostic</h2>';
echo '<table class="table table-bordered table-striped">';
echo '<tbody>';
echo '<tr><th>Version module</th><td>'.gestion_materiel_admin_html($module_version).'</td></tr>';
echo '<tr><th>Version BDD attendue</th><td>'.gestion_materiel_admin_html($module_versionBDD).'</td></tr>';
echo '<tr><th>Module actif</th><td>'.gestion_materiel_admin_status(GestionMaterielConfig::isEnabled()).'</td></tr>';
echo '<tr><th>Ajout de documents</th><td>'.gestion_materiel_admin_status(GestionMaterielConfig::documentsEnabled())
    .' - '.gestion_materiel_admin_html(GestionMaterielConfig::documentMaxMb()).' Mo maximum</td></tr>';
echo '<tr><th>Stockage des documents</th><td>'.gestion_materiel_admin_status(GestionMaterielRepository::ensureDocumentStorage())
    .' <small>'.gestion_materiel_admin_html(GestionMaterielRepository::documentStorageDir()).'</small></td></tr>';
$gestionMaterielManagerDiagnostic = GestionMaterielConfig::managerLogins();
echo '<tr><th>Gestionnaires configures</th><td>'.gestion_materiel_admin_html(count($gestionMaterielManagerDiagnostic))
    .(count($gestionMaterielManagerDiagnostic) > 0 ? '<br><small>'.gestion_materiel_admin_html(implode(', ', $gestionMaterielManagerDiagnostic)).'</small>' : '')
    .'</td></tr>';
echo '<tr><th>Token notifications planifiees</th><td>'.gestion_materiel_admin_status(GestionMaterielConfig::hasNotificationToken()).'</td></tr>';
foreach (GestionMaterielRepository::diagnostics() as $diagnostic) {
    echo '<tr>';
    echo '<th>'.gestion_materiel_admin_html($diagnostic['label']).'</th>';
    echo '<td>'.gestion_materiel_admin_status($diagnostic['exists']).' '.gestion_materiel_admin_html($diagnostic['table']).'</td>';
    echo '</tr>';
}
echo '<tr><th>Nombre de materiels</th><td>'.gestion_materiel_admin_html(GestionMaterielRepository::countItems()).'</td></tr>';
echo '<tr><th>Utilisateurs assignes</th><td>'.gestion_materiel_admin_html(GestionMaterielRepository::countAssignedUsers()).'</td></tr>';
echo '<tr><th>Actions enregistrees</th><td>'.gestion_materiel_admin_html(GestionMaterielRepository::countActions()).'</td></tr>';
echo '<tr><th>Documents enregistres</th><td>'.gestion_materiel_admin_html(GestionMaterielRepository::countDocuments()).'</td></tr>';
echo '<tr><th>Notifications journalisees</th><td>'.gestion_materiel_admin_html(GestionMaterielRepository::countNotificationLogs()).'</td></tr>';
echo '</tbody>';
echo '</table>';
echo '</div>';
echo '<script>'
    .'function gestionMaterielAdminOpenModal(id){'
        .'var modal=document.getElementById(id);'
        .'if(!modal){return false;}'
        .'modal.classList.add("is-open");'
        .'document.body.classList.add("modal-open");'
        .'return false;'
    .'}'
    .'function gestionMaterielAdminCloseModal(modal){'
        .'if(typeof modal==="string"){modal=document.getElementById(modal);}'
        .'if(!modal){return false;}'
        .'modal.classList.remove("is-open");'
        .'document.body.classList.remove("modal-open");'
        .'return false;'
    .'}'
    .'document.addEventListener("click",function(event){'
        .'var openButton=event.target.closest?event.target.closest("[data-gm-admin-modal-open]"):null;'
        .'if(openButton){event.preventDefault();gestionMaterielAdminOpenModal(openButton.getAttribute("data-gm-admin-modal-open"));return;}'
        .'var closeButton=event.target.closest?event.target.closest("[data-gm-admin-modal-close]"):null;'
        .'if(closeButton){event.preventDefault();gestionMaterielAdminCloseModal(closeButton.closest(".gm-admin-modal"));return;}'
        .'if(event.target&&event.target.classList&&event.target.classList.contains("gm-admin-modal")){gestionMaterielAdminCloseModal(event.target);}'
    .'});'
    .'document.addEventListener("keydown",function(event){'
        .'if(event.key!=="Escape"){return;}'
        .'var modals=document.querySelectorAll(".gm-admin-modal.is-open");'
        .'for(var i=0;i<modals.length;i++){gestionMaterielAdminCloseModal(modals[i]);}'
    .'});'
    .'function gestionMaterielMoveOptions(fromId,toId){'
        .'var from=document.getElementById(fromId);'
        .'var to=document.getElementById(toId);'
        .'if(!from||!to){return;}'
        .'var selected=[];'
        .'for(var index=0;index<from.options.length;index++){if(from.options[index].selected){selected.push(from.options[index]);}}'
        .'for(var i=0;i<selected.length;i++){selected[i].selected=false;to.appendChild(selected[i]);}'
        .'gestionMaterielSortOptions(to);'
    .'}'
    .'function gestionMaterielSortOptions(select){'
        .'var options=[];'
        .'for(var index=0;index<select.options.length;index++){options.push(select.options[index]);}'
        .'options.sort(function(left,right){return left.text.localeCompare(right.text);});'
        .'for(var i=0;i<options.length;i++){select.appendChild(options[i]);}'
    .'}'
    .'function gestionMaterielSelectAllDualListOptions(){'
        .'var ids=["gm_managers_enabled","gm_managers_disabled"];'
        .'for(var i=0;i<ids.length;i++){'
            .'var select=document.getElementById(ids[i]);'
            .'if(!select){continue;}'
            .'for(var index=0;index<select.options.length;index++){select.options[index].selected=true;}'
        .'}'
    .'}'
    .'function gestionMaterielMoveTile(button,direction){'
        .'var row=button&&button.closest?button.closest("tr"):null;'
        .'var list=document.getElementById("gm-dashboard-tile-list");'
        .'if(!row||!list){return;}'
        .'if(direction<0&&row.previousElementSibling){list.insertBefore(row,row.previousElementSibling);}'
        .'if(direction>0&&row.nextElementSibling){list.insertBefore(row.nextElementSibling,row);}'
        .'gestionMaterielUpdateTileButtons();'
    .'}'
    .'function gestionMaterielUpdateTileButtons(){'
        .'var list=document.getElementById("gm-dashboard-tile-list");'
        .'if(!list){return;}'
        .'var rows=list.querySelectorAll(".gm-dashboard-tile-row");'
        .'for(var i=0;i<rows.length;i++){'
            .'var up=rows[i].querySelector(".gm-tile-up");'
            .'var down=rows[i].querySelector(".gm-tile-down");'
            .'if(up){up.disabled=i===0;}'
            .'if(down){down.disabled=i===rows.length-1;}'
        .'}'
    .'}'
    .'function gestionMaterielTileTextColor(color){'
        .'var value=String(color||"").replace("#","");'
        .'if(!/^[0-9a-fA-F]{6}$/.test(value)){return "#ffffff";}'
        .'var red=parseInt(value.substring(0,2),16);'
        .'var green=parseInt(value.substring(2,4),16);'
        .'var blue=parseInt(value.substring(4,6),16);'
        .'return ((red*299+green*587+blue*114)/1000)>=160?"#212529":"#ffffff";'
    .'}'
    .'function gestionMaterielUpdateTilePreview(input){'
        .'var row=input&&input.closest?input.closest("tr"):null;'
        .'var preview=row?row.querySelector(".gm-tile-preview"):null;'
        .'if(!preview){return;}'
        .'preview.style.backgroundColor=input.value;'
        .'preview.style.color=gestionMaterielTileTextColor(input.value);'
    .'}'
    .'gestionMaterielUpdateTileButtons();'
    .'</script>';

if (!$gestionMaterielAdminEmbedded) {
    echo '</body></html>';
}
