<?php
$suiviDemandesAdminEmbedded = isset($suivi_demandes_admin_embedded) && $suivi_demandes_admin_embedded;

require_once __DIR__.'/lib/bootstrap.php';

$sessionOk = $suiviDemandesAdminEmbedded ? true : grr_suivi_bootstrap(true);

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';

if (!$sessionOk) {
    header('Location: ../../../index.php');
    exit;
}

$login = function_exists('getUserName') ? (string) getUserName() : '';
if ($login === '' || SecuAccess::UserLevel($login, -1) < 6) {
    if ($suiviDemandesAdminEmbedded) {
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
    $creationRight = isset($_POST['create_right']) ? (string) $_POST['create_right'] : SuiviDemandesConfig::creationRight();
    $closeRight = isset($_POST['close_right']) ? (string) $_POST['close_right'] : SuiviDemandesConfig::closeRight();
    $creationRightModes = SuiviDemandesConfig::creationRightModes();
    $closeRightModes = SuiviDemandesConfig::closeRightModes();
    $categoriesEnabled = isset($_POST['categories_enabled']);
    $categoryOptionsRaw = isset($_POST['category_options']) ? trim((string) $_POST['category_options']) : SuiviDemandesConfig::categoryOptionsText();
    $categoryOptions = SuiviDemandesConfig::categoryOptionsFromText($categoryOptionsRaw);
    $attachmentsEnabled = isset($_POST['attachments_enabled']) && (string) $_POST['attachments_enabled'] === '1';
    $attachmentMaxMb = isset($_POST['attachment_max_mb']) ? (int) $_POST['attachment_max_mb'] : SuiviDemandesConfig::attachmentMaxMb();
    $attachmentExtensionsRaw = isset($_POST['attachment_extensions']) ? trim((string) $_POST['attachment_extensions']) : SuiviDemandesConfig::attachmentExtensionsText();
    $attachmentExtensions = SuiviDemandesConfig::attachmentExtensionsFromText($attachmentExtensionsRaw);
    $invalidAttachmentExtensions = SuiviDemandesConfig::invalidAttachmentExtensionsFromText($attachmentExtensionsRaw);
    $roomsEnabled = suivi_demandes_admin_room_ids(isset($_POST['suivi_rooms_enabled']) ? $_POST['suivi_rooms_enabled'] : array());
    $roomsDisabled = suivi_demandes_admin_room_ids(isset($_POST['suivi_rooms_disabled']) ? $_POST['suivi_rooms_disabled'] : array());
    $usersEnabled = suivi_demandes_admin_logins(isset($_POST['suivi_users_enabled']) ? $_POST['suivi_users_enabled'] : array());
    $usersDisabled = suivi_demandes_admin_logins(isset($_POST['suivi_users_disabled']) ? $_POST['suivi_users_disabled'] : array());
    $statusLabels = array();
    $notificationLinkColors = array();
    $priorityLabels = array();
    $priorityEnabled = array();

    if ($displayName === '') {
        $errors[] = 'Le nom affiche est obligatoire.';
    } elseif (strlen($displayName) > 80) {
        $errors[] = 'Le nom affiche ne doit pas depasser 80 caracteres.';
    }

    if (!isset($creationRightModes[$creationRight])) {
        $errors[] = 'Le droit de creation selectionne est invalide.';
    }

    if (!isset($closeRightModes[$closeRight])) {
        $errors[] = 'Le droit de cloture selectionne est invalide.';
    }

    foreach (SuiviDemandesConfig::statusDefinitions() as $status => $defaultLabel) {
        $label = isset($_POST['status_label_'.$status])
            ? trim((string) $_POST['status_label_'.$status])
            : SuiviDemandesConfig::statusLabel($status);

        if ($label === '') {
            $errors[] = 'Le libelle de statut '.$status.' est obligatoire.';
        } elseif (strlen($label) > 40) {
            $errors[] = 'Le libelle de statut '.$status.' ne doit pas depasser 40 caracteres.';
        }

        $statusLabels[$status] = $label;
    }

    foreach (SuiviDemandesConfig::notificationLinkColorDefaults() as $status => $defaultColor) {
        $color = isset($_POST['notification_link_color_'.$status])
            ? trim((string) $_POST['notification_link_color_'.$status])
            : SuiviDemandesConfig::notificationLinkColor($status);

        $color = SuiviDemandesConfig::normalizeColor($color, '');
        if ($color === '') {
            $errors[] = 'La couleur du lien de notification '.SuiviDemandesConfig::statusLabel($status).' doit etre au format #RRGGBB.';
        }

        $notificationLinkColors[$status] = $color === '' ? $defaultColor : $color;
    }

    foreach (SuiviDemandesConfig::priorityDefinitions() as $priority => $defaultLabel) {
        $label = isset($_POST['priority_label_'.$priority])
            ? trim((string) $_POST['priority_label_'.$priority])
            : SuiviDemandesConfig::priorityLabel($priority);

        if ($label === '') {
            $errors[] = 'Le libelle de priorite '.$priority.' est obligatoire.';
        } elseif (strlen($label) > 40) {
            $errors[] = 'Le libelle de priorite '.$priority.' ne doit pas depasser 40 caracteres.';
        }

        $priorityLabels[$priority] = $label;
        $priorityEnabled[$priority] = isset($_POST['priority_enabled_'.$priority]);
    }

    if (!in_array(true, $priorityEnabled, true)) {
        $errors[] = 'Au moins une priorite doit rester active.';
    }

    if ($categoriesEnabled && count($categoryOptions) === 0) {
        $errors[] = 'Au moins une categorie doit etre renseignee.';
    } elseif (count($categoryOptions) > SuiviDemandesConfig::MAX_CATEGORIES) {
        $errors[] = 'Le nombre de categories ne doit pas depasser '.SuiviDemandesConfig::MAX_CATEGORIES.'.';
    }

    foreach ($categoryOptions as $category) {
        if (strlen($category) > SuiviDemandesConfig::MAX_CATEGORY_LENGTH) {
            $errors[] = 'La categorie "'.$category.'" ne doit pas depasser '.SuiviDemandesConfig::MAX_CATEGORY_LENGTH.' caracteres.';
        }
    }

    if ($attachmentMaxMb < SuiviDemandesConfig::MIN_ATTACHMENT_MAX_MB || $attachmentMaxMb > SuiviDemandesConfig::MAX_ATTACHMENT_MAX_MB) {
        $errors[] = 'La taille maximale des pieces jointes doit etre comprise entre '.SuiviDemandesConfig::MIN_ATTACHMENT_MAX_MB.' et '.SuiviDemandesConfig::MAX_ATTACHMENT_MAX_MB.' Mo.';
    }

    if (count($attachmentExtensions) === 0) {
        $errors[] = 'Au moins une extension de piece jointe doit etre autorisee.';
    }

    if (count($invalidAttachmentExtensions) > 0) {
        $errors[] = 'Extensions de pieces jointes refusees : '.implode(', ', $invalidAttachmentExtensions).'.';
    }

    if (count($errors) === 0) {
        SuiviDemandesConfig::set('display_name', $displayName);
        SuiviDemandesConfig::set('enabled', isset($_POST['enabled']) ? '1' : '0');
        SuiviDemandesConfig::set('account_enabled', isset($_POST['account_enabled']) ? '1' : '0');
        SuiviDemandesConfig::set('reservation_form_enabled', isset($_POST['reservation_form_enabled']) ? '1' : '0');
        SuiviDemandesConfig::set('reservation_detail_enabled', isset($_POST['reservation_detail_enabled']) ? '1' : '0');
        SuiviDemandesConfig::set('create_right', $creationRight);
        SuiviDemandesConfig::set('close_right', $closeRight);
        foreach (SuiviDemandesConfig::statusDefinitions() as $status => $defaultLabel) {
            SuiviDemandesConfig::setStatusLabel($status, $statusLabels[$status]);
        }
        foreach (SuiviDemandesConfig::priorityDefinitions() as $priority => $defaultLabel) {
            SuiviDemandesConfig::setPriorityLabel($priority, $priorityLabels[$priority]);
            SuiviDemandesConfig::setPriorityEnabled($priority, $priorityEnabled[$priority]);
        }
        SuiviDemandesConfig::set('categories_enabled', $categoriesEnabled ? '1' : '0');
        SuiviDemandesConfig::setCategoryOptions(implode("\n", $categoryOptions));
        SuiviDemandesConfig::set('attachments_enabled', $attachmentsEnabled ? '1' : '0');
        SuiviDemandesConfig::set('attachment_max_mb', (string) $attachmentMaxMb);
        SuiviDemandesConfig::setAttachmentExtensions(implode("\n", $attachmentExtensions));
        if (SuiviDemandesConfig::get('attachments_enabled', '1') !== ($attachmentsEnabled ? '1' : '0')) {
            $errors[] = 'La configuration d activation des pieces jointes n a pas pu etre enregistree.';
        }
        SuiviDemandesConfig::set('notifications_enabled', isset($_POST['notifications_enabled']) ? '1' : '0');
        foreach (SuiviDemandesConfig::notificationTypes() as $type => $label) {
            SuiviDemandesConfig::setNotificationTypeEnabled($type, isset($_POST['notification_'.$type]));
        }
        foreach (SuiviDemandesConfig::notificationLinkColorDefaults() as $status => $defaultColor) {
            SuiviDemandesConfig::setNotificationLinkColor($status, $notificationLinkColors[$status]);
        }
        if (!SuiviDemandesRepository::setRoomModuleStates($roomsEnabled, $roomsDisabled)) {
            $errors[] = 'La configuration par ressource n a pas pu etre enregistree.';
        } elseif (!SuiviDemandesRepository::setUserModuleStates($usersEnabled, $usersDisabled)) {
            $errors[] = 'La configuration par compte n a pas pu etre enregistree.';
        } elseif (count($errors) === 0) {
            $message = 'Configuration enregistree.';
        }
    }
}

function suivi_demandes_admin_html($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function suivi_demandes_admin_checked($enabled)
{
    return $enabled ? ' checked' : '';
}

function suivi_demandes_admin_selected($value, $expected)
{
    return $value === $expected ? ' selected' : '';
}

function suivi_demandes_admin_modal_button($modalId, $label, $class)
{
    return '<button type="button" class="'.suivi_demandes_admin_html($class).'" data-suivi-admin-modal-open="'.suivi_demandes_admin_html($modalId).'" aria-controls="'.suivi_demandes_admin_html($modalId).'">'.suivi_demandes_admin_html($label).'</button>';
}

function suivi_demandes_admin_modal($modalId, $title, $body)
{
    $safeId = suivi_demandes_admin_html($modalId);
    $titleId = $safeId.'-title';

    return '<div id="'.$safeId.'" class="suivi-demandes-admin-modal" role="dialog" aria-modal="true" aria-labelledby="'.$titleId.'">'
        .'<div class="suivi-demandes-admin-modal-dialog" role="document">'
        .'<div class="suivi-demandes-admin-modal-content">'
        .'<div class="suivi-demandes-admin-modal-header">'
        .'<h2 id="'.$titleId.'">'.suivi_demandes_admin_html($title).'</h2>'
        .'<button type="button" class="suivi-demandes-admin-modal-close" data-suivi-admin-modal-close="'.suivi_demandes_admin_html($modalId).'" aria-label="Fermer">&times;</button>'
        .'</div>'
        .'<div class="suivi-demandes-admin-modal-body">'.$body.'</div>'
        .'</div>'
        .'</div>'
        .'</div>';
}

function suivi_demandes_admin_room_ids($roomIds)
{
    $ids = array();
    if (!is_array($roomIds)) {
        $roomIds = array($roomIds);
    }

    foreach ($roomIds as $roomId) {
        $roomId = (int) $roomId;
        if ($roomId > 0) {
            $ids[$roomId] = $roomId;
        }
    }

    return array_values($ids);
}

function suivi_demandes_admin_logins($logins)
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

function suivi_demandes_admin_room_options($resources, $enabled)
{
    $html = '';
    foreach ($resources as $resource) {
        if ((bool) $resource['enabled'] !== (bool) $enabled) {
            continue;
        }

        $html .= '<option value="'.suivi_demandes_admin_html($resource['id']).'">'.suivi_demandes_admin_html($resource['label']).'</option>';
    }

    return $html;
}

function suivi_demandes_admin_user_options($users, $enabled)
{
    $html = '';
    foreach ($users as $user) {
        if ((bool) $user['enabled'] !== (bool) $enabled) {
            continue;
        }

        $label = trim((string) $user['prenom'].' '.(string) $user['nom']);
        if ($label === '') {
            $label = (string) $user['login'];
        } else {
            $label .= ' ('.$user['login'].')';
        }

        $html .= '<option value="'.suivi_demandes_admin_html($user['login']).'">'.suivi_demandes_admin_html($label).'</option>';
    }

    return $html;
}

$roomConfigResources = SuiviDemandesRepository::allResourcesWithModuleState();
$userConfigUsers = SuiviDemandesRepository::allUsersWithModuleState();
$suiviDemandesAdminAction = $suiviDemandesAdminEmbedded ? 'compte.php?pc=suivi_demandes&amp;admin=1' : 'admin.php';
$suiviDemandesAdminPostError = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && count($errors) > 0;
$suiviDemandesAttachmentsEnabled = $suiviDemandesAdminPostError ? $attachmentsEnabled : SuiviDemandesConfig::attachmentsEnabled();
$suiviDemandesAttachmentMaxMb = $suiviDemandesAdminPostError ? $attachmentMaxMb : SuiviDemandesConfig::attachmentMaxMb();
$suiviDemandesAttachmentExtensions = $suiviDemandesAdminPostError ? implode("\n", $attachmentExtensions) : SuiviDemandesConfig::attachmentExtensionsText();
$suiviDemandesNotificationLinkColors = array();
foreach (SuiviDemandesConfig::notificationLinkColorDefaults() as $status => $defaultColor) {
    $suiviDemandesNotificationLinkColors[$status] = $suiviDemandesAdminPostError && isset($notificationLinkColors[$status])
        ? $notificationLinkColors[$status]
        : SuiviDemandesConfig::notificationLinkColor($status);
}
?>
<?php if (!$suiviDemandesAdminEmbedded) { ?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Administration - Suivi des demandes</title>
<?php } ?>
    <style>
        <?php if (!$suiviDemandesAdminEmbedded) { ?>body { font-family: Arial, sans-serif; margin: 24px; color: #222; }<?php } ?>
        .suivi-demandes-admin { width: 100%; max-width: none; margin: 0; box-sizing: border-box; }
        .suivi-demandes-admin * { box-sizing: border-box; }
        .suivi-demandes-admin h1 { margin-top: 0; }
        .suivi-demandes-admin .top-links { margin-bottom: 18px; }
        .suivi-demandes-admin .top-links a { margin-right: 12px; }
        .suivi-demandes-admin .alert { padding: 10px 12px; margin: 12px 0; border-radius: 4px; }
        .suivi-demandes-admin .alert-success { background: #e7f4e4; border: 1px solid #b7dbaf; }
        .suivi-demandes-admin .alert-danger { background: #f8e1e1; border: 1px solid #dfaaaa; }
        .suivi-demandes-admin .admin-actions { display: flex; gap: 8px; flex-wrap: wrap; margin: 16px 0; }
        .suivi-demandes-admin .admin-actions .btn { white-space: normal; }
        .suivi-demandes-admin .panel { border: 1px solid #ccc; padding: 16px; margin: 16px 0; border-radius: 4px; }
        .suivi-demandes-admin .form-row { margin-bottom: 14px; }
        .suivi-demandes-admin label { display: block; font-weight: bold; margin-bottom: 4px; }
        .suivi-demandes-admin .check label { display: inline; font-weight: normal; }
        .suivi-demandes-admin input[type="text"], .suivi-demandes-admin input[type="number"] { width: 100%; max-width: 480px; padding: 7px; }
        .suivi-demandes-admin input[type="color"] { width: 72px; height: 34px; padding: 2px; }
        .suivi-demandes-admin textarea { width: 100%; max-width: 480px; padding: 7px; min-height: 110px; }
        .suivi-demandes-admin button { padding: 8px 14px; cursor: pointer; }
        .suivi-demandes-admin .muted { color: #666; font-size: 0.92em; }
        .suivi-demandes-admin .room-dual-list { display: flex; gap: 16px; align-items: center; max-width: 920px; }
        .suivi-demandes-admin .room-column { flex: 1; min-width: 0; }
        .suivi-demandes-admin .room-column select { width: 100%; min-height: 280px; }
        .suivi-demandes-admin .room-actions { display: flex; flex-direction: column; gap: 8px; }
        .suivi-demandes-admin .room-actions button { min-width: 120px; }
        .suivi-demandes-admin table { border-collapse: collapse; margin-top: 8px; width: 100%; }
        .suivi-demandes-admin th, .suivi-demandes-admin td { border: 1px solid #ddd; padding: 8px; text-align: left; overflow-wrap: anywhere; }
        .suivi-demandes-admin th { background: #f5f5f5; }
        .suivi-demandes-admin-modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background: rgba(0, 0, 0, 0.45); }
        .suivi-demandes-admin-modal.is-open { display: block; }
        .suivi-demandes-admin-modal-dialog { width: 100%; max-width: 1120px; margin: 30px auto; padding: 0 12px; }
        .suivi-demandes-admin-modal-content { background: #fff; border-radius: 4px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.22); }
        .suivi-demandes-admin-modal-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 16px; border-bottom: 1px solid #ddd; }
        .suivi-demandes-admin-modal-header h2 { margin: 0; font-size: 1.35em; }
        .suivi-demandes-admin-modal-close { border: 0; background: transparent; font-size: 28px; line-height: 1; padding: 0 4px; cursor: pointer; }
        .suivi-demandes-admin-modal-body { padding: 16px; }
        body.suivi-demandes-admin-modal-open { overflow: hidden; }
        @media (max-width: 767px) {
            .suivi-demandes-admin { padding-left: 0; padding-right: 0; }
            .suivi-demandes-admin .top-links a,
            .suivi-demandes-admin .admin-actions .btn,
            .suivi-demandes-admin .admin-actions a { display: block; width: 100%; margin: 0 0 8px; text-align: center; }
            .suivi-demandes-admin .room-dual-list { display: block; }
            .suivi-demandes-admin .room-column,
            .suivi-demandes-admin .room-actions { width: 100%; margin-bottom: 10px; }
            .suivi-demandes-admin .room-actions button { width: 100%; }
            .suivi-demandes-admin-modal-dialog { max-width: none; margin: 10px auto; padding: 0 10px; }
            .suivi-demandes-admin-modal-body { padding: 12px; }
            .suivi-demandes-admin table[data-responsive-table="1"],
            .suivi-demandes-admin table[data-responsive-table="1"] tbody,
            .suivi-demandes-admin table[data-responsive-table="1"] tr,
            .suivi-demandes-admin table[data-responsive-table="1"] th,
            .suivi-demandes-admin table[data-responsive-table="1"] td { display: block; width: 100%; }
            .suivi-demandes-admin table[data-responsive-table="1"] tr[data-responsive-head-row="1"] { display: none; }
            .suivi-demandes-admin table[data-responsive-table="1"] tr { margin: 0 0 12px; border: 1px solid #ddd; background: #fff; border-radius: 4px; overflow: hidden; }
            .suivi-demandes-admin table[data-responsive-table="1"] td { display: flex; gap: 10px; justify-content: space-between; align-items: flex-start; border: 0; border-bottom: 1px solid #eee; text-align: right; }
            .suivi-demandes-admin table[data-responsive-table="1"] td:last-child { border-bottom: 0; }
            .suivi-demandes-admin table[data-responsive-table="1"] td:before { content: attr(data-label); font-weight: bold; color: #555; text-align: left; flex: 0 0 42%; }
        }
    </style>
<?php if (!$suiviDemandesAdminEmbedded) { ?>
</head>
<body>
<?php } ?>
<div class="suivi-demandes-admin container-fluid">
    <h1>Administration - Suivi des demandes</h1>

    <div class="top-links">
        <?php if ($suiviDemandesAdminEmbedded) { ?>
            <a href="compte.php?pc=suivi_demandes">Retour au suivi des demandes</a>
            <a href="../personnalisation/modules/suivi_demandes/admin.php">Ouvrir la page autonome</a>
        <?php } else { ?>
            <a href="../../../admin/admin_config.php?p=admin_config5">Retour aux modules GRR</a>
            <a href="../../../compte/compte.php?pc=suivi_demandes">Ouvrir le module utilisateur</a>
        <?php } ?>
    </div>

    <?php if ($message !== '') { ?>
        <div class="alert alert-success"><?php echo suivi_demandes_admin_html($message); ?></div>
    <?php } ?>

    <?php foreach ($errors as $error) { ?>
        <div class="alert alert-danger"><?php echo suivi_demandes_admin_html($error); ?></div>
    <?php } ?>

    <div class="admin-actions">
        <?php echo suivi_demandes_admin_modal_button('suivi-demandes-admin-config-modal', 'Configuration du module', 'btn btn-primary'); ?>
    </div>

    <?php ob_start(); ?>
    <form id="suivi-demandes-admin-form" method="post" action="<?php echo $suiviDemandesAdminAction; ?>" onsubmit="suiviDemandesSelectAllDualListOptions();">
        <div class="panel">
            <h2>Configuration generale</h2>

            <div class="form-row">
                <label for="display_name">Nom affiche</label>
                <input id="display_name" type="text" name="display_name" maxlength="80" value="<?php echo suivi_demandes_admin_html(SuiviDemandesConfig::displayName()); ?>">
            </div>

            <div class="form-row check">
                <input id="enabled" type="checkbox" name="enabled" value="1"<?php echo suivi_demandes_admin_checked(SuiviDemandesConfig::isEnabled()); ?>>
                <label for="enabled">Activer le module cote utilisateurs</label>
                <div class="muted">Si cette option est decochee, les pages utilisateur, les integrations reservation et les notifications sont neutralisees.</div>
            </div>
        </div>

        <div class="panel">
            <h2>Points d'integration</h2>

            <div class="form-row check">
                <input id="account_enabled" type="checkbox" name="account_enabled" value="1"<?php echo suivi_demandes_admin_checked(SuiviDemandesConfig::accountEnabled()); ?>>
                <label for="account_enabled">Afficher dans Gerer mon compte</label>
            </div>

            <div class="form-row check">
                <input id="reservation_form_enabled" type="checkbox" name="reservation_form_enabled" value="1"<?php echo suivi_demandes_admin_checked(SuiviDemandesConfig::reservationFormEnabled()); ?>>
                <label for="reservation_form_enabled">Afficher dans le formulaire de reservation</label>
            </div>

            <div class="form-row check">
                <input id="reservation_detail_enabled" type="checkbox" name="reservation_detail_enabled" value="1"<?php echo suivi_demandes_admin_checked(SuiviDemandesConfig::reservationDetailEnabled()); ?>>
                <label for="reservation_detail_enabled">Afficher les demandes dans le detail d'une reservation</label>
            </div>
        </div>

        <div class="panel">
            <h2>Droits</h2>

            <div class="form-row">
                <label for="create_right">Creation de demandes</label>
                <select id="create_right" name="create_right">
                    <?php foreach (SuiviDemandesConfig::creationRightModes() as $value => $label) { ?>
                        <option value="<?php echo suivi_demandes_admin_html($value); ?>"<?php echo suivi_demandes_admin_selected(SuiviDemandesConfig::creationRight(), $value); ?>><?php echo suivi_demandes_admin_html($label); ?></option>
                    <?php } ?>
                </select>
                <div class="muted">Le droit s applique a la creation depuis Gerer mon compte et depuis le formulaire de reservation.</div>
            </div>

            <div class="form-row">
                <label for="close_right">Cloture de demandes</label>
                <select id="close_right" name="close_right">
                    <?php foreach (SuiviDemandesConfig::closeRightModes() as $value => $label) { ?>
                        <option value="<?php echo suivi_demandes_admin_html($value); ?>"<?php echo suivi_demandes_admin_selected(SuiviDemandesConfig::closeRight(), $value); ?>><?php echo suivi_demandes_admin_html($label); ?></option>
                    <?php } ?>
                </select>
                <div class="muted">Les administrateurs generaux gardent toujours le droit de cloture.</div>
            </div>
        </div>

        <div class="panel">
            <h2>Statuts</h2>

            <table>
                <tr><th>Code interne</th><th>Libelle affiche</th></tr>
                <?php foreach (SuiviDemandesConfig::statusDefinitions() as $status => $defaultLabel) { ?>
                    <tr>
                        <td><?php echo suivi_demandes_admin_html($status); ?></td>
                        <td><input type="text" name="status_label_<?php echo suivi_demandes_admin_html($status); ?>" maxlength="40" value="<?php echo suivi_demandes_admin_html(SuiviDemandesConfig::statusLabel($status)); ?>"></td>
                    </tr>
                <?php } ?>
            </table>
            <div class="muted">Les codes internes restent fixes car ils pilotent le cycle de vie des demandes.</div>
        </div>

        <div class="panel">
            <h2>Priorites</h2>

            <table>
                <tr><th>Code interne</th><th>Libelle affiche</th><th>Active</th></tr>
                <?php foreach (SuiviDemandesConfig::priorityDefinitions() as $priority => $defaultLabel) { ?>
                    <tr>
                        <td><?php echo suivi_demandes_admin_html($priority); ?></td>
                        <td><input type="text" name="priority_label_<?php echo suivi_demandes_admin_html($priority); ?>" maxlength="40" value="<?php echo suivi_demandes_admin_html(SuiviDemandesConfig::priorityLabel($priority)); ?>"></td>
                        <td><input type="checkbox" name="priority_enabled_<?php echo suivi_demandes_admin_html($priority); ?>" value="1"<?php echo suivi_demandes_admin_checked(SuiviDemandesConfig::priorityEnabled($priority)); ?>></td>
                    </tr>
                <?php } ?>
            </table>
            <div class="muted">Les codes internes restent fixes pour conserver la compatibilite avec les demandes existantes.</div>
        </div>

        <div class="panel">
            <h2>Categories</h2>

            <div class="form-row check">
                <input id="categories_enabled" type="checkbox" name="categories_enabled" value="1"<?php echo suivi_demandes_admin_checked(SuiviDemandesConfig::categoriesEnabled()); ?>>
                <label for="categories_enabled">Activer les categories</label>
                <div class="muted">Si cette option est decochee, les categories ne sont plus proposees ni affichees. Les anciennes valeurs restent conservees en base.</div>
            </div>

            <div class="form-row">
                <label for="category_options">Categories disponibles</label>
                <textarea id="category_options" name="category_options" maxlength="2400"><?php echo suivi_demandes_admin_html(SuiviDemandesConfig::categoryOptionsText()); ?></textarea>
                <div class="muted">Une categorie par ligne. Les doublons sont ignores. Les demandes peuvent aussi rester sans categorie.</div>
            </div>
        </div>

        <div class="panel">
            <h2>Ressources</h2>

            <div class="muted">Selectionnez une ou plusieurs ressources, puis utilisez les boutons pour les passer d une colonne a l autre.</div>
            <div class="room-dual-list">
                <div class="room-column">
                    <label for="suivi_rooms_enabled">Module active</label>
                    <select id="suivi_rooms_enabled" name="suivi_rooms_enabled[]" multiple size="14">
                        <?php echo suivi_demandes_admin_room_options($roomConfigResources, true); ?>
                    </select>
                </div>

                <div class="room-actions">
                    <button type="button" onclick="suiviDemandesMoveRooms('suivi_rooms_enabled', 'suivi_rooms_disabled');">Desactiver &gt;</button>
                    <button type="button" onclick="suiviDemandesMoveRooms('suivi_rooms_disabled', 'suivi_rooms_enabled');">&lt; Activer</button>
                </div>

                <div class="room-column">
                    <label for="suivi_rooms_disabled">Module desactive</label>
                    <select id="suivi_rooms_disabled" name="suivi_rooms_disabled[]" multiple size="14">
                        <?php echo suivi_demandes_admin_room_options($roomConfigResources, false); ?>
                    </select>
                </div>
            </div>
            <div class="muted">Les ressources desactivees ne sont plus proposees pour creer ou associer de nouvelles demandes. Les demandes existantes restent consultables.</div>
        </div>

        <div class="panel">
            <h2>Comptes</h2>

            <div class="muted">Selectionnez un ou plusieurs comptes, puis utilisez les boutons pour les passer d une colonne a l autre.</div>
            <div class="room-dual-list">
                <div class="room-column">
                    <label for="suivi_users_enabled">Module active</label>
                    <select id="suivi_users_enabled" name="suivi_users_enabled[]" multiple size="14">
                        <?php echo suivi_demandes_admin_user_options($userConfigUsers, true); ?>
                    </select>
                </div>

                <div class="room-actions">
                    <button type="button" onclick="suiviDemandesMoveOptions('suivi_users_enabled', 'suivi_users_disabled');">Desactiver &gt;</button>
                    <button type="button" onclick="suiviDemandesMoveOptions('suivi_users_disabled', 'suivi_users_enabled');">&lt; Activer</button>
                </div>

                <div class="room-column">
                    <label for="suivi_users_disabled">Module desactive</label>
                    <select id="suivi_users_disabled" name="suivi_users_disabled[]" multiple size="14">
                        <?php echo suivi_demandes_admin_user_options($userConfigUsers, false); ?>
                    </select>
                </div>
            </div>
            <div class="muted">Les comptes desactives ne voient plus le module dans Gerer mon compte, les compteurs en haut ni les integrations du formulaire de reservation.</div>
        </div>

        <div class="panel">
            <h2>Pieces jointes</h2>

            <div class="form-row check">
                <input type="hidden" name="attachments_enabled" value="0">
                <input id="attachments_enabled" type="checkbox" name="attachments_enabled" value="1"<?php echo suivi_demandes_admin_checked($suiviDemandesAttachmentsEnabled); ?>>
                <label for="attachments_enabled">Autoriser l'ajout de pieces jointes</label>
                <div class="muted">Si cette option est decochee, les pieces jointes existantes restent consultables mais aucun nouveau fichier ne peut etre ajoute.</div>
            </div>

            <div class="form-row">
                <label for="attachment_max_mb">Taille maximale par fichier en Mo</label>
                <input id="attachment_max_mb" type="number" name="attachment_max_mb" min="<?php echo suivi_demandes_admin_html(SuiviDemandesConfig::MIN_ATTACHMENT_MAX_MB); ?>" max="<?php echo suivi_demandes_admin_html(SuiviDemandesConfig::MAX_ATTACHMENT_MAX_MB); ?>" value="<?php echo suivi_demandes_admin_html($suiviDemandesAttachmentMaxMb); ?>">
                <div class="muted">Valeur autorisee : <?php echo suivi_demandes_admin_html(SuiviDemandesConfig::MIN_ATTACHMENT_MAX_MB); ?> a <?php echo suivi_demandes_admin_html(SuiviDemandesConfig::MAX_ATTACHMENT_MAX_MB); ?> Mo. La limite PHP du serveur reste prioritaire.</div>
            </div>

            <div class="form-row">
                <label for="attachment_extensions">Extensions autorisees</label>
                <textarea id="attachment_extensions" name="attachment_extensions" maxlength="1200"><?php echo suivi_demandes_admin_html($suiviDemandesAttachmentExtensions); ?></textarea>
                <div class="muted">Une extension par ligne, sans point. Les extensions executables ou web dangereuses sont refusees.</div>
            </div>
        </div>

        <div class="panel">
            <h2>Notifications</h2>

            <div class="form-row check">
                <input id="notifications_enabled" type="checkbox" name="notifications_enabled" value="1"<?php echo suivi_demandes_admin_checked(SuiviDemandesConfig::notificationsEnabled()); ?>>
                <label for="notifications_enabled">Activer les notifications e-mail du module</label>
                <div class="muted">Les e-mails restent soumis a la configuration mail globale de GRR.</div>
            </div>

            <?php foreach (SuiviDemandesConfig::notificationTypes() as $type => $label) { ?>
                <div class="form-row check">
                    <input id="notification_<?php echo suivi_demandes_admin_html($type); ?>" type="checkbox" name="notification_<?php echo suivi_demandes_admin_html($type); ?>" value="1"<?php echo suivi_demandes_admin_checked(SuiviDemandesConfig::notificationTypeEnabled($type)); ?>>
                    <label for="notification_<?php echo suivi_demandes_admin_html($type); ?>"><?php echo suivi_demandes_admin_html($label); ?></label>
                </div>
            <?php } ?>

            <h3>Liens de notification en haut</h3>
            <div class="muted">Ces couleurs s appliquent aux liens affiches sous les notifications de reservations en attente de moderation. Par defaut, elles reprennent les couleurs des statuts ouverts et en cours.</div>
            <table>
                <tr><th>Lien</th><th>Couleur de fond</th><th>Apercu</th></tr>
                <?php foreach (SuiviDemandesConfig::notificationLinkColorDefaults() as $status => $defaultColor) { ?>
                    <tr>
                        <td><?php echo suivi_demandes_admin_html(SuiviDemandesConfig::statusLabel($status)); ?></td>
                        <td>
                            <input id="notification_link_color_<?php echo suivi_demandes_admin_html($status); ?>" type="color" name="notification_link_color_<?php echo suivi_demandes_admin_html($status); ?>" value="<?php echo suivi_demandes_admin_html($suiviDemandesNotificationLinkColors[$status]); ?>">
                        </td>
                        <td><span style="background-color:<?php echo suivi_demandes_admin_html($suiviDemandesNotificationLinkColors[$status]); ?>; color:#fff; display:inline-block; padding:2px 6px;">1 demande <?php echo suivi_demandes_admin_html(strtolower(SuiviDemandesConfig::statusLabel($status))); ?></span></td>
                    </tr>
                <?php } ?>
            </table>
        </div>

        <button type="submit">Enregistrer</button>
    </form>
    <?php
    $suiviDemandesAdminConfigForm = ob_get_clean();
    echo suivi_demandes_admin_modal('suivi-demandes-admin-config-modal', 'Configuration du module', $suiviDemandesAdminConfigForm);
    ?>

    <div class="panel">
        <h2>Limites de cette version</h2>
        <table>
            <tr><th>Element</th><th>Etat V4.5</th></tr>
            <tr><td>Statuts</td><td>Libelles configurables, codes internes fixes</td></tr>
            <tr><td>Priorites</td><td>Libelles et disponibilite configurables</td></tr>
            <tr><td>Categories</td><td>Activables, liste configurable, categorie optionnelle par demande</td></tr>
            <tr><td>Ressources</td><td>Activation/desactivation globale par ressource</td></tr>
            <tr><td>Comptes</td><td>Activation/desactivation globale par compte actif</td></tr>
            <tr><td>Pieces jointes</td><td>Activation, taille maximale et extensions configurables</td></tr>
            <tr><td>Droits par groupe ou domaine</td><td>Non configurables</td></tr>
            <tr><td>Modeles d'e-mails</td><td>Non configurables</td></tr>
        </table>
    </div>
</div>
<script>
function suiviDemandesPrepareResponsiveAdminTables() {
    document.querySelectorAll('.suivi-demandes-admin table').forEach(function (table) {
        if (table.getAttribute('data-responsive-table') === '1') {
            return;
        }
        var headRow = table.tHead && table.tHead.rows.length ? table.tHead.rows[0] : table.rows[0];
        if (!headRow || !headRow.cells.length) {
            return;
        }
        table.setAttribute('data-responsive-table', '1');
        if (!table.tHead) {
            headRow.setAttribute('data-responsive-head-row', '1');
        }
        Array.prototype.forEach.call(table.rows, function (row) {
            if (row === headRow) {
                return;
            }
            Array.prototype.forEach.call(row.cells, function (cell, index) {
                var head = headRow.cells[index];
                if (head) {
                    cell.setAttribute('data-label', head.textContent.trim());
                }
            });
        });
    });
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', suiviDemandesPrepareResponsiveAdminTables);
} else {
    suiviDemandesPrepareResponsiveAdminTables();
}
setTimeout(suiviDemandesPrepareResponsiveAdminTables, 0);

function suiviDemandesMoveRooms(fromId, toId) {
    suiviDemandesMoveOptions(fromId, toId);
}

function suiviDemandesMoveOptions(fromId, toId) {
    var from = document.getElementById(fromId);
    var to = document.getElementById(toId);
    if (!from || !to) {
        return;
    }

    var selected = [];
    for (var index = 0; index < from.options.length; index++) {
        if (from.options[index].selected) {
            selected.push(from.options[index]);
        }
    }

    for (var i = 0; i < selected.length; i++) {
        selected[i].selected = false;
        to.appendChild(selected[i]);
    }

    suiviDemandesSortOptions(to);
}

function suiviDemandesSortRoomOptions(select) {
    suiviDemandesSortOptions(select);
}

function suiviDemandesSortOptions(select) {
    var options = [];
    for (var index = 0; index < select.options.length; index++) {
        options.push(select.options[index]);
    }

    options.sort(function (left, right) {
        return left.text.localeCompare(right.text);
    });

    for (var i = 0; i < options.length; i++) {
        select.appendChild(options[i]);
    }
}

function suiviDemandesSelectAllRoomOptions() {
    suiviDemandesSelectAllDualListOptions();
}

function suiviDemandesSelectAllDualListOptions() {
    var ids = ['suivi_rooms_enabled', 'suivi_rooms_disabled', 'suivi_users_enabled', 'suivi_users_disabled'];
    for (var i = 0; i < ids.length; i++) {
        var select = document.getElementById(ids[i]);
        if (!select) {
            continue;
        }

        for (var index = 0; index < select.options.length; index++) {
            select.options[index].selected = true;
        }
    }
}

function suiviDemandesAdminOpenModal(id) {
    var modal = document.getElementById(id);
    if (!modal) {
        return false;
    }

    modal.classList.add('is-open');
    document.body.classList.add('suivi-demandes-admin-modal-open');
    return false;
}

function suiviDemandesAdminCloseModal(id) {
    var modal = document.getElementById(id);
    if (!modal) {
        return false;
    }

    modal.classList.remove('is-open');
    document.body.classList.remove('suivi-demandes-admin-modal-open');
    return false;
}

document.addEventListener('click', function (event) {
    var opener = event.target.closest('[data-suivi-admin-modal-open]');
    if (opener) {
        event.preventDefault();
        suiviDemandesAdminOpenModal(opener.getAttribute('data-suivi-admin-modal-open'));
        return;
    }

    var closer = event.target.closest('[data-suivi-admin-modal-close]');
    if (closer) {
        event.preventDefault();
        suiviDemandesAdminCloseModal(closer.getAttribute('data-suivi-admin-modal-close'));
        return;
    }

    if (event.target.classList && event.target.classList.contains('suivi-demandes-admin-modal')) {
        suiviDemandesAdminCloseModal(event.target.id);
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') {
        return;
    }

    var modals = document.querySelectorAll('.suivi-demandes-admin-modal.is-open');
    for (var index = 0; index < modals.length; index++) {
        suiviDemandesAdminCloseModal(modals[index].id);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    <?php if ($suiviDemandesAdminPostError) { ?>
    suiviDemandesAdminOpenModal('suivi-demandes-admin-config-modal');
    <?php } ?>
});
</script>
<?php if (!$suiviDemandesAdminEmbedded) { ?>
</body>
</html>
<?php } ?>
