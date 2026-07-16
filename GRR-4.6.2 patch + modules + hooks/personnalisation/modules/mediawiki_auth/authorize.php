<?php

require_once __DIR__.'/lib/bootstrap.php';
$sessionOk = grr_mediawiki_auth_bootstrap(true);
require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/UrlPolicy.php';
require_once __DIR__.'/lib/AccessToken.php';
require_once __DIR__.'/lib/AccessCookie.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if (GrrMediaWikiAuthConfig::hasEnvironmentMismatch()) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    exit(
        'Configuration GRR/MediaWiki incohérente pour cette instance. '
        .'Ouvrez l’administration du module mediawiki_auth.'
    );
}

$returnTarget = GrrMediaWikiAuthUrlPolicy::normalizeReturnTarget(
    isset($_GET['return']) ? $_GET['return'] : ''
);
if ($returnTarget === null) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('URL de retour MediaWiki refusée.');
}

if (!GrrMediaWikiAuthConfig::isEnabled()) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Passerelle MediaWiki désactivée.');
}

if (!$sessionOk || getUserName() === '') {
    $callbackPath = GrrMediaWikiAuthConfig::authorizePath()
        .'?'.http_build_query(array('return' => $returnTarget), '', '&', PHP_QUERY_RFC3986);
    header('Location: '.GrrMediaWikiAuthConfig::loginPath($callbackPath), true, 302);
    exit;
}

if (!GrrMediaWikiAuthConfig::isSecureRequest()) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('HTTPS est obligatoire pour la passerelle MediaWiki.');
}

$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
if (!GrrMediaWikiAccessCookie::issue($userAgent)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    exit('Impossible de créer le cookie d’accès MediaWiki.');
}

header('Location: '.$returnTarget, true, 302);
exit;
