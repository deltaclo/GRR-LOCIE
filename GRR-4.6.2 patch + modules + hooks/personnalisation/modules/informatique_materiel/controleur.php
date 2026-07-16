<?php

require_once __DIR__.'/lib/bootstrap.php';
require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Security.php';
require_once __DIR__.'/lib/Navigation.php';
require_once __DIR__.'/lib/LdapDirectory.php';
require_once __DIR__.'/lib/Renderer.php';

if ($identifiant_hook === 'vocab') {
    $CtnHook['vocab'] = array(
        'informatique_materiel_title' => InformatiqueMaterielConfig::displayName(),
    );
}

if ($identifiant_hook === 'hookCompteMenu') {
    $CtnHook['hookCompteMenu'] = (isset($CtnHook['hookCompteMenu']) ? $CtnHook['hookCompteMenu'] : '')
        .InformatiqueMaterielRenderer::accountMenu();
}

if ($identifiant_hook === 'hookComptePage') {
    $CtnHook['hookComptePage'] = (isset($CtnHook['hookComptePage']) ? $CtnHook['hookComptePage'] : '')
        .InformatiqueMaterielRenderer::accountPage();
}

if ($identifiant_hook === 'hookDemandesStatus') {
    $CtnHook['hookDemandesStatus'] = (isset($CtnHook['hookDemandesStatus']) ? $CtnHook['hookDemandesStatus'] : '')
        .InformatiqueMaterielRenderer::statusSummaryLinks();
}
