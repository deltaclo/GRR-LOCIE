<?php

if (!defined('GRR_ICS_MODULE_DIR')) {
    define('GRR_ICS_MODULE_DIR', dirname(__DIR__));
}

if (!defined('GRR_ICS_ROOT')) {
    define('GRR_ICS_ROOT', dirname(__DIR__, 4));
}

function grr_ics_bootstrap($withSession = false)
{
    if (defined('GRR_ICS_BOOTSTRAPPED')) {
        if ($withSession && !defined('GRR_ICS_SESSION_BOOTSTRAPPED')) {
            require_once GRR_ICS_ROOT.'/include/session.inc.php';
            grr_resumeSession();
            define('GRR_ICS_SESSION_BOOTSTRAPPED', true);
        }
        return;
    }

    $autoload = GRR_ICS_ROOT.'/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }

    require_once GRR_ICS_ROOT.'/include/securite.class.php';
    require_once GRR_ICS_ROOT.'/include/functions.inc.php';

    include GRR_ICS_ROOT.'/personnalisation/connect.inc.php';
    include GRR_ICS_ROOT.'/include/config.inc.php';
    include GRR_ICS_ROOT.'/include/misc.inc.php';
    include GRR_ICS_ROOT.'/include/'.$dbsys.'.inc.php';
    include GRR_ICS_ROOT.'/include/mrbs_sql.inc.php';

    require_once GRR_ICS_ROOT.'/include/settings.class.php';
    if (!Settings::load()) {
        http_response_code(500);
        exit('Erreur chargement settings');
    }

    define('GRR_ICS_BOOTSTRAPPED', true);

    if ($withSession) {
        require_once GRR_ICS_ROOT.'/include/session.inc.php';
        grr_resumeSession();
        define('GRR_ICS_SESSION_BOOTSTRAPPED', true);
    }
}
