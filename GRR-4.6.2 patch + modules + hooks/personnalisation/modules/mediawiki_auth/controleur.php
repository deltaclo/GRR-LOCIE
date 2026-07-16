<?php

if ($identifiant_hook === 'hookBeforeLogout') {
    require_once __DIR__.'/lib/Config.php';
    require_once __DIR__.'/lib/AccessCookie.php';

    GrrMediaWikiAccessCookie::clear();
    $CtnHook['hookBeforeLogout'] = true;
}
