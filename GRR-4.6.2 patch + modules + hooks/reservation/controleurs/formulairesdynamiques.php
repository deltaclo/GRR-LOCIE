<?php

require_once __DIR__.'/../../personnalisation/modules/formulaires_dynamiques/lib/Config.php';
require_once __DIR__.'/../../personnalisation/modules/formulaires_dynamiques/lib/Repository.php';
require_once __DIR__.'/../../personnalisation/modules/formulaires_dynamiques/lib/Rights.php';
require_once __DIR__.'/../../personnalisation/modules/formulaires_dynamiques/lib/Notification.php';
require_once __DIR__.'/../../personnalisation/modules/formulaires_dynamiques/lib/Renderer.php';

$login = function_exists('getUserName') ? (string) getUserName() : '';

$d['modePage'] = 1;
$d['nomPage'] = FormulairesDynamiquesConfig::APP_PAGE;
$d['TitrePage'] = FormulairesDynamiquesConfig::displayName();
$d['CtnPage'] = FormulairesDynamiquesRenderer::appPage($login);

echo $twig->render('page.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
