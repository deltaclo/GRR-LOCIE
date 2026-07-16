<?php

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/ModuleRegistry.php';
require_once __DIR__.'/lib/Renderer.php';

if ($identifiant_hook === 'vocab') {
    $CtnHook['vocab'] = array(
        'boutons_perso_title' => 'Boutons perso',
    );
}

if ($identifiant_hook === 'hookBoutonsPersoCalendrier') {
    $CtnHook['hookBoutonsPersoCalendrier'] = (isset($CtnHook['hookBoutonsPersoCalendrier']) ? $CtnHook['hookBoutonsPersoCalendrier'] : '')
        .BoutonsPersoRenderer::calendarButtons();
}

if ($identifiant_hook === 'hookCompteMenu') {
    $CtnHook['hookCompteMenu'] = (isset($CtnHook['hookCompteMenu']) ? $CtnHook['hookCompteMenu'] : '')
        .BoutonsPersoRenderer::accountMenu();
}

if ($identifiant_hook === 'hookComptePage') {
    $CtnHook['hookComptePage'] = (isset($CtnHook['hookComptePage']) ? $CtnHook['hookComptePage'] : '')
        .BoutonsPersoRenderer::accountPage();
}
