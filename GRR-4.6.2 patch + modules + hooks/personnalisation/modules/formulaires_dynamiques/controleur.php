<?php

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Rights.php';
require_once __DIR__.'/lib/Notification.php';
require_once __DIR__.'/lib/Renderer.php';

if ($identifiant_hook === 'vocab') {
    $CtnHook['vocab'] = array(
        'formulaires_dynamiques_title' => FormulairesDynamiquesConfig::displayName(),
    );
}

if ($identifiant_hook === 'hookCompteMenu') {
    $CtnHook['hookCompteMenu'] = (isset($CtnHook['hookCompteMenu']) ? $CtnHook['hookCompteMenu'] : '')
        .FormulairesDynamiquesRenderer::accountMenu();
}

if ($identifiant_hook === 'hookComptePage') {
    $CtnHook['hookComptePage'] = (isset($CtnHook['hookComptePage']) ? $CtnHook['hookComptePage'] : '')
        .FormulairesDynamiquesRenderer::accountPage();
}
