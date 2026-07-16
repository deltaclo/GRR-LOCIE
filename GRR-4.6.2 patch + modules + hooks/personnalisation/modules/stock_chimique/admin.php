<?php

$stockChimiqueAdminEmbedded = isset($stock_chimique_admin_embedded) && $stock_chimique_admin_embedded;

require_once __DIR__.'/lib/bootstrap.php';
$sessionOk = $stockChimiqueAdminEmbedded ? true : grr_stock_chimique_bootstrap(true);
require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Security.php';
require_once __DIR__.'/lib/Import.php';

if (!$sessionOk || !StockChimiqueSecurity::isAdmin()) {
    if ($stockChimiqueAdminEmbedded) {
        echo '<div class="alert alert-warning">Accès refusé.</div>';
        return;
    }
    http_response_code(403);
    exit('Accès refusé');
}

function stock_chimique_admin_html($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function stock_chimique_admin_status($ok)
{
    return $ok ? '<span class="label label-success">OK</span>' : '<span class="label label-danger">Erreur</span>';
}

function stock_chimique_admin_modal_button($id, $label, $class = 'btn btn-primary')
{
    return '<button class="'.stock_chimique_admin_html($class).'" type="button" data-sc-admin-modal-open="'.stock_chimique_admin_html($id).'">'.stock_chimique_admin_html($label).'</button>';
}

function stock_chimique_admin_modal($id, $title, $content, $open = false)
{
    return '<div class="sc-admin-modal'.($open ? ' is-open' : '').'" id="'.stock_chimique_admin_html($id).'" role="dialog" aria-modal="true">'
        .'<div class="sc-admin-modal-dialog">'
        .'<div class="sc-admin-modal-head"><strong>'.stock_chimique_admin_html($title).'</strong><button class="sc-admin-modal-close" type="button" data-sc-admin-modal-close>&times;</button></div>'
        .'<div class="sc-admin-modal-body">'.$content.'</div>'
        .'</div></div>';
}

StockChimiqueRepository::ensureTables();
$login = StockChimiqueSecurity::currentLogin();
$message = '';
$errors = array();
$importPreview = null;
$importResult = null;
$selectedImportPackage = isset($_POST['import_package']) ? trim((string) $_POST['import_package']) : '';
$openConfigModal = false;
$openRoleModal = false;
$openImportModal = false;
$openImportExecuteModal = false;
$openTokenModal = false;

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!StockChimiqueSecurity::validatePost()) {
        $errors[] = 'Session de formulaire invalide. Rechargez la page.';
    } else {
        $action = isset($_POST['sc_admin_action']) ? (string) $_POST['sc_admin_action'] : 'save_config';
        if ($action === 'save_config') {
            $openConfigModal = true;
            $displayName = trim((string) (isset($_POST['display_name']) ? $_POST['display_name'] : ''));
            $expiryDays = (int) (isset($_POST['expiry_days']) ? $_POST['expiry_days'] : 90);
            $fdsMonths = (int) (isset($_POST['fds_months']) ? $_POST['fds_months'] : 36);
            $maxMb = (int) (isset($_POST['document_max_mb']) ? $_POST['document_max_mb'] : 10);
            $extensions = StockChimiqueConfig::extensionsFromText(isset($_POST['document_extensions']) ? $_POST['document_extensions'] : '');
            if ($displayName === '' || strlen($displayName) > 80) {
                $errors[] = 'Nom affiché invalide.';
            }
            if ($expiryDays < 1 || $expiryDays > 730) {
                $errors[] = 'Le délai de péremption doit être compris entre 1 et 730 jours.';
            }
            if ($fdsMonths < 1 || $fdsMonths > 120) {
                $errors[] = 'Le délai FDS doit être compris entre 1 et 120 mois.';
            }
            if ($maxMb < StockChimiqueConfig::MIN_DOCUMENT_MAX_MB || $maxMb > StockChimiqueConfig::MAX_DOCUMENT_MAX_MB) {
                $errors[] = 'Taille documentaire invalide.';
            }
            if (count($extensions) === 0) {
                $errors[] = 'Au moins une extension doit être autorisée.';
            }
            if (count($errors) === 0) {
                $results = array(
                    StockChimiqueConfig::set('enabled', isset($_POST['enabled']) ? '1' : '0'),
                    StockChimiqueConfig::set('display_name', $displayName),
                    StockChimiqueConfig::set('alerts_enabled', isset($_POST['alerts_enabled']) ? '1' : '0'),
                    StockChimiqueConfig::set('alert_stock_enabled', isset($_POST['alert_stock_enabled']) ? '1' : '0'),
                    StockChimiqueConfig::set('alert_expiry_enabled', isset($_POST['alert_expiry_enabled']) ? '1' : '0'),
                    StockChimiqueConfig::set('alert_fds_enabled', isset($_POST['alert_fds_enabled']) ? '1' : '0'),
                    StockChimiqueConfig::set('expiry_days', (string) $expiryDays),
                    StockChimiqueConfig::set('fds_months', (string) $fdsMonths),
                    StockChimiqueConfig::set('notifications_enabled', isset($_POST['notifications_enabled']) ? '1' : '0'),
                    StockChimiqueConfig::set('documents_enabled', isset($_POST['documents_enabled']) ? '1' : '0'),
                    StockChimiqueConfig::set('document_max_mb', (string) $maxMb),
                    StockChimiqueConfig::set('document_extensions', implode(',', $extensions)),
                );
                if (in_array(false, $results, true)) {
                    $errors[] = 'Une option n a pas pu être enregistrée.';
                } else {
                    $message = 'Configuration enregistrée.';
                    $openConfigModal = false;
                    StockChimiqueRepository::log('configuration_modifiee', 'configuration', 0, '', $login);
                }
            }
        } elseif ($action === 'save_role') {
            $openRoleModal = true;
            $roleLogin = isset($_POST['role_login']) ? (string) $_POST['role_login'] : '';
            $role = isset($_POST['role']) ? (string) $_POST['role'] : '';
            if (StockChimiqueRepository::setUserRole($roleLogin, $role, $login)) {
                $message = 'Rôle enregistré.';
                $openRoleModal = false;
            } else {
                $errors[] = 'Le rôle n a pas pu être enregistré.';
            }
        } elseif ($action === 'remove_role') {
            if (StockChimiqueRepository::setUserRole(isset($_POST['role_login']) ? $_POST['role_login'] : '', '', $login)) {
                $message = 'Rôle retiré.';
            } else {
                $errors[] = 'Le rôle n a pas pu être retiré.';
            }
        } elseif ($action === 'generate_token') {
            $openTokenModal = true;
            try {
                $token = bin2hex(random_bytes(32));
            } catch (Throwable $exception) {
                $token = hash('sha256', uniqid('', true).mt_rand());
            }
            if (StockChimiqueConfig::setNotificationToken($token)) {
                $message = 'Token de notification renouvelé.';
                $openTokenModal = false;
            } else {
                $errors[] = 'Le token n a pas pu être enregistré.';
            }
        } elseif ($action === 'validate_fds_alert') {
            if (StockChimiqueRepository::validateFdsAlert(
                isset($_POST['document_id']) ? (int) $_POST['document_id'] : 0,
                $login
            )) {
                $message = 'La FDS a été validée. L alerte réapparaîtra après le délai configuré.';
            } else {
                $errors[] = 'Validation impossible : la FDS n est peut-être plus la version courante.';
            }
        } elseif ($action === 'validate_all_fds_alerts') {
            $documentIds = array();
            foreach (StockChimiqueRepository::alerts(2000) as $alert) {
                if ((string) $alert['type'] === 'fds_a_verifier' && (int) $alert['document_id'] > 0) {
                    $documentIds[] = (int) $alert['document_id'];
                }
            }
            $validationResult = StockChimiqueRepository::validateFdsAlerts($documentIds, $login);
            if (!empty($validationResult['ok'])) {
                $message = (int) $validationResult['count'].' FDS ont été validées.';
            } else {
                $errors[] = isset($validationResult['error'])
                    ? (string) $validationResult['error']
                    : 'Validation groupée des FDS impossible.';
            }
        } elseif ($action === 'preview_import') {
            $openImportModal = true;
            $importPreview = StockChimiqueImport::preview($selectedImportPackage);
            if (empty($importPreview['ok'])) {
                $errors[] = 'Le paquet d import contient des erreurs. Consultez le détail ci-dessous.';
            } else {
                $openImportModal = false;
            }
        } elseif ($action === 'execute_import') {
            $openImportExecuteModal = true;
            if (empty($_POST['confirm_import'])) {
                $errors[] = 'Confirmez explicitement l import après avoir contrôlé la prévisualisation.';
                $importPreview = StockChimiqueImport::preview($selectedImportPackage);
            } else {
                $importResult = StockChimiqueImport::execute(
                    $selectedImportPackage,
                    isset($_POST['import_hash']) ? (string) $_POST['import_hash'] : '',
                    $login
                );
                $importPreview = StockChimiqueImport::preview($selectedImportPackage);
                if (!empty($importResult['ok'])) {
                    $message = !empty($importResult['completed'])
                        ? 'Import terminé.'
                        : 'Lot importé. Relancez le prochain lot depuis la prévisualisation.';
                    $openImportExecuteModal = false;
                } else {
                    $errors[] = 'L import s est terminé avec une ou plusieurs erreurs.';
                }
            }
        }
    }
}

$moduleVersion = '';
$moduleVersionBDD = '';
include __DIR__.'/infos.php';
$actionUrl = $stockChimiqueAdminEmbedded
    ? 'compte.php?pc=stock_chimique&admin=1'
    : 'admin.php';

if (!$stockChimiqueAdminEmbedded) {
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Administration Stock chimique</title>';
    echo '<link rel="stylesheet" href="../../../themes/default/css/style.css"></head><body>';
}

echo '<div class="container-fluid stock-chimique-admin">';
echo '<h1>Administration — '.stock_chimique_admin_html(StockChimiqueConfig::displayName()).'</h1>';
echo $stockChimiqueAdminEmbedded
    ? '<p><a href="compte.php?pc=stock_chimique">Retour au module</a> | <a href="../personnalisation/modules/stock_chimique/admin.php">Page autonome</a></p>'
    : '<p><a href="../../../compte/compte.php?pc=stock_chimique">Ouvrir le module</a></p>';
if ($message !== '') {
    echo '<div class="alert alert-success">'.stock_chimique_admin_html($message).'</div>';
}
foreach ($errors as $error) {
    echo '<div class="alert alert-danger">'.stock_chimique_admin_html($error).'</div>';
}

echo '<style>'
    .'.stock-chimique-admin{width:100%;max-width:none;margin:0;box-sizing:border-box;}'
    .'.stock-chimique-admin *{box-sizing:border-box;}'
    .'.stock-chimique-admin h1{margin-top:0;}'
    .'.stock-chimique-admin .sc-admin-actions{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0 18px;}'
    .'.stock-chimique-admin .sc-admin-actions .btn{white-space:normal;}'
    .'.stock-chimique-admin .sc-admin-modal{display:none;position:fixed;z-index:5000;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,.45);padding:24px 10px;}'
    .'.stock-chimique-admin .sc-admin-modal.is-open{display:block;}'
    .'.stock-chimique-admin .sc-admin-modal-dialog{background:#fff;border-radius:4px;box-shadow:0 12px 40px rgba(0,0,0,.28);margin:0 auto;width:100%;max-width:1120px;}'
    .'.stock-chimique-admin .sc-admin-modal-head{align-items:center;border-bottom:1px solid #ddd;display:flex;gap:12px;justify-content:space-between;padding:12px 16px;}'
    .'.stock-chimique-admin .sc-admin-modal-head strong{overflow-wrap:anywhere;}'
    .'.stock-chimique-admin .sc-admin-modal-close{background:transparent;border:0;font-size:24px;line-height:1;padding:0 4px;}'
    .'.stock-chimique-admin .sc-admin-modal-body{padding:16px;max-height:calc(100vh - 140px);overflow:auto;}'
    .'.stock-chimique-admin .table-responsive{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;}'
    .'.stock-chimique-admin table{width:100%;}'
    .'.stock-chimique-admin th,.stock-chimique-admin td{overflow-wrap:anywhere;}'
    .'@media (max-width:767px){'
        .'.stock-chimique-admin{padding-left:0;padding-right:0;}'
        .'.stock-chimique-admin .sc-admin-actions{align-items:stretch;}'
        .'.stock-chimique-admin .sc-admin-actions .btn,.stock-chimique-admin .sc-admin-actions a{width:100%;text-align:center;}'
        .'.stock-chimique-admin .sc-admin-modal{padding:10px;}'
        .'.stock-chimique-admin .sc-admin-modal-dialog{max-width:none;}'
        .'.stock-chimique-admin .sc-admin-modal-body{padding:12px;max-height:none;}'
        .'.stock-chimique-admin table[data-responsive-table="1"],.stock-chimique-admin table[data-responsive-table="1"] thead,.stock-chimique-admin table[data-responsive-table="1"] tbody,.stock-chimique-admin table[data-responsive-table="1"] tr,.stock-chimique-admin table[data-responsive-table="1"] th,.stock-chimique-admin table[data-responsive-table="1"] td{display:block;width:100%;}'
        .'.stock-chimique-admin table[data-responsive-table="1"] thead{display:none;}'
        .'.stock-chimique-admin table[data-responsive-table="1"] tr{margin:0 0 12px;border:1px solid #ddd;background:#fff;border-radius:4px;overflow:hidden;}'
        .'.stock-chimique-admin table[data-responsive-table="1"] td{display:flex;gap:10px;justify-content:space-between;align-items:flex-start;border:0;border-bottom:1px solid #eee;text-align:right;}'
        .'.stock-chimique-admin table[data-responsive-table="1"] td:last-child{border-bottom:0;}'
        .'.stock-chimique-admin table[data-responsive-table="1"] td:before{content:attr(data-label);font-weight:600;color:#555;text-align:left;flex:0 0 42%;}'
    .'}'
    .'</style>';
echo '<script>(function(){if(window.scAdminModalReady){return;}window.scAdminModalReady=true;function prepareTables(){document.querySelectorAll(".stock-chimique-admin table").forEach(function(table){if(table.getAttribute("data-responsive-table")==="1"){return;}var heads=table.tHead&&table.tHead.rows.length?table.tHead.rows[0].cells:[];if(!heads.length){return;}table.setAttribute("data-responsive-table","1");Array.prototype.forEach.call(table.tBodies,function(body){Array.prototype.forEach.call(body.rows,function(row){Array.prototype.forEach.call(row.cells,function(cell,index){var head=heads[index];if(head){cell.setAttribute("data-label",head.textContent.trim());}});});});});}if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",prepareTables);}else{prepareTables();}setTimeout(prepareTables,0);document.addEventListener("click",function(event){var open=event.target.closest?event.target.closest("[data-sc-admin-modal-open]"):null;if(open){event.preventDefault();var modal=document.getElementById(open.getAttribute("data-sc-admin-modal-open"));if(modal){modal.classList.add("is-open");document.body.classList.add("modal-open");}return;}var close=event.target.closest?event.target.closest("[data-sc-admin-modal-close]"):null;if(close){event.preventDefault();var box=close.closest(".sc-admin-modal");if(box){box.classList.remove("is-open");}if(!document.querySelector(".sc-admin-modal.is-open")){document.body.classList.remove("modal-open");}return;}if(event.target.classList&&event.target.classList.contains("sc-admin-modal")){event.target.classList.remove("is-open");if(!document.querySelector(".sc-admin-modal.is-open")){document.body.classList.remove("modal-open");}}});document.addEventListener("keydown",function(event){if(event.key==="Escape"){document.querySelectorAll(".sc-admin-modal.is-open").forEach(function(modal){modal.classList.remove("is-open");});document.body.classList.remove("modal-open");}});})();</script>';
echo '<div class="sc-admin-actions">'
    .stock_chimique_admin_modal_button('sc-admin-config', 'Configuration')
    .stock_chimique_admin_modal_button('sc-admin-role', 'Attribuer un rôle')
    .stock_chimique_admin_modal_button('sc-admin-import', 'Prévisualiser un import', 'btn btn-default')
    .stock_chimique_admin_modal_button('sc-admin-token', 'Notifications planifiées', 'btn btn-default')
    .'</div>';

ob_start();
echo '<form method="post" action="'.stock_chimique_admin_html($actionUrl).'">';
echo StockChimiqueSecurity::field().'<input type="hidden" name="sc_admin_action" value="save_config">';
echo '<h2>Configuration</h2>';
echo '<div class="form-group"><label><input type="checkbox" name="enabled" value="1"'.(StockChimiqueConfig::isEnabled() ? ' checked' : '').'> Activer le module</label></div>';
echo '<div class="form-group"><label>Nom affiché</label><input class="form-control" name="display_name" maxlength="80" value="'.stock_chimique_admin_html(StockChimiqueConfig::displayName()).'"></div>';

echo '<h3>Alertes et notifications</h3>';
echo '<div class="form-group"><label><input type="checkbox" name="alerts_enabled" value="1"'.(StockChimiqueConfig::alertsEnabled() ? ' checked' : '').'> Activer les alertes dans le module</label><p class="help-block">Si cette option est désactivée, aucun compteur ni tableau d alerte n est calculé.</p></div>';
echo '<div class="row">';
echo '<div class="col-md-4 form-group"><label><input type="checkbox" name="alert_stock_enabled" value="1"'.(StockChimiqueConfig::get('alert_stock_enabled', '1') === '1' ? ' checked' : '').'> Stock faible</label><p class="help-block">Alerte lorsque le stock passe sous le seuil du produit.</p></div>';
echo '<div class="col-md-4 form-group"><label><input type="checkbox" name="alert_expiry_enabled" value="1"'.(StockChimiqueConfig::get('alert_expiry_enabled', '1') === '1' ? ' checked' : '').'> Péremption</label><p class="help-block">Contenants périmés ou proches de la péremption.</p></div>';
echo '<div class="col-md-4 form-group"><label><input type="checkbox" name="alert_fds_enabled" value="1"'.(StockChimiqueConfig::get('alert_fds_enabled', '1') === '1' ? ' checked' : '').'> FDS</label><p class="help-block">FDS absentes ou anciennes.</p></div>';
echo '</div>';
echo '<div class="row"><div class="col-md-6 form-group"><label>Péremption proche (jours)</label><input class="form-control" type="number" name="expiry_days" min="1" max="730" value="'.StockChimiqueConfig::expiryDays().'"></div>';
echo '<div class="col-md-6 form-group"><label>FDS à vérifier après (mois)</label><input class="form-control" type="number" name="fds_months" min="1" max="120" value="'.StockChimiqueConfig::fdsMonths().'"></div></div>';
echo '<div class="form-group"><label><input type="checkbox" name="notifications_enabled" value="1"'.(StockChimiqueConfig::notificationsEnabled() ? ' checked' : '').'> Activer les notifications électroniques</label><p class="help-block">Contrôle les envois manuels et la tâche planifiée Synology. Les alertes peuvent rester visibles lorsque les notifications sont désactivées.</p></div>';

echo '<h3>Documents</h3>';
echo '<div class="form-group"><label>Taille maximale (Mo)</label><input class="form-control" type="number" name="document_max_mb" min="1" max="50" value="'.StockChimiqueConfig::documentMaxMb().'"></div>';
echo '<div class="form-group"><label><input type="checkbox" name="documents_enabled" value="1"'.(StockChimiqueConfig::documentsEnabled() ? ' checked' : '').'> Autoriser le dépôt de documents</label></div>';
echo '<div class="form-group"><label>Extensions autorisées hors FDS</label><textarea class="form-control" name="document_extensions" rows="2">'.stock_chimique_admin_html(StockChimiqueConfig::documentExtensionsText()).'</textarea></div>';
echo '<button class="btn btn-primary" type="submit">Enregistrer</button></form>';
$configForm = ob_get_clean();
echo stock_chimique_admin_modal('sc-admin-config', 'Configuration', $configForm, $openConfigModal);

echo '<hr><h2>Rôles utilisateurs</h2>';
ob_start();
echo '<form class="form-inline" method="post" action="'.stock_chimique_admin_html($actionUrl).'">';
echo StockChimiqueSecurity::field().'<input type="hidden" name="sc_admin_action" value="save_role">';
echo '<div class="form-group"><label>Compte&nbsp;</label><select class="form-control" name="role_login" required><option value="">Choisir</option>';
foreach (StockChimiqueRepository::activeUsers() as $user) {
    $label = trim($user['nom'].' '.$user['prenom']).' ('.$user['login'].')';
    echo '<option value="'.stock_chimique_admin_html($user['login']).'">'.stock_chimique_admin_html($label).'</option>';
}
echo '</select></div> <div class="form-group"><label>Rôle&nbsp;</label><select class="form-control" name="role">';
foreach (StockChimiqueRepository::roles() as $key => $label) {
    echo '<option value="'.stock_chimique_admin_html($key).'">'.stock_chimique_admin_html($label).'</option>';
}
echo '</select></div> <button class="btn btn-primary" type="submit">Attribuer</button></form>';
$roleForm = ob_get_clean();
echo stock_chimique_admin_modal('sc-admin-role', 'Attribuer un role', $roleForm, $openRoleModal);

echo '<div class="table-responsive"><table class="table table-bordered table-striped" style="margin-top:15px"><thead><tr><th>Compte</th><th>Nom</th><th>Rôle</th><th></th></tr></thead><tbody>';
$stockChimiqueRoleLabels = StockChimiqueRepository::roles();
foreach (StockChimiqueRepository::assignedRoles() as $role) {
    $roleLabel = isset($stockChimiqueRoleLabels[$role['role']]) ? $stockChimiqueRoleLabels[$role['role']] : $role['role'];
    echo '<tr><td>'.stock_chimique_admin_html($role['login']).'</td><td>'.stock_chimique_admin_html(trim($role['nom'].' '.$role['prenom'])).'</td><td>'.stock_chimique_admin_html($roleLabel).'</td><td>';
    echo '<form method="post" action="'.stock_chimique_admin_html($actionUrl).'">'.StockChimiqueSecurity::field().'<input type="hidden" name="sc_admin_action" value="remove_role"><input type="hidden" name="role_login" value="'.stock_chimique_admin_html($role['login']).'"><button class="btn btn-danger btn-xs" type="submit">Retirer</button></form>';
    echo '</td></tr>';
}
echo '</tbody></table></div>';

$fdsAlerts = array();
foreach (StockChimiqueRepository::alerts(1000) as $adminAlert) {
    if ((string) $adminAlert['type'] === 'fds_a_verifier' && (int) $adminAlert['document_id'] > 0) {
        $fdsAlerts[] = $adminAlert;
    }
}
echo '<hr><h2>Validation des alertes FDS</h2>';
echo '<p>Cette validation confirme un contrôle interne de la FDS sans modifier sa date de révision. L alerte reviendra après le délai configuré.</p>';
if (count($fdsAlerts) === 0) {
    echo '<div class="alert alert-success">Aucune FDS en attente de validation.</div>';
} else {
    echo '<form method="post" action="'.stock_chimique_admin_html($actionUrl).'" style="margin-bottom:15px" onsubmit="return confirm(\'Valider les '.count($fdsAlerts).' FDS actuellement signalées ?\')">';
    echo StockChimiqueSecurity::field().'<input type="hidden" name="sc_admin_action" value="validate_all_fds_alerts">';
    echo '<button class="btn btn-success" type="submit">Valider toutes les FDS ('.count($fdsAlerts).')</button></form>';
    echo '<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Produit</th><th>Détail</th><th>Action</th></tr></thead><tbody>';
    foreach ($fdsAlerts as $fdsAlert) {
        echo '<tr><td>'.stock_chimique_admin_html($fdsAlert['label']).'</td><td>'.stock_chimique_admin_html($fdsAlert['detail']).'</td><td>';
        echo '<form method="post" action="'.stock_chimique_admin_html($actionUrl).'" onsubmit="return confirm(\'Confirmer que cette FDS a été contrôlée ?\')">';
        echo StockChimiqueSecurity::field().'<input type="hidden" name="sc_admin_action" value="validate_fds_alert">';
        echo '<input type="hidden" name="document_id" value="'.(int) $fdsAlert['document_id'].'">';
        echo '<button class="btn btn-success btn-xs" type="submit">Valider la FDS</button></form></td></tr>';
    }
    echo '</tbody></table></div>';
}

$importStorageOk = StockChimiqueImport::ensureImportStorage();
$importPackages = StockChimiqueImport::availablePackages();
$importHistory = StockChimiqueRepository::importHistory(100);
echo '<hr><h2>Import initial du stock et des FDS</h2>';
echo '<p>Déposez sur le NAS un dossier contenant <code>'.stock_chimique_admin_html(StockChimiqueImport::CSV_FILE).'</code> et le sous-dossier <code>FDS</code> dans le répertoire d import ci-dessous. Une prévisualisation complète est obligatoire avant exécution.</p>';
echo '<div class="form-group"><label>Répertoire d import</label><input class="form-control" readonly value="'.stock_chimique_admin_html(StockChimiqueImport::importStorageDir()).'"></div>';
if (!$importStorageOk) {
    echo '<div class="alert alert-danger">Le répertoire d import est inaccessible.</div>';
    echo stock_chimique_admin_modal('sc-admin-import', 'Previsualiser un import', '<div class="alert alert-danger">Le repertoire d import est inaccessible.</div>', $openImportModal);
} elseif (count($importPackages) === 0) {
    echo '<div class="alert alert-warning">Aucun paquet détecté. Exemple attendu : <code>storage/import/Stock_et_FDS_2026/import_stock_chimique.csv</code>.</div>';
    echo stock_chimique_admin_modal('sc-admin-import', 'Previsualiser un import', '<div class="alert alert-warning">Aucun paquet detecte. Deposez un dossier dans <code>storage/import</code>.</div>', $openImportModal);
} else {
    ob_start();
    echo '<form class="form-inline" method="post" action="'.stock_chimique_admin_html($actionUrl).'">';
    echo StockChimiqueSecurity::field().'<input type="hidden" name="sc_admin_action" value="preview_import">';
    echo '<div class="form-group"><label>Paquet&nbsp;</label><select class="form-control" name="import_package" required>';
    foreach ($importPackages as $package) {
        $selected = $selectedImportPackage === $package ? ' selected' : '';
        echo '<option value="'.stock_chimique_admin_html($package).'"'.$selected.'>'.stock_chimique_admin_html($package).'</option>';
    }
    echo '</select></div> <button class="btn btn-primary" type="submit">Prévisualiser</button></form>';
    $importForm = ob_get_clean();
    echo stock_chimique_admin_modal('sc-admin-import', 'Previsualiser un import', $importForm, $openImportModal);
}

if (is_array($importPreview)) {
    echo '<h3>Prévisualisation du paquet '.stock_chimique_admin_html(isset($importPreview['package']) ? $importPreview['package'] : $selectedImportPackage).'</h3>';
    if (isset($importPreview['hash'])) {
        echo '<p><small>Empreinte CSV : <code>'.stock_chimique_admin_html($importPreview['hash']).'</code></small></p>';
    }
    if (isset($importPreview['total_rows'])) {
        echo '<div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Lignes</th><th>Produits</th><th>Fournisseurs</th><th>Emplacements</th><th>Contenants</th><th>FDS</th></tr></thead><tbody><tr>';
        echo '<td>'.(int) $importPreview['import_rows'].' importées / '.(int) $importPreview['total_rows'].'</td>';
        echo '<td>'.(int) $importPreview['products'].'</td><td>'.(int) $importPreview['suppliers'].'</td><td>'.(int) $importPreview['locations'].'</td>';
        echo '<td>'.(int) $importPreview['containers'].'</td><td>'.(int) $importPreview['documents'].'</td></tr></tbody></table></div>';
    }
    if (!empty($importPreview['errors'])) {
        echo '<div class="alert alert-danger"><strong>Erreurs bloquantes</strong><ul>';
        foreach ($importPreview['errors'] as $importError) {
            echo '<li>'.stock_chimique_admin_html($importError).'</li>';
        }
        echo '</ul></div>';
    }
    if (!empty($importPreview['warnings'])) {
        echo '<div class="alert alert-warning"><strong>Avertissements à contrôler</strong><ul>';
        foreach ($importPreview['warnings'] as $importWarning) {
            echo '<li>'.stock_chimique_admin_html($importWarning).'</li>';
        }
        echo '</ul></div>';
    }
    if (!empty($importPreview['ok'])) {
        ob_start();
        echo '<form method="post" action="'.stock_chimique_admin_html($actionUrl).'">';
        echo StockChimiqueSecurity::field().'<input type="hidden" name="sc_admin_action" value="execute_import">';
        echo '<input type="hidden" name="import_package" value="'.stock_chimique_admin_html($importPreview['package']).'">';
        echo '<input type="hidden" name="import_hash" value="'.stock_chimique_admin_html($importPreview['hash']).'">';
        echo '<div class="checkbox"><label><input type="checkbox" name="confirm_import" value="1" required> J ai vérifié le classeur de contrôle et je confirme cet import.</label></div>';
        echo '<button class="btn btn-danger" type="submit">Importer le prochain lot ('.(int) StockChimiqueImport::BATCH_SIZE.' lignes maximum)</button></form>';
        $executeImportForm = ob_get_clean();
        echo '<div class="sc-admin-actions">'.stock_chimique_admin_modal_button('sc-admin-import-execute', 'Importer le prochain lot', 'btn btn-danger').'</div>';
        echo stock_chimique_admin_modal('sc-admin-import-execute', 'Importer le prochain lot', $executeImportForm, $openImportExecuteModal);
    }
}

if (is_array($importResult)) {
    echo '<h3>Résultat du dernier lancement</h3>';
    echo '<div class="table-responsive"><table class="table table-bordered"><tbody>';
    echo '<tr><th>Lignes traitées</th><td>'.(int) $importResult['processed'].'</td></tr>';
    echo '<tr><th>Lignes tentées dans ce lot</th><td>'.(int) $importResult['attempted'].'</td></tr>';
    echo '<tr><th>Déjà importées</th><td>'.(int) $importResult['already_imported'].'</td></tr>';
    echo '<tr><th>Lignes restantes</th><td>'.(int) $importResult['remaining'].'</td></tr>';
    echo '<tr><th>Produits créés</th><td>'.(int) $importResult['products_created'].'</td></tr>';
    echo '<tr><th>Contenants créés</th><td>'.(int) $importResult['containers_created'].'</td></tr>';
    echo '<tr><th>FDS créées</th><td>'.(int) $importResult['documents_created'].'</td></tr>';
    echo '</tbody></table></div>';
    if (!empty($importResult['errors'])) {
        echo '<div class="alert alert-danger"><ul>';
        foreach ($importResult['errors'] as $importError) {
            echo '<li>'.stock_chimique_admin_html($importError).'</li>';
        }
        echo '</ul></div>';
    }
}

if (count($importHistory) > 0) {
    echo '<h3>Historique récent</h3><div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Paquet</th><th>Ligne source</th><th>Statut</th><th>Produit</th><th>Contenant</th><th>Document</th><th>Message</th></tr></thead><tbody>';
    foreach ($importHistory as $entry) {
        echo '<tr><td>'.stock_chimique_admin_html(date('d/m/Y H:i:s', (int) $entry['created_at'])).'</td>';
        echo '<td>'.stock_chimique_admin_html($entry['package_name']).'</td><td>'.(int) $entry['source_row'].'</td>';
        echo '<td>'.stock_chimique_admin_html($entry['status']).'</td><td>'.(int) $entry['product_id'].'</td>';
        echo '<td>'.(int) $entry['container_id'].'</td><td>'.(int) $entry['document_id'].'</td>';
        echo '<td>'.stock_chimique_admin_html($entry['message']).'</td></tr>';
    }
    echo '</tbody></table></div>';
}

$token = StockChimiqueConfig::notificationToken();
$baseUrl = function_exists('traite_grr_url') ? traite_grr_url('', 'y') : '';
ob_start();
if (!StockChimiqueConfig::notificationsEnabled()) {
    echo '<div class="alert alert-warning">Les notifications sont désactivées. L URL planifiée refusera les envois tant qu elles ne seront pas réactivées.</div>';
}
if ($token !== '') {
    echo '<div class="form-group"><label>URL Synology</label><input class="form-control" readonly value="'.stock_chimique_admin_html($baseUrl.'personnalisation/modules/stock_chimique/cron_notifications.php?token='.$token).'"></div>';
} else {
    echo '<div class="alert alert-warning">Aucun token configuré.</div>';
}
echo '<form method="post" action="'.stock_chimique_admin_html($actionUrl).'">'.StockChimiqueSecurity::field().'<input type="hidden" name="sc_admin_action" value="generate_token"><button class="btn btn-default" type="submit">'.($token === '' ? 'Générer' : 'Renouveler').' le token</button></form>';
$tokenForm = ob_get_clean();
echo stock_chimique_admin_modal('sc-admin-token', 'Notifications planifiees', $tokenForm, $openTokenModal);

echo '<hr><h2>Diagnostic</h2><table class="table table-bordered table-striped"><tbody>';
echo '<tr><th>Version module</th><td>'.stock_chimique_admin_html($module_version).'</td></tr>';
echo '<tr><th>Version BDD</th><td>'.stock_chimique_admin_html($module_versionBDD).'</td></tr>';
echo '<tr><th>Répertoire documentaire</th><td>'.stock_chimique_admin_status(StockChimiqueRepository::ensureDocumentStorage()).' '.stock_chimique_admin_html(StockChimiqueRepository::documentStorageDir()).'</td></tr>';
foreach (StockChimiqueRepository::diagnostics() as $diagnostic) {
    $engineOk = $diagnostic['engine'] === '' || strcasecmp($diagnostic['engine'], 'InnoDB') === 0;
    echo '<tr><th>'.stock_chimique_admin_html($diagnostic['label']).'</th><td>'.stock_chimique_admin_status($diagnostic['exists'] && $engineOk).' '.stock_chimique_admin_html($diagnostic['table']).' '.stock_chimique_admin_html($diagnostic['engine']).'</td></tr>';
}
echo '</tbody></table></div>';

if (!$stockChimiqueAdminEmbedded) {
    echo '</body></html>';
}
