<?php
require_once __DIR__.'/lib/bootstrap.php';
grr_ics_bootstrap(true);
require_once __DIR__.'/lib/ModuleConfig.php';

$userName = getUserName();
if ($userName === '') {
    header('Location: ../../../app.php?p=login');
    exit;
}

$canAdmin = (SecuAccess::UserLevel($userName, -1, 'area') >= 4) || (SecuAccess::UserLevel($userName, -1, 'user') == 1);
if (!$canAdmin) {
    http_response_code(403);
    exit('Acces refuse');
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['regenerate_secret'])) {
        GrrIcsConfig::regenerateSecret();
        $message = 'Tous les liens ICS ont ete regeneres.';
    } else {
        GrrIcsConfig::set('enabled', isset($_POST['enabled']) ? '1' : '0');
        GrrIcsConfig::set('privacy', isset($_POST['privacy']) ? $_POST['privacy'] : 'busy');
        GrrIcsConfig::set('past_days', isset($_POST['past_days']) ? max(0, (int) $_POST['past_days']) : 30);
        GrrIcsConfig::set('future_days', isset($_POST['future_days']) ? max(1, (int) $_POST['future_days']) : 365);
        GrrIcsConfig::set('include_moderated', isset($_POST['include_moderated']) ? '1' : '0');
        GrrIcsConfig::set('include_option', isset($_POST['include_option']) ? '1' : '0');
        GrrIcsConfig::set('include_inactive_rooms', isset($_POST['include_inactive_rooms']) ? '1' : '0');
        GrrIcsConfig::setEnabledRooms(isset($_POST['enabled_rooms']) ? $_POST['enabled_rooms'] : array());
        $message = 'Configuration enregistree.';
    }
}

$rooms = GrrIcsConfig::rooms();
$disabledRooms = GrrIcsConfig::disabledRooms();
$baseUrl = GrrIcsConfig::moduleBaseUrl();

function grr_ics_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Feeds ICS par ressource</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #222; }
        h1 { font-size: 24px; }
        fieldset { border: 1px solid #ccc; margin: 0 0 18px; padding: 16px; }
        label { display: block; margin: 8px 0; }
        input[type="number"] { width: 90px; }
        input[type="text"] { width: 100%; box-sizing: border-box; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; vertical-align: top; }
        th { background: #f5f5f5; text-align: left; }
        .message { background: #e9f7ef; border: 1px solid #a9dfbf; padding: 10px; margin-bottom: 16px; }
        .actions { margin: 16px 0; }
        button { padding: 6px 12px; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <h1>Feeds ICS par ressource</h1>

    <?php if ($message !== ''): ?>
        <div class="message"><?php echo grr_ics_h($message); ?></div>
    <?php endif; ?>

    <form method="post">
        <fieldset>
            <legend>Configuration</legend>
            <label><input type="checkbox" name="enabled" value="1" <?php echo GrrIcsConfig::isEnabled() ? 'checked' : ''; ?>> Activer les feeds ICS</label>
            <label>Confidentialite
                <select name="privacy">
                    <option value="busy" <?php echo GrrIcsConfig::privacy() === 'busy' ? 'selected' : ''; ?>>Occupe uniquement</option>
                    <option value="title" <?php echo GrrIcsConfig::privacy() === 'title' ? 'selected' : ''; ?>>Titre de la reservation</option>
                    <option value="full" <?php echo GrrIcsConfig::privacy() === 'full' ? 'selected' : ''; ?>>Titre + description + beneficiaire</option>
                </select>
            </label>
            <label>Jours passes inclus <input type="number" min="0" name="past_days" value="<?php echo grr_ics_h(GrrIcsConfig::pastDays()); ?>"></label>
            <label>Jours futurs inclus <input type="number" min="1" name="future_days" value="<?php echo grr_ics_h(GrrIcsConfig::futureDays()); ?>"></label>
            <label><input type="checkbox" name="include_moderated" value="1" <?php echo GrrIcsConfig::includeModerated() ? 'checked' : ''; ?>> Inclure les reservations moderees</label>
            <label><input type="checkbox" name="include_option" value="1" <?php echo GrrIcsConfig::includeOption() ? 'checked' : ''; ?>> Inclure les reservations optionnelles</label>
            <label><input type="checkbox" name="include_inactive_rooms" value="1" <?php echo GrrIcsConfig::includeInactiveRooms() ? 'checked' : ''; ?>> Autoriser les ressources inactives</label>
        </fieldset>

        <fieldset>
            <legend>Ressources publiees</legend>
            <p class="muted">Decochez une ressource pour desactiver son feed.</p>
            <table>
                <thead>
                    <tr>
                        <th>Publiee</th>
                        <th>Domaine</th>
                        <th>Ressource</th>
                        <th>URL ICS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <?php $enabled = !in_array((int) $room['id'], $disabledRooms, true); ?>
                        <tr>
                            <td><input type="checkbox" name="enabled_rooms[]" value="<?php echo (int) $room['id']; ?>" <?php echo $enabled ? 'checked' : ''; ?>></td>
                            <td><?php echo grr_ics_h($room['area_name']); ?></td>
                            <td><?php echo grr_ics_h($room['room_name']); ?></td>
                            <td><input type="text" readonly value="<?php echo grr_ics_h(GrrIcsConfig::feedUrl($room['id'], $baseUrl)); ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </fieldset>

        <div class="actions">
            <button type="submit">Enregistrer</button>
        </div>
    </form>

    <form method="post" onsubmit="return confirm('Regenerer le secret invalidera tous les anciens liens ICS. Continuer ?');">
        <button type="submit" name="regenerate_secret" value="1">Regenerer tous les liens</button>
    </form>
</body>
</html>
