<?php

require_once __DIR__.'/Config.php';
require_once __DIR__.'/Repository.php';
require_once __DIR__.'/Rights.php';

class SuiviDemandesNavigation
{
    const BUTTON_ID = 'module:suivi_demandes';

    public static function buttonDefinition($login = null)
    {
        $login = $login === null && function_exists('getUserName')
            ? (string) getUserName()
            : (string) $login;

        return array(
            'id' => self::BUTTON_ID,
            'module' => SuiviDemandesConfig::MODULE,
            'label' => SuiviDemandesConfig::displayName(),
            'url' => self::accountUrl(),
            'enabled' => SuiviDemandesConfig::isEnabled() && SuiviDemandesConfig::accountEnabled(),
            'can_access' => SuiviDemandesRights::canAccessModule($login),
        );
    }

    private static function accountUrl()
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
        $base = strpos($script, '/compte/') !== false ? 'compte.php' : 'compte/compte.php';

        return $base.'?pc='.rawurlencode(SuiviDemandesConfig::MODULE);
    }
}
