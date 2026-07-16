<?php

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Rights.php';
require_once __DIR__.'/lib/Navigation.php';
require_once __DIR__.'/lib/Notification.php';
require_once __DIR__.'/lib/Renderer.php';

if ($identifiant_hook === 'vocab') {
    $CtnHook['vocab'] = array(
        'suivi_demandes_title' => SuiviDemandesConfig::displayName(),
        'suivi_demandes_my_requests' => SuiviDemandesConfig::displayName(),
    );
}

if ($identifiant_hook === 'hookCompteMenu') {
    $CtnHook['hookCompteMenu'] = (isset($CtnHook['hookCompteMenu']) ? $CtnHook['hookCompteMenu'] : '')
        .SuiviDemandesRenderer::accountMenu();
}

if ($identifiant_hook === 'hookComptePage') {
    $CtnHook['hookComptePage'] = (isset($CtnHook['hookComptePage']) ? $CtnHook['hookComptePage'] : '')
        .SuiviDemandesRenderer::accountPage();
}

if ($identifiant_hook === 'hookDemandesStatus') {
    $CtnHook['hookDemandesStatus'] = (isset($CtnHook['hookDemandesStatus']) ? $CtnHook['hookDemandesStatus'] : '')
        .SuiviDemandesRenderer::statusSummaryLinks();
}

if ($identifiant_hook === 'hookEditRoom1') {
    $CtnHook['hookEditRoom1'] = (isset($CtnHook['hookEditRoom1']) ? $CtnHook['hookEditRoom1'] : '')
        .SuiviDemandesRenderer::resourceConfigForm();
}

if ($identifiant_hook === 'hookEditRoomSave') {
    SuiviDemandesRenderer::resourceConfigSave();
}

if ($identifiant_hook === 'hookEditEntreeForm') {
    $CtnHook['hookEditEntreeForm'] = (isset($CtnHook['hookEditEntreeForm']) ? $CtnHook['hookEditEntreeForm'] : '')
        .SuiviDemandesRenderer::reservationForm();
}

if ($identifiant_hook === 'hookEditEntreeTrt') {
    SuiviDemandesRenderer::reservationSubmit();
}

if ($identifiant_hook === 'hookVueReservation') {
    $CtnHook['hookVueReservation'] = (isset($CtnHook['hookVueReservation']) ? $CtnHook['hookVueReservation'] : '')
        .SuiviDemandesRenderer::reservationLinks();
}
