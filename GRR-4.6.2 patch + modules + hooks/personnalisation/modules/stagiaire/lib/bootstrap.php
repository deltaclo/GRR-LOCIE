<?php

if (!defined('GRR_STAGIAIRE_MODULE_DIR')) {
    define('GRR_STAGIAIRE_MODULE_DIR', dirname(__DIR__));
}

if (!defined('GRR_STAGIAIRE_ROOT')) {
    define('GRR_STAGIAIRE_ROOT', dirname(__DIR__, 4));
}

function grr_stagiaire_bootstrap($withSession = false)
{
    global $dbsys, $dbHost, $dbUser, $dbPass, $dbDb, $dbPort, $db_nopersist;
    global $gSessionName, $gSameSite, $motDePasseConfig, $table_prefix;

    if (defined('GRR_STAGIAIRE_BOOTSTRAPPED')) {
        if ($withSession && !defined('GRR_STAGIAIRE_SESSION_BOOTSTRAPPED')) {
            require_once GRR_STAGIAIRE_ROOT.'/include/session.inc.php';
            $sessionOk = grr_resumeSession();
            define('GRR_STAGIAIRE_SESSION_BOOTSTRAPPED', true);
            return $sessionOk;
        }

        return true;
    }

    set_include_path(GRR_STAGIAIRE_ROOT.'/include'.PATH_SEPARATOR.get_include_path());

    $autoload = GRR_STAGIAIRE_ROOT.'/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }

    require_once GRR_STAGIAIRE_ROOT.'/include/securite.class.php';
    require_once GRR_STAGIAIRE_ROOT.'/include/functions.inc.php';

    include GRR_STAGIAIRE_ROOT.'/personnalisation/connect.inc.php';
    include GRR_STAGIAIRE_ROOT.'/include/config.inc.php';
    include GRR_STAGIAIRE_ROOT.'/include/misc.inc.php';
    include GRR_STAGIAIRE_ROOT.'/include/'.$dbsys.'.inc.php';
    include GRR_STAGIAIRE_ROOT.'/include/mrbs_sql.inc.php';

    require_once GRR_STAGIAIRE_ROOT.'/include/settings.class.php';
    if (!Settings::load()) {
        http_response_code(500);
        exit('Erreur chargement settings');
    }

    define('GRR_STAGIAIRE_BOOTSTRAPPED', true);

    if ($withSession) {
        require_once GRR_STAGIAIRE_ROOT.'/include/session.inc.php';
        $sessionOk = grr_resumeSession();
        define('GRR_STAGIAIRE_SESSION_BOOTSTRAPPED', true);
        return $sessionOk;
    }

    return true;
}
