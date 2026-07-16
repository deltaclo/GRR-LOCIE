<?php

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Notification.php';
require_once __DIR__.'/lib/Renderer.php';

if ($identifiant_hook === 'vocab') {
    $CtnHook['vocab'] = array(
        'stagiaire_title' => StagiaireConfig::displayName(),
    );
}

if ($identifiant_hook === 'hookCompteMenu') {
    $CtnHook['hookCompteMenu'] = (isset($CtnHook['hookCompteMenu']) ? $CtnHook['hookCompteMenu'] : '')
        .StagiaireRenderer::accountMenu();
}

if ($identifiant_hook === 'hookComptePage') {
    $CtnHook['hookComptePage'] = (isset($CtnHook['hookComptePage']) ? $CtnHook['hookComptePage'] : '')
        .StagiaireRenderer::accountPage();
}

if ($identifiant_hook === 'hookEditEntreeForm') {
    $CtnHook['hookEditEntreeForm'] = (isset($CtnHook['hookEditEntreeForm']) ? $CtnHook['hookEditEntreeForm'] : '')
        .StagiaireRenderer::reservationForm();
}

if ($identifiant_hook === 'hookEditEntreeValidate') {
    $CtnHook['hookEditEntreeValidate'] = StagiaireRenderer::validateReservation();
}

if ($identifiant_hook === 'hookEditEntreeTrt') {
    StagiaireRenderer::reservationSubmit();
}

if ($identifiant_hook === 'hookVueReservation') {
    $CtnHook['hookVueReservation'] = (isset($CtnHook['hookVueReservation']) ? $CtnHook['hookVueReservation'] : '')
        .StagiaireRenderer::reservationDetail();
}

if ($identifiant_hook === 'hookModerateEntry') {
    StagiaireNotification::sendModerationConfirmation(
        isset($GLOBALS['grr_moderate_entry_context']) ? $GLOBALS['grr_moderate_entry_context'] : array()
    );
}

if ($identifiant_hook === 'hookDeleteEntry') {
    StagiaireNotification::sendDeletionConfirmation(
        isset($GLOBALS['grr_delete_entry_context']) ? $GLOBALS['grr_delete_entry_context'] : array()
    );
}
