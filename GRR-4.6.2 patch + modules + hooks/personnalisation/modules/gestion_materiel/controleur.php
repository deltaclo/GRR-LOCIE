<?php

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Rights.php';
require_once __DIR__.'/lib/Navigation.php';
require_once __DIR__.'/lib/Notification.php';
require_once __DIR__.'/lib/Renderer.php';

if ($identifiant_hook === 'vocab') {
    $CtnHook['vocab'] = array(
        'gestion_materiel_title' => GestionMaterielConfig::displayName(),
    );
}

if ($identifiant_hook === 'hookCompteMenu') {
    $CtnHook['hookCompteMenu'] = (isset($CtnHook['hookCompteMenu']) ? $CtnHook['hookCompteMenu'] : '')
        .GestionMaterielRenderer::accountMenu();
}

if ($identifiant_hook === 'hookComptePage') {
    $CtnHook['hookComptePage'] = (isset($CtnHook['hookComptePage']) ? $CtnHook['hookComptePage'] : '')
        .GestionMaterielRenderer::accountPage();
}

if ($identifiant_hook === 'hookDemandesStatus') {
    $CtnHook['hookDemandesStatus'] = (isset($CtnHook['hookDemandesStatus']) ? $CtnHook['hookDemandesStatus'] : '')
        .GestionMaterielRenderer::statusSummaryLinks();
}
