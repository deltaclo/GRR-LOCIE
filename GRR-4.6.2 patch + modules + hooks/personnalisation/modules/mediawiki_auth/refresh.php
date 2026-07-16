<?php

require_once __DIR__.'/lib/bootstrap.php';
$sessionOk = grr_mediawiki_auth_bootstrap(true);
require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/AccessToken.php';
require_once __DIR__.'/lib/AccessCookie.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

if (GrrMediaWikiAuthConfig::hasEnvironmentMismatch()) {
    http_response_code(503);
    exit;
}

if (!GrrMediaWikiAuthConfig::isEnabled()) {
    http_response_code(503);
    exit;
}

if (!$sessionOk || getUserName() === '') {
    GrrMediaWikiAccessCookie::clear();
    http_response_code(401);
    exit;
}

if (!GrrMediaWikiAuthConfig::isSecureRequest()) {
    http_response_code(503);
    exit;
}

$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
if (!GrrMediaWikiAccessCookie::issue($userAgent)) {
    http_response_code(500);
    exit;
}

http_response_code(204);
