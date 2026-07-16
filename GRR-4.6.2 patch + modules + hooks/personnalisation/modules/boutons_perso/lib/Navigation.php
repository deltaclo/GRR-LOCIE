<?php

require_once __DIR__.'/Config.php';

class BoutonsPersoNavigation
{
    const BUTTON_ID = 'module:boutons_perso';

    public static function buttonDefinition($login = null)
    {
        $login = $login === null && function_exists('getUserName')
            ? (string) getUserName()
            : (string) $login;

        return array(
            'id' => self::BUTTON_ID,
            'module' => BoutonsPersoConfig::MODULE,
            'label' => BoutonsPersoConfig::displayName(),
            'url' => self::accountUrl(),
            'enabled' => BoutonsPersoConfig::isEnabled(),
            'can_access' => class_exists('SecuAccess') && SecuAccess::UserLevel($login, -1) >= 6,
        );
    }

    private static function accountUrl()
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
        $base = strpos($script, '/compte/') !== false ? 'compte.php' : 'compte/compte.php';

        return $base.'?pc='.rawurlencode(BoutonsPersoConfig::MODULE);
    }
}
