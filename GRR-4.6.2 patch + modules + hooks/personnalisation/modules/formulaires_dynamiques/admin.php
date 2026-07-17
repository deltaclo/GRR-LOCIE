<?php

$formulairesDynamiquesAdminEmbedded = isset($formulaires_dynamiques_admin_embedded) && $formulaires_dynamiques_admin_embedded;

require_once __DIR__.'/lib/bootstrap.php';

$sessionOk = $formulairesDynamiquesAdminEmbedded ? true : grr_formdyn_bootstrap(true);

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Rights.php';

if (!$sessionOk) {
    header('Location: ../../../index.php');
    exit;
}

$login = function_exists('getUserName') ? (string) getUserName() : '';
if (!FormulairesDynamiquesRights::isAdmin($login)) {
    if ($formulairesDynamiquesAdminEmbedded) {
        echo '<div class="alert alert-warning">Acces refuse.</div>';
        return;
    }

    http_response_code(403);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Acces refuse</title></head><body>';
    echo '<h1>Acces refuse</h1><p>Cette page est reservee aux administrateurs generaux GRR.</p>';
    echo '</body></html>';
    exit;
}

$message = '';
$errors = array();

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $displayName = isset($_POST['display_name']) ? trim((string) $_POST['display_name']) : '';
    $managerLoginsRaw = isset($_POST['manager_logins']) ? $_POST['manager_logins'] : array();
    $managerLogins = formulaires_dynamiques_admin_logins_from_text($managerLoginsRaw);

    if ($displayName === '') {
        $errors[] = 'Le nom affiche est obligatoire.';
    } elseif (strlen($displayName) > 80) {
        $errors[] = 'Le nom affiche ne doit pas depasser 80 caracteres.';
    }

    if (count($errors) === 0) {
        $saved = true;
        $saved = FormulairesDynamiquesConfig::set('display_name', $displayName) && $saved;
        $saved = FormulairesDynamiquesConfig::set('enabled', isset($_POST['enabled']) ? '1' : '0') && $saved;
        $saved = FormulairesDynamiquesConfig::set('account_enabled', isset($_POST['account_enabled']) ? '1' : '0') && $saved;
        $saved = FormulairesDynamiquesConfig::set('autonomous_enabled', isset($_POST['autonomous_enabled']) ? '1' : '0') && $saved;
        $saved = FormulairesDynamiquesConfig::set('notifications_enabled', isset($_POST['notifications_enabled']) ? '1' : '0') && $saved;
        $saved = FormulairesDynamiquesConfig::setManagerLogins($managerLogins) && $saved;

        if ($saved) {
            $message = 'Configuration enregistree.';
        } else {
            $errors[] = 'La configuration n a pas pu etre enregistree completement.';
        }
    }
}

function formulaires_dynamiques_admin_html($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formulaires_dynamiques_admin_checked($enabled)
{
    return $enabled ? ' checked' : '';
}

function formulaires_dynamiques_admin_status($ok)
{
    return $ok
        ? '<span class="label label-success">OK</span>'
        : '<span class="label label-danger">Erreur</span>';
}

function formulaires_dynamiques_admin_logins_from_text($text)
{
    if (is_array($text)) {
        $tokens = $text;
    } else {
        $tokens = preg_split('/[\r\n,;]+/', (string) $text);
    }
    $logins = array();

    foreach ($tokens as $token) {
        $login = trim((string) $token);
        if ($login !== '' && strlen($login) <= 190 && FormulairesDynamiquesRepository::userByLogin($login)) {
            $logins[$login] = $login;
        }
    }

    return array_values($logins);
}

function formulaires_dynamiques_admin_manager_text()
{
    return implode("\n", FormulairesDynamiquesConfig::managerLogins());
}

function formulaires_dynamiques_admin_manager_option($user)
{
    $login = isset($user['login']) ? (string) $user['login'] : '';
    $label = isset($user['label']) ? (string) $user['label'] : $login;

    return '<option value="'.formulaires_dynamiques_admin_html($login).'">'.formulaires_dynamiques_admin_html($label).'</option>';
}

function formulaires_dynamiques_admin_manager_options($selectedOnly)
{
    $selectedOnly = (bool) $selectedOnly;
    $selected = array();
    foreach (FormulairesDynamiquesConfig::managerLogins() as $login) {
        $selected[$login] = true;
    }

    $html = '';
    foreach (FormulairesDynamiquesRepository::activeUsers() as $user) {
        $login = isset($user['login']) ? (string) $user['login'] : '';
        $isSelected = isset($selected[$login]);
        if (($selectedOnly && $isSelected) || (!$selectedOnly && !$isSelected)) {
            $html .= formulaires_dynamiques_admin_manager_option($user);
        }
    }

    return $html;
}

function formulaires_dynamiques_admin_dual_select_assets()
{
    return '<style>'
        .'.formdyn-manager-picker{display:grid;grid-template-columns:minmax(240px,1fr) auto minmax(240px,1fr);gap:12px;align-items:center;max-width:980px;margin:8px 0 14px;}'
        .'.formdyn-manager-picker label{margin:0;}'
        .'.formdyn-manager-picker select{width:100%;max-width:none;min-height:240px;}'
        .'.formdyn-manager-buttons{display:flex;flex-direction:column;gap:8px;align-items:stretch;}'
        .'.formdyn-manager-buttons .btn{min-width:130px;text-align:center;}'
        .'@media (max-width:767px){.formdyn-manager-picker{grid-template-columns:minmax(0,1fr);}.formdyn-manager-buttons{flex-direction:row;flex-wrap:wrap;}.formdyn-manager-buttons .btn{min-width:0;flex:1 1 120px;}}'
        .'</style>'
        .'<script>'
        .'(function(){'
        .'function sortOptions(select){var options=Array.prototype.slice.call(select.options);options.sort(function(a,b){return a.text.localeCompare(b.text);});for(var i=0;i<options.length;i++){select.add(options[i]);}}'
        .'function move(source,target,all){var moved=[];for(var i=source.options.length-1;i>=0;i--){var option=source.options[i];if(all||option.selected){option.selected=false;moved.unshift(option);}}for(var j=0;j<moved.length;j++){target.add(moved[j]);}sortOptions(target);}'
        .'document.addEventListener("click",function(event){var button=event.target;if(!button.getAttribute||!button.getAttribute("data-manager-action")){return;}var picker=button.closest(".formdyn-manager-picker");if(!picker){return;}var available=picker.querySelector("[data-manager-list=available]");var selected=picker.querySelector("[data-manager-list=selected]");if(!available||!selected){return;}var action=button.getAttribute("data-manager-action");if(action==="add"){move(available,selected,false);}if(action==="add-all"){move(available,selected,true);}if(action==="remove"){move(selected,available,false);}if(action==="remove-all"){move(selected,available,true);}});'
        .'document.addEventListener("dblclick",function(event){var select=event.target&&event.target.closest?event.target.closest("select[data-manager-list]"):null;if(!select){return;}var picker=select.closest(".formdyn-manager-picker");if(!picker){return;}var available=picker.querySelector("[data-manager-list=available]");var selected=picker.querySelector("[data-manager-list=selected]");if(select===available){move(available,selected,false);}else{move(selected,available,false);}});'
        .'document.addEventListener("submit",function(event){var selected=event.target.querySelector("[data-manager-list=selected]");if(!selected){return;}for(var i=0;i<selected.options.length;i++){selected.options[i].selected=true;}});'
        .'})();'
        .'</script>';
}

function formulaires_dynamiques_admin_current_url()
{
    if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] !== '') {
        return $_SERVER['REQUEST_URI'];
    }

    return 'admin.php';
}

$diagnostics = FormulairesDynamiquesRepository::diagnostics();

if (!$formulairesDynamiquesAdminEmbedded) {
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Administration - Formulaires dynamiques</title>';
    echo '<style>body{font-family:Arial,sans-serif;margin:24px;}label{display:block;margin:8px 0;}table{border-collapse:collapse;margin:12px 0;width:100%;max-width:900px;}th,td{border:1px solid #ccc;padding:6px;text-align:left;}.label{display:inline-block;padding:3px 6px;color:#fff}.label-success{background:#5cb85c}.label-danger{background:#d9534f}.alert{padding:10px;margin:10px 0;max-width:900px}.alert-success{background:#dff0d8}.alert-danger{background:#f2dede}.form-control{width:100%;max-width:520px;box-sizing:border-box;padding:6px}.btn{display:inline-block;padding:6px 10px;border:1px solid #888;background:#eee;text-decoration:none;color:#222}.btn-primary{background:#337ab7;color:#fff;border-color:#2e6da4}</style>';
    echo '</head><body>';
}

echo '<h1>Administration - '.formulaires_dynamiques_admin_html(FormulairesDynamiquesConfig::displayName()).'</h1>';

if ($message !== '') {
    echo '<div class="alert alert-success">'.formulaires_dynamiques_admin_html($message).'</div>';
}
if (count($errors) > 0) {
    echo '<div class="alert alert-danger"><ul>';
    foreach ($errors as $error) {
        echo '<li>'.formulaires_dynamiques_admin_html($error).'</li>';
    }
    echo '</ul></div>';
}

echo formulaires_dynamiques_admin_dual_select_assets();

echo '<form method="post" action="'.formulaires_dynamiques_admin_html(formulaires_dynamiques_admin_current_url()).'">';
echo '<h2>Configuration generale</h2>';
echo '<label>Nom affiche<br><input class="form-control" type="text" name="display_name" maxlength="80" value="'.formulaires_dynamiques_admin_html(FormulairesDynamiquesConfig::displayName()).'"></label>';
echo '<label><input type="checkbox" name="enabled" value="1"'.formulaires_dynamiques_admin_checked(FormulairesDynamiquesConfig::isEnabled()).'> Activer le module</label>';
echo '<label><input type="checkbox" name="account_enabled" value="1"'.formulaires_dynamiques_admin_checked(FormulairesDynamiquesConfig::accountEnabled()).'> Afficher le module dans Gerer mon compte</label>';
echo '<label><input type="checkbox" name="autonomous_enabled" value="1"'.formulaires_dynamiques_admin_checked(FormulairesDynamiquesConfig::autonomousEnabled()).'> Autoriser les pages autonomes du module</label>';
echo '<label><input type="checkbox" name="notifications_enabled" value="1"'.formulaires_dynamiques_admin_checked(FormulairesDynamiquesConfig::notificationsEnabled()).'> Activer les notifications e-mail du module</label>';

echo '<h2>Gestionnaires globaux</h2>';
echo '<p>Selectionner les utilisateurs GRR actifs. Les administrateurs generaux gardent toujours tous les droits.</p>';
echo '<div class="formdyn-manager-picker">';
echo '<label>Utilisateurs disponibles<br><select class="form-control" data-manager-list="available" multiple size="12">'.formulaires_dynamiques_admin_manager_options(false).'</select></label>';
echo '<div class="formdyn-manager-buttons">';
echo '<button class="btn" type="button" data-manager-action="add">Ajouter &gt;</button>';
echo '<button class="btn" type="button" data-manager-action="add-all">Tout ajouter &gt;&gt;</button>';
echo '<button class="btn" type="button" data-manager-action="remove">&lt; Retirer</button>';
echo '<button class="btn" type="button" data-manager-action="remove-all">&lt;&lt; Tout retirer</button>';
echo '</div>';
echo '<label>Gestionnaires globaux<br><select class="form-control" data-manager-list="selected" name="manager_logins[]" multiple size="12">'.formulaires_dynamiques_admin_manager_options(true).'</select></label>';
echo '</div>';
echo '<p><button class="btn btn-primary" type="submit">Enregistrer</button></p>';
echo '</form>';

echo '<h2>Diagnostic SQL</h2>';
echo '<table><thead><tr><th>Element</th><th>Table</th><th>Etat</th></tr></thead><tbody>';
foreach ($diagnostics as $diagnostic) {
    echo '<tr><td>'.formulaires_dynamiques_admin_html($diagnostic['label']).'</td><td><code>'.formulaires_dynamiques_admin_html($diagnostic['table']).'</code></td><td>'.formulaires_dynamiques_admin_status($diagnostic['exists']).'</td></tr>';
}
echo '</tbody></table>';

echo '<h2>Compteurs</h2>';
echo '<table><tbody>';
echo '<tr><th>Formulaires</th><td>'.formulaires_dynamiques_admin_html(FormulairesDynamiquesRepository::countForms()).'</td></tr>';
echo '<tr><th>Champs</th><td>'.formulaires_dynamiques_admin_html(FormulairesDynamiquesRepository::countFields()).'</td></tr>';
echo '<tr><th>Reponses</th><td>'.formulaires_dynamiques_admin_html(FormulairesDynamiquesRepository::countResponses()).'</td></tr>';
echo '</tbody></table>';

if (!$formulairesDynamiquesAdminEmbedded) {
    echo '<p><a class="btn" href="../../../compte/compte.php?pc='.formulaires_dynamiques_admin_html(FormulairesDynamiquesConfig::MODULE).'">Ouvrir le module utilisateur</a></p>';
    echo '</body></html>';
}
