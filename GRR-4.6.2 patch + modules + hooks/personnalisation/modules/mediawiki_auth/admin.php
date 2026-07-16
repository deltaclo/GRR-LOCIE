<?php

require_once __DIR__.'/lib/bootstrap.php';
$sessionOk = grr_mediawiki_auth_bootstrap(true);
require_once __DIR__.'/lib/Config.php';

if (!$sessionOk || getUserName() === '') {
    $callbackPath = GrrMediaWikiAuthConfig::modulePath().'/admin.php';
    header('Location: '.GrrMediaWikiAuthConfig::loginPath($callbackPath));
    exit;
}

$userName = getUserName();
$canAdmin = SecuAccess::UserLevel($userName, -1) >= 6;
if (!$canAdmin) {
    http_response_code(403);
    exit('Accès refusé');
}

if (empty($_SESSION['mediawiki_auth_admin_token'])) {
    try {
        $_SESSION['mediawiki_auth_admin_token'] = bin2hex(random_bytes(24));
    } catch (Throwable $exception) {
        $_SESSION['mediawiki_auth_admin_token'] = hash('sha256', uniqid('', true).mt_rand());
    }
}

$message = '';
$errors = array();
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
    if (!hash_equals((string) $_SESSION['mediawiki_auth_admin_token'], $postedToken)) {
        $errors[] = 'Session de formulaire invalide. Rechargez la page.';
    } elseif (isset($_POST['regenerate_secret'])) {
        GrrMediaWikiAuthConfig::regenerateSecret();
        $message = 'Secret renouvelé. Les preuves déjà émises sont invalidées.';
    } else {
        $allowedPath = GrrMediaWikiAuthConfig::normalizePathSetting(
            isset($_POST['allowed_path']) ? $_POST['allowed_path'] : '',
            ''
        );
        $cookiePath = GrrMediaWikiAuthConfig::normalizePathSetting(
            isset($_POST['cookie_path']) ? $_POST['cookie_path'] : '',
            ''
        );
        $cookieName = isset($_POST['cookie_name']) ? trim((string) $_POST['cookie_name']) : '';
        $audience = isset($_POST['audience']) ? trim((string) $_POST['audience']) : '';
        $ttl = isset($_POST['ttl']) ? (int) $_POST['ttl'] : 120;

        if (!GrrMediaWikiAuthConfig::isAllowedDeploymentPath($allowedPath)) {
            $errors[] = 'Le chemin autorisé attendu pour cette instance est '
                .GrrMediaWikiAuthConfig::defaultAllowedPath().'.';
        }
        if ($cookiePath !== $allowedPath) {
            $errors[] = 'Le chemin du cookie doit être identique au chemin MediaWiki autorisé.';
        }
        if (!GrrMediaWikiAuthConfig::isValidCookieName($cookieName)) {
            $errors[] = 'Nom de cookie invalide.';
        }
        if (!GrrMediaWikiAuthConfig::isValidAudience($audience)) {
            $errors[] = 'Audience invalide.';
        }
        if ($ttl < 30 || $ttl > 600) {
            $errors[] = 'La durée doit être comprise entre 30 et 600 secondes.';
        }

        if (count($errors) === 0) {
            $results = array(
                GrrMediaWikiAuthConfig::set('enabled', isset($_POST['enabled']) ? '1' : '0'),
                GrrMediaWikiAuthConfig::set('allowed_path', $allowedPath),
                GrrMediaWikiAuthConfig::set('cookie_path', $cookiePath),
                GrrMediaWikiAuthConfig::set('cookie_name', $cookieName),
                GrrMediaWikiAuthConfig::set('audience', $audience),
                GrrMediaWikiAuthConfig::set('ttl', (string) $ttl),
            );
            if (in_array(false, $results, true)) {
                $errors[] = 'Une option n’a pas pu être enregistrée.';
            } else {
                $message = 'Configuration enregistrée.';
            }
        }
    }
}

function grr_mediawiki_auth_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$testUrl = GrrMediaWikiAuthConfig::authorizePath()
    .'?'.http_build_query(
        array('return' => GrrMediaWikiAuthConfig::allowedPath()),
        '',
        '&',
        PHP_QUERY_RFC3986
    );
$environmentMismatch = GrrMediaWikiAuthConfig::hasEnvironmentMismatch();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Accès MediaWiki via GRR</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #222; }
        main { max-width: 900px; }
        fieldset { border: 1px solid #ccc; margin: 0 0 18px; padding: 16px; }
        label { display: block; margin: 10px 0; }
        input[type="text"], input[type="number"] { width: 100%; max-width: 700px; box-sizing: border-box; }
        .message { background: #e9f7ef; border: 1px solid #a9dfbf; padding: 10px; margin-bottom: 16px; }
        .error { background: #fdecea; border: 1px solid #e6b0aa; padding: 10px; margin-bottom: 8px; }
        .warning { background: #fff8e1; border: 1px solid #e5c46b; padding: 10px; margin: 16px 0; }
        code, .secret { font-family: Consolas, monospace; word-break: break-all; }
        button { padding: 7px 14px; }
    </style>
</head>
<body>
<main>
    <h1>Accès MediaWiki via GRR</h1>

    <?php if ($message !== ''): ?>
        <div class="message"><?php echo grr_mediawiki_auth_h($message); ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $error): ?>
        <div class="error"><?php echo grr_mediawiki_auth_h($error); ?></div>
    <?php endforeach; ?>
    <?php if ($environmentMismatch): ?>
        <div class="error">
            La configuration enregistrée ne correspond pas à cette instance GRR.
            Valeurs attendues :
            chemin <code><?php echo grr_mediawiki_auth_h(GrrMediaWikiAuthConfig::defaultAllowedPath()); ?></code>,
            cookie <code><?php echo grr_mediawiki_auth_h(GrrMediaWikiAuthConfig::defaultCookieName()); ?></code>,
            audience <code><?php echo grr_mediawiki_auth_h(GrrMediaWikiAuthConfig::defaultAudience()); ?></code>.
            Enregistrez ces valeurs avant de tester MediaWiki.
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo grr_mediawiki_auth_h($_SESSION['mediawiki_auth_admin_token']); ?>">
        <fieldset>
            <legend>Configuration de l'environnement</legend>
            <label>
                <input type="checkbox" name="enabled" value="1" <?php echo GrrMediaWikiAuthConfig::isEnabled() ? 'checked' : ''; ?>>
                Passerelle activée
            </label>
            <label>
                Chemin MediaWiki autorisé
                <input type="text" name="allowed_path" value="<?php echo grr_mediawiki_auth_h(GrrMediaWikiAuthConfig::allowedPath()); ?>">
            </label>
            <label>
                Chemin du cookie
                <input type="text" name="cookie_path" value="<?php echo grr_mediawiki_auth_h(GrrMediaWikiAuthConfig::cookiePath()); ?>">
            </label>
            <label>
                Nom du cookie
                <input type="text" name="cookie_name" value="<?php echo grr_mediawiki_auth_h(GrrMediaWikiAuthConfig::cookieName()); ?>">
            </label>
            <label>
                Audience signée
                <input type="text" name="audience" value="<?php echo grr_mediawiki_auth_h(GrrMediaWikiAuthConfig::audience()); ?>">
            </label>
            <label>
                Durée de la preuve, en secondes
                <input type="number" min="30" max="600" name="ttl" value="<?php echo (int) GrrMediaWikiAuthConfig::ttl(); ?>">
            </label>
            <button type="submit">Enregistrer</button>
        </fieldset>
    </form>

    <fieldset>
        <legend>Diagnostic</legend>
        <p>URL de test : <a href="<?php echo grr_mediawiki_auth_h($testUrl); ?>"><?php echo grr_mediawiki_auth_h($testUrl); ?></a></p>
        <p>Cookie attendu : <code><?php echo grr_mediawiki_auth_h(GrrMediaWikiAuthConfig::cookieName()); ?></code></p>
        <p>Durée maximale actuelle : <?php echo (int) GrrMediaWikiAuthConfig::ttl(); ?> secondes.</p>
    </fieldset>

    <fieldset>
        <legend>Secret partagé</legend>
        <div class="warning">
            Ce secret devra être copié dans la configuration de l’extension MediaWiki à l’étape 4.
            Il ne doit jamais être placé dans une page wiki, un journal ou un message public.
        </div>
        <p class="secret"><?php echo grr_mediawiki_auth_h(GrrMediaWikiAuthConfig::secret()); ?></p>
        <form method="post" onsubmit="return confirm('Renouveler le secret invalidera les preuves déjà émises. Continuer ?');">
            <input type="hidden" name="csrf_token" value="<?php echo grr_mediawiki_auth_h($_SESSION['mediawiki_auth_admin_token']); ?>">
            <button type="submit" name="regenerate_secret" value="1">Renouveler le secret</button>
        </form>
    </fieldset>
</main>
</body>
</html>
