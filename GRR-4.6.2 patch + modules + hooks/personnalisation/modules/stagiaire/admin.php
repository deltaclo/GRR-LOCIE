<?php

$stagiaireAdminEmbedded = isset($stagiaire_admin_embedded) && $stagiaire_admin_embedded;

require_once __DIR__.'/lib/bootstrap.php';

$sessionOk = $stagiaireAdminEmbedded ? true : grr_stagiaire_bootstrap(true);

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';

$stagiaireModuleVersion = '';
$stagiaireInfoPath = __DIR__.'/infos.php';
if (is_file($stagiaireInfoPath)) {
    include $stagiaireInfoPath;
    $stagiaireModuleVersion = isset($module_version) ? (string) $module_version : '';
}

if (!$sessionOk) {
    header('Location: ../../../index.php');
    exit;
}

$login = function_exists('getUserName') ? (string) getUserName() : '';
if ($login === '' || SecuAccess::UserLevel($login, -1) < 6) {
    if ($stagiaireAdminEmbedded) {
        echo '<div class="alert alert-warning">Acces refuse.</div>';
        return;
    }

    http_response_code(403);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Acces refuse</title></head><body>';
    echo '<h1>Acces refuse</h1><p>Cette page est reservee aux administrateurs generaux GRR.</p>';
    echo '</body></html>';
    exit;
}

StagiaireRepository::ensureTables();

$message = '';
$errors = array();

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $displayName = isset($_POST['display_name']) ? trim((string) $_POST['display_name']) : '';
    $selectedLogins = isset($_POST['stagiaire_logins']) ? $_POST['stagiaire_logins'] : array();

    if ($displayName === '') {
        $errors[] = 'Le nom affiche est obligatoire.';
    } elseif (strlen($displayName) > 80) {
        $errors[] = 'Le nom affiche ne doit pas depasser 80 caracteres.';
    }

    $validLogins = StagiaireRepository::validUserLogins($selectedLogins);

    if (count($errors) === 0) {
        StagiaireConfig::set('display_name', $displayName);
        StagiaireConfig::set('enabled', isset($_POST['enabled']) ? '1' : '0');
        StagiaireConfig::set('form_enabled', isset($_POST['form_enabled']) ? '1' : '0');
        StagiaireConfig::set('detail_enabled', isset($_POST['detail_enabled']) ? '1' : '0');
        StagiaireConfig::set('mail_enabled', isset($_POST['mail_enabled']) ? '1' : '0');

        if (!StagiaireRepository::setStagiaireLogins($validLogins, $login)) {
            $errors[] = 'La liste des comptes stagiaires n a pas pu etre enregistree.';
        } else {
            $message = 'Configuration enregistree.';
        }
    }
}

$users = StagiaireRepository::activeUsersWithStagiaireStatus();
$selectedCount = 0;
foreach ($users as $user) {
    if (!empty($user['is_stagiaire'])) {
        $selectedCount++;
    }
}
$diagnostics = stagiaire_admin_diagnostics();
$stagiaireAdminAction = $stagiaireAdminEmbedded ? 'compte.php?pc=stagiaire&amp;admin=1' : 'admin.php';

function stagiaire_admin_html($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function stagiaire_admin_checked($enabled)
{
    return $enabled ? ' checked' : '';
}

function stagiaire_admin_diagnostics()
{
    global $stagiaireModuleVersion;

    $rows = array();

    $rows[] = stagiaire_admin_diag_row(
        'Version module',
        $stagiaireModuleVersion !== '',
        $stagiaireModuleVersion !== '' ? 'Version detectee : '.$stagiaireModuleVersion.'.' : 'Version module non detectee.',
        $stagiaireModuleVersion !== '' ? 'ok' : 'warn'
    );
    $rows[] = stagiaire_admin_diag_row(
        'Version PHP',
        version_compare(PHP_VERSION, '7.4.0', '>='),
        'Version PHP detectee : '.PHP_VERSION.'.',
        version_compare(PHP_VERSION, '7.4.0', '>=') ? 'ok' : 'warn'
    );

    $rows[] = stagiaire_admin_diag_row(
        'Module actif',
        StagiaireConfig::isEnabled(),
        StagiaireConfig::isEnabled() ? 'Le module est actif.' : 'Le module est desactive dans sa configuration.',
        StagiaireConfig::isEnabled() ? 'ok' : 'warn'
    );
    $rows[] = stagiaire_admin_diag_row(
        'Formulaire de reservation',
        StagiaireConfig::formEnabled(),
        StagiaireConfig::formEnabled() ? 'Le bloc stagiaire peut etre affiche dans le formulaire.' : 'Le bloc stagiaire est masque dans le formulaire.',
        StagiaireConfig::formEnabled() ? 'ok' : 'warn'
    );
    $rows[] = stagiaire_admin_diag_row(
        'Detail de reservation',
        StagiaireConfig::detailEnabled(),
        StagiaireConfig::detailEnabled() ? 'Les informations stagiaire peuvent etre affichees dans le detail.' : 'L affichage dans le detail est desactive.',
        StagiaireConfig::detailEnabled() ? 'ok' : 'warn'
    );
    $rows[] = stagiaire_admin_diag_row(
        'Option mail du module',
        StagiaireConfig::mailEnabled(),
        StagiaireConfig::mailEnabled() ? 'Les mails stagiaire sont autorises par le module.' : 'Les mails stagiaire sont desactives dans le module.',
        StagiaireConfig::mailEnabled() ? 'ok' : 'warn'
    );

    $automaticMail = Settings::get('automatic_mail') === 'yes';
    $mailMethod = (string) Settings::get('grr_mail_method');
    $mailMethodOk = $mailMethod === 'smtp' || $mailMethod === 'mail';
    $rows[] = stagiaire_admin_diag_row(
        'Mails automatiques GRR',
        $automaticMail && $mailMethodOk,
        $automaticMail
            ? 'Methode mail GRR : '.$mailMethod.'.'
            : 'Les mails automatiques GRR sont desactives.',
        ($automaticMail && $mailMethodOk) ? 'ok' : 'warn'
    );

    $hasUserTable = StagiaireRepository::tableExists('stagiaire_user');
    $hasReservationTable = StagiaireRepository::tableExists('stagiaire_reservation');
    $rows[] = stagiaire_admin_diag_row(
        'Table stagiaire_user',
        $hasUserTable,
        'Table '.TABLE_PREFIX.'_stagiaire_user.',
        $hasUserTable ? 'ok' : 'ko'
    );
    $rows[] = stagiaire_admin_diag_row(
        'Table stagiaire_reservation',
        $hasReservationTable,
        'Table '.TABLE_PREFIX.'_stagiaire_reservation.',
        $hasReservationTable ? 'ok' : 'ko'
    );

    $hasFormHook = stagiaire_admin_file_contains('reservation/controleurs/editentree.php', 'hookEditEntreeForm');
    $hasValidateHook = stagiaire_admin_file_contains('reservation/controleurs/editentreetrt.php', 'hookEditEntreeValidate');
    $hasTrtHook = stagiaire_admin_file_contains('reservation/controleurs/editentreetrt.php', 'hookEditEntreeTrt');
    $hasEntryIdsContext = stagiaire_admin_file_contains('reservation/controleurs/editentreetrt.php', "'entry_ids'");
    $hasDetailHook = stagiaire_admin_file_contains('reservation/controleurs/vuereservation.php', 'hookVueReservation');
    $hasModerationHook = stagiaire_admin_file_contains('include/mrbs_sql.inc.php', 'hookModerateEntry');
    $hasDeleteHook = stagiaire_admin_file_contains('reservation/controleurs/supreservation.php', 'hookDeleteEntry');

    $rows[] = stagiaire_admin_diag_row(
        'Hook formulaire',
        $hasFormHook,
        'reservation/controleurs/editentree.php doit appeler hookEditEntreeForm.',
        $hasFormHook ? 'ok' : 'ko'
    );
    $rows[] = stagiaire_admin_diag_row(
        'Hook validation et traitement',
        $hasValidateHook && $hasTrtHook,
        'reservation/controleurs/editentreetrt.php doit appeler hookEditEntreeValidate et hookEditEntreeTrt.',
        ($hasValidateHook && $hasTrtHook) ? 'ok' : 'ko'
    );
    $rows[] = stagiaire_admin_diag_row(
        'Contexte series',
        $hasEntryIdsContext,
        'reservation/controleurs/editentreetrt.php doit transmettre entry_ids pour les reservations repetees.',
        $hasEntryIdsContext ? 'ok' : 'ko'
    );
    $rows[] = stagiaire_admin_diag_row(
        'Hook detail',
        $hasDetailHook,
        'reservation/controleurs/vuereservation.php doit appeler hookVueReservation.',
        $hasDetailHook ? 'ok' : 'ko'
    );
    $rows[] = stagiaire_admin_diag_row(
        'Hook moderation',
        $hasModerationHook,
        'include/mrbs_sql.inc.php doit appeler hookModerateEntry.',
        $hasModerationHook ? 'ok' : 'ko'
    );
    $rows[] = stagiaire_admin_diag_row(
        'Hook suppression',
        $hasDeleteHook,
        'reservation/controleurs/supreservation.php doit appeler hookDeleteEntry.',
        $hasDeleteHook ? 'ok' : 'ko'
    );

    return $rows;
}

function stagiaire_admin_diag_row($label, $ok, $message, $status)
{
    return array(
        'label' => $label,
        'state' => $ok ? 'OK' : 'A verifier',
        'message' => $message,
        'status' => $status,
    );
}

function stagiaire_admin_file_contains($relativePath, $needle)
{
    if (!defined('GRR_STAGIAIRE_ROOT')) {
        return false;
    }

    $path = GRR_STAGIAIRE_ROOT.'/'.str_replace('\\', '/', $relativePath);
    if (!is_file($path) || !is_readable($path)) {
        return false;
    }

    $content = @file_get_contents($path);
    return is_string($content) && strpos($content, $needle) !== false;
}

?>
<?php if (!$stagiaireAdminEmbedded) { ?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo stagiaire_admin_html(StagiaireConfig::displayName()); ?></title>
<?php } ?>
    <style>
        <?php if (!$stagiaireAdminEmbedded) { ?>body { font-family: Arial, sans-serif; margin: 24px; color: #222; }<?php } ?>
        .stagiaire-admin { width: 100%; max-width: none; margin: 0; box-sizing: border-box; }
        .stagiaire-admin * { box-sizing: border-box; }
        .stagiaire-admin h1 { margin-top: 0; }
        .stagiaire-admin .top-links { margin-bottom: 18px; }
        .stagiaire-admin .top-links a { margin-right: 12px; }
        .stagiaire-admin fieldset { border: 1px solid #ccc; margin: 0 0 18px; padding: 16px; }
        .stagiaire-admin legend { font-weight: bold; padding: 0 6px; }
        .stagiaire-admin label { display: inline-block; margin: 4px 0; }
        .stagiaire-admin input[type="text"] { width: 100%; max-width: 520px; min-width: 0; padding: 6px; }
        .stagiaire-admin table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        .stagiaire-admin th, .stagiaire-admin td { border: 1px solid #ddd; padding: 7px; text-align: left; overflow-wrap: anywhere; }
        .stagiaire-admin th { background: #f5f5f5; }
        .stagiaire-admin .message { background: #e7f5e7; border: 1px solid #8bc48b; padding: 10px; margin-bottom: 12px; }
        .stagiaire-admin .error { background: #fbeaea; border: 1px solid #d99; padding: 10px; margin-bottom: 12px; }
        .stagiaire-admin .muted { color: #666; font-size: 0.92em; }
        .stagiaire-admin .actions { margin-top: 18px; }
        .stagiaire-admin .button { background: #2f6f9f; border: 0; color: #fff; cursor: pointer; padding: 8px 14px; }
        .stagiaire-admin .status-ok { color: #146c2e; font-weight: bold; }
        .stagiaire-admin .status-warn { color: #8a5a00; font-weight: bold; }
        .stagiaire-admin .status-ko { color: #9b1c1c; font-weight: bold; }
        @media (max-width: 767px) {
            .stagiaire-admin { padding-left: 0; padding-right: 0; }
            .stagiaire-admin .top-links a, .stagiaire-admin .button { display: block; width: 100%; margin: 0 0 8px; text-align: center; }
            .stagiaire-admin table[data-responsive-table="1"],
            .stagiaire-admin table[data-responsive-table="1"] thead,
            .stagiaire-admin table[data-responsive-table="1"] tbody,
            .stagiaire-admin table[data-responsive-table="1"] tr,
            .stagiaire-admin table[data-responsive-table="1"] th,
            .stagiaire-admin table[data-responsive-table="1"] td { display: block; width: 100%; }
            .stagiaire-admin table[data-responsive-table="1"] thead { display: none; }
            .stagiaire-admin table[data-responsive-table="1"] tr { margin: 0 0 12px; border: 1px solid #ddd; background: #fff; border-radius: 4px; overflow: hidden; }
            .stagiaire-admin table[data-responsive-table="1"] td { display: flex; gap: 10px; justify-content: space-between; align-items: flex-start; border: 0; border-bottom: 1px solid #eee; text-align: right; }
            .stagiaire-admin table[data-responsive-table="1"] td:last-child { border-bottom: 0; }
            .stagiaire-admin table[data-responsive-table="1"] td:before { content: attr(data-label); font-weight: bold; color: #555; text-align: left; flex: 0 0 42%; }
        }
    </style>
    <script>
        (function () {
            if (window.stagiaireAdminResponsiveReady) {
                return;
            }
            window.stagiaireAdminResponsiveReady = true;
            function prepareTables() {
                document.querySelectorAll('.stagiaire-admin table').forEach(function (table) {
                    if (table.getAttribute('data-responsive-table') === '1') {
                        return;
                    }
                    var heads = table.tHead && table.tHead.rows.length ? table.tHead.rows[0].cells : [];
                    if (!heads.length) {
                        return;
                    }
                    table.setAttribute('data-responsive-table', '1');
                    Array.prototype.forEach.call(table.tBodies, function (body) {
                        Array.prototype.forEach.call(body.rows, function (row) {
                            Array.prototype.forEach.call(row.cells, function (cell, index) {
                                var head = heads[index];
                                if (head) {
                                    cell.setAttribute('data-label', head.textContent.trim());
                                }
                            });
                        });
                    });
                });
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', prepareTables);
            } else {
                prepareTables();
            }
            setTimeout(prepareTables, 0);
        }());
    </script>
<?php if (!$stagiaireAdminEmbedded) { ?>
</head>
<body>
<?php } ?>
<div class="stagiaire-admin container-fluid">
    <h1>Administration - <?php echo stagiaire_admin_html(StagiaireConfig::displayName()); ?></h1>

    <div class="top-links">
        <?php if ($stagiaireAdminEmbedded) { ?>
            <a href="compte.php?pc=stagiaire">Retour</a>
            <a href="../personnalisation/modules/stagiaire/admin.php">Ouvrir la page autonome</a>
        <?php } else { ?>
            <a href="../../../compte/compte.php?pc=stagiaire&amp;admin=1">Ouvrir dans Gerer mon compte</a>
        <?php } ?>
    </div>

    <?php if ($message !== ''): ?>
        <div class="message"><?php echo stagiaire_admin_html($message); ?></div>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <div class="error"><?php echo stagiaire_admin_html($error); ?></div>
    <?php endforeach; ?>

    <form method="post" action="<?php echo $stagiaireAdminAction; ?>">
        <fieldset>
            <legend>Configuration generale</legend>

            <p>
                <label for="display_name">Nom affiche</label><br>
                <input id="display_name" type="text" name="display_name" maxlength="80" value="<?php echo stagiaire_admin_html(StagiaireConfig::displayName()); ?>" required>
            </p>

            <p>
                <label><input type="checkbox" name="enabled" value="1"<?php echo stagiaire_admin_checked(StagiaireConfig::isEnabled()); ?>> Activer le module</label><br>
                <label><input type="checkbox" name="form_enabled" value="1"<?php echo stagiaire_admin_checked(StagiaireConfig::formEnabled()); ?>> Afficher les champs dans le formulaire de reservation</label><br>
                <label><input type="checkbox" name="detail_enabled" value="1"<?php echo stagiaire_admin_checked(StagiaireConfig::detailEnabled()); ?>> Afficher les informations stagiaire dans le detail de reservation</label><br>
                <label><input type="checkbox" name="mail_enabled" value="1"<?php echo stagiaire_admin_checked(StagiaireConfig::mailEnabled()); ?>> Envoyer les confirmations e-mail au stagiaire</label>
            </p>
            <p class="muted">Les e-mails resteront soumis a la configuration mail globale de GRR.</p>
        </fieldset>

        <fieldset>
            <legend>Diagnostic</legend>
            <p class="muted">Ce diagnostic aide a verifier rapidement les prerequis du module sur l installation courante.</p>
            <table>
                <thead>
                    <tr>
                        <th>Controle</th>
                        <th>Etat</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diagnostics as $diagnostic): ?>
                        <tr>
                            <td><?php echo stagiaire_admin_html($diagnostic['label']); ?></td>
                            <td class="status-<?php echo stagiaire_admin_html($diagnostic['status']); ?>"><?php echo stagiaire_admin_html($diagnostic['state']); ?></td>
                            <td><?php echo stagiaire_admin_html($diagnostic['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </fieldset>

        <fieldset>
            <legend>Comptes stagiaires</legend>
            <p class="muted"><?php echo stagiaire_admin_html($selectedCount); ?> compte(s) selectionne(s). Seuls les comptes actifs sont affiches.</p>

            <table>
                <thead>
                    <tr>
                        <th>Stagiaire</th>
                        <th>Utilisateur</th>
                        <th>Statut GRR</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) === 0): ?>
                        <tr><td colspan="3">Aucun utilisateur actif trouve.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="stagiaire_logins[]" value="<?php echo stagiaire_admin_html($user['login']); ?>"<?php echo stagiaire_admin_checked(!empty($user['is_stagiaire'])); ?>>
                            </td>
                            <td><?php echo stagiaire_admin_html($user['label']); ?></td>
                            <td><?php echo stagiaire_admin_html(isset($user['statut']) ? $user['statut'] : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </fieldset>

        <div class="actions">
            <button class="button" type="submit">Enregistrer</button>
        </div>
    </form>
</div>
<?php if (!$stagiaireAdminEmbedded) { ?>
</body>
</html>
<?php } ?>
