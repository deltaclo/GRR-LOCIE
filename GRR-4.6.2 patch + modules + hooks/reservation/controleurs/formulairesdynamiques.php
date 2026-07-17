<?php

require_once __DIR__.'/../../personnalisation/modules/formulaires_dynamiques/lib/Config.php';
require_once __DIR__.'/../../personnalisation/modules/formulaires_dynamiques/lib/Repository.php';
require_once __DIR__.'/../../personnalisation/modules/formulaires_dynamiques/lib/Rights.php';
require_once __DIR__.'/../../personnalisation/modules/formulaires_dynamiques/lib/Notification.php';
require_once __DIR__.'/../../personnalisation/modules/formulaires_dynamiques/lib/Export.php';
require_once __DIR__.'/../../personnalisation/modules/formulaires_dynamiques/lib/Renderer.php';

$login = function_exists('getUserName') ? (string) getUserName() : '';

$d['nomPage'] = FormulairesDynamiquesConfig::APP_PAGE;
$d['TitrePage'] = FormulairesDynamiquesConfig::displayName();
$d['CtnPage'] = FormulairesDynamiquesRenderer::appPage($login);
$d['pview'] = 0;

echo $twig->render('formulairesdynamiques.twig', array('trad' => $trad, 'd' => $d, 'settings' => $AllSettings));
