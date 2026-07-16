<?php

require_once __DIR__.'/Config.php';
require_once __DIR__.'/Repository.php';
require_once __DIR__.'/Rights.php';

class GestionMaterielNavigation
{
    const BUTTON_ID = 'module:gestion_materiel';

    public static function buttonDefinition($login = null)
    {
        $login = $login === null ? GestionMaterielRights::currentLogin() : (string) $login;

        return array(
            'id' => self::BUTTON_ID,
            'module' => GestionMaterielConfig::MODULE,
            'label' => GestionMaterielConfig::displayName(),
            'url' => self::accountUrl(),
            'enabled' => GestionMaterielConfig::isEnabled(),
            'can_access' => GestionMaterielRights::canAccessModule($login),
        );
    }

    private static function accountUrl()
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
        $base = strpos($script, '/compte/') !== false ? 'compte.php' : 'compte/compte.php';

        return $base.'?pc='.rawurlencode(GestionMaterielConfig::MODULE);
    }
}
