<?php

$boutonsPersoAdminEmbedded = isset($boutons_perso_admin_embedded) && $boutons_perso_admin_embedded;
$GLOBALS['boutonsPersoAdminEmbedded'] = $boutonsPersoAdminEmbedded;

require_once __DIR__.'/lib/bootstrap.php';

$sessionOk = $boutonsPersoAdminEmbedded ? true : grr_boutons_perso_bootstrap(true);
if (!$sessionOk) {
    header('Location: ../../../index.php');
    exit;
}

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/ModuleRegistry.php';

function boutons_perso_admin_html($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function boutons_perso_admin_base_url()
{
    return isset($GLOBALS['boutonsPersoAdminEmbedded']) && $GLOBALS['boutonsPersoAdminEmbedded']
        ? 'compte.php?pc=boutons_perso'
        : 'admin.php';
}

function boutons_perso_admin_url($params = array(), $html = true)
{
    $url = boutons_perso_admin_base_url();
    foreach ($params as $name => $value) {
        $url .= (strpos($url, '?') === false ? '?' : '&').rawurlencode((string) $name).'='.rawurlencode((string) $value);
    }

    return $html ? boutons_perso_admin_html($url) : $url;
}

function boutons_perso_admin_values_from_button($button)
{
    $values = BoutonsPersoRepository::emptyButtonValues();
    foreach ($values as $key => $value) {
        if (isset($button[$key])) {
            $values[$key] = (string) $button[$key];
        }
    }

    return $values;
}

function boutons_perso_admin_setting_color($name, $default)
{
    return BoutonsPersoConfig::normalizeColor(BoutonsPersoConfig::get($name, $default), $default);
}

function boutons_perso_admin_target_label($mode)
{
    $modes = BoutonsPersoConfig::targetModes();
    return isset($modes[$mode]) ? $modes[$mode] : $modes['current'];
}

function boutons_perso_admin_style_label($style)
{
    $styles = BoutonsPersoConfig::buttonStyles();
    return isset($styles[$style]) ? $styles[$style] : $styles['default'];
}

function boutons_perso_admin_button_preview($values)
{
    $style = isset($values['button_style']) ? (string) $values['button_style'] : 'default';
    $classStyle = $style === 'custom' ? 'default' : $style;
    $styles = BoutonsPersoConfig::buttonStyles();
    if (!isset($styles[$classStyle])) {
        $classStyle = 'default';
    }

    $inlineStyle = '';
    if ($style === 'custom') {
        $bgColor = BoutonsPersoConfig::normalizeColor(isset($values['custom_bg_color']) ? $values['custom_bg_color'] : '', '');
        $textColor = BoutonsPersoConfig::normalizeColor(isset($values['custom_text_color']) ? $values['custom_text_color'] : '', '');
        if ($bgColor !== '' && $textColor !== '') {
            $inlineStyle = ' style="background-color:'.boutons_perso_admin_html($bgColor).';border-color:'.boutons_perso_admin_html($bgColor).';color:'.boutons_perso_admin_html($textColor).';"';
        }
    }

    $label = isset($values['label']) && trim((string) $values['label']) !== '' ? (string) $values['label'] : 'Apercu';
    return '<span class="btn btn-'.$classStyle.' btn-sm"'.$inlineStyle.'>'.boutons_perso_admin_html($label).'</span>';
}

function boutons_perso_admin_modal_button($id, $label, $class = 'btn btn-primary')
{
    return '<button class="'.boutons_perso_admin_html($class).'" type="button" data-bp-admin-modal-open="'.boutons_perso_admin_html($id).'">'.boutons_perso_admin_html($label).'</button>';
}

function boutons_perso_admin_modal($id, $title, $content, $open = false)
{
    return '<div class="bp-admin-modal'.($open ? ' is-open' : '').'" id="'.boutons_perso_admin_html($id).'" role="dialog" aria-modal="true">'
        .'<div class="bp-admin-modal-dialog">'
        .'<div class="bp-admin-modal-head"><strong>'.boutons_perso_admin_html($title).'</strong><button class="bp-admin-modal-close" type="button" data-bp-admin-modal-close>&times;</button></div>'
        .'<div class="bp-admin-modal-body">'.$content.'</div>'
        .'</div></div>';
}

function boutons_perso_admin_resolve_button($button, $definitions)
{
    if (!is_array($button)) {
        return array();
    }

    $button['provider_available'] = true;
    $button['external_active'] = true;
    $button['module_enabled'] = true;

    if (!isset($button['source_type']) || (string) $button['source_type'] !== BoutonsPersoRepository::SOURCE_MODULE) {
        return $button;
    }

    $sourceKey = isset($button['source_key']) ? (string) $button['source_key'] : '';
    $definition = isset($definitions[$sourceKey]) ? $definitions[$sourceKey] : array();
    if (!$definition) {
        $button['provider_available'] = false;
        $button['external_active'] = false;
        $button['module_enabled'] = false;
        return $button;
    }

    $button['provider_available'] = !empty($definition['provider_available']);
    $button['external_active'] = !empty($definition['external_active']);
    $button['module_enabled'] = !empty($definition['enabled']);
    if ($button['provider_available']) {
        if (isset($definition['label']) && trim((string) $definition['label']) !== '') {
            $button['label'] = (string) $definition['label'];
        }
        if (isset($definition['url']) && trim((string) $definition['url']) !== '') {
            $button['url'] = (string) $definition['url'];
        }
    }

    return $button;
}

function boutons_perso_admin_module_status($button)
{
    if (empty($button['provider_available'])) {
        return '<span class="label label-danger">Fournisseur indisponible</span>';
    }
    if (empty($button['external_active'])) {
        return '<span class="label label-default">Module externe inactif</span>';
    }
    if (empty($button['module_enabled'])) {
        return '<span class="label label-warning">Module desactive</span>';
    }

    return '<span class="label label-success">Disponible</span>';
}

$login = function_exists('getUserName') ? (string) getUserName() : '';
if ($login === '' || !class_exists('SecuAccess') || SecuAccess::UserLevel($login, -1) < 6) {
    if ($boutonsPersoAdminEmbedded) {
        echo '<div class="alert alert-warning">Acces refuse.</div>';
        return;
    }

    http_response_code(403);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Acces refuse</title></head><body>';
    echo '<h1>Acces refuse</h1><p>Cette page est reservee aux administrateurs generaux GRR.</p>';
    echo '</body></html>';
    exit;
}

BoutonsPersoRepository::ensureTables();

$messages = array();
$errors = array();
$moduleDefinitions = BoutonsPersoModuleRegistry::definitions($login);
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editModuleKey = isset($_GET['module']) ? trim((string) $_GET['module']) : '';
$formValues = BoutonsPersoRepository::emptyButtonValues();
$moduleFormValues = BoutonsPersoRepository::emptyButtonValues();
$moduleDefinition = array();
$openSettingsModal = false;
$openButtonModal = false;

if ($editModuleKey !== '') {
    $editId = 0;
    $moduleButton = BoutonsPersoRepository::moduleButton($editModuleKey, true);
    if ($moduleButton) {
        $moduleDefinition = isset($moduleDefinitions[$editModuleKey]) ? $moduleDefinitions[$editModuleKey] : array();
        $moduleButton = boutons_perso_admin_resolve_button($moduleButton, $moduleDefinitions);
        $moduleFormValues = boutons_perso_admin_values_from_button($moduleButton);
    } else {
        $editModuleKey = '';
        $errors[] = 'Bouton de module introuvable.';
    }
}

if ($editId > 0 && $editModuleKey === '') {
    $button = BoutonsPersoRepository::button($editId, true);
    if ($button) {
        $formValues = boutons_perso_admin_values_from_button($button);
    } else {
        $editId = 0;
        $errors[] = 'Bouton introuvable.';
    }
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['bp_admin_action']) ? (string) $_POST['bp_admin_action'] : '';

    if ($action === 'save_settings') {
        $openSettingsModal = true;
        $displayName = isset($_POST['display_name']) ? trim((string) $_POST['display_name']) : '';
        $panelBg = isset($_POST['panel_bg_color']) ? trim((string) $_POST['panel_bg_color']) : '';
        $panelBorder = isset($_POST['panel_border_color']) ? trim((string) $_POST['panel_border_color']) : '';

        if ($displayName === '') {
            $errors[] = 'Le nom affiche est obligatoire.';
        } elseif (strlen($displayName) > 80) {
            $errors[] = 'Le nom affiche ne doit pas depasser 80 caracteres.';
        }

        if (BoutonsPersoConfig::normalizeColor($panelBg, '') === '') {
            $errors[] = 'La couleur de fond du bloc est invalide.';
        }

        if (BoutonsPersoConfig::normalizeColor($panelBorder, '') === '') {
            $errors[] = 'La couleur de bordure du bloc est invalide.';
        }

        if (count($errors) === 0) {
            $enabled = isset($_POST['enabled']) ? '1' : '0';
            BoutonsPersoConfig::set('enabled', $enabled);
            BoutonsPersoConfig::set('display_name', $displayName);
            BoutonsPersoConfig::set('show_title', isset($_POST['show_title']) ? '1' : '0');
            BoutonsPersoConfig::set('account_menu_enabled', isset($_POST['account_menu_enabled']) ? '1' : '0');
            BoutonsPersoConfig::set('panel_bg_color', BoutonsPersoConfig::normalizeColor($panelBg, '#f6f8fb'));
            BoutonsPersoConfig::set('panel_border_color', BoutonsPersoConfig::normalizeColor($panelBorder, '#d8dee6'));

            // Keep the legacy V0.1 setting in sync.
            Settings::set('boutons_perso_enabled', $enabled);
            Settings::load();

            $messages[] = 'Configuration enregistree.';
            $openSettingsModal = false;
        }
    }

    if ($action === 'save_button') {
        $openButtonModal = true;
        $buttonId = isset($_POST['button_id']) ? (int) $_POST['button_id'] : 0;
        $formValues = BoutonsPersoRepository::normalizeButtonValues($_POST);
        $errors = array_merge($errors, BoutonsPersoRepository::validateButtonValues($formValues));

        if (count($errors) === 0) {
            $ok = $buttonId > 0
                ? BoutonsPersoRepository::updateButton($buttonId, $formValues)
                : BoutonsPersoRepository::createButton($formValues);

            if ($ok) {
                $messages[] = $buttonId > 0 ? 'Bouton modifie.' : 'Bouton ajoute.';
                $formValues = BoutonsPersoRepository::emptyButtonValues();
                $editId = 0;
                $openButtonModal = false;
            } else {
                $errors[] = 'Erreur lors de l enregistrement du bouton.';
                $editId = $buttonId;
            }
        } else {
            $editId = $buttonId;
        }
    }

    if ($action === 'save_module_button') {
        $openButtonModal = true;
        $sourceKey = isset($_POST['source_key']) ? trim((string) $_POST['source_key']) : '';
        $current = BoutonsPersoRepository::moduleButton($sourceKey, true);
        if (!$current) {
            $errors[] = 'Bouton de module introuvable.';
            $editModuleKey = '';
            $openButtonModal = false;
        } else {
            $moduleDefinition = isset($moduleDefinitions[$sourceKey]) ? $moduleDefinitions[$sourceKey] : array();
            $moduleFormValues = BoutonsPersoRepository::normalizeModuleButtonValues($_POST, $current);
            $errors = array_merge($errors, BoutonsPersoRepository::validateModuleButtonValues($moduleFormValues));

            if (count($errors) === 0) {
                if (BoutonsPersoRepository::updateModuleButton($sourceKey, $moduleFormValues)) {
                    $messages[] = 'Configuration du bouton de module enregistree.';
                    $editModuleKey = '';
                    $moduleFormValues = BoutonsPersoRepository::emptyButtonValues();
                    $openButtonModal = false;
                } else {
                    $errors[] = 'Erreur lors de l enregistrement du bouton de module.';
                    $editModuleKey = $sourceKey;
                }
            } else {
                $editModuleKey = $sourceKey;
            }

            if ($editModuleKey !== '') {
                $resolvedModule = boutons_perso_admin_resolve_button(
                    array_merge($current, $moduleFormValues),
                    $moduleDefinitions
                );
                $moduleFormValues = boutons_perso_admin_values_from_button($resolvedModule);
            }
        }
    }

    if ($action === 'delete_button') {
        $buttonId = isset($_POST['button_id']) ? (int) $_POST['button_id'] : 0;
        if (BoutonsPersoRepository::deleteButton($buttonId)) {
            $messages[] = 'Bouton supprime.';
            if ($editId === $buttonId) {
                $editId = 0;
                $formValues = BoutonsPersoRepository::emptyButtonValues();
            }
        } else {
            $errors[] = 'Erreur lors de la suppression du bouton.';
        }
    }
}

$buttons = array();
foreach (BoutonsPersoRepository::allButtons(true) as $button) {
    $buttons[] = boutons_perso_admin_resolve_button($button, $moduleDefinitions);
}
$moduleDisplayButton = array();
if ($editModuleKey !== '') {
    $currentModuleRow = BoutonsPersoRepository::moduleButton($editModuleKey, true);
    if ($currentModuleRow) {
        $moduleDisplayButton = boutons_perso_admin_resolve_button($currentModuleRow, $moduleDefinitions);
        $moduleFormValues['label'] = isset($moduleDisplayButton['label']) ? (string) $moduleDisplayButton['label'] : '';
        $moduleFormValues['url'] = isset($moduleDisplayButton['url']) ? (string) $moduleDisplayButton['url'] : '';
    }
}
$diagnostics = BoutonsPersoRepository::diagnostics();
$actionUrl = boutons_perso_admin_url(array(), true);

if (!$boutonsPersoAdminEmbedded) {
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Boutons perso</title>';
    echo '<link rel="stylesheet" href="../../../themes/default/css/style.css">';
    echo '</head><body>';
}

echo '<div class="container-fluid boutons-perso-admin">';
echo '<h1>'.boutons_perso_admin_html(BoutonsPersoConfig::displayName()).'</h1>';

if ($boutonsPersoAdminEmbedded) {
    echo '<p><a href="../personnalisation/modules/boutons_perso/admin.php">Ouvrir la page autonome</a></p>';
} else {
    echo '<p><a href="../../../compte/compte.php?pc=boutons_perso">Ouvrir la page integree</a></p>';
}

foreach ($messages as $message) {
    echo '<div class="alert alert-success">'.boutons_perso_admin_html($message).'</div>';
}
foreach ($errors as $error) {
    echo '<div class="alert alert-danger">'.boutons_perso_admin_html($error).'</div>';
}

echo '<style>'
    .'.boutons-perso-admin{width:100%;max-width:none;margin:0;box-sizing:border-box;}'
    .'.boutons-perso-admin *{box-sizing:border-box;}'
    .'.boutons-perso-admin h1{margin-top:0;}'
    .'.boutons-perso-admin .card{width:100%;margin-bottom:16px;}'
    .'.boutons-perso-admin .card-body{width:100%;overflow:visible;}'
    .'.boutons-perso-admin .bp-admin-actions{display:flex;gap:6px;flex-wrap:wrap;align-items:center;}'
    .'.boutons-perso-admin .bp-admin-actions .btn{white-space:normal;}'
    .'.boutons-perso-admin .table-responsive{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch;}'
    .'.boutons-perso-admin table{width:100%;table-layout:auto;}'
    .'.boutons-perso-admin th,.boutons-perso-admin td{vertical-align:middle;overflow-wrap:anywhere;}'
    .'.boutons-perso-admin .bp-preview-cell{min-width:120px;}'
    .'.boutons-perso-admin .bp-url-cell{max-width:340px;}'
    .'.boutons-perso-admin .bp-help{color:#666;font-size:12px;margin-top:4px;}'
    .'.boutons-perso-admin .bp-admin-modal{display:none;position:fixed;z-index:5000;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,.45);padding:30px 12px;}'
    .'.boutons-perso-admin .bp-admin-modal.is-open{display:block;}'
    .'.boutons-perso-admin .bp-admin-modal-dialog{background:#fff;margin:0 auto;width:100%;max-width:1120px;border-radius:4px;box-shadow:0 8px 28px rgba(0,0,0,.25);}'
    .'.boutons-perso-admin .bp-admin-modal-head{display:flex;gap:12px;justify-content:space-between;align-items:center;border-bottom:1px solid #ddd;padding:12px 15px;font-size:16px;}'
    .'.boutons-perso-admin .bp-admin-modal-head strong{overflow-wrap:anywhere;}'
    .'.boutons-perso-admin .bp-admin-modal-body{padding:15px;}'
    .'.boutons-perso-admin .bp-admin-modal-close{border:0;background:transparent;font-size:24px;line-height:1;}'
    .'.boutons-perso-admin .bp-window-options,.boutons-perso-admin .bp-custom-colors{display:none;}'
    .'.boutons-perso-admin .btn-secondary{background-color:#6c757d;border-color:#6c757d;color:#fff;}'
    .'.boutons-perso-admin .btn-secondary:hover,.boutons-perso-admin .btn-secondary:focus{background-color:#5a6268;border-color:#545b62;color:#fff;}'
    .'.boutons-perso-admin .btn-dark{background-color:#343a40;border-color:#343a40;color:#fff;}'
    .'.boutons-perso-admin .btn-dark:hover,.boutons-perso-admin .btn-dark:focus{background-color:#23272b;border-color:#1d2124;color:#fff;}'
    .'.boutons-perso-admin .btn-light{background-color:#f8f9fa;border-color:#d6d8db;color:#212529;}'
    .'.boutons-perso-admin .btn-light:hover,.boutons-perso-admin .btn-light:focus{background-color:#e2e6ea;border-color:#dae0e5;color:#212529;}'
    .'.boutons-perso-admin .btn-purple{background-color:#6f42c1;border-color:#6f42c1;color:#fff;}'
    .'.boutons-perso-admin .btn-purple:hover,.boutons-perso-admin .btn-purple:focus{background-color:#5a32a3;border-color:#512d92;color:#fff;}'
    .'.boutons-perso-admin .btn-maroon{background-color:#d81b60;border-color:#d81b60;color:#fff;}'
    .'.boutons-perso-admin .btn-maroon:hover,.boutons-perso-admin .btn-maroon:focus{background-color:#b71550;border-color:#a31247;color:#fff;}'
    .'.boutons-perso-admin .btn-navy{background-color:#001f3f;border-color:#001f3f;color:#fff;}'
    .'.boutons-perso-admin .btn-navy:hover,.boutons-perso-admin .btn-navy:focus{background-color:#00162d;border-color:#001020;color:#fff;}'
    .'.boutons-perso-admin .btn-teal{background-color:#39cccc;border-color:#39cccc;color:#fff;}'
    .'.boutons-perso-admin .btn-teal:hover,.boutons-perso-admin .btn-teal:focus{background-color:#30b5b5;border-color:#2aa3a3;color:#fff;}'
    .'.boutons-perso-admin .btn-olive{background-color:#3d9970;border-color:#3d9970;color:#fff;}'
    .'.boutons-perso-admin .btn-olive:hover,.boutons-perso-admin .btn-olive:focus{background-color:#327f5d;border-color:#2b6f51;color:#fff;}'
    .'@media (max-width:767px){'
        .'.boutons-perso-admin{padding-left:0;padding-right:0;}'
        .'.boutons-perso-admin h1{font-size:24px;}'
        .'.boutons-perso-admin .bp-admin-actions{align-items:stretch;}'
        .'.boutons-perso-admin .bp-admin-actions .btn,.boutons-perso-admin .bp-admin-actions a{width:100%;text-align:center;}'
        .'.boutons-perso-admin .bp-admin-modal{padding:10px;}'
        .'.boutons-perso-admin .bp-admin-modal-dialog{max-width:none;}'
        .'.boutons-perso-admin .bp-admin-modal-body{padding:12px;}'
        .'.boutons-perso-admin .table-responsive{border:0;overflow:visible;}'
        .'.boutons-perso-admin .table-responsive table,.boutons-perso-admin .table-responsive thead,.boutons-perso-admin .table-responsive tbody,.boutons-perso-admin .table-responsive tr,.boutons-perso-admin .table-responsive th,.boutons-perso-admin .table-responsive td{display:block;width:100%;}'
        .'.boutons-perso-admin .table-responsive thead{display:none;}'
        .'.boutons-perso-admin .table-responsive tr{margin:0 0 12px;border:1px solid #ddd;background:#fff;border-radius:4px;overflow:hidden;}'
        .'.boutons-perso-admin .table-responsive td{display:flex;gap:10px;justify-content:space-between;align-items:flex-start;border:0;border-bottom:1px solid #eee;text-align:right;}'
        .'.boutons-perso-admin .table-responsive td:last-child{border-bottom:0;}'
        .'.boutons-perso-admin .table-responsive td:before{content:attr(data-label);font-weight:600;color:#555;text-align:left;flex:0 0 42%;}'
        .'.boutons-perso-admin .table-responsive td>*{max-width:100%;}'
        .'.boutons-perso-admin .bp-preview-cell{min-width:0;}'
        .'.boutons-perso-admin .bp-url-cell{max-width:none;}'
    .'}'
.'</style>';
echo '<script>(function(){if(window.bpAdminModalReady){return;}window.bpAdminModalReady=true;document.addEventListener("click",function(e){var open=e.target.closest("[data-bp-admin-modal-open]");if(open){e.preventDefault();var id=open.getAttribute("data-bp-admin-modal-open");var modal=document.getElementById(id);if(modal){modal.classList.add("is-open");}}var close=e.target.closest("[data-bp-admin-modal-close]");if(close){e.preventDefault();var box=close.closest(".bp-admin-modal");if(box){box.classList.remove("is-open");}}if(e.target.classList&&e.target.classList.contains("bp-admin-modal")){e.target.classList.remove("is-open");}});document.addEventListener("keydown",function(e){if(e.key==="Escape"){document.querySelectorAll(".bp-admin-modal.is-open").forEach(function(modal){modal.classList.remove("is-open");});}});})();</script>';

echo '<div class="bp-admin-actions" style="margin:12px 0 18px;">'
    .boutons_perso_admin_modal_button('bp-settings-modal', 'Configuration')
    .' '.boutons_perso_admin_modal_button('bp-button-modal', $editId > 0 || $editModuleKey !== '' ? 'Formulaire bouton' : 'Ajouter un bouton')
    .'</div>';

ob_start();
echo '<form method="post" action="'.$actionUrl.'">';
echo '<input type="hidden" name="bp_admin_action" value="save_settings">';
echo '<div class="row">';
echo '<div class="col-md-4"><div class="form-group"><label for="bp_display_name">Nom affiche</label><input class="form-control" id="bp_display_name" name="display_name" maxlength="80" required value="'.boutons_perso_admin_html(BoutonsPersoConfig::displayName()).'"></div></div>';
echo '<div class="col-md-2"><div class="form-group"><label><input type="checkbox" name="enabled" value="1"'.(BoutonsPersoConfig::isEnabled() ? ' checked' : '').'> Module actif</label></div></div>';
echo '<div class="col-md-2"><div class="form-group"><label><input type="checkbox" name="show_title" value="1"'.(BoutonsPersoConfig::showTitle() ? ' checked' : '').'> Afficher le titre</label></div></div>';
echo '<div class="col-md-2"><div class="form-group"><label for="bp_panel_bg_color">Fond</label><input class="form-control" type="color" id="bp_panel_bg_color" name="panel_bg_color" value="'.boutons_perso_admin_html(boutons_perso_admin_setting_color('panel_bg_color', '#f6f8fb')).'"></div></div>';
echo '<div class="col-md-2"><div class="form-group"><label for="bp_panel_border_color">Bordure</label><input class="form-control" type="color" id="bp_panel_border_color" name="panel_border_color" value="'.boutons_perso_admin_html(boutons_perso_admin_setting_color('panel_border_color', '#d8dee6')).'"></div></div>';
echo '</div>';
echo '<div class="row">';
echo '<div class="col-md-6"><div class="form-group"><label><input type="checkbox" name="account_menu_enabled" value="1"'.(BoutonsPersoConfig::accountMenuEnabled() ? ' checked' : '').'> Gerer les boutons modules de Gerer mon compte</label><div class="bp-help">Concerne uniquement les boutons ajoutes par les modules via hookCompteMenu. Les liens Mon compte, Mes connexions et Mes reservations restent codes en dur.</div></div></div>';
echo '</div>';
echo '<button class="btn btn-primary" type="submit">Enregistrer la configuration</button>';
echo '</form>';
$settingsForm = ob_get_clean();
echo boutons_perso_admin_modal('bp-settings-modal', 'Configuration du module', $settingsForm, $openSettingsModal);

$editingModule = $editModuleKey !== '';
$activeFormValues = $editingModule ? $moduleFormValues : $formValues;
$buttonModalTitle = $editingModule ? 'Configurer un bouton de module' : ($editId > 0 ? 'Modifier un bouton personnalise' : 'Ajouter un bouton personnalise');
ob_start();
echo '<form method="post" action="'.$actionUrl.'" id="bp_button_form">';
if ($editingModule) {
    echo '<input type="hidden" name="bp_admin_action" value="save_module_button">';
    echo '<input type="hidden" name="source_key" value="'.boutons_perso_admin_html($editModuleKey).'">';
    echo '<div class="alert alert-info"><strong>Bouton systeme :</strong> le libelle et l URL sont fournis par le module et ne sont pas modifiables. '
        .boutons_perso_admin_module_status($moduleDisplayButton).'</div>';
} else {
    echo '<input type="hidden" name="bp_admin_action" value="save_button">';
    echo '<input type="hidden" name="button_id" value="'.(int) $editId.'">';
}
echo '<div class="row">';
if ($editingModule) {
    echo '<div class="col-md-4"><div class="form-group"><label for="bp_label">Libelle fourni</label><input class="form-control" id="bp_label" readonly value="'.boutons_perso_admin_html($activeFormValues['label']).'"></div></div>';
    echo '<div class="col-md-5"><div class="form-group"><label for="bp_url">URL fournie</label><input class="form-control" id="bp_url" readonly value="'.boutons_perso_admin_html($activeFormValues['url']).'"><div class="bp-help"><code>'.boutons_perso_admin_html($editModuleKey).'</code></div></div></div>';
} else {
    echo '<div class="col-md-4"><div class="form-group"><label for="bp_label">Libelle</label><input class="form-control" id="bp_label" name="label" maxlength="120" required value="'.boutons_perso_admin_html($activeFormValues['label']).'"></div></div>';
    echo '<div class="col-md-5"><div class="form-group"><label for="bp_url">URL</label><input class="form-control" id="bp_url" name="url" maxlength="500" required value="'.boutons_perso_admin_html($activeFormValues['url']).'"><div class="bp-help">Exemples : <code>app.php?p=jour</code>, <code>/grr/app.php</code>, <code>https://exemple.fr</code>.</div></div></div>';
}
echo '<div class="col-md-3"><div class="form-group"><label for="bp_position_order">Ordre</label><input class="form-control" id="bp_position_order" type="number" min="0" name="position_order" value="'.boutons_perso_admin_html($activeFormValues['position_order']).'"></div></div>';
echo '</div>';
if ($editingModule) {
    echo '<div class="row">';
    echo '<div class="col-md-4"><div class="form-group"><label><input type="checkbox" name="account_menu_active" value="1"'.((int) $activeFormValues['account_menu_active'] === 1 ? ' checked' : '').'> Afficher dans Gerer mon compte</label></div></div>';
    echo '<div class="col-md-3"><div class="form-group"><label for="bp_account_position_order">Ordre menu compte</label><input class="form-control" id="bp_account_position_order" type="number" min="0" name="account_position_order" value="'.boutons_perso_admin_html($activeFormValues['account_position_order']).'"></div></div>';
    echo '</div>';
}
echo '<div class="row">';
echo '<div class="col-md-3"><div class="form-group"><label for="bp_target_mode">Ouverture</label><select class="form-control" id="bp_target_mode" name="target_mode">';
foreach (BoutonsPersoConfig::targetModes() as $mode => $label) {
    echo '<option value="'.boutons_perso_admin_html($mode).'"'.((string) $activeFormValues['target_mode'] === (string) $mode ? ' selected' : '').'>'.boutons_perso_admin_html($label).'</option>';
}
echo '</select></div></div>';
echo '<div class="col-md-3"><div class="form-group"><label for="bp_button_style">Style</label><select class="form-control" id="bp_button_style" name="button_style">';
foreach (BoutonsPersoConfig::buttonStyles() as $style => $label) {
    echo '<option value="'.boutons_perso_admin_html($style).'"'.((string) $activeFormValues['button_style'] === (string) $style ? ' selected' : '').'>'.boutons_perso_admin_html($label).'</option>';
}
echo '</select></div></div>';
echo '<div class="col-md-4"><div class="form-group"><label for="bp_tooltip">Infobulle</label><input class="form-control" id="bp_tooltip" name="tooltip" maxlength="190" value="'.boutons_perso_admin_html($activeFormValues['tooltip']).'"></div></div>';
echo '<div class="col-md-2"><div class="form-group"><label><input type="checkbox" name="active" value="1"'.((int) $activeFormValues['active'] === 1 ? ' checked' : '').'> '.($editingModule ? 'Actif calendrier' : 'Actif').'</label></div></div>';
echo '</div>';
echo '<div class="row bp-custom-colors">';
echo '<div class="col-md-3"><div class="form-group"><label for="bp_custom_bg_color">Fond personnalise</label><input class="form-control" type="color" id="bp_custom_bg_color" name="custom_bg_color" value="'.boutons_perso_admin_html($activeFormValues['custom_bg_color'] !== '' ? $activeFormValues['custom_bg_color'] : '#337ab7').'"></div></div>';
echo '<div class="col-md-3"><div class="form-group"><label for="bp_custom_text_color">Texte personnalise</label><input class="form-control" type="color" id="bp_custom_text_color" name="custom_text_color" value="'.boutons_perso_admin_html($activeFormValues['custom_text_color'] !== '' ? $activeFormValues['custom_text_color'] : '#ffffff').'"></div></div>';
echo '</div>';
echo '<div class="row bp-window-options">';
echo '<div class="col-md-3"><div class="form-group"><label for="bp_window_width">Largeur fenetre</label><input class="form-control" id="bp_window_width" type="number" min="300" max="2400" name="window_width" value="'.boutons_perso_admin_html($activeFormValues['window_width']).'"></div></div>';
echo '<div class="col-md-3"><div class="form-group"><label for="bp_window_height">Hauteur fenetre</label><input class="form-control" id="bp_window_height" type="number" min="300" max="1600" name="window_height" value="'.boutons_perso_admin_html($activeFormValues['window_height']).'"></div></div>';
echo '<div class="col-md-4"><div class="form-group"><label for="bp_window_name">Nom technique fenetre</label><input class="form-control" id="bp_window_name" name="window_name" maxlength="80" value="'.boutons_perso_admin_html($activeFormValues['window_name']).'"><div class="bp-help">Facultatif. Permet de reutiliser la meme fenetre.</div></div></div>';
echo '</div>';
echo '<div class="row">';
echo '<div class="col-md-6"><div class="form-group"><label for="bp_confirm_message">Message de confirmation</label><input class="form-control" id="bp_confirm_message" name="confirm_message" maxlength="190" value="'.boutons_perso_admin_html($activeFormValues['confirm_message']).'"><div class="bp-help">Facultatif. Si renseigne, une confirmation est demandee avant ouverture.</div></div></div>';
echo '<div class="col-md-3"><div class="form-group"><label>Apercu</label><div>'.boutons_perso_admin_button_preview($activeFormValues).'</div></div></div>';
echo '</div>';
echo '<button class="btn btn-primary" type="submit">'.($editingModule || $editId > 0 ? 'Enregistrer les modifications' : 'Ajouter le bouton').'</button> ';
if ($editingModule || $editId > 0) {
    echo '<a class="btn btn-default" href="'.boutons_perso_admin_url().'">Annuler</a>';
}
echo '</form>';
$buttonForm = ob_get_clean();
echo boutons_perso_admin_modal('bp-button-modal', $buttonModalTitle, $buttonForm, $openButtonModal || $editingModule || $editId > 0);

echo '<div class="card card-primary card-outline">';
echo '<div class="card-header"><h3 class="card-title">Boutons configures</h3></div>';
echo '<div class="card-body">';
if (count($buttons) === 0) {
    echo '<div class="alert alert-info">Aucun bouton configure.</div>';
} else {
    $deleteModals = '';
    echo '<div class="table-responsive"><table class="table table-bordered table-striped">';
    echo '<thead><tr><th>Ordre</th><th>Type</th><th>Apercu</th><th>Libelle</th><th>URL</th><th>Etat source</th><th>Ouverture</th><th>Style</th><th>Actif calendrier</th><th>Menu compte</th><th>Ordre menu</th><th>Actions</th></tr></thead><tbody>';
    foreach ($buttons as $button) {
        $id = isset($button['id']) ? (int) $button['id'] : 0;
        $isModuleButton = isset($button['source_type']) && (string) $button['source_type'] === BoutonsPersoRepository::SOURCE_MODULE;
        echo '<tr>';
        echo '<td data-label="Ordre">'.(int) $button['position_order'].'</td>';
        echo '<td data-label="Type">'.($isModuleButton ? '<span class="label label-info">Module</span>' : '<span class="label label-default">Personnalise</span>').'</td>';
        echo '<td data-label="Apercu" class="bp-preview-cell">'.boutons_perso_admin_button_preview($button).'</td>';
        echo '<td data-label="Libelle">'.boutons_perso_admin_html($button['label']).'</td>';
        echo '<td data-label="URL" class="bp-url-cell">'.boutons_perso_admin_html($button['url']).'</td>';
        echo '<td data-label="Etat source">'.($isModuleButton ? boutons_perso_admin_module_status($button) : '<span class="text-muted">Interne</span>').'</td>';
        echo '<td data-label="Ouverture">'.boutons_perso_admin_html(boutons_perso_admin_target_label($button['target_mode'])).'</td>';
        echo '<td data-label="Style">'.boutons_perso_admin_html(boutons_perso_admin_style_label($button['button_style'])).'</td>';
        echo '<td data-label="Actif calendrier">'.((int) $button['active'] === 1 ? '<span class="label label-success">Oui</span>' : '<span class="label label-default">Non</span>').'</td>';
        echo '<td data-label="Menu compte">'.($isModuleButton ? ((int) $button['account_menu_active'] === 1 ? '<span class="label label-success">Oui</span>' : '<span class="label label-default">Non</span>') : '<span class="text-muted">-</span>').'</td>';
        echo '<td data-label="Ordre menu">'.($isModuleButton ? (int) $button['account_position_order'] : '<span class="text-muted">-</span>').'</td>';
        echo '<td data-label="Actions"><div class="bp-admin-actions">';
        if ($isModuleButton) {
            echo '<a class="btn btn-primary btn-xs" href="'.boutons_perso_admin_url(array('module' => $button['source_key'])).'">Configurer</a>';
        } else {
            echo '<a class="btn btn-primary btn-xs" href="'.boutons_perso_admin_url(array('edit' => $id)).'">Modifier</a>';
            echo boutons_perso_admin_modal_button('bp-delete-button-'.$id, 'Supprimer', 'btn btn-danger btn-xs');
            $deleteForm = '<p>Supprimer definitivement le bouton <strong>'.boutons_perso_admin_html($button['label']).'</strong> ?</p>'
                .'<form method="post" action="'.$actionUrl.'">'
                .'<input type="hidden" name="bp_admin_action" value="delete_button">'
                .'<input type="hidden" name="button_id" value="'.$id.'">'
                .'<button class="btn btn-danger" type="submit">Supprimer</button> '
                .'<button class="btn btn-default" type="button" data-bp-admin-modal-close>Annuler</button>'
                .'</form>';
            $deleteModals .= boutons_perso_admin_modal('bp-delete-button-'.$id, 'Supprimer un bouton', $deleteForm);
        }
        echo '</div></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
    echo $deleteModals;
}
echo '</div></div>';

echo '<div class="card card-primary card-outline">';
echo '<div class="card-header"><h3 class="card-title">Diagnostic</h3></div>';
echo '<div class="card-body">';
echo '<table class="table table-bordered table-striped"><thead><tr><th>Element</th><th>Table</th><th>Etat</th></tr></thead><tbody>';
foreach ($diagnostics as $diagnostic) {
    echo '<tr>';
    echo '<td data-label="Element">'.boutons_perso_admin_html($diagnostic['label']).'</td>';
    echo '<td data-label="Table"><code>'.boutons_perso_admin_html($diagnostic['table']).'</code></td>';
    echo '<td data-label="Etat">'.($diagnostic['exists'] ? '<span class="label label-success">OK</span>' : '<span class="label label-danger">Manquant</span>').'</td>';
    echo '</tr>';
}
echo '</tbody></table>';
echo '</div></div>';

echo '<script>
(function () {
    function updateOptions() {
        var targetMode = document.getElementById("bp_target_mode");
        var buttonStyle = document.getElementById("bp_button_style");
        var windowBlocks = document.querySelectorAll(".bp-window-options");
        var customBlocks = document.querySelectorAll(".bp-custom-colors");
        var showWindow = targetMode && targetMode.value === "new_window";
        var showCustom = buttonStyle && buttonStyle.value === "custom";
        for (var i = 0; i < windowBlocks.length; i++) {
            windowBlocks[i].style.display = showWindow ? "block" : "none";
        }
        for (var j = 0; j < customBlocks.length; j++) {
            customBlocks[j].style.display = showCustom ? "block" : "none";
        }
    }
    var targetMode = document.getElementById("bp_target_mode");
    var buttonStyle = document.getElementById("bp_button_style");
    if (targetMode) {
        targetMode.addEventListener("change", updateOptions);
    }
    if (buttonStyle) {
        buttonStyle.addEventListener("change", updateOptions);
    }
    updateOptions();
}());
</script>';

echo '</div>';

if (!$boutonsPersoAdminEmbedded) {
    echo '</body></html>';
}
