<?php

require_once __DIR__.'/lib/bootstrap.php';

grr_formdyn_bootstrap(false);

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Rights.php';
require_once __DIR__.'/lib/Notification.php';
require_once __DIR__.'/lib/Export.php';
require_once __DIR__.'/lib/Renderer.php';

header('Content-Type: text/html; charset=utf-8');

echo FormulairesDynamiquesRenderer::standalonePage();
