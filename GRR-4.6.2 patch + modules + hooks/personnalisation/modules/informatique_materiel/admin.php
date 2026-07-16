<?php

$informatiqueMaterielAdminEmbedded = isset($informatique_materiel_admin_embedded) && $informatique_materiel_admin_embedded;

require_once __DIR__.'/lib/bootstrap.php';
$sessionOk = $informatiqueMaterielAdminEmbedded ? true : grr_informatique_materiel_bootstrap(true);
require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Security.php';
require_once __DIR__.'/lib/LdapDirectory.php';

if (!$sessionOk || !InformatiqueMaterielSecurity::isAdmin()) {
    if ($informatiqueMaterielAdminEmbedded) {
        echo '<div class="alert alert-warning">Acces refuse.</div>';
        return;
    }
    http_response_code(403);
    exit('Acces refuse');
}

function informatique_materiel_admin_html($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function informatique_materiel_admin_status($ok)
{
    return $ok ? '<span class="label label-success">OK</span>' : '<span class="label label-danger">Erreur</span>';
}

function informatique_materiel_admin_modal_button($id, $label, $class = 'btn btn-primary')
{
    return '<button class="'.informatique_materiel_admin_html($class).'" type="button" data-imat-admin-modal-open="'.informatique_materiel_admin_html($id).'">'.informatique_materiel_admin_html($label).'</button>';
}

function informatique_materiel_admin_modal($id, $title, $content, $open = false)
{
    return '<div class="imat-admin-modal'.($open ? ' is-open' : '').'" id="'.informatique_materiel_admin_html($id).'" role="dialog" aria-modal="true">'
        .'<div class="imat-admin-modal-dialog">'
        .'<div class="imat-admin-modal-head"><strong>'.informatique_materiel_admin_html($title).'</strong><button class="imat-admin-modal-close" type="button" data-imat-admin-modal-close>&times;</button></div>'
        .'<div class="imat-admin-modal-body">'.$content.'</div>'
        .'</div></div>';
}

InformatiqueMaterielRepository::ensureTables();
$login = InformatiqueMaterielSecurity::currentLogin();
$message = '';
$errors = array();
$ldapTest = null;
$openConfigModal = false;
$openRoleModal = false;
$openLdapTest = false;
$openResetModal = false;

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!InformatiqueMaterielSecurity::validatePost()) {
        $errors[] = 'Session de formulaire invalide. Rechargez la page.';
    } else {
        $action = isset($_POST['imat_admin_action']) ? (string) $_POST['imat_admin_action'] : 'save_config';
        if ($action === 'save_config') {
            $openConfigModal = true;
            $displayName = trim((string) (isset($_POST['display_name']) ? $_POST['display_name'] : ''));
            $departDays = (int) (isset($_POST['depart_days']) ? $_POST['depart_days'] : 30);
            $maxMb = (int) (isset($_POST['document_max_mb']) ? $_POST['document_max_mb'] : 10);
            $extensions = InformatiqueMaterielConfig::extensionsFromText(isset($_POST['document_extensions']) ? $_POST['document_extensions'] : '');
            $alertDangerColor = trim((string) (isset($_POST['alert_danger_color']) ? $_POST['alert_danger_color'] : ''));
            $alertWarningColor = trim((string) (isset($_POST['alert_warning_color']) ? $_POST['alert_warning_color'] : ''));
            $conflictAlertColor = trim((string) (isset($_POST['conflict_alert_color']) ? $_POST['conflict_alert_color'] : ''));

            if ($displayName === '' || strlen($displayName) > 80) {
                $errors[] = 'Nom affiche invalide.';
            }
            if ($departDays < 1 || $departDays > 365) {
                $errors[] = 'Le delai de depart proche doit etre compris entre 1 et 365 jours.';
            }
            if ($maxMb < InformatiqueMaterielConfig::MIN_DOCUMENT_MAX_MB || $maxMb > InformatiqueMaterielConfig::MAX_DOCUMENT_MAX_MB) {
                $errors[] = 'Taille documentaire invalide.';
            }
            if (count($extensions) === 0) {
                $errors[] = 'Au moins une extension doit etre autorisee.';
            }
            if (!InformatiqueMaterielConfig::isHexColor($alertDangerColor)) {
                $errors[] = 'Couleur des alertes critiques invalide.';
            }
            if (!InformatiqueMaterielConfig::isHexColor($alertWarningColor)) {
                $errors[] = 'Couleur des alertes d avertissement invalide.';
            }
            if (!InformatiqueMaterielConfig::isHexColor($conflictAlertColor)) {
                $errors[] = 'Couleur des conflits invalide.';
            }

            if (count($errors) === 0) {
                $results = array(
                    InformatiqueMaterielConfig::set('enabled', isset($_POST['enabled']) ? '1' : '0'),
                    InformatiqueMaterielConfig::set('display_name', $displayName),
                    InformatiqueMaterielConfig::set('alerts_enabled', isset($_POST['alerts_enabled']) ? '1' : '0'),
                    InformatiqueMaterielConfig::set('depart_days', (string) $departDays),
                    InformatiqueMaterielConfig::set('conflict_banner_enabled', isset($_POST['conflict_banner_enabled']) ? '1' : '0'),
                    InformatiqueMaterielConfig::set('alert_danger_color', InformatiqueMaterielConfig::cleanColor($alertDangerColor, InformatiqueMaterielConfig::DEFAULT_ALERT_DANGER_COLOR)),
                    InformatiqueMaterielConfig::set('alert_warning_color', InformatiqueMaterielConfig::cleanColor($alertWarningColor, InformatiqueMaterielConfig::DEFAULT_ALERT_WARNING_COLOR)),
                    InformatiqueMaterielConfig::set('conflict_alert_color', InformatiqueMaterielConfig::cleanColor($conflictAlertColor, InformatiqueMaterielConfig::DEFAULT_CONFLICT_ALERT_COLOR)),
                    InformatiqueMaterielConfig::set('documents_enabled', isset($_POST['documents_enabled']) ? '1' : '0'),
                    InformatiqueMaterielConfig::set('document_max_mb', (string) $maxMb),
                    InformatiqueMaterielConfig::set('document_extensions', implode(',', $extensions)),
                );

                if (in_array(false, $results, true)) {
                    $errors[] = 'Une option n a pas pu etre enregistree.';
                } else {
                    $message = 'Configuration enregistree.';
                    $openConfigModal = false;
                    InformatiqueMaterielRepository::log('configuration_modifiee', 'configuration', 0, '', $login);
                }
            }
        } elseif ($action === 'save_role') {
            $openRoleModal = true;
            $roleLogin = isset($_POST['role_login']) ? (string) $_POST['role_login'] : '';
            $role = isset($_POST['role']) ? (string) $_POST['role'] : '';
            if (InformatiqueMaterielRepository::setUserRole($roleLogin, $role, $login)) {
                $message = 'Role enregistre.';
                $openRoleModal = false;
            } else {
                $errors[] = 'Le role n a pas pu etre enregistre.';
            }
        } elseif ($action === 'remove_role') {
            if (InformatiqueMaterielRepository::setUserRole(isset($_POST['role_login']) ? $_POST['role_login'] : '', '', $login)) {
                $message = 'Role retire.';
            } else {
                $errors[] = 'Le role n a pas pu etre retire.';
            }
        } elseif ($action === 'reset_module') {
            $openResetModal = true;
            $confirmation = trim((string) (isset($_POST['reset_confirmation']) ? $_POST['reset_confirmation'] : ''));
            if ($confirmation !== 'REMISE A ZERO') {
                $errors[] = 'Confirmation de remise a zero invalide.';
            } else {
                $includeRolesAndJournal = isset($_POST['reset_roles_journal']);
                $result = InformatiqueMaterielRepository::resetModuleData($includeRolesAndJournal, $login);
                if (!empty($result['ok'])) {
                    $message = 'Remise a zero executee. Fichiers supprimes : '.(int) $result['deleted_files'].'.';
                    $openResetModal = false;
                } else {
                    $errors[] = 'La remise a zero n a pas pu etre executee completement.';
                }
            }
        } elseif ($action === 'test_ldap') {
            $term = isset($_POST['ldap_test_term']) ? (string) $_POST['ldap_test_term'] : '';
            $ldapTest = InformatiqueMaterielLdapDirectory::test($term);
            $openLdapTest = true;
            if (!empty($ldapTest['ok'])) {
                $message = 'Test LDAP execute.';
            } else {
                $errors[] = 'Test LDAP en echec.';
            }
        } else {
            $errors[] = 'Action inconnue.';
        }
    }
}

$module_version = '';
$module_versionBDD = '';
include __DIR__.'/infos.php';
$actionUrl = $informatiqueMaterielAdminEmbedded
    ? 'compte.php?pc=informatique_materiel&admin=1'
    : 'admin.php';

if (!$informatiqueMaterielAdminEmbedded) {
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Administration Informatique materiel</title>';
    echo '<link rel="stylesheet" href="../../../themes/default/css/style.css"></head><body>';
}

echo '<div class="container-fluid informatique-materiel-admin">';
echo '<h1>Administration - '.informatique_materiel_admin_html(InformatiqueMaterielConfig::displayName()).'</h1>';
echo $informatiqueMaterielAdminEmbedded
    ? '<p><a href="compte.php?pc=informatique_materiel">Retour au module</a> | <a href="../personnalisation/modules/informatique_materiel/admin.php">Page autonome</a></p>'
    : '<p><a href="../../../compte/compte.php?pc=informatique_materiel">Ouvrir le module</a></p>';

if ($message !== '') {
    echo '<div class="alert alert-success">'.informatique_materiel_admin_html($message).'</div>';
}
foreach ($errors as $error) {
    echo '<div class="alert alert-danger">'.informatique_materiel_admin_html($error).'</div>';
}

echo '<style>'
    .'.informatique-materiel-admin{width:100%;max-width:none;margin:0;box-sizing:border-box;}'
    .'.informatique-materiel-admin *{box-sizing:border-box;}'
    .'.informatique-materiel-admin h1{margin-top:0;}'
    .'.informatique-materiel-admin .imat-admin-actions{display:flex;flex-wrap:wrap;gap:6px;margin:12px 0 18px;}'
    .'.informatique-materiel-admin .imat-admin-actions .btn{white-space:normal;}'
    .'.informatique-materiel-admin .imat-admin-modal{display:none;position:fixed;z-index:5000;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,.45);padding:30px 12px;}'
    .'.informatique-materiel-admin .imat-admin-modal.is-open{display:block;}'
    .'.informatique-materiel-admin .imat-admin-modal-dialog{background:#fff;margin:0 auto;width:100%;max-width:1120px;border-radius:4px;box-shadow:0 8px 28px rgba(0,0,0,.25);}'
    .'.informatique-materiel-admin .imat-admin-modal-head{display:flex;gap:12px;justify-content:space-between;align-items:center;border-bottom:1px solid #ddd;padding:12px 15px;font-size:16px;}'
    .'.informatique-materiel-admin .imat-admin-modal-head strong{overflow-wrap:anywhere;}'
    .'.informatique-materiel-admin .imat-admin-modal-body{padding:15px;}'
    .'.informatique-materiel-admin .imat-admin-modal-close{border:0;background:transparent;font-size:24px;line-height:1;}'
    .'.informatique-materiel-admin .table-responsive{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;}'
    .'.informatique-materiel-admin table{width:100%;}'
    .'.informatique-materiel-admin th,.informatique-materiel-admin td{overflow-wrap:anywhere;}'
    .'@media (max-width:767px){'
        .'.informatique-materiel-admin{padding-left:0;padding-right:0;}'
        .'.informatique-materiel-admin .imat-admin-actions{align-items:stretch;}'
        .'.informatique-materiel-admin .imat-admin-actions .btn,.informatique-materiel-admin .imat-admin-actions a{width:100%;text-align:center;}'
        .'.informatique-materiel-admin .imat-admin-modal{padding:10px;}'
        .'.informatique-materiel-admin .imat-admin-modal-dialog{max-width:none;}'
        .'.informatique-materiel-admin .imat-admin-modal-body{padding:12px;}'
        .'.informatique-materiel-admin table[data-responsive-table="1"],.informatique-materiel-admin table[data-responsive-table="1"] thead,.informatique-materiel-admin table[data-responsive-table="1"] tbody,.informatique-materiel-admin table[data-responsive-table="1"] tr,.informatique-materiel-admin table[data-responsive-table="1"] th,.informatique-materiel-admin table[data-responsive-table="1"] td{display:block;width:100%;}'
        .'.informatique-materiel-admin table[data-responsive-table="1"] thead{display:none;}'
        .'.informatique-materiel-admin table[data-responsive-table="1"] tr{margin:0 0 12px;border:1px solid #ddd;background:#fff;border-radius:4px;overflow:hidden;}'
        .'.informatique-materiel-admin table[data-responsive-table="1"] td{display:flex;gap:10px;justify-content:space-between;align-items:flex-start;border:0;border-bottom:1px solid #eee;text-align:right;}'
        .'.informatique-materiel-admin table[data-responsive-table="1"] td:last-child{border-bottom:0;}'
        .'.informatique-materiel-admin table[data-responsive-table="1"] td:before{content:attr(data-label);font-weight:600;color:#555;text-align:left;flex:0 0 42%;}'
    .'}'
    .'</style>';
echo '<script>(function(){if(window.imatAdminModalReady){return;}window.imatAdminModalReady=true;function prepareTables(){document.querySelectorAll(".informatique-materiel-admin table").forEach(function(table){if(table.getAttribute("data-responsive-table")==="1"){return;}var heads=table.tHead&&table.tHead.rows.length?table.tHead.rows[0].cells:[];if(!heads.length){return;}table.setAttribute("data-responsive-table","1");Array.prototype.forEach.call(table.tBodies,function(body){Array.prototype.forEach.call(body.rows,function(row){Array.prototype.forEach.call(row.cells,function(cell,index){var head=heads[index];if(head){cell.setAttribute("data-label",head.textContent.trim());}});});});});}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",prepareTables);}else{prepareTables();}setTimeout(prepareTables,0);document.addEventListener("click",function(e){var open=e.target.closest("[data-imat-admin-modal-open]");if(open){e.preventDefault();var id=open.getAttribute("data-imat-admin-modal-open");var modal=document.getElementById(id);if(modal){modal.classList.add("is-open");}}var close=e.target.closest("[data-imat-admin-modal-close]");if(close){e.preventDefault();var box=close.closest(".imat-admin-modal");if(box){box.classList.remove("is-open");}}if(e.target.classList&&e.target.classList.contains("imat-admin-modal")){e.target.classList.remove("is-open");}});document.addEventListener("keydown",function(e){if(e.key==="Escape"){document.querySelectorAll(".imat-admin-modal.is-open").forEach(function(modal){modal.classList.remove("is-open");});}});})();</script>';
echo '<div class="imat-admin-actions">'
    .informatique_materiel_admin_modal_button('imat-admin-config', 'Configuration')
    .' '.informatique_materiel_admin_modal_button('imat-admin-role', 'Attribuer un role')
    .' '.informatique_materiel_admin_modal_button('imat-admin-ldap-test', 'Tester LDAP', 'btn btn-default')
    .' '.informatique_materiel_admin_modal_button('imat-admin-reset', 'Remise a zero', 'btn btn-danger')
    .'</div>';

ob_start();
echo '<form method="post" action="'.informatique_materiel_admin_html($actionUrl).'">';
echo InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_admin_action" value="save_config">';
echo '<h2>Configuration</h2>';
echo '<div class="form-group"><label><input type="checkbox" name="enabled" value="1"'.(InformatiqueMaterielConfig::isEnabled() ? ' checked' : '').'> Activer le module</label></div>';
echo '<div class="form-group"><label>Nom affiche</label><input class="form-control" name="display_name" maxlength="80" value="'.informatique_materiel_admin_html(InformatiqueMaterielConfig::displayName()).'"></div>';
echo '<h3>Alertes</h3>';
echo '<div class="form-group"><label><input type="checkbox" name="alerts_enabled" value="1"'.(InformatiqueMaterielConfig::alertsEnabled() ? ' checked' : '').'> Activer les alertes dans le module</label></div>';
echo '<div class="form-group"><label><input type="checkbox" name="conflict_banner_enabled" value="1"'.(InformatiqueMaterielConfig::conflictBannerEnabled() ? ' checked' : '').'> Afficher le bandeau des conflits en haut du module</label></div>';
echo '<div class="form-group"><label>Depart proche (jours)</label><input class="form-control" type="number" name="depart_days" min="1" max="365" value="'.(int) InformatiqueMaterielConfig::departDays().'"></div>';
echo '<div class="row">';
echo '<div class="col-md-4 form-group"><label>Couleur critique</label><input class="form-control" type="color" name="alert_danger_color" value="'.informatique_materiel_admin_html(InformatiqueMaterielConfig::alertDangerColor()).'"></div>';
echo '<div class="col-md-4 form-group"><label>Couleur avertissement</label><input class="form-control" type="color" name="alert_warning_color" value="'.informatique_materiel_admin_html(InformatiqueMaterielConfig::alertWarningColor()).'"></div>';
echo '<div class="col-md-4 form-group"><label>Couleur conflits</label><input class="form-control" type="color" name="conflict_alert_color" value="'.informatique_materiel_admin_html(InformatiqueMaterielConfig::conflictAlertColor()).'"></div>';
echo '</div>';
echo '<h3>Documents</h3>';
echo '<div class="form-group"><label><input type="checkbox" name="documents_enabled" value="1"'.(InformatiqueMaterielConfig::documentsEnabled() ? ' checked' : '').'> Autoriser le depot de documents</label></div>';
echo '<div class="form-group"><label>Taille maximale (Mo)</label><input class="form-control" type="number" name="document_max_mb" min="1" max="50" value="'.(int) InformatiqueMaterielConfig::documentMaxMb().'"></div>';
echo '<div class="form-group"><label>Extensions autorisees</label><textarea class="form-control" name="document_extensions" rows="2">'.informatique_materiel_admin_html(InformatiqueMaterielConfig::documentExtensionsText()).'</textarea></div>';
echo '<button class="btn btn-primary" type="submit">Enregistrer</button>';
echo '</form>';
$configForm = ob_get_clean();
echo informatique_materiel_admin_modal('imat-admin-config', 'Configuration', $configForm, $openConfigModal);

ob_start();
echo '<form method="post" action="'.informatique_materiel_admin_html($actionUrl).'">';
echo InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_admin_action" value="test_ldap">';
echo '<div class="form-group"><label>Terme de recherche</label><input class="form-control" name="ldap_test_term" maxlength="100" value="'.informatique_materiel_admin_html(isset($_POST['ldap_test_term']) ? $_POST['ldap_test_term'] : '').'" placeholder="nom, prenom ou login"></div>';
echo '<button class="btn btn-primary" type="submit">Tester</button>';
echo '</form>';
if (is_array($ldapTest)) {
    echo '<hr><h3>Resultat</h3>';
    echo '<p><strong>Statut :</strong> '.informatique_materiel_admin_html($ldapTest['status']).'</p>';
    foreach ($ldapTest['details'] as $detail) {
        echo '<div class="alert '.(!empty($ldapTest['ok']) ? 'alert-success' : 'alert-warning').'">'.informatique_materiel_admin_html($detail).'</div>';
    }
    if (!empty($ldapTest['entries'])) {
        echo '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Login</th><th>Source</th><th>Nom</th><th>Prenom</th><th>Email</th></tr></thead><tbody>';
        foreach ($ldapTest['entries'] as $entry) {
            echo '<tr><td>'.informatique_materiel_admin_html($entry['login']).'</td><td>'.informatique_materiel_admin_html($entry['source']).'</td><td>'.informatique_materiel_admin_html($entry['nom']).'</td><td>'.informatique_materiel_admin_html($entry['prenom']).'</td><td>'.informatique_materiel_admin_html($entry['email']).'</td></tr>';
        }
        echo '</tbody></table></div>';
    }
}
$ldapForm = ob_get_clean();
echo informatique_materiel_admin_modal('imat-admin-ldap-test', 'Test LDAP', $ldapForm, $openLdapTest);

echo '<hr><h2>Roles utilisateurs</h2>';
ob_start();
echo '<form class="form-inline" method="post" action="'.informatique_materiel_admin_html($actionUrl).'">';
echo InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_admin_action" value="save_role">';
echo '<div class="form-group"><label>Compte&nbsp;</label><select class="form-control" name="role_login" required><option value="">Choisir</option>';
foreach (InformatiqueMaterielRepository::activeUsers() as $user) {
    $userLogin = isset($user['login']) ? (string) $user['login'] : '';
    $label = trim((string) $user['nom'].' '.(string) $user['prenom']).' ('.$userLogin.')';
    echo '<option value="'.informatique_materiel_admin_html($userLogin).'">'.informatique_materiel_admin_html($label).'</option>';
}
echo '</select></div> <div class="form-group"><label>Role&nbsp;</label><select class="form-control" name="role">';
foreach (InformatiqueMaterielRepository::roles() as $key => $label) {
    echo '<option value="'.informatique_materiel_admin_html($key).'">'.informatique_materiel_admin_html($label).'</option>';
}
echo '</select></div> <button class="btn btn-primary" type="submit">Attribuer</button></form>';
$roleForm = ob_get_clean();
echo informatique_materiel_admin_modal('imat-admin-role', 'Attribuer un role', $roleForm, $openRoleModal);

echo '<div class="table-responsive"><table class="table table-bordered table-striped" style="margin-top:15px"><thead><tr><th>Compte</th><th>Nom</th><th>Role</th><th></th></tr></thead><tbody>';
$roleLabels = InformatiqueMaterielRepository::roles();
foreach (InformatiqueMaterielRepository::assignedRoles() as $role) {
    $roleLabel = isset($roleLabels[$role['role']]) ? $roleLabels[$role['role']] : $role['role'];
    echo '<tr><td>'.informatique_materiel_admin_html($role['login']).'</td><td>'.informatique_materiel_admin_html(trim($role['nom'].' '.$role['prenom'])).'</td><td>'.informatique_materiel_admin_html($roleLabel).'</td><td>';
    echo '<form method="post" action="'.informatique_materiel_admin_html($actionUrl).'">'.InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_admin_action" value="remove_role"><input type="hidden" name="role_login" value="'.informatique_materiel_admin_html($role['login']).'"><button class="btn btn-danger btn-xs" type="submit">Retirer</button></form>';
    echo '</td></tr>';
}
echo '</tbody></table></div>';

ob_start();
echo '<div class="alert alert-warning">Action irreversible sans sauvegarde. Les tables sont conservees, mais les donnees selectionnees et les fichiers stockes sont supprimes.</div>';
echo '<form method="post" action="'.informatique_materiel_admin_html($actionUrl).'" onsubmit="return confirm(\'Executer la remise a zero du module ?\')">';
echo InformatiqueMaterielSecurity::field().'<input type="hidden" name="imat_admin_action" value="reset_module">';
echo '<div class="form-group"><label>Confirmation</label><input class="form-control" name="reset_confirmation" maxlength="20" placeholder="REMISE A ZERO" required></div>';
echo '<div class="checkbox"><label><input type="checkbox" name="reset_roles_journal" value="1"> Inclure aussi les roles utilisateurs et le journal fonctionnel</label></div>';
echo '<button class="btn btn-danger" type="submit">Remettre a zero le module</button>';
echo '</form>';
$resetForm = ob_get_clean();
echo informatique_materiel_admin_modal('imat-admin-reset', 'Remise a zero', $resetForm, $openResetModal);

echo '<hr><h2>Diagnostic</h2>';
echo '<table class="table table-bordered"><tbody>';
echo '<tr><th>Version module</th><td>'.informatique_materiel_admin_html($module_version).'</td></tr>';
echo '<tr><th>Version BDD attendue</th><td>'.informatique_materiel_admin_html($module_versionBDD).'</td></tr>';
echo '<tr><th>Module actif</th><td>'.informatique_materiel_admin_status(InformatiqueMaterielConfig::isEnabled()).'</td></tr>';
echo '<tr><th>Alertes actives</th><td>'.informatique_materiel_admin_status(InformatiqueMaterielConfig::alertsEnabled()).'</td></tr>';
echo '<tr><th>Bandeau conflits actif</th><td>'.informatique_materiel_admin_status(InformatiqueMaterielConfig::conflictBannerEnabled()).'</td></tr>';
echo '<tr><th>Couleurs alertes</th><td>Critique '.informatique_materiel_admin_html(InformatiqueMaterielConfig::alertDangerColor()).' ; avertissement '.informatique_materiel_admin_html(InformatiqueMaterielConfig::alertWarningColor()).' ; conflits '.informatique_materiel_admin_html(InformatiqueMaterielConfig::conflictAlertColor()).'</td></tr>';
echo '<tr><th>Recherche LDAP</th><td>'.informatique_materiel_admin_html(InformatiqueMaterielLdapDirectory::status()).'</td></tr>';
foreach (InformatiqueMaterielRepository::diagnostics() as $diagnostic) {
    $engine = $diagnostic['engine'] !== '' ? ' - moteur '.informatique_materiel_admin_html($diagnostic['engine']) : '';
    echo '<tr><th>'.informatique_materiel_admin_html($diagnostic['label']).'</th><td>'
        .informatique_materiel_admin_status($diagnostic['exists']).' '
        .informatique_materiel_admin_html($diagnostic['table']).$engine.'</td></tr>';
}
$counts = InformatiqueMaterielRepository::dashboardCounts();
echo '<tr><th>Roles attribues</th><td>'.(int) $counts['roles'].'</td></tr>';
echo '<tr><th>Categories actives</th><td>'.(int) $counts['categories'].'</td></tr>';
echo '<tr><th>Personnes actives</th><td>'.(int) $counts['personnes'].'</td></tr>';
echo '<tr><th>Materiels actifs</th><td>'.(int) $counts['materiels'].'</td></tr>';
echo '<tr><th>Prets ouverts</th><td>'.(int) $counts['prets_ouverts'].'</td></tr>';
echo '<tr><th>Conflits de prets en attente</th><td>'.(int) $counts['conflits_prets'].'</td></tr>';
echo '<tr><th>Documents actifs</th><td>'.(int) $counts['documents'].'</td></tr>';
echo '<tr><th>Alertes operationnelles</th><td>'.(int) $counts['alertes'].'</td></tr>';
echo '<tr><th>Lignes d import journalisees</th><td>'.(int) $counts['imports'].'</td></tr>';
echo '<tr><th>Evenements journalises</th><td>'.(int) $counts['journal'].'</td></tr>';
echo '<tr><th>Stockage documents</th><td>'.informatique_materiel_admin_status(InformatiqueMaterielRepository::ensureDocumentStorage()).' '.informatique_materiel_admin_html(InformatiqueMaterielRepository::documentStorageDir()).'</td></tr>';
$alertCounts = InformatiqueMaterielRepository::alertCounts();
echo '<tr><th>Alertes - prets en retard</th><td>'.(int) $alertCounts['prets_en_retard'].'</td></tr>';
echo '<tr><th>Alertes - personnes parties</th><td>'.(int) $alertCounts['personnes_parties_avec_pret'].'</td></tr>';
echo '<tr><th>Alertes - materiels sans identifiant</th><td>'.(int) $alertCounts['materiels_sans_identifiant'].'</td></tr>';
echo '<tr><th>Alertes - materiels sans categorie</th><td>'.(int) $alertCounts['materiels_sans_categorie'].'</td></tr>';
echo '<tr><th>Alertes - codes-barres dupliques</th><td>'.(int) $alertCounts['codes_barres_dupliques'].'</td></tr>';
echo '<tr><th>Alertes - prets multiples non generiques</th><td>'.(int) $alertCounts['prets_ouverts_multiples'].'</td></tr>';
$itemDiagnostics = InformatiqueMaterielRepository::itemDiagnostics();
echo '<tr><th>Materiels sans categorie</th><td>'.informatique_materiel_admin_status((int) $itemDiagnostics['sans_categorie'] === 0).' '.(int) $itemDiagnostics['sans_categorie'].'</td></tr>';
echo '<tr><th>MAC dupliquees</th><td>'.informatique_materiel_admin_status((int) $itemDiagnostics['mac_dupliquees'] === 0).' '.(int) $itemDiagnostics['mac_dupliquees'].'</td></tr>';
echo '<tr><th>Numeros de serie dupliques</th><td>'.informatique_materiel_admin_status((int) $itemDiagnostics['numeros_serie_dupliques'] === 0).' '.(int) $itemDiagnostics['numeros_serie_dupliques'].'</td></tr>';
echo '<tr><th>Codes-barres dupliques</th><td>'.informatique_materiel_admin_status((int) $itemDiagnostics['codes_barres_dupliques'] === 0).' '.(int) $itemDiagnostics['codes_barres_dupliques'].'</td></tr>';
$loanDiagnostics = InformatiqueMaterielRepository::loanDiagnostics();
echo '<tr><th>Materiels non generiques avec plusieurs prets ouverts</th><td>'.informatique_materiel_admin_status((int) $loanDiagnostics['prets_ouverts_multiples'] === 0).' '.(int) $loanDiagnostics['prets_ouverts_multiples'].'</td></tr>';
echo '<tr><th>Prets sans personne</th><td>'.informatique_materiel_admin_status((int) $loanDiagnostics['prets_sans_personne'] === 0).' '.(int) $loanDiagnostics['prets_sans_personne'].'</td></tr>';
echo '<tr><th>Prets sans materiel</th><td>'.informatique_materiel_admin_status((int) $loanDiagnostics['prets_sans_materiel'] === 0).' '.(int) $loanDiagnostics['prets_sans_materiel'].'</td></tr>';
echo '<tr><th>Prets en retard</th><td>'.informatique_materiel_admin_status((int) $loanDiagnostics['prets_en_retard'] === 0).' '.(int) $loanDiagnostics['prets_en_retard'].'</td></tr>';
echo '<tr><th>Personnes parties avec pret ouvert</th><td>'.informatique_materiel_admin_status((int) $loanDiagnostics['personnes_parties_avec_pret'] === 0).' '.(int) $loanDiagnostics['personnes_parties_avec_pret'].'</td></tr>';
echo '</tbody></table>';
echo '</div>';

if (!$informatiqueMaterielAdminEmbedded) {
    echo '</body></html>';
}
